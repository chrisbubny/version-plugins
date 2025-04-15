/**
 * Testing Steps Section Block
 * Provides a testing steps section with heading, description, and tools
 */
(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InnerBlocks, BlockControls } = blockEditor;
    const { ToolbarGroup, ToolbarButton, PanelBody, TextControl } = components;
    const { InspectorControls } = blockEditor;
    
    registerBlockType('uswds-gutenberg/testing-steps-section', {
        title: 'Testing Steps Section',
        icon: 'table-row-after',
        category: 'astp-tp',
        parent: ['uswds-gutenberg/testing-steps-item'],
        supports: {
            html: false,
            reusable: false,
            multiple: true,
            inserter: true
        },
        attributes: {
            heading: { type: 'string', default: 'Testing Steps' },
            description: { type: 'string', default: 'Certification option: Applies to all applicable base regulatory and SVAP standards' }
        },
        
        edit: function({ attributes, setAttributes, clientId }) {
            const blockProps = useBlockProps({
                className: 'testing-steps',
            });
            const { heading, description } = attributes;
            
            // Allow content blocks for the testing steps section
            const ALLOWED_BLOCKS = [
                'core/paragraph',
                'core/heading',
                'core/list',
                'uswds-gutenberg/testing-steps-tool'
            ];
            
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
                            title: 'Remove Section',
                            onClick: removeBlock
                        })
                    )
                ),
                
                // Inspector controls
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Section Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Section Heading',
                            value: heading,
                            onChange: (value) => setAttributes({ heading: value })
                        }),
                        el(TextControl, {
                            label: 'Section Description',
                            value: description,
                            onChange: (value) => setAttributes({ description: value })
                        })
                    )
                ),
                
                // Main block content
                el('div', blockProps,
                    // Section header
                    el(RichText, {
                        tagName: 'h5',
                        value: heading,
                        onChange: value => setAttributes({ heading: value }),
                        placeholder: 'Testing Steps',
                        className: 'section-heading'
                    }),
                    el(RichText, {
                        tagName: 'p',
                        value: description,
                        onChange: value => setAttributes({ description: value }),
                        placeholder: 'Enter description...',
                        className: 'section-description'
                    }),
                    
                    // Testing steps tools area
                    el('div', { className: 'testing-steps__steps' },
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
            const { heading, description } = attributes;
            
            // Save with proper structure for frontend rendering
            return el('div', { className: 'testing-steps' },
                el('h5', {}, heading),
                el('p', {}, description),
                el('div', { className: 'testing-steps__steps' },
                    el(InnerBlocks.Content)
                )
            );
        }
    });
})(window.wp);