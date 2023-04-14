<?php

namespace Sfwj\SfwjWeb;


use Sfwj\SfwjWeb\Patterns\SingletonPattern;

/**
 * 会員の作品を管理する
 */
class MemberWorks extends SingletonPattern {

	const POST_TYPE = 'member-work';

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_post' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_filter( 'enter_title_here', function( $title, $post ) {
			if ( self::POST_TYPE === $post->post_type ) {
				return __( '書名を入力してください。', 'sfwj' );
			}
			return $title;
		}, 10, 2 );
	}

	/**
	 * 投稿タイプを保存する
	 *
	 * @return void
	 */
	public function register_post() {
		register_post_type( self::POST_TYPE, [
			'label'             => __( '会員の作品', 'sfwj' ),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => 'edit.php?post_type=member',
			'supports'          => [ 'title', 'thumbnail', 'excerpt', 'page-attributes' ],
			'has_archive'       => false,
			'show_in_rest'      => false,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'taxonomies'        => [ 'authors' ],
		] );
	}

	/**
	 * メタボックスを登録する
	 *
	 * @param string $post_type 投稿タイプ
	 * @return void
	 */
	public function register_meta_box( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}
		add_meta_box( 'member-work', __( '作品データ', 'sfwj' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * メタボックスの中身を描画する
	 *
	 * @param \WP_Post $post 投稿オブジェクト。
	 *
	 * @return void
	 */
	public function render_meta_box( $post ) {
		?>
		<p>
			<label>
				ISBN<br />
				<input class="regular-text" type="text" name="_isbn"
					value="<?php echo esc_attr( get_post_meta( $post->ID, '_isbn', true ) ); ?>"
					placeholder="<?php esc_attr_e( '13桁のISBNをハイフンなしで入力してください。', 'sfwj' ); ?>" />
			</label>
		</p>
		<p>
			<label>
				URL<br />
				<input style="width: 100%; box-sizing: border-box" class="regular-text"
					type="url" name="_url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_url', true ) ); ?>"
					placeholder="<?php esc_attr_e( 'URLがある場合は入力してください。', 'sfwj' ); ?>" />
			</label>
		</p>
		<hr />
		<p style="padding-bottom: 10px;">
			<button class="button" style="float: right;" data-book-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'openBDからデータを取得する', 'sfwj' ); ?></button>
			<span>
				<?php esc_html_e( '最終同期日：', 'sfwj' ) ?>
				<?php
				$last_synced = get_post_meta( $post->ID, '_last_synced', true );
				if ( $last_synced ) {
					printf( '<strong>%s</strong>', mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_synced ) );
				} else {
					echo '<code>---</code>';
				}
				?>
			</span>
			<span style="clear: both;"></span>
		</p>

		<?php
	}

	/**
	 * 作品を登録する
	 *
	 * @param string $isbn 13桁のISBN
	 * @return \WP_Post|null
	 */
	public function get_work( $isbn, $status = 'any' ) {
		$query = new \WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => $status,
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'   => '_isbn',
					'value' => $isbn,
				],
			],
		] );
		return $query->have_posts() ? $query->posts[0] : null;
	}

	/**
	 * ISBNで作品を登録する
	 *
	 * @param int    $author_id 作者の投稿ID
	 * @param string $isbn      13桁のISBN
	 *
	 * @return int|\WP_Error
	 */
	public function register_work_with_isbn( $author_id, $isbn ) {
		$registered = $this->get_work( $isbn );
		if ( $registered ) {
			return $registered->ID;
		}
		// 作者のタームオブジェクトを取得
		$term = get_author_term( $author_id );
		if ( ! $term ) {
			$message = sprintf( __( '%1$sの作者が%2$s(%3$d)が登録されていません。', 'sfwj' ), $isbn, get_the_title( $author_id ), $author_id );
			return new \WP_Error( 'no_author', $message );
		}
		// 同じ名前のものがあった場合は、それを使う
		$registered = $this->get_work( $isbn );
		if ( $registered ) {
			$post_id = $registered->ID;
		} else {
			// 既存のものはないので、新規登録
			$post_id = wp_insert_post( [
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $isbn,
			], true );
			if ( is_wp_error( $post_id ) ) {
				$message = sprintf( __( '%2$s（%1$s）を保存できませんでした: %3$d', 'sfwj' ), $isbn, get_the_title( $author_id ), $author_id );
				return new \WP_Error( 'register_failed', $message );
			}
			// ISBNなどを保存
			update_post_meta( $post_id, '_isbn', $isbn );
		}
		// 作者を保存
		wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy );
		return $post_id;
	}

	/**
	 * 作品を登録する
	 *
	 * @param int    $author_id 作品の投稿ID
	 * @param string $title 　　 タイトル
	 * @param string $url 　　　　URL
	 * @param string $thumbnail Google Driveにあるサムネイル画像のURL
	 * @return int|\WP_Error
	 */
	public function register_work( $author_id, $title, $url = '', $thumbnail = '' ) {
		// 作者のタームオブジェクトを取得
		$term = get_author_term( $author_id );
		if ( ! $term ) {
			$message = sprintf( __( '%1$sの作者が%2$s(%3$d)が登録されていません。', 'sfwj' ), $title, get_the_title( $author_id ), $author_id );
			return new \WP_Error( 'no_author', $message );
		}
		// 新規登録
		$post_id = wp_insert_post( [
			'post_title'  => $title,
			'post_type'   => self::POST_TYPE,
			'post_status' => 'private',
		], true );
		if ( is_wp_error( $post_id ) ) {
			$message = sprintf( __( '%2$s『%1$s』を保存できませんでした: %3$d', 'sfwj' ), $title, get_the_title( $author_id ), $author_id );
			return new \WP_Error( 'register_failed', $message );
		}
		// 作者を保存
		wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy );
		// 投稿メタを保存
		if ( $url ) {
			update_post_meta( $post_id, '_url', $url );
		}
		if ( $thumbnail ) {
			update_post_meta( $post_id, '_google_drive_url', $thumbnail );
		}
		return $post_id;

	}
}
