<?php

namespace Sfwj\SfwjWeb;

/**
 * SFWJ用のコマンドラインツール
 */
class Command extends \WP_CLI_Command {

	/**
	 * Google Driveへのアクセスを確認する
	 *
	 * @synopsis <url>
	 * @param array $args Command arguments.
	 *
	 * @return void
	 */
	public function drive( $args ) {
		list( $url ) = $args;
		$result = sfwj_save_file( $url );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( 'Success: ' . admin_url( 'upload.php?item=' . $result ) );
	}
}
