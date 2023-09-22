<?php
/**
 * SF大賞を表示する
 *
 * @var array $data スプレッドシートのデータ。
 */
$data = array_values( array_filter( $data, function( $row ) {
	return '公開' === $row[8];
} ) );
// todo: 新着順にソート

// 表示すべきデータが存在しないので、出色しない。
if ( empty( $data ) ) {
	?>
	<div class="wp-block-vk-blocks-alert alert alert-danger is-text-center">
		<p><?php esc_html_e( '表示できるデータがありません。', 'sfwj' ); ?></p>
	</div>
	<?php
	return;
}

// 以下、データが存在しえるので表示
?>


<div class="sfwj-nominees">
	<div class="sfwj-nominees-stats">
		<strong>
			<?php printf( esc_html__( 'ノミネート件数: %d', 'sfwj' ), count( $data ) ); ?>
		</strong>
		<select>
			<option><?php esc_html_e( '新着順', 'sfwj' ); ?></option>
			<option><?php esc_html_e( '投稿順', 'sfwj' ); ?></option>
		</select>
		<input type="text" placeholder="<?php esc_attr_e( '絞り込み', 'sfwj' ) ?>" />
	</div>
	<ol class="sfwj-nominees-list">
		<?php foreach ( $data as $row ) :
			list( $id, $timestamp, $mail, $voted_by, $title, $author, $comment, $sns_available, $status, $checked_by, $work_id, $publisher ) = $row;
			$formatted = new DateTime( $timestamp, new DateTimeZone( wp_timezone_string() ) );
			?>
			<li class="sfwj-nominiees-item">
				<h3 class="sfwj-nominees-title">
					<?php echo esc_html( $author . ' ' . $title ); ?>
				</h3>
				<div class="sfwj-nominees-meta">
					<span class="sfwj-nominees-vodted_by">
						<?php echo esc_html( $voted_by ); ?>
					</span>
					<time class="sfwj-nominees-date" datetime="<?php echo esc_attr( $timestamp ); ?>">
						<?php echo esc_html( $formatted->format( get_option( 'date_format' ) ) ); ?>
					</time>
				</div>
				<div class="sfwj-nominees-description">
					<?php echo wp_kses_post( wpautop( $comment ) ); ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
