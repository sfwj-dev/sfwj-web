<?php
/**
 * Basic function
 *
 * @package sfwj
 */


/**
 * ベースティレクトリを取得する。
 *
 * @return string
 */
function sfwj_base_dir() {
	return dirname( __DIR__ );
}

/**
 * ベースURLを取得する。
 *
 * @return string
 */
function sfwj_base_url() {
	return trailingslashit( plugin_dir_url( __DIR__ ) );
}

/**
 * アセットのURLとハッシュを取得する
 *
 * @param string $rel_path アセットへの相対パス
 * @return string[]
 */
function sfwj_asset_url_and_version( $rel_path ) {
	$rel_path = ltrim( $rel_path, '/' );
	$url  = trailingslashit( sfwj_base_url() ) . $rel_path;
	$path = trailingslashit( sfwj_base_dir() ) . $rel_path;
	return [ $url, filemtime( $path ) ];
}
