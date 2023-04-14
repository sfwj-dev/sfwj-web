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
		\WP_CLI::confirm( 'これは会員情報をすべて削除します。実行してよろしいですか？' );
		$query = new \WP_Query( [
			'post_type'      => 'member',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		] );
		while ( $query->have_posts() ) {
			$query->the_post();
			$term = get_author_term( get_the_ID() );
			if ( $term ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
			wp_delete_post( get_the_ID(), true );
			echo '.';
		}
		$query = new \WP_Query( [
			'post_type'      => MemberWorks::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		] );
		while ( $query->have_posts() ) {
			$query->the_post();
			wp_delete_post( get_the_ID(), true );
			echo '.';
		}
		\WP_CLI::line( '' );
		\WP_CLI::success( '投稿をすべて削除しました。' );
	}
}
