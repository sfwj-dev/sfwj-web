<?php
/**
 * OpenBDのAPIを利用する関数群
 */


/**
 * OpenBDのAPIを利用して書籍情報を取得する
 *
 * @param string $isbn 13桁のISBN
 * @return array|WP_Error
 */
function sfwj_openbd_get( $isbn ) {
	$endpoint = add_query_arg( [
		'isbn' => $isbn,
	], 'https://api.openbd.jp/v1/get' );
	$response = wp_remote_get( $endpoint );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$body = $response['body'];
	$json = json_decode( $body, true );
	if ( ! $json ) {
		return new WP_Error( 'sfwj-openbd-api-error', __( 'OpenBD APIのレスポンスが不正です。', 'sfwj' ) );
	}
	foreach ( $json as $item ) {
		if ( empty( $item ) ) {
			continue;
		}
		return $item;
	}
	return new WP_Error( 'sfwj-openbd-api-error', __( '該当するISBNの書籍を取得することができませんでした。', 'sfwj' ) );
}
