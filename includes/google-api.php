<?php
/**
 * Google API 周りの関数
 */


/**
 * Googleサービスアカウントを取得する
 *
 * @return string
 */
function sfwj_google_service_account() {
	return (string) get_option( 'sfwj-ga-account', '' );
}

/**
 * Google API Clientを取得する
 *
 * @param string[] $scopes スコープ
 *
 * @return WP_Error|Google\Client
 */
function sfwj_google_auth_client( $scopes = [] ) {
	$key = sfwj_google_service_account();
	if ( ! $key ) {
		return new WP_Error( 'service_account_invalid', __( '認証情報が設定されていません。', 'sfwj' ) );
	}
	$ga = new Google\Client();
	$ga->setAuthConfig( json_decode( $key, true ) );
	$ga->setScopes( $scopes );
	return $ga;
}

/**
 * Get Google API Client
 *
 * @return WP_Error|Google\Service\Drive
 */
function sfwj_google_client() {
	static $client = null;
	if ( ! is_null( $client ) ) {
		return $client;
	}
	try {
		$ga = sfwj_google_auth_client( [
			'https://www.googleapis.com/auth/drive',
			'https://www.googleapis.com/auth/drive.file',
			'https://www.googleapis.com/auth/drive.appdata',
			'https://www.googleapis.com/auth/drive.metadata',
			'https://www.googleapis.com/auth/drive.metadata.readonly',
			'https://www.googleapis.com/auth/drive.photos.readonly',
			'https://www.googleapis.com/auth/drive.readonly',
		] );
		if ( is_wp_error( $ga ) ) {
			return $ga;
		}
		$service = new Google\Service\Drive( $ga );
		$client  = $service;
		return $service;
	} catch ( \Exception $e ) {
		return new WP_Error( 'sfwj-google-api-error', $e->getMessage() );
	}
}

/**
 * Google DriveのファイルIDを取得する
 *
 * @param string $url Google DriveのURL
 * @return string
 */
function sfwj_get_file_id_of_drive( $url ) {
	$query = parse_url( $url, PHP_URL_QUERY );
	parse_str( $query, $params );
	return $params['id'] ?? '';
}

/**
 * Google Driveのファイルをメディアライブラリに登録する
 *
 * @param string $url     Google DriveのURL
 * @param int    $post_id 投稿ID
 *
 * @return int|WP_Error
 */
function sfwj_save_file( $url, $post_id = 0 ) {
	try {
		$id = sfwj_get_file_id_of_drive( $url );
		if ( ! $id ) {
			throw new Exception( __( 'Google DriveのURLが正しくありません。', 'sfwj' ) );
		}
		$client = sfwj_google_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		// メタデータを取得
		$meta      = $client->files->get( $id );
		$file_info = [
			'name' => $meta->getName(),
			'type' => $meta->getMimeType(),
		];
		// ファイルの実体をダウンロード
		$result = $client->files->get( $id, [
			'alt' => 'media',
		] );
		$tmp    = tempnam( sys_get_temp_dir(), 'sfwj-thumbnail' );
		if ( ! file_put_contents( $tmp, $result->getBody()->getContents() ) ) {
			throw new Exception( __( 'ファイルの保存に失敗しました。ディレクトリの書き込み権限がありません。', 'sfwj' ) );
		}
		$file_info['tmp_name'] = $tmp;
		$file_info['error']    = UPLOAD_ERR_OK;
		$file_info['size']     = $result->getBody()->getSize();
		// メディアライブラリに登録
		return media_handle_sideload( $file_info, $post_id );
	} catch ( \Exception $e ) {
		return new WP_Error( 'sfwj-google-api-error', $e->getMessage() );
	}
}

/**
 * スプレッドシートからシートIDを取得する
 *
 * @param string $url スプレッドシートのURL
 * @return string|WP_Error
 */
function sfwj_extract_sheet_id( $url ) {
	$sheet_id = null;
	// URLを分解しクエリパラメータから取得する
	$parsed_url = parse_url( $url );
	if ( isset( $parsed_url[ 'query' ] ) ) {
		parse_str( $parsed_url[ 'query' ], $params );
		if ( isset( $params[ 'gid' ] ) ) {
			$sheet_id = $params[ 'gid' ];
		}
	}
	// ハッシュ部分からgidを取得（上記で取得できなかった場合のみ）
	if ( is_null( $sheet_id ) && isset( $parsed_url[ 'fragment' ] ) ) {
		parse_str( $parsed_url[ 'fragment' ], $fragments );
		if ( isset( $fragments[ 'gid' ] ) ) {
			$sheet_id = $fragments[ 'gid' ];
		}
	}
	if ( ! $sheet_id ) {
		return new WP_Error( 'sfwj-invalid-url', __( 'シートIDが取得できません。', 'sfwj' ) );
	}
	return $sheet_id;
}

/**
 * スプレッドシートからCSVを取得する
 *
 * @param string $url スプレッドシートのURL
 * @param
 * @return WP_Error|array
 */
function sfwj_get_csv( $url, $use_cache = true ) {
	$cache_key = 'sfwj-csv-' . md5( $url );
	if ( $use_cache ) {
		// キャッシュを使う設定なので、キャッシュがあればそれを返す。
		$cache = get_transient( $cache_key );
		if ( false !== $cache ) {
			return $cache;
		}
	}
	// URLからIDを取得
	if ( ! preg_match( '@https://docs\.google\.com/spreadsheets/d/([^/]+)/edit@u', $url, $matches ) ) {
		return new WP_Error( 'sfwj-invalid-url', __( 'URLが正しくありません。', 'sfwj' ) );
	}
	list( $all, $id ) = $matches;
	// URLからシートIDを取得
	$sheet_id = sfwj_extract_sheet_id( $url );
	if ( is_wp_error( $sheet_id ) ) {
		return $sheet_id;
	}
	try {
		$ga = sfwj_google_auth_client( [
			Google\Service\Sheets::SPREADSHEETS_READONLY,
		] );
		if ( is_wp_error( $ga ) ) {
			return $ga;
		}
		$sheet      = new Google_Service_Sheets( $ga );
		$sheet_name = '';
		$response   = $sheet->spreadsheets->get( $id );
		foreach ( $response->getSheets() as $sheet_meta ) {
			if ( $sheet_meta->getProperties()->getSheetId() === (int) $sheet_id ) {
				$sheet_name = $sheet_meta->getProperties()->getTitle();
				break;
			}
		}
		if ( ! $sheet_name ) {
			throw new Exception( __( 'シートが見つかりませんでした。', 'sfwj' ), 404 );
		}
		$response = $sheet->spreadsheets_values->get( $id, $sheet_name );
		$result   = $response->getValues();
		// キャッシュを1日保存
		delete_transient( $cache_key );
		set_transient( $cache_key, $result, 3600 * 24 );
		return $result;
	} catch ( \Exception $e ) {
		return new WP_Error( 'sfwj-google-api-error', $e->getMessage(), [
			'code' => $e->getCode(),
		] );
	}
}
