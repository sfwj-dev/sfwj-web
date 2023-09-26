/*!
 * SF大賞ノミネートブロック
 *
 * @handle sfwj-nominees
 * @deps wp-blocks, wp-i18n, wp-block-editor, wp-components, wp-server-side-render
 */
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;
const { serverSideRender: ServerSideRender } = wp;

/* global SwfNomineesVars:false */

registerBlockType( 'sfwj/nominees', {

	title: __( 'SF大賞ノミネート作品', 'sfwj' ),

	icon: 'awards',

	category: 'embed',

	keywords: [ 'sf' ],

	attributes: SwfNomineesVars.attributes,

	description: __( 'スプレッドシートを元にノミネート作一覧を出力します。', 'sfwj' ),

	edit( { attributes, setAttributes } ) {
		const SpreadSheetUrl = () => {
			return (
				<TextControl label={ __( 'スプレッドシートのURL', 'sfwj' ) } value={ attributes.spreadsheet } onChange={ spreadsheet => setAttributes( { spreadsheet } ) } />
			);
		};
		return (
			<>
				<InspectorControls>
					<PanelBody defaultOpen={ true } title={ __( 'SFブロック設定', 'taro-taxonomy-blocks' ) } >
						<SpreadSheetUrl />
						<TextControl type="datetime-local" label={ __( '公開予定日', 'sfwj' ) } value={ attributes.published_at } onChange={ published_at => setAttributes( { published_at } ) }
							help={ __( 'ページだけを先に公開しておき、ブロックを特定の日以降に公開したい場合は日時を指定してください。', 'sfwj' ) }/>
					</PanelBody>
				</InspectorControls>

				{ ( attributes.spreadsheet.length < 1 ) ? (
					<div style={ { margin: "40px auto" } }>
						<p>{ __( 'スプレッドシートのURLを入力してください.', 'sfwj' ) }</p>
						<SpreadSheetUrl />
					</div>
				) : (
					<div className="sfwj-nominees-in-editor">
						<ServerSideRender block="sfwj/nominees" attributes={ attributes } />
					</div>
				) }
			</>
		);
	},

	save() {
		return null;
	},
} );

