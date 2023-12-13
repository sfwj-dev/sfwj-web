<?php
/**
 * SF大賞関連の処理
 *
 * @see \Sfwj\SfwjWeb\Block\SfAward
 */

/**
 * SF大賞のデータをリフレッシュする
 *
 * @return int|WP_Error
 */
function sfwj_refresh_awards( $id = 0 ) {
	$args = [
		'post_type'      => 'any',
		'post_status'    => 'any',
		's'              => 'sfwj/nominees',
		'posts_per_page' => -1,
	];
	if ( $id ) {
		$args['p'] = $id;
	}
	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return 0;
	}
	$errors  = new WP_Error();
	$success = 0;
	$urls    = [];
	$total   = $query->found_posts;
	foreach ( $query->posts as $post ) {
		if ( ! has_block( 'sfwj/nominees', $post ) ) {
			continue;
		}
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( 'sfwj/nominees' === $block['blockName'] && ! empty( $block['attrs']['spreadsheet'] ) ) {
				$urls[] = $block['attrs']['spreadsheet'];
			}
		}
	}
	$spreadsheets = array_unique( $urls );
	foreach ( $spreadsheets as $url ) {
		$result = sfwj_get_csv( $url, false );
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		} else {
			++$success;
		}
	}
	if ( ! $errors->get_error_messages() ) {
		return $success;
	} else {
		return $errors;
	}
}

/**
 * 管理画面にボタンを追加する
 */
add_action( 'tool_box', function() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$message_type = filter_input( INPUT_GET, 'sfwj-award-refreshed' );
	$message      = '';
	switch ( $message_type ) {
		case '-1':
			$message = __( 'エラーがあり、更新を完了できませんでした。', 'sfwj' );
			break;
		case '0':
			$message = __( '更新するスプレッドシートがありませんでした。', 'sfwj' );
			break;
		default:
			if ( is_numeric( $message_type ) ) {
				$message = sprintf( __( '%d件のスプレッドシートを更新しました。', 'sfwj' ), $message_type );
			}
			break;
	}
	if ( ! empty( $message ) ) {
		printf(
			'<div class="updated"><p>%s</p></div>',
			esc_html( $message )
		);
	}
	?>
	<div class="card">
		<h2 class="title"><?php esc_html_e( 'SF大賞のデータ更新', 'sfwj' ); ?></h2>
		<p>
			<?php esc_html_e( 'SF大賞のスプレッドシートは1時間に一回更新されますが、緊急時には下のボタンからも更新できます。', 'sfwj' ); ?>
		</p>
		<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post">
			<input type="hidden" name="action" value="sfwj_refresh_awards" />
			<?php wp_nonce_field( 'sfwj_refresh_awards' ); ?>
			<?php submit_button( __( '最新の情報に更新', 'sfwj' ) ); ?>
		</form>
	</div>
	<?php
} );

/**
 * Ajaxアクションでキャッシュをフラッシュする
 */
add_action( 'wp_ajax_sfwj_refresh_awards', function() {
	if ( ! check_admin_referer( 'sfwj_refresh_awards' ) ) {
		wp_safe_redirect( admin_url( 'tools.php' ) );
		exit;
	}
	// キャッシュをクリアする
	$result = sfwj_refresh_awards();
	if ( is_wp_error( $result ) ) {
		$result = -1;
	}
	wp_safe_redirect( add_query_arg( [
		'sfwj-award-refreshed' => $result,
	], admin_url( 'tools.php' ) ) );
	exit;
} );

/**
 * Cronを登録する
 */
add_action( 'init', function() {
	if ( ! wp_next_scheduled( 'sfwj_sync_spreadsheet' ) ) {
		wp_schedule_event( time(), 'hourly', 'sfwj_sync_spreadsheet' );
	}
} );

/**
 * スプレッドシートをCRONで更新する。
 */
add_action( 'sfwj_sync_spreadsheet', function() {
	$result = sfwj_refresh_awards();
	if ( is_wp_error( $result ) ) {
		error_log( $result->get_error_message() );
	}
} );
