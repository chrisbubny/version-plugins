(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var RichText = wp.blockEditor.RichText;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;

	// Register the block
	registerBlockType('uswds-gutenberg/conditions-maintenance-block', {
		title: 'Conditions and Maintenance Block',
		icon: 'admin-settings',
		category: 'astp-ccg',
		attributes: {
			baseEHRDefinition: {
				type: 'string',
				default: 'Included'
			},
			realWorldTesting: {
				type: 'string',
				default: 'Yes'
			},
			insightsCondition: {
				type: 'string',
				default: 'Yes'
			},
			svapVersions: {
				type: 'string',
				default: 'Yes'
			}
		},

		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			// Options for select controls
			var optionsYesNo = [
				{ label: 'Yes', value: 'Yes' },
				{ label: 'No', value: 'No' }
			];

			var optionsBaseEHR = [
				{ label: 'Included', value: 'Included' },
				{ label: 'Not Included', value: 'Not Included' },
				{ label: 'Not Applicable', value: 'Not Applicable' }
			];

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Conditions and Maintenance Settings', initialOpen: true },
						el(SelectControl, {
							label: 'Base EHR Definition',
							value: attributes.baseEHRDefinition,
							options: optionsBaseEHR,
							onChange: function(value) {
								setAttributes({ baseEHRDefinition: value });
							}
						}),
						el(SelectControl, {
							label: 'Real World Testing',
							value: attributes.realWorldTesting,
							options: optionsYesNo,
							onChange: function(value) {
								setAttributes({ realWorldTesting: value });
							}
						}),
						el(SelectControl, {
							label: 'Insights Condition',
							value: attributes.insightsCondition,
							options: optionsYesNo,
							onChange: function(value) {
								setAttributes({ insightsCondition: value });
							}
						}),
						el(SelectControl, {
							label: 'SVAP Versions',
							value: attributes.svapVersions,
							options: optionsYesNo,
							onChange: function(value) {
								setAttributes({ svapVersions: value });
							}
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'conditions-maintenance-block' },
						el('h3', null, 'Conditions and Maintenance Block'),
						el('div', { className: 'conditions-fields' },
							el('div', { className: 'condition-field' },
								el('label', null, 'Base EHR Definition'),
								el(SelectControl, {
									value: attributes.baseEHRDefinition,
									options: optionsBaseEHR,
									onChange: function(value) {
										setAttributes({ baseEHRDefinition: value });
									}
								})
							),
							el('div', { className: 'condition-field' },
								el('label', null, 'Real World Testing'),
								el(SelectControl, {
									value: attributes.realWorldTesting,
									options: optionsYesNo,
									onChange: function(value) {
										setAttributes({ realWorldTesting: value });
									}
								})
							),
							el('div', { className: 'condition-field' },
								el('label', null, 'Insights Condition'),
								el(SelectControl, {
									value: attributes.insightsCondition,
									options: optionsYesNo,
									onChange: function(value) {
										setAttributes({ insightsCondition: value });
									}
								})
							),
							el('div', { className: 'condition-field' },
								el('label', null, 'SVAP Versions'),
								el(SelectControl, {
									value: attributes.svapVersions,
									options: optionsYesNo,
									onChange: function(value) {
										setAttributes({ svapVersions: value });
									}
								})
							)
						)
					)
				)
			);
		},

		save: function() {
			// Return null as we'll use a PHP render callback
			return null;
		}
	});
})(window.wp);