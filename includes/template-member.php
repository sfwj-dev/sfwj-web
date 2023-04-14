<?php
/**
 * 会員情報のテンプレートに関するフック
 *
 */


/**
 * メンバーページのタイトルを変更する
 *
 * @param string $post_title 投稿タイトル
 * @param int    $post_id    投稿ID
 * @return string
 */
add_filter( 'the_title', function ( $post_title, $post_id ) {
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
	return sprintf( '<ruby>%s<rp>（</rp><rt>%s</rt><rp>）</rp></ruby>', $post_title, $yomigana );
}, 10, 2 );
