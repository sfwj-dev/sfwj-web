<?php
/**
 * 記事一覧に旧サイトへのリンクを表示する
 *
 */
add_action('lightning_loop_after', function() {
	if ( is_category() ) {
		$term     = get_queried_object();
		$cat_slug = $term->slug;
	} elseif ( is_post_type_archive() ) {
		$cat_slug = get_query_var( 'post_type' );
		if ( is_array( $cat_slug ) ) {
			$cat_slug = reset( $cat_slug );
		}
	}
	if ( empty( $cat_slug ) ) {
		return;
	}
	switch ( $cat_slug ) {
		case 'news':
			$link_url  = '/news/';
			$link_text = '過去のニュース';
			break;
		case 'japan-sf-grand-prize':
			$link_url  = '/awards/';
			$link_text = '過去の日本SF大賞';
			break;
		case 'members-publication':
			$link_url  = '/books/';
			$link_text = '過去の新刊案内';
			break;
		case 'events':
			$link_url  = '/events/';
			$link_text = '過去のイベント案内';
			break;
		default:
		return;
	}
	echo '<a class="btn btn-secondary d-block mt-5 mx-auto" href="' . $link_url . '">' . $link_text . '</a>';
}, 10, 3);
