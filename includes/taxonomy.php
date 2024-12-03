<?php
/**
 * 会員情報と新刊情報に紐づける「作家」を関連づける
 *
 * - 関連関数
 * - 「作家」の編集画面に会員情報紐付け機能を作成する
 */
namespace Sfwj\SfwjWeb;


const TAXONOMY_AUTHOR     = 'authors';
const TERM_META_AUTHOR_ID = 'member_id';

/**
 * 投稿に紐づく著者タグを取得する
 *
 * @param int $post_id 投稿ID
 * @return \WP_Term|null
 */
function get_author_term( $post_id ) {
	$term_query = new \WP_Term_Query( [
		'taxonomy'   => TAXONOMY_AUTHOR,
		'hide_empty' => false,
		'number'     => 1,
		'meta_query' => [
			[
				'key'   => TERM_META_AUTHOR_ID,
				'value' => $post_id,
			],
		],
	] );
	foreach ( $term_query->get_terms() as $term ) {
		return $term;
	}
	return null;
}

/**
 * 会員情報から「作家」タグを作成する
 *
 * @param int $post_id 会員の投稿ID
 * @return int|\WP_Error
 */
function create_author_term( $post_id ) {
	$title = get_the_title( $post_id );
	// 既存の同名タグがあるかを確認
	$term_query = new \WP_Term_Query( [
		'taxonomy'   => TAXONOMY_AUTHOR,
		'hide_empty' => false,
		'number'     => 1,
		'name'       => $title,
	] );
	foreach ( $term_query->get_terms() as $term ) {
		// 同じ名前のタグを発見したので、メタ情報を更新する
		add_term_meta( $term->term_id, TERM_META_AUTHOR_ID, $post_id );
		return $term->term_id;
	}
	// ここまで来たということは、タグはなかった。
	$term = wp_insert_term( get_the_title( $post_id ), TAXONOMY_AUTHOR );
	if ( is_wp_error( $term ) ) {
		return $term;
	}
	add_term_meta( $term['term_id'], TERM_META_AUTHOR_ID, $post_id );
	return $term['term_id'];
}

/**
 * 会員情報紐付け用にすべての作家を取得する
 *
 * @return \WP_Post[]
 */
function get_available_authors() {
	$query = new \WP_Query( [
		'post_type'      => 'member',
		'post_status'    => 'any',
		'orderby'        => [ 'meta_value' => 'ASC' ],
		'meta_key'       => '_yomigana',
		'posts_per_page' => -1,
	] );
	return $query->posts;
}

/**
 * 投稿種別を取得する
 *
 * @param int|null|\WP_Post $post 投稿オブジェクト
 * @return \WP_Term|null
 */
function member_status( $post = null ) {
	$post  = get_post( $post );
	$terms = get_the_terms( $post, 'member-status' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	foreach ( $terms as $term ) {
		return $term;
	}
}

\add_action( 'add_meta_boxes', function( $post_type ) {
	if ( 'member' !== $post_type ) {
		return;
	}
	add_meta_box( 'member-tag', __( '作家タグ', 'sfwj' ), function( \WP_Post $post ) {
		printf( '<p>%s</p>', esc_html__( '作家タグはニュースと会員を紐づけるために利用されます。', 'sfwj' ) );
		$term = get_author_term( $post->ID );
		if ( $term ) {
			echo '<p class="description">';
			echo wp_kses_post( sprintf(
				__( 'この作家は作家タグ「<a href="%2$s" target="_blank" rel="noopener noreferrer">%1$s</a>」と紐づいています。', 'sfwj' ),
				esc_html( $term->name ),
				esc_url( get_term_link( $term ) )
			) );
			echo '</p>';
		} else {
			echo '<p class="description wp-ui-text-notification">';
			echo wp_kses_post( sprintf(
				__( 'この作家は作家タグと紐づいていません。<a href="%s">作家タグ</a>へ移動し、この作家と同名のタグを作成し、会員情報を紐づけてください。', 'sfwj' ),
				admin_url( 'edit-tags.php?taxonomy=authors&post_type=members-publication' )
			) );
			echo '</p>';
		}
	}, $post_type, 'side', 'high' );
} );

/**
 * 作家タグの保存
 */
\add_action( 'edited_' . TAXONOMY_AUTHOR, function( $term_id, $tt_id, $args ) {
	if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_sfwjauthornonce' ), 'sfwj_update_author' ) ) {
		return;
	}
	update_term_meta( $term_id, TERM_META_AUTHOR_ID, (int) filter_input( INPUT_POST, 'member_id' ) );
}, 10, 3 );

