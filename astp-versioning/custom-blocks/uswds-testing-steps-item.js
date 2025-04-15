(function ({ blocks, element, blockEditor, components, data }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InnerBlocks, BlockControls } = blockEditor;
    const { ToolbarGroup, ToolbarButton, PanelBody, TextControl, DatePicker } = components;
    const { useSelect } = data;
    const { InspectorControls } = blockEditor;
    
    registerBlockType('uswds-gutenberg/testing-steps-item', {
        title: 'Testing Steps Item',
        icon: 'excerpt-view',
        category: 'astp-tp',
        parent: ['uswds-gutenberg/testing-steps-block'],
        supports: {
            html: false,
            reusable: false,
            multiple: true
        },
        attributes: {
            heading: { type: 'string', default: 'New Test Step Item' },
            version: { type: 'string', default: 'Paragraph (g)(10)(iv)' },
            expiryDate: { type: 'string', default: '' }
        },
        
        edit: function({ attributes, setAttributes, clientId }) {
            const blockProps = useBlockProps({
                className: 'testing-steps-accordion-item',
                'data-section-id': clientId,
            });
            
            // Add date classes if applicable
            if (attributes.expiryDate) {
                if (attributes.expiryDate === '2025-12-31') {
                    blockProps.className += ' expires-2025-12-31';
                } else if (attributes.expiryDate === '2026-01-01') {
                    blockProps.className += ' required-2026-01-01';
                }
            }
            
            const { heading, version, expiryDate } = attributes;
            
            // Create sanitized version for filtering
            const versionSlug = version.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-');
            
            // Allow any content blocks for main content
            const ALLOWED_BLOCKS = [
                'core/paragraph', 
                'core/heading', 
                'core/list', 
                'core/table', 
                'core/image',
                'core/buttons',
                'core/button',
                'core/columns',
                'core/column',
                'uswds-gutenberg/testing-steps-section'
            ];
            
            // Function to remove this block
            const removeBlock = () => {
                const { removeBlock } = wp.data.dispatch('core/block-editor');
                removeBlock(clientId);
            };
            
            // Format date for display
            const formatDateForDisplay = (dateString) => {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            };
            
            // Generate section ID for accordion
            const sectionId = 'section-' + heading.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]+/g, '') + '-' + clientId.slice(0, 6);
            
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
                // Inspector Controls
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Date Settings', initialOpen: true },
                        el('div', { className: 'date-settings-panel' },
                            el('p', {}, 'Standards Expiry Date:'),
                            el('div', { className: 'date-picker-wrapper' },
                                el(TextControl, {
                                    type: 'date',
                                    value: expiryDate,
                                    onChange: (value) => setAttributes({ expiryDate: value }),
                                    help: 'Set to 2025-12-31 for "Expires on Dec 31, 2025" or 2026-01-01 for "Required by Jan 1, 2026"'
                                })
                            ),
                            expiryDate && el('p', { className: 'date-preview' }, 
                                `Display as: "${expiryDate === '2025-12-31' ? 'Expires on December 31, 2025' : 
                                            (expiryDate === '2026-01-01' ? 'Required by January 1, 2026' : 
                                            `Expires on ${formatDateForDisplay(expiryDate)}`)}"`)
                        )
                    )
                ),
                el('div', blockProps,
                    el('div', { className: 'accordion-header-wrapper' },
                        el('h4', { className: 'accordion-heading', 'data-section-id': sectionId, 'id': 'heading-' + sectionId },
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
                    // Main content area
                    el('div', { className: 'section-content', id: sectionId },
                        // Content section (main inner blocks area)
                        el('div', { className: 'content' },
                            el(InnerBlocks, {
                                allowedBlocks: ALLOWED_BLOCKS,
                                templateLock: false,
                                renderAppender: InnerBlocks.ButtonBlockAppender
                            })
                        )
                    )
                )
            );
        },
        
        save: function({ attributes }) {
            const { heading, version, expiryDate } = attributes;
            
            // Apply date classes
            let dateClass = '';
            if (expiryDate === '2025-12-31') {
                dateClass = 'expires-2025-12-31';
            } else if (expiryDate === '2026-01-01') {
                dateClass = 'required-2026-01-01';
            }
            
            // Generate section ID for accordion
            const sectionId = 'section-' + heading.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]+/g, '');
            
            // Create sanitized version for filtering
            const versionSlug = version.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-');
            
            // Save with proper structure for frontend rendering
            return el('div', { 
                className: `testing-steps-accordion-item ${dateClass}`,
                'data-section-id': sectionId,
                'data-version-slug': versionSlug,
                'data-expiry-date': expiryDate
            },
                el('h4', { className: 'usa-accordion__heading' },
                    el('button', {
                        type: 'button',
                        className: 'usa-accordion__button',
                        'aria-expanded': 'false',
                        'aria-controls': sectionId,
                        id: 'accordion-' + sectionId,
                        'data-version': version,
                        'data-version-slug': versionSlug
                    },
                        el('div', { className: 'code' }, version),
                        heading
                    )
                ),
                el('div', { 
                    id: sectionId, 
                    className: 'usa-accordion__content usa-prose accordion-content',
                    hidden: true
                },
                    el('div', { className: 'content' },
                        el(InnerBlocks.Content)
                    )
                )
            );
        }
    });
})(window.wp);