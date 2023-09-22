<?php
namespace Sfwj\SfwjWeb\Block;

use Sfwj\SfwjWeb\Patterns\SingletonPattern;

/**
 * SF大会のエントリーを表示するブロッック
 */
class SfAward extends SingletonPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_block' ], 11 );
	}

	/**
	 * ブロックを登録する
	 *
	 * @return void
	 */
	public function register_block() {
		// ブロック登録
		register_block_type( 'sfwj/nominees', [
			'editor_script'   => 'sfwj-nominees',
			'style'           => 'sfwj-nominees',
			'render_callback' => [ $this, 'render' ],
			'attributes'      => $this->attributes(),
		] );
		// スクリプトに属性を渡す
		wp_localize_script( 'sfwj-nominees', 'SwfNomineesVars', [
			'attributes' => $this->attributes(),
		] );
	}

	/**
	 * ブロックの俗市愛知
	 *
	 * @return array[]
	 */
	public function attributes() {
		return [
			'published_at' => [
				'type'    => 'string',
				'default' => '',
			],
			'spreadsheet' => [
				'type'    => 'string',
				'default' => '',
			],
		];
	}

	/**
	 * Render block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @return string
	 */
	public function render( $attributes = [], $content = '' ) {
		$default = [];
		foreach ( $this->attributes() as $key => $value ) {
			$default[ $key ] = $value['default'];
		}
		$attributes = wp_parse_args( $attributes, $default );
		try {
			$data = sfwj_get_csv( $attributes['spreadsheet'], true );
			if ( is_wp_error( $data ) ) {
				throw new \Exception( $data->get_error_message(), 500 );
			}
			// todo: 日付指定する
			if ( false ) {
				$date = date_i18n( get_option( 'date_format' ) );
				throw new \Exception( sprintf( __( 'このデータは%s以降に公開予定です。' ), $date ), 503 );
			}
			ob_start();
			include dirname( __DIR__, 4 ) . '/includes/template-awards.php';
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} catch ( \Exception $e ) {
			return sprintf( '<div class="wp-block-vk-blocks-alert alert alert-danger is-text-center"><p>%s</p></div>', esc_html( $e->getMessage() ) );
		}
	}
}
