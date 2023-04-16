<?php
/**
 * 設定を保存する画面を作成する
 *
 *
 */


add_action( 'admin_menu', function() {
	add_options_page( __( 'SFWJ設定', 'sfwj' ), __( 'SFWJ設定', 'sfwj' ), 'manage_options', 'sfwj-settings', function() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SFWJ設定', 'sfwj' ); ?></h1>
			<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
				<?php
				settings_fields( 'sfwj-settings' );
				do_settings_sections( 'sfwj-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	} );
} );

/**
 * 設定を保存する画面を作成
 */
add_action( 'admin_init', function() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	// 設定セクションを追加
	add_settings_section( 'sfwj-api-section', __( 'API設定', 'sfwj' ), function() {
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'SFWJのAPIを利用するための設定です。', 'sfwj' )
		);
	}, 'sfwj-settings' );

	// 設定項目を追加
	// Google API サービスアカウント
	add_settings_field( 'sfwj-ga-account', __( 'Google API サービスアカウント', 'sfwj' ), function() {
		$account = get_option( 'sfwj-ga-account', '' );
		printf(
			'<textarea name="sfwj-ga-account" rows="13" style="width: 100%%; box-sizing: border-box">%s</textarea>',
			esc_textarea( $account )
		);
	}, 'sfwj-settings', 'sfwj-api-section' );
	register_setting( 'sfwj-settings', 'sfwj-ga-account' );
} );
