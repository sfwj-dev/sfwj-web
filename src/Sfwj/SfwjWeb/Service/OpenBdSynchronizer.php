<?php

namespace Sfwj\SfwjWeb\Service;

use Sfwj\SfwjWeb\MemberWorks;
use Sfwj\SfwjWeb\Patterns\SingletonPattern;

/**
 * OpenBdとデータを同期する
 */
class OpenBdSynchronizer extends SingletonPattern {

	const EVENT_NAME = 'sfwj_openbd_sync_event';

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_cron' ] );
		add_action( self::EVENT_NAME, [ $this, 'do_cron' ] );
	}

	/**
	 * クーロンを登録する
	 *
	 * @return void
	 */
	public function register_cron() {
		if ( ! wp_next_scheduled( self::EVENT_NAME ) ) {
			wp_schedule_event( time(), 'hourly', self::EVENT_NAME );
		}
	}

	/**
	 * クーロンを実行する
	 *
	 * @return void
	 */
	public function do_cron() {
		$result = $this->synchronize();
		if ( is_wp_error( $result ) ) {
			error_log( implode( "\n", $result->get_error_messages() ) );
		}
	}

	/**
	 * OpenBDとデータを同期する投稿を取得
	 *
	 * @return \WP_Post[]
	 */
	public function get_posts( $diff = 7, $args = [] ) {
		$args = wp_parse_args( $args, [
			'post_type'      => MemberWorks::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 100,
		] );
		// 指定された期日を取得
		$date = new \DateTime( 'now', new \DateTimeZone( wp_timezone_string() ) );
		$date->sub( new \DateInterval( sprintf( 'P%dD', $diff ) ) );
		$args['meta_query'] = [
			[
				'key'     => '_isbn',
				'value'   => '',
				'compare' => '!=',
			],
			[
				'key'     => '_last_synced',
				'value'   => $date->format( 'Y-m-d H:i:s' ),
				'compare' => '<',
				'type'    => 'DATETIME',
			],
		];
		$query              = new \WP_Query( $args );
		return $query->posts;
	}

	/**
	 * OpenBDとデータを同期する
	 *
	 * @return \WP_Error|int 成功した場合は更新件数
	 */
	public function synchronize( $diff = 7, $args = [] ) {
		$posts   = $this->get_posts( $diff, $args );
		$errors  = new \WP_Error();
		$success = 0;
		$total   = count( $posts );
		foreach ( $posts as $post ) {
			$result = MemberWorks::get()->fix_post_with_isbn( $post->ID, false );
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			} else {
				++$success;
			}
		}
		if ( $errors->get_error_messages() ) {
			$errors->add( 'opebd_api_error', sprintf( '%d/%d件の書籍データを同期しました。', $success, $total ) );
			return $errors;
		}
		return $success;
	}
}
