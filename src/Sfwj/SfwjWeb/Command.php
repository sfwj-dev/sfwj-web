<?php

namespace Sfwj\SfwjWeb;

use Sfwj\SfwjWeb\Tools\CsvParser\NormalMemberCsvParser;

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

	/**
	 * Excelのカラム番号をインデックスに変換する
	 *
	 * @synopsis <column>
	 * @param array $args
	 *
	 * @return void
	 */
	public function column( $args ) {
		list( $col ) = $args;
		$parser = new NormalMemberCsvParser( 0 );
		\WP_CLI::success( 'Success: ' . $parser->column_to_index( $col ) );
	}

	/**
	 * ISBN10をISBN13に変換する
	 *
	 * @synopsis <isbn10>
	 * @param array $args
	 *
	 * @return void
	 */
	public function isbn10( $args ) {
		list( $isbn10 ) = $args;
		$parser = new NormalMemberCsvParser( 0 );
		$isbn13 = $parser->isbn10_to_13( $isbn10 );
		\WP_CLI::success( sprintf( '%s => %s', $isbn10, $isbn13 ) );
	}

	/**
	 * インポートした情報をクリーンアップする。
	 *
	 * @return void
	 */
	public function cleanup() {

	}
}
