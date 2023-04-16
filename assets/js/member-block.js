/*!
 * SFWJの会員一覧ブロック
 *
 * @handle sfwj-member-block
 * @deps wp-blocks, wp-i18n, wp-components, wp-block-editor, wp-server-side-render
 */


/* global SfwjMemberStatus:false */

const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, ToggleControl } = wp.components;
const { serverSideRender: ServerSideRender } = wp;

registerBlockType( 'sfwj/members', {

	title: __( '会員一覧ブロック', 'sfwj' ),

	icon: 'user',

	category: 'embed',

	description: __( '会員の一覧を表示します。', 'sfwj' ),

	attributes: {
		status: {
			type: 'string',
			default: '',
		},
		link: {
			type: 'boolean',
			default: true,
		},
		grouping: {
			type: 'boolean',
			default: true,
		},
	},

	edit( { attributes, setAttributes } ) {
		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( '会員ブロック', 'sfwj' ) }>
						<SelectControl value={ attributes.status }
							label={ __( '表示する会員種別', 'sfwj' ) }
							options={ SfwjMemberStatus }
							onChange={ ( status ) => setAttributes( { status } ) } />
						<ToggleControl label={ __( '50音でわける', 'sfwj' ) }
							onChange={ ( grouping ) => setAttributes( { grouping } ) }
							checked={ attributes.grouping} />
						<ToggleControl label={ __( 'リンクする', 'sfwj' ) }
							onChange={ ( link ) => setAttributes( { link } ) }
							checked={ attributes.link } />
					</PanelBody>
				</InspectorControls>
				<div className="sfwj-member-block">
					<ServerSideRender
						block="sfwj/members"
						attributes={ attributes } />
				</div>
			</>
		);
	},

	save() {
		return null;
	},
} );
