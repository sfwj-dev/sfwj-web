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
			'script'          => 'sfwj-nominees-helper',
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
			'spreadsheet'  => [
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
			$published_at = (string) $attributes['published_at'];
			if ( $published_at ) {
				if ( preg_match( '/(\d{4}-\d{2}-\d{2})T(\d{2}):(\d{2})/u', $published_at, $matches ) ) {
					// 日付をMySQL互換性に変更
					$published_at = sprintf( '%s-%s:00', $matches[1], $matches[2] );
				}
			}
			$is_rest     = defined( 'REST_REQUEST' ) && REST_REQUEST;
			$can_publish = ! $published_at || ( get_the_time( 'Y-m-d H:i:s' ) >= $published_at );
			ob_start();
			include dirname( __DIR__, 4 ) . '/includes/template-awards.php';
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} catch ( \Exception $e ) {
			return sprintf( '<div class="wp-block-vk-blocks-alert alert alert-info is-text-center"><p>%s</p></div>', esc_html( $e->getMessage() ) );
		}
	}
}
