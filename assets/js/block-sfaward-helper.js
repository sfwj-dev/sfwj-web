/*!
 * SF大賞ノミネートブロックのレンダリングヘルパー
 *
 * @handle sfwj-nominees-helper
 * @deps jquery
 */

const $ = jQuery;

$( document ).ready( () => {
	$( '.sfwj-nominees-button' ).click( function( e ) {
		e.preventDefault();
		$( this ).parents( '.sfwj-nominees-item' ).toggleClass( 'detail-open' );
	} );

	$( '.sfwj-nominees-open-all' ).click( function( e ) {
		e.preventDefault();
		$( '.sfwj-nominees-item' ).toggleClass( 'detail-open' );
	} );

	let timer = null;
	$( '.sfwj-nominees-filter' ).on( 'keyup', function() {
		if ( timer ) {
			clearTimeout( timer );
		}
		const msg = $( this ).val();
		timer = setTimeout( () => {
			// 該当するやつだけ表示
			let hit = 0;
			$( '.sfwj-nominees-item' ).each( function() {
				if ( $( this ).text().indexOf( msg ) > -1 ) {
					// 検索にヒット
					$( this ).removeClass( 'd-none' );
					hit++;
				} else if ( msg.length < 1 ) {
					// そもそも文字列がない
					$( this ).removeClass( 'd-none' );
				} else {
					// 検索にヒットしなかった
					$( this ).addClass( 'd-none' );
				}
				if ( hit || msg.length < 1 ) {
					$( '.sfwj-nominees-list' ).removeClass(
						'sfwj-nominees-list-empty'
					);
				} else {
					$( '.sfwj-nominees-list' ).addClass(
						'sfwj-nominees-list-empty'
					);
				}
			} );
		}, 1000 );
	} );

	$( '#sfwj-nominees-sort' ).on( 'change', function() {
		const order = $( this ).val();
		let sorter;
		switch ( order ) {
			case 'asc':
				sorter = function( a, b ) {
					return a < b ? -1 : 1;
				};
				break;
			case 'desc':
				sorter = function( a, b ) {
					return a > b ? -1 : 1;
				};
				break;
			default:
				return false;
		}
		// 昇順降順を並び替え
		const $list = $( '.sfwj-nominees-item' );
		$list.sort( function( a, b ) {
			const aIndex = parseInt( $( a ).data( 'index' ), 10 );
			const bIndex = parseInt( $( b ).data( 'index' ), 10 );
			if ( aIndex === bIndex ) {
				return 0;
			}
			return sorter( aIndex, bIndex );
		} );
		$list.each( function( index, elem ) {
			$( '.sfwj-nominees-list' ).append( elem );
		} );
	} );
} );
