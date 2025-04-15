(function (blocks, i18n, element, blockEditor, components) {
	const { registerBlockType } = blocks;
	const { __ } = i18n;
	const { createElement, Fragment } = element;
	const { InnerBlocks, useBlockProps, InspectorControls } = blockEditor;
	const { PanelBody, TextControl, TextareaControl, ToggleControl } = components;

	registerBlockType('uswds-gutenberg/ccg-editor-wrapper', {
		title: __('CCG Editor Wrapper', 'uswds-gutenberg'),
		icon: 'index-card',
		category: 'layout',
		attributes: {
			criteria: { type: 'string', default: '§ 170.315(g)(10)' },
			ccgTitle: { type: 'string', default: 'Certification Companion Guide' },
			tpTitle: { type: 'string', default: 'Test Procedure' },
			useConformanceMethod: { type: 'boolean', default: false },
			ccgContent: { type: 'string', default: '' },
			tpContent: { type: 'string', default: '' },
			ccgVersion: { type: 'string', default: 'V1.0' },
			tpVersion: { type: 'string', default: 'V1.0' },
			ccgIssuedDate: { type: 'string', default: '2020-02-19' },
			ccgLastUpdatedDate: { type: 'string', default: '2024-08-19' },
			tpIssuedDate: { type: 'string', default: '2020-02-19' },
			tpLastUpdatedDate: { type: 'string', default: '2024-08-19' }
		},

		edit: function ({ attributes, setAttributes }) {
			const blockProps = useBlockProps({ className: 'ccg-editor-wrapper' });

			return createElement(
				Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					// Criteria Settings
					createElement(
						PanelBody,
						{ title: __('Criteria Settings', 'uswds-gutenberg'), initialOpen: true },
						createElement(TextControl, {
							label: __('Criteria Number', 'uswds-gutenberg'),
							value: attributes.criteria,
							onChange: (value) => setAttributes({ criteria: value })
						})
					),

					// CCG Settings
					createElement(
						PanelBody,
						{ title: __('CCG Settings', 'uswds-gutenberg'), initialOpen: true },
						createElement(TextControl, {
							label: __('CCG Tab Title', 'uswds-gutenberg'),
							value: attributes.ccgTitle,
							onChange: (value) => setAttributes({ ccgTitle: value })
						}),
						createElement(TextControl, {
							label: __('CCG Version', 'uswds-gutenberg'),
							value: attributes.ccgVersion,
							onChange: (value) => setAttributes({ ccgVersion: value })
						}),
						createElement(TextControl, {
							label: __('CCG Issued Date (YYYY-MM-DD)', 'uswds-gutenberg'),
							value: attributes.ccgIssuedDate,
							onChange: (value) => setAttributes({ ccgIssuedDate: value })
						}),
						createElement(TextControl, {
							label: __('CCG Last Updated (YYYY-MM-DD)', 'uswds-gutenberg'),
							value: attributes.ccgLastUpdatedDate,
							onChange: (value) => setAttributes({ ccgLastUpdatedDate: value })
						}),
						createElement(TextareaControl, {
							label: __('CCG Content', 'uswds-gutenberg'),
							value: attributes.ccgContent,
							onChange: (value) => setAttributes({ ccgContent: value }),
							rows: 4
						})
					),

					// TP/Conformance Settings
					createElement(
						PanelBody,
						{ title: __('TP / Conformance Method Settings', 'uswds-gutenberg'), initialOpen: true },
						createElement(TextControl, {
							label: __('TP Tab Title', 'uswds-gutenberg'),
							value: attributes.tpTitle,
							onChange: (value) => setAttributes({ tpTitle: value })
						}),
						createElement(ToggleControl, {
							label: __('Use Conformance Method?', 'uswds-gutenberg'),
							checked: attributes.useConformanceMethod,
							onChange: (value) => setAttributes({ useConformanceMethod: value }),
							help: __('If enabled, second tab will be labeled "Conformance Method".', 'uswds-gutenberg')
						}),
						createElement(TextControl, {
							label: __('TP Version', 'uswds-gutenberg'),
							value: attributes.tpVersion,
							onChange: (value) => setAttributes({ tpVersion: value })
						}),
						createElement(TextControl, {
							label: __('TP Issued Date (YYYY-MM-DD)', 'uswds-gutenberg'),
							value: attributes.tpIssuedDate,
							onChange: (value) => setAttributes({ tpIssuedDate: value })
						}),
						createElement(TextControl, {
							label: __('TP Last Updated (YYYY-MM-DD)', 'uswds-gutenberg'),
							value: attributes.tpLastUpdatedDate,
							onChange: (value) => setAttributes({ tpLastUpdatedDate: value })
						}),
						createElement(TextareaControl, {
							label: __('TP Content', 'uswds-gutenberg'),
							value: attributes.tpContent,
							onChange: (value) => setAttributes({ tpContent: value }),
							rows: 4
						})
					)
				),
				createElement(
					'div',
					blockProps,
					createElement(InnerBlocks, {
						allowedBlocks: [
							'uswds-gutenberg/overview-block',
							'uswds-gutenberg/clarification-block',
							'uswds-gutenberg/regulatory-block',
							'uswds-gutenberg/standards-block',
							'uswds-gutenberg/dependencies-block',
							'uswds-gutenberg/resources-block',
							'uswds-gutenberg/testing-steps-block',
							'uswds-gutenberg/testing-tools-block',
							'uswds-gutenberg/testing-components'
						],
						template: [
							[
								'uswds-gutenberg/overview-block',
								{},
								[
									['uswds-gutenberg/required-updates-block'],
									['uswds-gutenberg/design-performance-block'],
									['uswds-gutenberg/conditions-maintenance-block']
								]
							],
							['uswds-gutenberg/clarification-block'],
							['uswds-gutenberg/regulatory-block'],
							['uswds-gutenberg/standards-block'],
							['uswds-gutenberg/dependencies-block'],
							['uswds-gutenberg/resources-block'],
							['uswds-gutenberg/testing-steps-block'],
							['uswds-gutenberg/testing-tools-block'],
							['uswds-gutenberg/testing-components']
						],
						templateLock: false
					})
				)
			);
		},

		save: function () {
			const blockProps = useBlockProps.save({ className: 'ccg-editor-wrapper' });
			return createElement('div', blockProps, createElement(InnerBlocks.Content));
		}
	});
})(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components
);
