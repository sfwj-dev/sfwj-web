<?php

namespace Sfwj\SfwjWeb;

use cli\Table;
use Sfwj\SfwjWeb\Service\OpenBdSynchronizer;
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
		$result      = sfwj_save_file( $url );
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
		$parser      = new NormalMemberCsvParser( 0 );
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
		$parser         = new NormalMemberCsvParser( 0 );
		$isbn13         = $parser->isbn10_to_13( $isbn10 );
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
			$url           = get_post_meta( $post->ID, '_google_drive_url', true );
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
			// 更新日時を保存
			update_post_meta( $post->ID, '_google_fetched', current_time( 'mysql' ) );
			$success++;
			\WP_CLI::line( 'OK: ' . $url );
		}
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf( '書籍画像を%d件取得しました。', $success ) );
	}

	/**
	 * プロフィール画像を作成する
	 *
	 * @return void
	 */
	public function profile_picture() {
		$query = new \WP_Query( [
			'post_type'      => 'member',
			'post_status'    => 'any',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => '_thumbnail_synced',
					'compare' => 'NOT EXISTS',
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_profile_pic',
						'compare' => '!=',
						'value'   => '',
					],
					[
						'key'     => '_thumbnail_pic',
						'compare' => '!=',
						'value'   => '',
					],
				],
			],
			'posts_per_page' => -1,
		] );
		$posts = $query->posts;
		if ( ! $posts ) {
			\WP_CLI::success( '修正すべき会員情報はありませんでした。' );
		}
		\WP_CLI::line( sprintf( '%d件の投稿を修正します。', count( $posts ) ) );
		$success = 0;
		foreach ( $posts as $post ) {
			$profile_pic_id   = 0;
			$thumbnail_pic_id = 0;
			$profile_pic_url  = get_post_meta( $post->ID, '_profile_pic', true );
			if ( $profile_pic_url ) {
				$profile_pic_result = sfwj_save_file( $profile_pic_url, $post->ID );
				if ( is_wp_error( $profile_pic_result ) ) {
					\WP_CLI::warning( sprintf( '#%d %s: %s', $post->ID, $post->post_title, $profile_pic_result->get_error_message() ) );
				} else {
					$profile_pic_id = $profile_pic_result;
					\WP_CLI::line( 'Profile   OK: ' . $profile_pic_url );
				}
			}
			$thumbnail_pic_url = get_post_meta( $post->ID, '_thumbnail_pic', true );
			if ( $thumbnail_pic_url ) {
				$thumbnail_pic_result = sfwj_save_file( $thumbnail_pic_url, $post->ID );
				if ( is_wp_error( $thumbnail_pic_result ) ) {
					\WP_CLI::warning( sprintf( '#%d %s: %s', $post->ID, $post->post_title, $thumbnail_pic_result->get_error_message() ) );
				} else {
					$thumbnail_pic_id = $thumbnail_pic_result;
					\WP_CLI::line( 'Thumbnail OK: ' . $thumbnail_pic_url );
				}
			}
			if ( ! $profile_pic_id && ! $thumbnail_pic_id ) {
				// どちらも取得できなかったので、スキップ
				continue;
			}
			// 画像は少なくとも取得出来たので、同期完了とみなす。
			update_post_meta( $post->ID, '_thumbnail_synced', current_time( 'mysql' ) );
			// サムネイルがあれば、画像を設定
			if ( ! $thumbnail_pic_id ) {
				$thumbnail_pic_id = $profile_pic_id;
			}
			set_post_thumbnail( $post->ID, $thumbnail_pic_id );
			// メイン画像があれば、画像を設定
			if ( $profile_pic_id ) {
				update_post_meta( $post->ID, '_profile_pic_id', $profile_pic_id );
			}
			$success++;
		}
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf( 'プロフィール画像を%d件取得しました。', $success ) );
	}

	/**
	 * ISBNを取得する
	 *
	 * @synopsis <isbn>
	 * @param array $args Command arguments.
	 * @return void
	 */
	public function isbn( $args ) {
		list( $isbn ) = $args;
		$result       = sfwj_openbd_get( $isbn );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		$table = new Table();
		$table->setHeaders( [ 'Field', 'Value' ] );
		$data = [
			'Title'     => $result['summary']['title'],
			'Publisher' => $result['summary']['publisher'],
			'Author'    => $result['summary']['author'],
			'Published' => $result['summary']['pubdate'],
			'Cover'     => $result['summary']['cover'],
		];
		foreach ( $data as $field => $value ) {
			$table->addRow( [ $field, $value ] );
		}
		$table->display();
	}

	/**
	 * ISBNのある書籍を更新する
	 *
	 * @synopsis [--dry-run]
	 * @param array $args コマンドの引数
	 * @param array $assoc コマンドのオプション
	 * @return void
	 */
	public function complete_isbn( $args, $assoc ) {
		$posts = MemberWorks::get()->post_to_fix_isbn();
		if ( ! $posts ) {
			\WP_CLI::success( '修正すべき書籍情報はありませんでした。' );
		}
		\WP_CLI::line( sprintf( '%d件の投稿を修正します。', count( $posts ) ) );
		if ( $args['dry-run'] ) {
			\WP_CLI::success( '終了' );
		}
		$success = 0;
		foreach ( $posts as $post ) {
			$result = MemberWorks::get()->fix_post_with_isbn( $post->ID, true );
			if ( is_wp_error( $result ) ) {
				\WP_CLI::warning( sprintf( 'Error   #%d %s: %s', $post->ID, $post->post_title, $result->get_error_message() ) );
			} else {
				$success++;
				\WP_CLI::line( sprintf( 'Success #%d %s', $post->ID, $post->post_title ) );
			}
		}
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf( 'ISBNをもとに%d / %d件を取得しました。', $success, count( $posts ) ) );
	}

	/**
	 * 修正すべき書籍データの一覧を取得する
	 *
	 * @return void
	 */
	public function books_to_sync() {
		$posts = OpenBdSynchronizer::get()->get_posts();
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Title', 'ISBN', 'Last Synced' ] );
		foreach ( $posts as $post ) {
			$table->addRow( [
				$post->ID,
				get_the_title( $post ),
				get_post_meta( $post->ID, '_isbn', true ),
				get_post_meta( $post->ID, '_last_synced', true ),
			] );
		}
		$table->display();
	}

	/**
	 * 書籍データを同期する。
	 *
	 * @return void
	 */
	public function sync_books() {
		$result = OpenBdSynchronizer::get()->synchronize();
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $message ) {
				\WP_CLI::warning( $message );
			}
			\WP_CLI::error( 'データの同期に失敗しました。' );
		}
		\WP_CLI::success( sprintf( '%d件の書籍データを同期しました。', $result ) );
	}
}
