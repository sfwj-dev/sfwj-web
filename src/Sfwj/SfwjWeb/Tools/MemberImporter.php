<?php

namespace Sfwj\SfwjWeb\Tools;


use Sfwj\SfwjWeb\Patterns\SingletonPattern;
use Sfwj\SfwjWeb\Tools\CsvParser\NormalMemberCsvParser;
use Sfwj\SfwjWeb\Tools\CsvParser\SimpleMemberCsvParser;

/**
 * メンバーをインポートする機能を作成
 */
class MemberImporter extends SingletonPattern {


	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
	}

	/**
	 * ツールにメニューを追加
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page( 'tools.php', __( '会員インポート', 'sfwj' ), __( '会員インポート', 'sfwj' ), 'manage_options', 'sfwj-member-importer', [ $this, 'render' ] );
	}

	/**
	 * ツールの初期化
	 *
	 * @return void
	 */
	public function admin_init() {
		if ( ! isset( $_SERVER['SCRIPT_FILENAME'] ) || 'tools.php' !== basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
			// Do nothing.
			return;
		}
		if ( 'sfwj-member-importer' !== filter_input( INPUT_GET, 'page' ) ) {
			// This is not the page.
			return;
		}
		if ( 'import-members' !== filter_input( INPUT_GET, 'action' ) ) {
			// This is not the action.
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'sfwj-member-importer' ) ) {
			// Invalid nonce.
			return;
		}
		$file_id = filter_input( INPUT_POST, 'csv' );
		$type    = filter_input( INPUT_POST, 'type' );
		// 種類別に処理を開始する
		$parser = null;
		switch ( $type ) {
			case 'normal':
				$parser = new NormalMemberCsvParser( $file_id );
				break;
			case 'other':
				$parser = new SimpleMemberCsvParser( $file_id );
				break;
			default:
				wp_die( __( '不正な会員種別が指定されています。', 'sfwj' ), '400 Bad Request', [
					'back_link' => true,
				] );
		}
		$result = $parser->parse();
		if ( is_wp_error( $result ) ) {
			wp_die( $result, '500 Internal Server Error', [
				'back_link' => true,
			] );
		}
		wp_safe_redirect( 'tools.php?page=sfwj-member-importer&success=' . $result );
		exit;
	}

	/**
	 * インポーターの画面を登録する
	 *
	 * @return void
	 */
	public function render() {
		$messages = [];
		if ( isset( $_GET['success'] ) ) {
			$messages [] = sprintf( __( '%d件の会員データをインポートしました。', 'sfwj' ), filter_input( INPUT_GET, 'success' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( '会員インポート', 'sfwj' ); ?></h1>
			<?php if ( ! empty( $messages ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<?php foreach ( $messages as $message ) : ?>
						<p><?php echo esc_html( $message ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<form action="<?php echo admin_url( 'tools.php?page=sfwj-member-importer&action=import-members' ); ?>" method="post">
				<?php wp_nonce_field( 'sfwj-member-importer' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="sfwj-csv">
								<?php esc_html_e( 'CSVファイル', 'sfwj' ); ?>
							</label>
						</th>
						<td>
							<?php
							$csvs = $this->get_csv();
							if ( empty( $csvs ) ) :
								?>
								<p style="color: red"><?php esc_html_e( 'CSVファイルが登録されていません。', 'sfwj' ); ?></p>
							<?php else : ?>
								<select name="csv" id="sfwj-csv" style="max-width: 100%; box-sizing: border-box;">
									<option value="0" selected><?php esc_html_e( 'ファイルを選択してください。', 'sfwj' ); ?></option>
									<?php foreach ( $csvs as $csv ) : ?>
									<option value="<?php echo esc_attr( $csv->ID ); ?>">
										<?php echo esc_html( get_the_title( $csv ) ); ?>
										（<?php echo esc_html( $csv->post_mime_type ); ?>）
									</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
							<p class="description">
								<?php
								printf(
									esc_html__( 'CSVファイルは%sより登録してください。', 'sfwj' ),
									sprintf( '<a href="%s">%s</a>', admin_url( 'upload.php' ), esc_html__( 'メディア・アップローダー', 'sfwj' ) )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sfwj-member-type">
								<?php esc_html_e( '種別', 'sfwj' ); ?>
							</label>
						</th>
						<td>
							<select name="type" id="sfwj-member-type">
								<option value="" selected><?php esc_html_e( '選択してください', 'sfwj' ); ?></option>
								<option value="normal"><?php esc_html_e( '一般会員', 'sfwj' ); ?></option>
								<option value="other"><?php esc_html_e( '物故会員・賛助会員など', 'sfwj' ); ?></option>
							</select>
							<p class="description">
								<?php
								printf(
									esc_html__( '会員種別によってCSVのレイアウトが異なります。詳細は%sをご覧ください。', 'sfwj' ),
									'<a href="https://drive.google.com/drive/folders/1cMzjdMkBibqjzaCcpVlJF78_shWE03fw" target="_blank" rel="noopener noreferrer">Google Drive</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'インポート', 'sfwj' ) ); ?>
		</div>
		<?php
	}

	/**
	 * アップロードされたCSVファイルを登録する
	 *
	 * @return \WP_Post[]
	 */
	protected function get_csv() {
		$query = new \WP_Query( [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'text/csv',
			'posts_per_page' => -1,
			'orderby'        => [ 'date' => 'DESC' ],
		] );
		return $query->get_posts();
	}
}