/**
 * 作家タグの編集画面
 */
\add_action(  TAXONOMY_AUTHOR . '_edit_form_fields', function( \WP_Term $term, $taxonomy ) {
	$id = get_term_meta( $term->term_id, TERM_META_AUTHOR_ID, true );
	wp_enqueue_script( 'select2' );
	wp_enqueue_style( 'select2' );
	$js = <<<JS
jQuery( document ).ready( function( $ ) {
    $('select[name="member_id"]').select2();
} );
JS;

	wp_add_inline_script( 'select2', $js );
	?>
	<tr>
		<th><label for="sfwj-post-author"><?php esc_html_e( '会員情報', 'sfwj' ); ?></label></th>
		<td>
			<?php wp_nonce_field( 'sfwj_update_author', '_sfwjauthornonce', false ); ?>
			<select name="member_id" id="sfwj-post-author">
				<option value="" <?php selected( $id, '' ); ?>><?php esc_html_e( '該当会員なし', 'sfwj' ); ?></option>
				<?php foreach ( get_available_authors() as $author ) : ?>
					<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $id, $author->ID ); ?>>
						<?php
						echo get_the_title( $author );
						$status = member_status( $author );
						printf( '（%s）', $status ? esc_html( $status->name ) : esc_html__( '未設定', 'sfwj' ) );
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! $id ) : ?>
			<p class="description wp-ui-text-notification"><?php esc_html_e( 'この作家タグは作家と紐づいていません。', 'sfwj' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}, 10, 2 );

/**
 * 会員に「貢献」というタクソノミーをつける
 */
add_action( 'init', function() {
	register_taxonomy( 'contribution', 'member', [
		'label'             => __( '貢献', 'sfwj' ),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
	] );
} );

/**
 * 作家タグ一覧に「会員」カラムを追加する
 */
add_filter( 'manage_edit-authors_columns', function( $columns ) {
	$new_columns = [];
	foreach ( $columns as $key => $label ) {
		if ( 'description' === $key ) {
			$new_columns[ 'member' ] = __( '会員', 'sfwj' );
		} else {
			$new_columns[ $key ] = $label;
		}
	}
	return $new_columns;
} );

/**
 * 作家タグ一覧に「会員」カラムの内容を追加する
 */
add_filter( 'manage_authors_custom_column', function( $td, $column_name, $term_id ) {
	if ( 'member' === $column_name ) {
		$td = sprintf( '<span style="color: lightgray">%s</span>', esc_html__( '登録なし', 'sfwj' ) );
		$member_id = get_term_meta( $term_id, TERM_META_AUTHOR_ID, true );
		if ( $member_id ) {
			$member = get_post( $member_id );
			if ( $member ) {
				$td = wp_kses_post( sprintf(
					__( '<a href="%2$s">%1$s</a>', 'sfwj' ),
					esc_html( get_the_title( $member ) ),
					esc_url( get_edit_post_link( $member_id ) )
				) );
			}
		}
	}
	return $td;
}, 10, 3 );

/**
 * 作家タグのメニューを追加する
 */
add_action( 'admin_menu', function() {
	add_submenu_page( 'edit.php?post_type=member', __( '作家タグ', 'sfwj' ), __( '作家タグ', 'sfwj' ), 'edit_posts', 'edit-tags.php?taxonomy=authors&post_type=member' );
} );
