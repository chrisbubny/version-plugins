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
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var useEntityProp = wp.coreData ? wp.coreData.useEntityProp : null;

	// Register the block
	registerBlockType('uswds-gutenberg/required-updates-block', {
		title: 'Required Updates Block',
		icon: 'calendar-alt',
		category: 'uswds-custom-blocks',
		attributes: {
			title: {
				type: 'string',
				default: 'Required Updates'
			},
			description: {
				type: 'string',
				default: 'Descriptive content here.'
			},
			deadlines: {
				type: 'array',
				default: [
					{
						id: 'd1',
						date: 'March 11, 2024',
						description: 'Developers must support the new patient access revocation requirements detailed in subparagraph (g)(10)(vi).'
					}
				]
			}
		},

		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			
			// Get post ID and type
			var postType = useSelect(function(select) {
				return select('core/editor').getCurrentPostType();
			}, []);
			
			var postId = useSelect(function(select) {
				return select('core/editor').getCurrentPostId();
			}, []);
			
			// Save to post meta when attributes change
			var previousAttributes = wp.element.useRef(attributes);
			
			wp.element.useEffect(function() {
				// Skip on initial render
				if (JSON.stringify(previousAttributes.current) === JSON.stringify(attributes)) {
					return;
				}
				
				// Update ref
				previousAttributes.current = attributes;
				
				// Only update meta for test_method post type
				if (postType === 'test_method' && postId) {
					// Use wp.apiRequest to update post meta
					wp.apiRequest({
						path: '/wp/v2/test_method/' + postId,
						method: 'POST',
						data: {
							meta: {
								_required_updates_content: attributes
							}
						}
					}).catch(function(error) {
						console.error('Error saving meta:', error);
					});
				}
			}, [attributes, postId, postType]);

			// Function to add a new deadline item
			function addDeadline() {
				var newDeadlines = [...attributes.deadlines, {
					id: 'd' + (attributes.deadlines.length + 1),
					date: '',
					description: ''
				}];
				setAttributes({ deadlines: newDeadlines });
			}

			// Function to remove a deadline item
			function removeDeadline(index) {
				var newDeadlines = [...attributes.deadlines];
				newDeadlines.splice(index, 1);
				setAttributes({ deadlines: newDeadlines });
			}

			// Function to update a deadline's properties
			function updateDeadline(index, property, value) {
				var newDeadlines = [...attributes.deadlines];
				newDeadlines[index][property] = value;
				setAttributes({ deadlines: newDeadlines });
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
					el('div', { className: 'required-updates-block' },
						el('h3', null, 'Required Updates Block'),
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
						el('h4', null, 'Deadlines:'),
						el('div', { className: 'deadlines-container' },
							attributes.deadlines.map(function(deadline, index) {
								return el('div', { key: deadline.id, className: 'deadline-item' },
									el('div', { className: 'deadline-date' },
										el(TextControl, {
											label: 'Date',
											value: deadline.date,
											onChange: function(value) {
												updateDeadline(index, 'date', value);
											}
										})
									),
									el('div', { className: 'deadline-description' },
										el(RichText, {
											tagName: 'p',
											value: deadline.description,
											onChange: function(value) {
												updateDeadline(index, 'description', value);
											},
											placeholder: 'Enter actions to be taken'
										})
									),
									el(Button, {
										isDestructive: true,
										variant: 'secondary',
										onClick: function() {
											removeDeadline(index);
										}
									}, 'Remove')
								);
							})
						),
						el(Button, {
							variant: 'primary',
							onClick: addDeadline
						}, '+ Add Deadline')
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