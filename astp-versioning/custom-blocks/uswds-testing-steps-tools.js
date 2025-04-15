/**
 * Testing Steps Tool Block with fixed content rendering
 */
(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InnerBlocks, BlockControls } = blockEditor;
    const { ToolbarGroup, ToolbarButton, PanelBody, TextControl, SelectControl } = components;
    const { InspectorControls } = blockEditor;
    
    registerBlockType('uswds-gutenberg/testing-steps-tool', {
        title: 'Testing Steps Tool',
        icon: 'columns',
        category: 'astp-tp',
        parent: ['uswds-gutenberg/testing-steps-section'],
        supports: {
            html: false,
            reusable: false,
            multiple: true,
            inserter: true
        },
        attributes: {
            title: { type: 'string', default: 'System Under Test' },
            variant: { type: 'string', default: 'standard' } // New attribute for variant selection
        },
        
        edit: function({ attributes, setAttributes, clientId }) {
            const blockProps = useBlockProps({
                className: 'step',
            });
            const { title, variant } = attributes;
            
            // Define allowed blocks based on variant
            let ALLOWED_BLOCKS;
            
            if (variant === 'accordion') {
                ALLOWED_BLOCKS = [
                    'uswds-gutenberg/testing-steps-accordion',
                    'core/paragraph'
                ];
            } else {
                // Standard variant
                ALLOWED_BLOCKS = [
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/table',
                    'core/image',
                    'core/buttons',
                    'core/button',
                    'core/html',
                    'core/group'
                ];
            }
            
            // Function to remove this block
            const removeBlock = () => {
                const { removeBlock } = wp.data.dispatch('core/block-editor');
                removeBlock(clientId);
            };
            
            return el(Fragment, null,
                el(BlockControls, null,
                    el(ToolbarGroup, null,
                        el(ToolbarButton, {
                            icon: 'trash',
                            title: 'Remove Testing Tool',
                            onClick: removeBlock
                        })
                    )
                ),
                
                // Inspector controls
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Tool Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Tool Title',
                            value: title,
                            onChange: (value) => setAttributes({ title: value })
                        }),
                        el(SelectControl, {
                            label: 'Layout Variant',
                            value: variant,
                            options: [
                                { label: 'Standard Content', value: 'standard' },
                                { label: 'Accordion Layout', value: 'accordion' }
                            ],
                            onChange: (value) => setAttributes({ variant: value })
                        })
                    )
                ),
                
                // Main block content
                el('div', blockProps,
                    // Tool header
                    el('div', { className: 'tool-header' },
                        el(RichText, {
                            tagName: 'h6',
                            value: title,
                            onChange: value => setAttributes({ title: value }),
                            placeholder: 'Tool Title',
                            className: 'tool-title'
                        }),
                        el('button', {
                            className: 'remove-tool-button',
                            onClick: removeBlock,
                            'aria-label': 'Remove tool'
                        }, '×')
                    ),
                    
                    // Tool content with inner blocks
                    el('div', { 
                        className: `tool-content ${variant === 'accordion' ? 'accordion-variant' : 'standard-variant'}`
                    },
                        el(InnerBlocks, {
                            allowedBlocks: ALLOWED_BLOCKS,
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        })
                    )
                )
            );
        },
        
       save: function({ attributes }) {
           // Just return inner blocks content - PHP will handle the structure
           return el(InnerBlocks.Content);
       }
    });
})(window.wp);