<?php
/**
 * 会員情報のテンプレートに関するフック
 *
 */

namespace Sfwj\SfwjWeb;

use Google\Service\YouTube\Member;

/**
 * 専用のCSSを読み込む
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'sfwj-custom' );
} );

/**
 * プロフィール画像を取得する
 *
 * @param int|null|\WP_Post $post 投稿オブジェクト。
 * @return int
 */
function get_profile_picture( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	// メイン画像があれば、それを返す
	$picture = get_post_meta( $post->ID, '_profile_pic_id', true );
	if ( $picture ) {
		return $picture;
	}
	// サムネイルがあればそれを返す
	if ( has_post_thumbnail( $post ) ) {
		return get_post_thumbnail_id( $post );
	}
	return 0;
}

/**
 * 作者の代表作を一覧で取得
 *
 * @param null|int|\WP_Post $post 投稿
 * @return \WP_Post[]
 */
function get_authors_works( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return [];
	}
	$term = get_author_term( $post->ID );
	if ( ! $term ) {
		return [];
	}
	$query = new \WP_Query( [
		'post_type'      => MemberWorks::POST_TYPE,
		'posts_per_page' => 10,
		'post_status'    => 'publish',
		'tax_query'      => [
			[
				'taxonomy' => 'authors',
				'field'    => 'id',
				'terms'    => $term->term_id,
			],
		],
		'orderby'        => [
			'menu_order' => 'DESC',
			'date'       => 'DESC',
		],
	] );
	return $query->posts;
}

/**
 * メンバーページのタイトルに読み仮名を追加する
 *
 * @param string $post_title 投稿タイトル
 * @param int    $post_id    投稿ID
 * @return string
 */
\add_filter( 'the_title', function ( $post_title, $post_id ) {
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_admin() || 'member' !== get_post_type( $post_id ) ) {
		return $post_title;
	}
	if ( ! in_the_loop() ) {
		return $post_title;
	}
	$yomigana = get_post_meta( $post_id, '_yomigana', true );
	if ( ! $yomigana ) {
		return $post_title;
	}
	$post_title = sprintf( '<ruby>%s<rp>（</rp><rt>%s</rt><rp>）</rp></ruby>', $post_title, $yomigana );
	$name_en    = get_post_meta( $post_id, 'name_en', true );
	if ( $name_en ) {
		$post_title .= sprintf( '<small class="sfwj-profile-name-en">%s</small>', esc_html( $name_en ) );
	}
	return $post_title;
}, 10, 2 );

/**
 * 投稿本文の最初にアイキャッチを追加する
 */
\add_action( 'lightning_content_before', function() {
	if ( ! is_singular( 'member' ) ) {
		return '';
	}
	// アイキャッチを表示
	$thumbnail_id = get_profile_picture();
	if ( $thumbnail_id ) {
		?>
		<figure class="sfwj-profile-picture">
			<?php
			echo wp_get_attachment_image( $thumbnail_id, 'large', false, [
				'class' => 'sfwj-profile-img',
				'alt'   => esc_attr( get_post()->post_title ),
			] );
			?>
		</figure>
		<?php
	}
}, 9999 );

/**
 * 投稿本文に英語プロフィールを追加する
 */
