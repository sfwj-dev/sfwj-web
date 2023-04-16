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
	$url      = trailingslashit( sfwj_base_url() ) . $rel_path;
	$path     = trailingslashit( sfwj_base_dir() ) . $rel_path;
	return [ $url, filemtime( $path ) ];
}

/**
 * リンクを自動的にリンクに変換する。
 *
 * @param string $string URLを含む文字列
 * @param bool $external 外部リンクにするかどうか
 * @return string
 */
function sfwj_linkify( $string, $external = true ) {
	$link = preg_replace( '@(https?://[a-zA-Z0-9.\-_?#%+/~]+)@u', '<a href="$1"%s>$1</a>', $string );
	return sprintf( $link, $external ? ' target="_blank" rel="noopener noreferrer"' : '' );
}
