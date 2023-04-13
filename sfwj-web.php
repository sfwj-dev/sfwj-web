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

// This file actually do nothing.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

// Load composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Activate plugin
 */
add_action( 'plugins_loaded', function() {
	// Activate translations.
	load_plugin_textdomain( 'sfwj', false, basename( __DIR__ ) . '/languages' );
	// Load everything php file in 'includes' directory.
	require_once  __DIR__ . '/includes/functions.php';
	require_once __DIR__ . '/includes/settings.php';
	require_once  __DIR__ . '/includes/member-importer.php';
	require_once  __DIR__ . '/includes/google-api.php';
	// Register CLI.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'sfwj', 'Sfwj\SfwjWeb\Command' );
	}
} );
