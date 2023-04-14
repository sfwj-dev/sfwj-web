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

	/**
	 * 書影をGoogle Driveから取得してアイキャッチに指定する
	 *
	 * @return void
	 */
	public function book_cover() {
		$posts  = MemberWorks::get()->post_to_fix_covers();
		$number = count( $posts );
		if ( ! $number ) {
			\WP_CLI::success( '修正対象の投稿はありません' );
		}
		\WP_CLI::line( sprintf( '%d件の書籍画像を取得します。', $number ) );
		$success = 0;
		foreach ( $posts as $post ) {
			$url = get_post_meta( $post->ID, '_google_drive_url', true );
			$attachment_id = sfwj_save_file( $url, $post->ID );
			if ( is_wp_error( $attachment_id ) ) {
				\WP_CLI::warning( sprintf( '#%d %s: %s', $post->ID, $post->post_title, $attachment_id->get_error_message() ) );
				continue;
			}
			// アイキャッチ画像に指定する
			set_post_thumbnail( $post->ID, $attachment_id );
			// 投稿を公開にする。
			wp_update_post( [
				'ID'          => $post->ID,
				'post_status' => 'publish',
			] );
			$success++;
			\WP_CLI::line( 'OK: ' . $url );
		}
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf( '書籍画像を%d件取得しました。', $success ) );
	}
}
