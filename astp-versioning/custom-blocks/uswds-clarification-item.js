/**
 * Child Clarification Item Block - With Standards References
 */
(function ({ blocks, element, blockEditor, components, data, compose }) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { RichText, useBlockProps, InnerBlocks, BlockControls } = blockEditor;
	const { ToolbarGroup, ToolbarButton, PanelBody, SelectControl, Button, TextControl } = components;
	const { useSelect } = data;
	const { InspectorControls } = blockEditor;
	
	registerBlockType('uswds-gutenberg/clarification-item', {
		title: 'Clarification Item',
		icon: 'excerpt-view',
		category: 'astp-ccg',
		parent: ['uswds-gutenberg/clarification-block'],
		supports: {
			html: false,
			reusable: false,
			multiple: true
		},
		attributes: {
			heading: { type: 'string', default: 'New Accordion Item' },
			version: { type: 'string', default: 'Paragraph (g)(10)(i)(A)' },
			standardReferences: { 
				type: 'array',
				default: [] 
			}
		},
		
		edit: function({ attributes, setAttributes, clientId }) {
			const blockProps = useBlockProps({
				className: 'clarification-accordion-item',
				'data-section-id': clientId,
				'data-section-index': '',
			});
			const { heading, version, standardReferences } = attributes;
			
			const ALLOWED_BLOCKS = ['core/paragraph', 'core/heading', 'core/list', 'core/table', 'core/image'];
			
			// Function to remove this block
			const removeBlock = () => {
				const { removeBlock } = wp.data.dispatch('core/block-editor');
				removeBlock(clientId);
			};
			
			// Get available standards from any Standards Block in the post, including nested ones
			const availableStandards = useSelect((select) => {
				const { getBlocks } = select('core/block-editor');
				const allBlocks = getBlocks();
				let standards = [];
				
				// Recursive function to search all blocks and their children
				function findStandardsInBlocks(blocks) {
					if (!blocks || !blocks.length) return;
					
					blocks.forEach(block => {
						// Check if this is a standards block
						if (block.name === 'uswds-gutenberg/standards-block') {
							if (block.attributes && block.attributes.sections) {
								standards = standards.concat(block.attributes.sections.map(section => ({
									value: section.id,
									label: `${section.standard_reference_number || section.standard_refernce_number} - ${section.standard_reference_title || section.standard_refernce_title}`,
									standard_reference_number: section.standard_reference_number || section.standard_refernce_number,
									standard_reference_title: section.standard_reference_title || section.standard_refernce_title,
									standard_reference_url: section.standard_reference_url || '', // Add URL
									external_link: section.external_link || false, // Add external link flag
									expiryDate: section.expiryDate
								})));
							}
						}
						
						// Search inner blocks recursively
						if (block.innerBlocks && block.innerBlocks.length) {
							findStandardsInBlocks(block.innerBlocks);
						}
					});
				}
				
				// Start recursive search
				findStandardsInBlocks(allBlocks);
				return standards;
			}, []);
			
			// Function to add a new standard reference
			const addStandardReference = () => {
				setAttributes({
					standardReferences: [...standardReferences, { 
						id: '', 
						standard_reference_number: '',
						standard_reference_title: '',
						standard_reference_url: '', // Add URL field
						external_link: false, // Add external link flag
						expiryDate: ''
					}]
				});
			};
			
			// Function to remove a standard reference
			const removeStandardReference = (index) => {
				const updatedReferences = [...standardReferences];
				updatedReferences.splice(index, 1);
				setAttributes({ standardReferences: updatedReferences });
			};
			
			// Function to update a standard reference
			const updateStandardReference = (index, standardId) => {
				const selectedStandard = availableStandards.find(std => std.value === standardId);
				
				if (selectedStandard) {
					const updatedReferences = [...standardReferences];
					updatedReferences[index] = {
						id: standardId,
						standard_reference_number: selectedStandard.standard_reference_number,
						standard_reference_title: selectedStandard.standard_reference_title,
						standard_reference_url: selectedStandard.standard_reference_url || '', // Include URL
						external_link: selectedStandard.external_link || false, // Include external link flag
						expiryDate: selectedStandard.expiryDate
					};
					setAttributes({ standardReferences: updatedReferences });
				}
			};
			
			// Function to manually update a standard reference when no selection is available
			const updateManualReference = (index, field, value) => {
				const updatedReferences = [...standardReferences];
				updatedReferences[index][field] = value;
				
				// If updating URL, also set external_link flag
				if (field === 'standard_reference_url') {
					updatedReferences[index].external_link = isExternalUrl(value);
				}
				
				setAttributes({ standardReferences: updatedReferences });
			};
			
			// Function to check if URL is external
			const isExternalUrl = (url) => {
				// Skip empty URLs
				if (!url) return false;
				
				try {
					// Parse the URL
					const currentHost = window.location.hostname;
					const urlObj = new URL(url);
					
					// If hostname is different, it's external
					return urlObj.hostname !== currentHost;
				} catch (e) {
					// If URL is invalid, consider it internal
					return false;
				}
			};
			
			return el(Fragment, null,
				el(BlockControls, null,
					el(ToolbarGroup, null,
						el(ToolbarButton, {
							icon: 'trash',
							title: 'Remove Item',
							onClick: removeBlock
						})
					)
				),
				// Inspector Controls for Standards References
				el(InspectorControls, null,
					el(PanelBody, { title: 'Standards References', initialOpen: true },
						el('div', { className: 'standards-references-manager' },
							standardReferences.map((ref, index) => 
								el('div', { className: 'standard-reference-item', key: index },
									el('h4', {}, `Reference #${index + 1}`),
									availableStandards.length > 0 ? 
										el(SelectControl, {
											label: 'Select Standard',
											value: ref.id,
											options: [
												{ label: 'Select a standard...', value: '' },
												...availableStandards
											],
											onChange: (value) => updateStandardReference(index, value)
										}) :
										el(Fragment, {},
											el(TextControl, {
												label: 'Standard Reference Number',
												value: ref.standard_reference_number,
												onChange: (value) => updateManualReference(index, 'standard_reference_number', value)
											}),
											el(TextControl, {
												label: 'Standard Reference Title',
												value: ref.standard_reference_title,
												onChange: (value) => updateManualReference(index, 'standard_reference_title', value)
											}),
											el(TextControl, {
												label: 'Standard Reference URL',
												value: ref.standard_reference_url || '',
												onChange: (value) => updateManualReference(index, 'standard_reference_url', value)
											}),
											el(TextControl, {
												label: 'Expiry Date (YYYY-MM-DD)',
												value: ref.expiryDate,
												onChange: (value) => updateManualReference(index, 'expiryDate', value)
											})
										),
									el(Button, {
										isDestructive: true,
										onClick: () => removeStandardReference(index)
									}, 'Remove Reference')
								)
							),
							el(Button, {
								isPrimary: true,
								onClick: addStandardReference,
								className: 'add-standard-reference-button'
							}, 'Add Standard Reference')
						)
					)
				),
				el('div', blockProps,
					el('div', { className: 'accordion-header-wrapper' },
						el('h4', { className: 'accordion-heading' },
							el(RichText, {
								tagName: 'span',
								className: 'section-version',
								value: version,
								onChange: value => setAttributes({ version: value }),
								placeholder: 'Enter paragraph/version'
							}),
							el(RichText, {
								tagName: 'span',
								className: 'section-heading',
								value: heading,
								onChange: value => setAttributes({ heading: value }),
								placeholder: 'Enter section heading'
							})
						),
						el('button', {
							className: 'remove-item-button',
							onClick: removeBlock,
							'aria-label': 'Remove item'
						}, '×')
					),
					el('div', { className: 'section-content' },
						el(InnerBlocks, {
							allowedBlocks: ALLOWED_BLOCKS,
							templateLock: false
						})
					),
					standardReferences.length > 0 && el('div', { className: 'standard-references-preview' },
						el('h5', {}, 'STANDARDS REFERENCED'),
						el('ul', { className: 'standard-references-list' },
							standardReferences.map((ref, index) => 
								el('li', { key: index },
									ref.expiryDate && el('div', { className: 'title' }, 
										ref.expiryDate === '2026-01-01' 
											? 'THIS STANDARD IS REQUIRED BY DECEMBER 31, 2025' 
											: ref.expiryDate === '2025-12-31' 
												? 'ADOPTION OF THIS STANDARD EXPIRES ON JANUARY 1, 2026'
												: `EXPIRES ON ${ref.expiryDate}`
									),
									el('div', { className: 'code' }, ref.standard_reference_number),
									el('a', { 
										href: ref.standard_reference_url || '#',
										className: ref.external_link ? 'external-link' : '' 
									}, 
										ref.standard_reference_title,
										ref.external_link && el('img', {
											src: '/wp-content/themes/healthit/assets/dist/icons/third-party-link-icon.svg',
											alt: 'External link',
											className: 'external-link-icon',
											width: 14,
											height: 14
										})
									)
								)
							)
						)
					)
				)
			);
		},
		
		save: function({ attributes }) {
			const { heading, version, standardReferences } = attributes;
			
			return el('div', { className: 'clarification-accordion-item' },
				el('h4', { className: 'accordion-heading' },
					el('span', { className: 'section-version' }, version),
					el('span', { className: 'section-heading' }, heading)
				),
				el('div', { className: 'section-content' },
					el(InnerBlocks.Content)
				),
				standardReferences.length > 0 && el('div', { className: 'standard-references' },
					el('h5', {}, 'STANDARDS REFERENCED'),
					el('ul', { className: 'standard-references-list' },
						standardReferences.map((ref, index) => 
							el('li', { key: index },
								ref.expiryDate && el('div', { className: 'title' }, 
									ref.expiryDate === '2026-01-01' 
										? 'THIS STANDARD IS REQUIRED BY DECEMBER 31, 2025' 
										: ref.expiryDate === '2025-12-31' 
											? 'ADOPTION OF THIS STANDARD EXPIRES ON JANUARY 1, 2026'
											: `EXPIRES ON ${ref.expiryDate}`
								),
								el('div', { className: 'code' }, ref.standard_reference_number),
								el('a', { 
									href: ref.standard_reference_url || '#',
									className: ref.external_link ? 'external-link' : '',
									target: ref.external_link ? '_blank' : '',
									rel: ref.external_link ? 'noopener noreferrer' : ''
								}, 
									ref.standard_reference_title,
									ref.external_link && el('img', {
										src: '/wp-content/themes/healthit/assets/dist/icons/third-party-link-icon.svg',
										alt: 'External link',
										className: 'external-link-icon',
										width: 14,
										height: 14
									})
								)
							)
						)
					)
				)
			);
		}
	});
})(window.wp);