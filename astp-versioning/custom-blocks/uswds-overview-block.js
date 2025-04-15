( function( blocks, i18n, element, blockEditor ) {
	const { registerBlockType } = blocks;
	const { __ } = i18n;
	const { createElement } = element;
	const { InnerBlocks } = blockEditor;

	registerBlockType( 'uswds-gutenberg/overview-block', {
		title: __( 'Overview Section', 'uswds-gutenberg' ),
		description: __( 'Wrapper for Required Updates, Privacy, etc.', 'uswds-gutenberg' ),
		icon: 'feedback',
		category: 'layout',
		supports: {
			html: false
		},
		edit: function() {
			return createElement(
				'div',
				{ className: 'overview-wrapper-block' },
				createElement( 'h2', {}, 'Overview Section' ),
				createElement( InnerBlocks, {
					allowedBlocks: [
						'uswds-gutenberg/required-updates-block',
						'uswds-gutenberg/design-performance-block',
						'uswds-gutenberg/conditions-maintenance-block'
					]
				})
			);
		},
		save: function () {
			return wp.element.createElement(
				'section',
				{ className: 'overview', id: 'overview' },
				wp.element.createElement(wp.blockEditor.InnerBlocks.Content)
			);
		}

	});
} )(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element,
	window.wp.blockEditor
);