\add_filter( 'the_content', function( $content ) {
	if ( 'member' !== get_post_type() ) {
		return $content;
	}
	ob_start();
	// 英文プロフィールを表示
	$profile_en = get_post_meta( get_the_ID(), 'desc_en', true );
	if ( $profile_en ) {
		?>
		<div class="sfwj-bio">
			<hr />
			<?php echo wp_kses_post( wpautop( $profile_en ) ); ?>
		</div>
		<?php
	}
	// その他の言語でのプロフィール
	$profile_other = get_post_meta( get_the_ID(), 'desc_misc', true );
	if ( $profile_other ) {
		?>
		<div class="sfwj-bio">
			<hr />
			<?php echo wp_kses_post( wpautop( $profile_other ) ); ?>
		</div>
		<?php
	}
	// リンクがあれば表示
	$links = get_post_meta( get_the_ID(), '_external_url', true );
	if ( ! empty( $links ) ) {
		?>
		<h2><?php esc_html_e( 'Webサイト・SNS', 'sfwj' ); ?></h2>
		<ul class="sfwj-profile-links">
			<?php foreach ( explode( "\n", $links ) as $link ) : ?>
				<li>
					<?php echo wp_kses_post( sfwj_linkify( $link ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
	// 代表作があれば追加
	$works = get_authors_works();
	if ( ! empty( $works ) ) {
		?>
		<h2><?php esc_html_e( '代表作', 'sfwj' ); ?></h2>
		<ul class="sfwj-member-work-list">
			<?php
			foreach ( $works as $work ) :
				$url   = MemberWorks::get_link( $work );
				$image = MemberWorks::get_cover( $work );
				?>
				<li class="sfwj-member-work-item">
					<?php
					ob_start();
					?>
					<a class="sfwj-member-work-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php
						if ( $image ) {
							echo sprintf( '<span class="sfwj-member-work-cover-frame">%s</span>', $image );
						} else {
							echo '<span class="sfwj-member-work-noimage"></span>';
						}
						?>
						<span class="sfwj-member-work-title">
							<?php echo esc_html( get_the_title( $work ) ); ?>
						</span>
						<?php
						$publishers = get_the_terms( $work, 'publisher' );
						if ( $publishers && ! is_wp_error( $publishers ) ) :
							?>
							<span class="sfwj-member-work-publisher">
								<?php
								echo esc_html( implode( ', ', array_map( function( $publisher ) {
									return $publisher->name;
								}, $publishers ) ) );
								?>
							</span>
						<?php endif; ?>
					</a>
					<?php
					// 余計な改行を削除するためにバッファを追加
					$link_content = ob_get_contents();
					ob_end_clean();
					echo preg_replace( '/(\t|\n)+/um', '', $link_content );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
	// 新刊案内が存在していれば表示
	$query = get_author_news( 3 );
	if ( $query && $query->have_posts() ) {
		?>
		<h2><?php printf( esc_html__( '%sの新刊案内', 'sfwj' ), esc_html( get_post()->post_title ) ); ?></h2>
		<ul class="sfwj-profile-links">
		<?php foreach ( $query->posts as $news ) : ?>
			<li>
				<a href="<?php echo get_permalink( $news ); ?>"><?php echo esc_html( $news->post_title ); ?>（<?php echo mysql2date( get_option( 'date_format' ), $news->post_date ); ?>）</a>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php if ( $query->found_posts > 3 ) : ?>
			<div class="wp-block-buttons is-content-justification-center is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_term_link( get_author_term( get_the_ID() ) ) ); ?>"><?php esc_html_e( 'すべての新刊案内', 'sfwj' ); ?></a></div>
			</div>
		<?php endif; ?>
		<?php
	}
	// バッファを追加
	$content .= ob_get_contents();
	ob_end_clean();
	return $content;
}, 1 );

/**
 * 新刊情報に会員が紐づいていたら、一覧を表示する
 */
add_action( 'lightning_content_after', function() {
	if ( ! is_singular( 'members-publication' ) ) {
		return;
	}
	$members = get_tagged_members();
	if ( empty( $members ) ) {
		return;
	}
	?>
	<div class="sfwj-member-card-wrap">
		<h2 class="sfwj-member-card-title"><?php esc_html_e( '会員情報', 'sfwj' ); ?></h2>
		<div class="sfwj-member-card-list">
			<?php foreach ( $members as $member ) : ?>
				<div class="sfwj-member-card">
					<?php
					$thumbnail_id = get_profile_picture( $member );
					if ( $thumbnail_id ) {
						?>
						<figure class="sfwj-member-card-picture">
							<?php
							echo wp_get_attachment_image( $thumbnail_id, 'thumbnail', false, [
								'class' => 'sfwj-member-card-img',
								'alt'   => esc_attr( ( $member->post_title ) ),
							] );
							?>
						</figure>
						<?php
					} else {
						echo '<div class="no-photo"></div>';
					}
					?>
					<div class="sfwj-member-card-content">
						<h3 class="sfwj-member-card-name">
							<a href="<?php echo esc_url( get_permalink( $member ) ); ?>">
								<?php echo wp_kses_post( get_the_title( $member ) ); ?>
							</a>
						</h3>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
} );
