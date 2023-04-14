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
		$key = sfwj_google_service_account();
		if ( ! $key ) {
			throw new Exception( __( '認証情報が設定されていません。', 'sfwj' ) );
		}
		$ga = new Google\Client();
		$ga->setAuthConfig( json_decode( $key, true ) );
		$ga->setScopes( [
			'https://www.googleapis.com/auth/drive',
			'https://www.googleapis.com/auth/drive.file',
			'https://www.googleapis.com/auth/drive.appdata',
			'https://www.googleapis.com/auth/drive.metadata',
			'https://www.googleapis.com/auth/drive.metadata.readonly',
			'https://www.googleapis.com/auth/drive.photos.readonly',
			'https://www.googleapis.com/auth/drive.readonly',
		] );
		$service = new Google\Service\Drive( $ga );
		$client = $service;
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
	$query = parse_url($url, PHP_URL_QUERY);
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
		$tmp = tempnam( sys_get_temp_dir(), 'sfwj-thumbnail' );
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
