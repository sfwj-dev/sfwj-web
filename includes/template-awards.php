<?php
/**
 * SF大賞を表示する
 *
 * @var array  $data         スプレッドシートのデータ。
 * @var string $published_at 公開予定日時
 * @var bool   $is_rest      REST APIからの呼び出しかどうか
 * @var bool   $can_publish  公開してよいかどうか
 */

// 日付指定がある場合は権限によって表示を変更
if ( ! $can_publish ) {
	$message = sprintf( __( 'エントリー作品は%sに公開予定です。', 'sfwj' ), mysql2date( get_option( 'date_format' ), $published_at ) );
	?>
		<div class="wp-block-vk-blocks-alert alert alert-info is-text-center">
			<p class="is-text-center">
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
	<?php
	if ( ! current_user_can( 'edit_post', get_the_ID() ) ) {
		// 編集権限のないユーザーはここで終了
		return;
	}
}
// データ
$data = array_values( array_filter( $data, function( $row ) {
	return '公開' === $row[8];
} ) );
// 表示すべきデータが存在しないので、出力しない。
if ( empty( $data ) ) {
	?>
	<div class="wp-block-vk-blocks-alert alert alert-danger is-text-center">
		<p><?php esc_html_e( '表示できるデータがありません。', 'sfwj' ); ?></p>
	</div>
	<?php
	return;
}
// IDではなく、ノミネート潤を変更
foreach ( $data as $i => &$d ) {
	$i++;
	array_unshift( $d,  $i );
}
// 新着順にソート
usort( $data, function( $a, $b ) {
	if ( $a[0] === $b[0] ) {
		return 0;
	} else {
		return ( $a[0] > $b[0] ) ? -1 : 1;
	}
} );
?>


<div class="sfwj-nominees">
	<div class="sfwj-nominees-stats">
		<div>
			<strong>
				<?php printf( esc_html__( 'ノミネート件数: %d', 'sfwj' ), count( $data ) ); ?>
			</strong>
		</div>
		<div class="text-right">
			<button class="sfwj-nominees-open-all btn btn-outline-secondary btn-sm"><?php esc_html_e( 'すべてのコメントを展開', 'sfwj' ) ?></button>
		</div>
		<div>
			<select id="sfwj-nominees-sort">
				<option value="desc"><?php esc_html_e( '新着順', 'sfwj' ); ?></option>
				<option value="asc"><?php esc_html_e( '投稿順', 'sfwj' ); ?></option>
			</select>
		</div>
		<div class="text-right">
			<input class="sfwj-nominees-filter" type="text" placeholder="<?php esc_attr_e( '文字で絞り込み', 'sfwj' ) ?>" />
		</div>
	</div>
	<ol class="sfwj-nominees-list">
		<?php foreach ( $data as $row ) :
			list( $index, $id, $timestamp, $mail, $voted_by, $title, $author, $comment, $sns_available, $status, $checked_by, $work_id, $publisher ) = $row;
			$formatted = new DateTime( $timestamp, new DateTimeZone( wp_timezone_string() ) );
			?>
			<li class="sfwj-nominees-item" data-index="<?php echo esc_attr( $index ); ?>">
				<span class="sfwj-nominees-id">No.<?php echo number_format_i18n( $index ); ?></span>
				<h3 class="sfwj-nominees-title">
					<span class="sfwj-nominees-title-txt">
						<?php echo esc_html( $author . ' ' . $title ); ?>
					</span>
					<span class="sfwj-nominees-publisher">
						<?php echo esc_html( $publisher ); ?>
					</span>
				</h3>
				<div class="sfwj-nominees-meta">
					<span class="sfwj-nominees-voted_by">
						<?php esc_html_e( '投票者: ', 'sfwj' ); ?>
						<strong><?php echo esc_html( $voted_by ); ?></strong>
					</span>
					<time class="sfwj-nominees-date" datetime="<?php echo esc_attr( $timestamp ); ?>">
						<?php echo esc_html( $formatted->format( get_option( 'date_format' ) ) ); ?>
					</time>
				</div>
				<div class="sfwj-nominees-description">
					<?php echo wp_kses_post( wpautop( $comment ) ); ?>
				</div>
				<p class="sfwj-nominees-item-footer">
					<button class="sfwj-nominees-button btn btn-outline-secondary btn-sm">
						<span class="sfwj-nominees-item-footer-open"><?php esc_html_e( 'コメントをすべて読む', 'sfwj' ); ?></span>
						<span class="sfwj-nominees-item-footer-close"><?php esc_html_e( 'コメントを閉じる', 'sfwj' ); ?></span>
					</button>
				</p>
			</li>
		<?php endforeach; ?>
	</ol>
	<div class="wp-block-vk-blocks-alert alert alert-danger is-text-center">
		<p><?php esc_html_e( '表示できるデータがありません。', 'sfwj' ); ?></p>
	</div>
</div>
