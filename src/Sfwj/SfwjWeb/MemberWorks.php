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
		// 投稿タイプを登録
		add_action( 'init', [ $this, 'register_post' ] );
		// メタボックスを登録
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_post' ], 10, 2 );
		// タイトルのプレースホルダーを変更
		add_filter( 'enter_title_here', function( $title, $post ) {
			if ( self::POST_TYPE === $post->post_type ) {
				return __( '書名を入力してください。', 'sfwj' );
			}
			return $title;
		}, 10, 2 );
		// REST APIを登録
		add_action( 'rest_api_init', [ $this, 'register_rest_api' ] );
		// ブロックを追加
		add_action( 'init', [ $this, 'register_block' ] );
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
			'taxonomies'        => [ 'authors', 'publisher' ],
		] );
	}

	/**
	 * メタボックスを登録する
	 *
	 * @param string $post_type 投稿タイプ
	 * @return void
	 */
	public function register_meta_box( $post_type ) {
		// TODO: ブロック用JSの場所を移す
		// ブロック用途に会員種別を読み込み
		$member_status = [];
		$terms         = get_terms( [
			'taxonomy'   => 'member-status',
			'hide_empty' => false,
		] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$member_status[] = [
					'label' => $term->name,
					'value' => $term->slug,
				];
			}
		}
		wp_localize_script( 'sfwj-member-block', 'SfwjMemberStatus', $member_status );
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}
		// メタボックスを登録
		add_meta_box( 'member-work', __( '作品データ', 'sfwj' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
		// スクリプトを読み込み
		wp_enqueue_script( 'sfwj-isbn-helper' );
	}

	/**
	 * 投稿データを保存する
	 *
	 * @param int      $post_id 投稿ID。
	 * @param \WP_Post $post    投稿オブジェクト。
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_sfwjisbnnonce' ), 'update_isbn' ) ) {
			return;
		}
		update_post_meta( $post_id, '_isbn', filter_input( INPUT_POST, '_isbn' ) );
		update_post_meta( $post_id, '_url', filter_input( INPUT_POST, '_url' ) );
	}

	/**
	 * メタボックスの中身を描画する
	 *
	 * @param \WP_Post $post 投稿オブジェクト。
	 *
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'update_isbn', '_sfwjisbnnonce', false );
		$isbn_data = get_post_meta( $post->ID, '_isbn_data', true );
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
				<?php esc_html_e( '最終同期日：', 'sfwj' ); ?>
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
		if ( $isbn_data ) :
			if ( ! empty( $isbn_data['summary']['cover'] ) ) :
				?>
					<div>
					<img src="<?php echo esc_url( $isbn_data['summary']['cover'] ); ?>" alt="<?php echo esc_attr( $isbn_data['summary']['title'] ); ?>" loading="lazy" style="width: auto; height: auto; max-width: 150px;" />
				</div>
				<p class="description">
					<?php esc_html_e( 'OpenBDと同期されています。', 'sfwj' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'OpenBDと同期されていますが、書影はありません。', 'sfwj' ); ?>
				</p>
			<?php endif; ?>
			<textarea readonly rows="8" style="width: 100%; box-sizing: border-box;"><?php echo esc_textarea( json_encode( $isbn_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ); ?></textarea>
		<?php endif; ?>
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
		$append     = false;
		if ( $registered ) {
			$post_id = $registered->ID;
			// 消さないようにする
			$append = true;
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
		wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy, $append );
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

	/**
	 * カバー画像を修正すべき投稿を取得する
	 *
	 * @return \WP_Post[]
	 */
	public function post_to_fix_covers() {
		$query = new \WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'meta_query'     => [
				[
					'key'     => '_google_drive_url',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_google_fetched',
					'compare' => 'NOT EXISTS',
				],
			],
			'posts_per_page' => -1,
		] );
		return $query->posts;
	}

	/**
	 * ISBNを修正すべき投稿を取得する
	 *
	 * @return \WP_Post[]
	 */
	public function post_to_fix_isbn() {
		$query = new \WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_isbn',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_last_synced',
					'compare' => 'NOT EXISTS',
				],
			],
		] );
		return $query->posts;
	}

	/**
	 * 投稿のデータをISBNから取得して修正する
	 *
	 * @param int  $post_id 書籍のID
	 * @parma bool $publish 修正後に公開するかどうか
	 *
	 * @return int|\WP_Error
	 */
	public function fix_post_with_isbn( $post_id, $publish = false ) {
		$post = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'invalid_post', __( '無効な投稿です。', 'sfwj' ) );
		}
		$isbn = (string) get_post_meta( $post_id, '_isbn', true );
		if ( ! preg_match( '/^\d{13}$/u', $isbn ) ) {
			return new \WP_Error( 'invalid_isbn', __( '無効なISBNです。', 'sfwj' ) );
		}
		// APIからデータを取得
		$book_info = sfwj_openbd_get( $isbn );
		if ( is_wp_error( $book_info ) ) {
			// なかったらタイトルを変更
			if ( $publish ) {
				wp_update_post( [
					'ID'         => $post_id,
					'post_title' => $isbn . ' ' . '失敗',
				] );
			}
			return $book_info;
		}
		// ISBNの情報を保存
		update_post_meta( $post_id, '_isbn_data', $book_info );
		update_post_meta( $post_id, '_last_synced', current_time( 'mysql' ) );
		// 出版社を保存
		if ( ! empty( $book_info['summary']['publisher'] ) ) {
			wp_set_object_terms( $post_id, $book_info['summary']['publisher'], 'publisher' );
		}
		// 投稿を保存
		$post_args = [
			'ID'         => $post_id,
			'post_title' => $book_info['summary']['title'],
		];
		if ( $publish ) {
			$post_args['post_status'] = 'publish';
		}
		return wp_update_post( $post_args, true );
	}

	/**
	 * REST APIを登録する
	 *
	 * @return void
	 */
	public function register_rest_api() {
		register_rest_route( 'sfwj/v1', '/isbn/(?P<id>\d+)', [
			'methods'             => 'POST',
			'args'                => [
				'id'   => [
					'required'          => true,
					'description'       => __( '投稿のIDです。', 'sfwj' ),
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && get_post( $param );
					},
				],
				'isbn' => [
					'required'          => true,
					'description'       => __( '13桁のISBNです。', 'sfwj' ),
					'validate_callback' => function( $param ) {
						return preg_match( '/^\d{13}$/u', $param );
					},
				],
			],
			'callback'            => [ $this, 'rest_update_isbn' ],
			'permission_callback' => function( \WP_REST_Request $request ) {
				return current_user_can( 'edit_post', $request->get_param( 'id' ) );
			},
		] );
	}

	/**
	 * 投稿をISBNの情報を元にする
	 *
	 *
	 * @param \WP_REST_Request $request リクエストオブジェクト
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function rest_update_isbn( $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'invalid_post', __( '該当する投稿がありません。', 'sfwj' ), [
				'status' => 400,
			] );
		}
		// ISBNを保存
		$isbn = $request->get_param( 'isbn' );
		if ( ! preg_match( '/^\d{13}$/u', $isbn ) ) {
			return new \WP_Error( 'invalid_post', __( 'ISBNの形式が不正です。', 'sfwj' ), [
				'status' => 400,
			] );
		}
		update_post_meta( $post_id, '_isbn', $isbn );
		// ISBNの情報をもとに投稿を更新
		$result = $this->fix_post_with_isbn( $post_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( [
			'success' => true,
			'post_id' => $post_id,
			'message' => __( 'ISBNを元に投稿を更新しました。', 'sfwj' ),
			'url'     => get_edit_post_link( $post_id, 'api' ),
		] );
	}

	/**
	 * ブロックを追加する
	 *
	 * @return void
	 */
	public function register_block() {
		register_block_type( 'sfwj/members', [
			'attributes'      => [
				'status'   => [
					'type'    => 'string',
					'default' => '',
				],
				'link'     => [
					'type'    => 'boolean',
					'default' => true,
				],
				'grouping' => [
					'type'    => 'boolean',
					'default' => true,
				],
			],
			'render_callback' => [ $this, 'block_render_callback' ],
			'editor_script'   => 'sfwj-member-block',
			'style'           => 'sfwj-member-block',
		] );
	}

	/**
	 * 会員一覧ブロックを追加する
	 *
	 * @param array  $attributes ブロックの属性
	 * @param string $content    ブロックの内容
	 *
	 * @return string
	 */
	public function block_render_callback( $attributes, $content = '' ) {
		$attributes = wp_parse_args( $attributes, [
			'status'   => '',
			'link'     => true,
			'grouping' => true,
		] );
		$args       = [
			'post_type'        => 'member',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => [ 'meta_value' => 'ASC' ],
			'meta_key'         => '_yomigana',
			'suppress_filters' => false,
		];
		if ( $attributes['status'] ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'member-status',
					'field'    => 'slug',
					'terms'    => $attributes['status'],
				],
			];
		}
		$posts = get_posts( $args );
		ob_start();
		if ( empty( $posts ) ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				printf( '<p class="description">%s</p>', esc_html__( '該当する会員はいません。', 'sfwj' ) );
			}
		} else {
			if ( $attributes['grouping'] ) {
				$members = [];
				$kana    = [
					'あいうえお',
					'かきくけこがぎぐけご',
					'さしすせそさじずぜぞ',
					'たちつてとたぢづてど',
					'なにぬねの',
					'はひふへほばびぶべぼぱぴぷぺぽ',
					'まみむめも',
					'やゆよ',
					'らりるれろ',
					'わをん',
				];
				foreach ( $posts as $post ) {
					$yomigana = get_post_meta( $post->ID, '_yomigana', true );
					if ( ! $yomigana ) {
						continue;

					}
					$first_letter = mb_substr( $yomigana, 0, 1 );
					$group        = '';
					foreach ( $kana as $kana_group ) {
						$first_kana = mb_substr( $kana_group, 0, 1 );
						$kana_group = $kana_group . mb_convert_kana( $kana_group, 'C' );
						if ( false !== mb_strpos( $kana_group, $first_letter ) ) {
							$group = $first_kana;
							break 1;
						}
					}
					if ( ! $group ) {
						continue;
					}
					if ( ! isset( $members[ $group ] ) ) {
						$members[ $group ] = [];
					}
					$members[ $group ][] = $post;
				}
			} else {
				$members = [ '' => $posts ];
			}
			// 統計様のラベル
			$stats_label = '';
			if ( $attributes['status'] ) {
				$term = get_term_by( 'slug', $attributes['status'], 'member-status' );
				if ( $term && ! is_wp_error( $term ) ) {
					$stats_label = $term->name . ': ';
				}
			}
			?>
			<div class="wp-block-sfwj-members sfwj-members">
				<p class="sfwj-members-count"><?php printf( esc_html__( '%1$s%2$s名', 'sfwj' ), $stats_label, number_format( count( $posts ) ) ); ?></p>
				<?php
				foreach ( $members as $key => $ms ) {
					if ( $key ) {
						printf( '<h3 class="sfwj-members-title">%s行</h3>', esc_html( $key ) );
					}
					if ( empty( $ms ) ) {
						continue;
					}
					?>
					<ul class="sfwj-members-list">
						<?php foreach ( $ms as $m ) : ?>
						<li class="sfwj-members-item">
							<?php if ( $attributes['link'] ) : ?>
								<a href="<?php echo get_the_permalink( $m ); ?>" class="sfwj-members-link">
									<?php echo get_the_title( $m ); ?>
								</a>
							<?php else : ?>
								<span class="sfwj-memers-name">
									<?php echo get_the_title( $m ); ?>
								</span>
							<?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php
				}
				?>
			</div>
			<?php
		}
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	/**
	 * 作品の表紙画像を返す
	 *
	 * @param null|int|\WP_Post $post 投稿オブジェクト
	 * @param string $class_name クラス名
	 * @return string
	 */
	public static function get_cover( $post = null, $class_name = 'sfwj-member-work-cover' ) {
		$post = get_post( $post );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}
		$thumbnail_id = get_post_thumbnail_id( $post );
		if ( $thumbnail_id ) {
			// アイキャッチが設定されているのでそのまま返す
			return wp_get_attachment_image( $thumbnail_id, 'large', false, [
				'class' => $class_name,
			] );
		}
		// アイキャッチがない場合は、openBDの情報をもとに返す
		$isbn = get_post_meta( $post->ID, '_isbn_data', true );
		if ( ! empty( $isbn['summary']['cover'] ) ) {
			return sprintf( '<img class="%s" alt="%s" src="%s" />', esc_attr( $class_name ), esc_attr( get_the_title( $post ) ), esc_url( $isbn['summary']['cover'] ) );
		}
		return '';
	}

	/**
	 * 作品のリンクを返す
	 *
	 * @param null|int|\WP_Post $post 投稿オブジェクト
	 * @return string
	 */
	public static function get_link( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}
		// 投稿メタに保存されたものを取得
		$url = get_post_meta( $post->ID, '_url', true );
		if ( $url ) {
			return $url;
		}
		// openBDの情報をもとに返す
		$isbn = get_post_meta( $post->ID, '_isbn_data', true );
		if ( $isbn ) {
			// JSONがあるということは版元.comの情報があるのでそちらを優先
			return sprintf( 'https://www.hanmoto.com/bd/isbn/%d', $isbn['summary']['isbn'] );
		}
		// ない場合はシャープ
		return '#';

	}
}
