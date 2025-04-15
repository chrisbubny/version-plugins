(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var RichText = wp.blockEditor.RichText;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;
	var ToggleControl = wp.components.ToggleControl;

	// Register the block
	registerBlockType('uswds-gutenberg/design-performance-block', {
		title: 'Design and Performance Block',
		icon: 'dashboard',
		category: 'uswds-custom-blocks',
		attributes: {
			title: {
				type: 'string',
				default: 'Design and Performance'
			},
			description: {
				type: 'string',
				default: 'Descriptive content here.'
			},
			criteria: {
				type: 'array',
				default: [
					{
						id: 'c1',
						criteriaId: '(§ 170.315(g)(4))',
						title: 'Quality management system',
						description: 'When a single quality management system (QMS) is used, the QMS only needs to be identified once. Otherwise, when different QMS are used, each QMS needs to be separately identified for every capability to which it was applied.',
						buttonUrl: '#',
						buttonText: 'Learn More'
					}
				]
			}
		},

		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			// Function to add a new criteria item
			function addCriteria() {
				var newCriteria = [...attributes.criteria, {
					id: 'c' + (attributes.criteria.length + 1),
					criteriaId: '',
					title: '',
					description: '',
					buttonUrl: '#',
					buttonText: 'Learn More'
				}];
				setAttributes({ criteria: newCriteria });
			}

			// Function to remove a criteria item
			function removeCriteria(index) {
				var newCriteria = [...attributes.criteria];
				newCriteria.splice(index, 1);
				setAttributes({ criteria: newCriteria });
			}

			// Function to update a criteria's properties
			function updateCriteria(index, property, value) {
				var newCriteria = [...attributes.criteria];
				newCriteria[index][property] = value;
				setAttributes({ criteria: newCriteria });
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Block Settings', initialOpen: true },
						el(TextControl, {
							label: 'Title',
							value: attributes.title,
							onChange: function(value) {
								setAttributes({ title: value });
							}
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'design-performance-block' },
						el('h3', null, 'Design and Performance Block'),
						el(RichText, {
							tagName: 'h2',
							value: attributes.title,
							onChange: function(value) {
								setAttributes({ title: value });
							},
							placeholder: 'Enter block title'
						}),
						el(RichText, {
							tagName: 'p',
							value: attributes.description,
							onChange: function(value) {
								setAttributes({ description: value });
							},
							placeholder: 'Enter block description'
						}),
						el('h4', null, 'Additional Criteria:'),
						el('div', { className: 'criteria-container' },
							attributes.criteria.map(function(criteria, index) {
								return el('div', { key: criteria.id, className: 'criteria-item' },
									el('div', { className: 'criteria-item-header' },
										el(TextControl, {
											label: 'Criteria ID',
											value: criteria.criteriaId,
											onChange: function(value) {
												updateCriteria(index, 'criteriaId', value);
											},
											placeholder: 'e.g., (§ 170.315(g)(4))'
										}),
										el(TextControl, {
											label: 'Title',
											value: criteria.title,
											onChange: function(value) {
												updateCriteria(index, 'title', value);
											},
											placeholder: 'Enter criteria title'
										})
									),
									el('div', { className: 'criteria-button-settings' },
										el(TextControl, {
											label: 'Button Text',
											value: criteria.buttonText,
											onChange: function(value) {
												updateCriteria(index, 'buttonText', value);
											}
										}),
										el(TextControl, {
											label: 'Button URL',
											value: criteria.buttonUrl,
											onChange: function(value) {
												updateCriteria(index, 'buttonUrl', value);
											}
										})
									),
									el('div', { className: 'criteria-description' },
										el(RichText, {
											tagName: 'p',
											value: criteria.description,
											onChange: function(value) {
												updateCriteria(index, 'description', value);
											},
											placeholder: 'Enter criteria description'
										})
									),
									el(Button, {
										isDestructive: true,
										variant: 'secondary',
										onClick: function() {
											removeCriteria(index);
										}
									}, 'Remove')
								);
							})
						),
						el(Button, {
							variant: 'primary',
							onClick: addCriteria
						}, '+ Add Criteria')
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