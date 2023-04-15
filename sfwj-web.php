<?php
/**
 * Plugin Name: SFWJ Custom Plugin
 * Plugin URI:  https://github.com/sfwj-admin/sfwj-web
 * Description: Custom Plugin for sfwj.jp
 * Version:     nightly
 * Author:      Science Fiction and Fantasy Writers of Japan
 * Author URI:  https://sfwj.jp/
 * License:     GPLv3 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-3.0.html
 * Text Domain: sfwj
 * Domain Path: /languages
 */

// 直接読み込みを禁止
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

// composer読み込み
require_once __DIR__ . '/vendor/autoload.php';

/**
 * プラグインのブートストラップ
 */
add_action( 'plugins_loaded', function () {
	// Activate translations.
	load_plugin_textdomain( 'sfwj', false, basename( __DIR__ ) . '/languages' );
	// Load everything php file in 'includes' directory.
	require_once __DIR__ . '/includes/functions.php';
	require_once __DIR__ . '/includes/settings.php';
	require_once __DIR__ . '/includes/member-importer.php';
	require_once __DIR__ . '/includes/google-api.php';
	require_once __DIR__ . '/includes/openbd-api.php';
	require_once __DIR__ . '/includes/template-member.php';
	require_once __DIR__ . '/includes/taxonomy.php';
	// Register hooks.
	\Sfwj\SfwjWeb\Tools\MemberImporter::get();
	\Sfwj\SfwjWeb\MemberWorks::get();
	// Register CLI.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'sfwj', 'Sfwj\SfwjWeb\Command' );
	}
} );

/**
 * アセットを登録する
 */
add_action( 'init', function () {
	$json = __DIR__ . '/wp-dependencies.json';
	if ( ! file_exists( $json ) ) {
		return;
	}
	$dependencies = json_decode( file_get_contents( $json ), true );
	if ( ! $dependencies ) {
		return;
	}
	foreach ( $dependencies as $dependency ) {
		if ( empty( $dependency[ 'handle' ] ) ) {
			continue;
		}
		$handle = $dependency[ 'handle' ];
		$src    = plugin_dir_url( __FILE__ ) . $dependency[ 'path' ];
		switch ( $dependency['ext'] ) {
			case 'js':
				wp_register_script( $handle, $src, $dependency[ 'deps' ], $dependency[ 'hash' ], $dependency[ 'footer' ] );
				break;
			case 'css':
				wp_register_style( $handle, $src, $dependency[ 'deps' ], $dependency[ 'hash' ], $dependency[ 'media' ] );
				break;

		}
	}
} );
