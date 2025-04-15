/**
 * Section Block for the Dependencies Block
 * 
 * Provides rich content capabilities including lists, paragraphs, and button groups
 */
(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, RichText, useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    
    // Register Section Block
    registerBlockType('uswds-gutenberg/section-block', {
        title: 'Dependencies Section',
        icon: 'editor-ul',
        category: 'uswds-custom-blocks',
        parent: ['uswds-gutenberg/dependencies-block'], // Match the parent block's name
        
        attributes: {
            heading: {
                type: 'string',
                default: 'Section Heading'
            },
            versionCode: {
                type: 'string',
                default: ''
            },
            showInPrivacySecurity: {
                type: 'boolean',
                default: false
            }
        },
        
        // Support options
        supports: {
            html: false,
            reusable: false,
            anchor: true,
        },
        
        // Edit function
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { heading, versionCode, showInPrivacySecurity } = attributes;
            const blockProps = useBlockProps({
                className: 'content-block' + (showInPrivacySecurity ? ' privacy-security-content' : '')
            });
            
            // Template for content - include all allowed blocks
            const CONTENT_TEMPLATE = [
                ['core/paragraph', { placeholder: 'Enter section content...' }]
            ];
            
            return el(Fragment, null,
                // Inspector controls
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Section Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Version Code',
                            value: versionCode,
                            onChange: (value) => setAttributes({ versionCode: value }),
                            placeholder: 'e.g., 170.550(h)'
                        }),
                        el(ToggleControl, {
                            label: 'Display in Privacy & Security Panel',
                            help: showInPrivacySecurity ? 
                                'This section will be shown in the Privacy & Security slideout panel.' : 
                                'This section will only be shown in the Dependencies tab.',
                            checked: showInPrivacySecurity,
                            onChange: (value) => setAttributes({ showInPrivacySecurity: value })
                        })
                    )
                ),
                
                // Main block edit UI
                el('div', blockProps,
                    // Version code
                    el(RichText, {
                        tagName: 'div',
                        value: versionCode,
                        onChange: (value) => setAttributes({ versionCode: value }),
                        className: 'code',
                        placeholder: 'Version code (e.g., 170.550(h))'
                    }),
                    
                    // Section heading
                    el(RichText, {
                        tagName: 'h3',
                        value: heading,
                        onChange: (value) => setAttributes({ heading: value }),
                        className: 'section-heading',
                        placeholder: 'Section heading'
                    }),

                    // Privacy & Security indicator (visual cue in editor)
                    showInPrivacySecurity && el('div', { 
                        className: 'privacy-security-indicator',
                        style: {
                            backgroundColor: '#e7f4e4',
                            padding: '8px 12px',
                            marginBottom: '15px',
                            borderLeft: '4px solid #2e8540',
                            fontSize: '14px'
                        }
                    }, 'This section will appear in the Privacy & Security slideout panel'),
                    
                    // Section content - support for all blocks including Approach blocks
                    el('div', { className: 'section-content' },
                        el(InnerBlocks, {
                            template: CONTENT_TEMPLATE,
                            allowedBlocks: [
                                'core/paragraph', 
                                'core/list', 
                                'core/heading',
                                'core/image',
                                'uswds-gutenberg/uswds-button',
                                'uswds-gutenberg/uswds-button-group',
                                'uswds-gutenberg/approach-block' // Allow approach blocks directly in the content
                            ],
                            templateLock: false
                        })
                    )
                )
            );
        },
        
        // Save function
        save: function(props) {
            const { attributes } = props;
            const { heading, versionCode, showInPrivacySecurity } = attributes;
            const blockProps = useBlockProps.save({
                className: 'content-block' + (showInPrivacySecurity ? ' privacy-security-content' : '')
            });
            
            // Create ID from heading for navigation
            const headingId = heading.toLowerCase().replace(/\s+/g, '-');
            
            return el('div', blockProps,
                // Version code
                versionCode && el('div', { className: 'code' }, versionCode),
                
                // Section heading with ID for navigation
                el('h3', {
                    id: headingId,
                    'data-privacy-security': showInPrivacySecurity ? 'true' : 'false'
                }, heading),
                
                // Section content - includes everything
                el('div', { className: 'section-content' },
                    el(InnerBlocks.Content)
                )
            );
        }
    });
})(window.wp);