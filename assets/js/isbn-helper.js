/*!
 * ISBNから書籍情報を入力できるようにする。
 *
 * @handle sfwj-isbn-helper
 * @deps wp-api-fetch, wp-i18n, jquery
 */

const $ = jQuery;
const { __, sprintf } = wp.i18n;
const { apiFetch } = wp;

$( document ).ready( function() {
	$( 'button[data-book-id]' ).click( function( e ) {
		e.preventDefault();
		const isbn = $( 'input[name="_isbn"]' ).val();
		const id = $( this ).data( 'book-id' );
		// translators: %s is ISBN.
		const msg = sprintf( __( 'ISBN: %s の情報を元に投稿を更新します。この画面で保存していない内容は破棄されますが、よろしいですか？', 'sfwj' ), isbn );
		if ( window.confirm( msg ) ) {
			apiFetch( {
				path: '/sfwj/v1/isbn/' + id + '/',
				method: 'post',
				data: {
					isbn,
				},
			} )
				.then( ( res ) => {
					alert( res.message );
					window.location.href = res.url;
				} )
				.catch( ( res ) => {
					alert( res.message );
				} );
		}
	} );
} );
