<?php
/**
 * Basic function
 *
 * @package sfwj
 */


/**
 * Get base directory.
 *
 * @return void
 */
function sfwj_base_dir() {
	return dirname( __DIR__ );
}

/**
 * Get base directory URL.
 *
 * @return string
 */
function sfwj_base_url() {
	return trailingslashit( plugin_dir_url( __DIR__ ) );
}

