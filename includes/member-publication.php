<?php
/**
 * 新刊案内関連の処理
 *
 */

/**
 * 新刊案内は署名＋著者名にする
 *
 * @see https://github.com/sfwj-admin/sfwj-web/issues/31
 * @param string $title   元のタイトル
 * @param int    $post_id 投稿ID
 * @return string
 */
add_filter( 'the_title', function( $title, $post_id ) {
	if ( 'members-publication' !== get_post_type( $post_id ) ) {
		return $title;
	}
	if ( ! str_contains( $title, '『' ) ) {
		$title = '『' . $title . '』';
	}
	$authors = get_the_terms( $post_id, 'authors' );
	if ( ! $authors || is_wp_error( $authors ) ) {
		return $title;
	}
	return $title . ' （' . implode( '・', array_map( function( WP_Term $author ) {
		return $author->name;
	}, $authors ) ) . '）';
}, 10, 2 );
