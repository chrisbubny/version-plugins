(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InnerBlocks = wp.blockEditor.InnerBlocks;
    var BlockControls = wp.blockEditor.BlockControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var Toolbar = wp.components.Toolbar;
    var ToolbarButton = wp.components.ToolbarButton;
    
    // Utility to create a slug from text
    function slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-') // spaces to dashes
            .replace(/[^\w\-]+/g, '') // remove non-word chars
            .replace(/\-\-+/g, '-') // collapse dashes
            .replace(/^-+|-+$/g, ''); // trim
    }
    
    registerBlockType('uswds-gutenberg/regulatory-section', {
        apiVersion: 2,
        title: 'Regulatory Section',
        icon: 'excerpt-view',
        category: 'astp-ccg',
        parent: ['uswds-gutenberg/regulatory-block'],
        supports: { 
            reusable: false,
            html: false 
        },
        attributes: {
            sectionId: { 
                type: 'string', 
                default: '' 
            },
            // Button attributes
            hasButton: {
                type: 'boolean',
                default: false
            },
            buttonText: {
                type: 'string',
                default: 'Learn More'
            },
            buttonUrl: {
                type: 'string',
                default: '#'
            },
            buttonStyle: {
                type: 'string',
                default: 'usa-button'
            },
            buttonBig: {
                type: 'boolean',
                default: false
            },
            buttonUnstyled: {
                type: 'boolean',
                default: false
            },
            buttonDisabled: {
                type: 'boolean',
                default: false
            },
            buttonOpensInNewTab: {
                type: 'boolean',
                default: false
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var clientId = props.clientId;
            var blockProps = useBlockProps();
            
            // Set a default section ID if not already set
            if (!attributes.sectionId) {
                setAttributes({ sectionId: 'section-' + clientId.slice(0, 8) });
            }
            
            // Compute button class names
            function getButtonClassNames() {
                var classNames = [attributes.buttonStyle];
                if (attributes.buttonDisabled) classNames.push('usa-button--disabled');
                if (attributes.buttonBig) classNames.push('usa-button--big');
                if (attributes.buttonUnstyled) classNames.push('usa-button--unstyled');
                return classNames.join(' ');
            }
            
            // Define which blocks can be inserted
            var ALLOWED_BLOCKS = [
                'core/heading',
                'core/paragraph', 
                'core/list',
                'core/table',
                'uswds-gutenberg/uswds-button' // Add back USWDS button block
            ];
            
            // Template with a single heading
            var TEMPLATE = [
                ['core/heading', { 
                    level: 3, 
                    content: 'New Heading',
                    className: 'section-heading',
                    anchor: attributes.sectionId
                }]
            ];
            
            // Button preview component for the built-in button
            function ButtonPreview() {
                return el('div', { className: 'uswds-button-preview' },
                    el('a', {
                        href: attributes.buttonUrl || '#',
                        target: attributes.buttonOpensInNewTab ? '_blank' : undefined,
                        rel: attributes.buttonOpensInNewTab ? 'noopener noreferrer' : undefined,
                        className: getButtonClassNames(),
                        disabled: attributes.buttonDisabled ? true : undefined
                    }, attributes.buttonText || 'Button')
                );
            }
            
            return el(
                Fragment,
                null,
                el(
                    BlockControls,
                    null,
                    el(
                        Toolbar,
                        null,
                        el(
                            ToolbarButton,
                            {
                                icon: 'button',
                                label: attributes.hasButton ? 'Remove Button' : 'Add Button',
                                onClick: function() {
                                    setAttributes({ hasButton: !attributes.hasButton });
                                },
                                isActive: attributes.hasButton
                            }
                        )
                    )
                ),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Section Settings', initialOpen: true },
                        el('div', { className: 'heading-info' },
                            el('p', {}, 
                                'Edit the heading directly in the editor. The section heading should be the first block in this section.'
                            )
                        ),
                        el(ToggleControl, {
                            label: 'Add USWDS Button',
                            checked: attributes.hasButton,
                            onChange: function(value) {
                                setAttributes({ hasButton: value });
                            },
                            help: 'Add a built-in USWDS-styled button to this section'
                        })
                    ),
                    attributes.hasButton && el(
                        PanelBody,
                        { title: 'Button Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Button Text',
                            value: attributes.buttonText,
                            onChange: function(value) {
                                setAttributes({ buttonText: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Button URL',
                            value: attributes.buttonUrl,
                            onChange: function(value) {
                                setAttributes({ buttonUrl: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Open in New Tab',
                            checked: attributes.buttonOpensInNewTab,
                            onChange: function(value) {
                                setAttributes({ buttonOpensInNewTab: value });
                            }
                        }),
                        el(SelectControl, {
                            label: 'Button Style',
                            value: attributes.buttonStyle,
                            options: [
                                { label: 'Default', value: 'usa-button' },
                                { label: 'Secondary', value: 'usa-button usa-button--secondary' },
                                { label: 'Accent Cool', value: 'usa-button usa-button--accent-cool' },
                                { label: 'Accent Warm', value: 'usa-button usa-button--accent-warm' },
                                { label: 'Base', value: 'usa-button usa-button--base' },
                                { label: 'Outline', value: 'usa-button usa-button--outline' },
                                { label: 'Outline Inverse', value: 'usa-button usa-button--outline usa-button--inverse' }
                            ],
                            onChange: function(value) {
                                setAttributes({ buttonStyle: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Big',
                            checked: attributes.buttonBig,
                            onChange: function(value) {
                                setAttributes({ buttonBig: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Unstyled',
                            checked: attributes.buttonUnstyled,
                            onChange: function(value) {
                                setAttributes({ buttonUnstyled: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Disabled',
                            checked: attributes.buttonDisabled,
                            onChange: function(value) {
                                setAttributes({ buttonDisabled: value });
                            }
                        })
                    )
                ),
                el('div', { className: 'regulatory-section' },
                    el('div', { className: 'section-content' },
                        el(InnerBlocks, {
                            allowedBlocks: ALLOWED_BLOCKS,
                            template: TEMPLATE,
                            templateLock: false,
                            renderAppender: function() {
                                return el(InnerBlocks.ButtonBlockAppender);
                            }
                        }),
                        attributes.hasButton && el(ButtonPreview)
                    )
                )
            );
        },
        
        save: function(props) {
            var attributes = props.attributes;
            
            // Compute button class names for save
            function getButtonClassNames() {
                var classNames = [attributes.buttonStyle];
                if (attributes.buttonDisabled) classNames.push('usa-button--disabled');
                if (attributes.buttonBig) classNames.push('usa-button--big');
                if (attributes.buttonUnstyled) classNames.push('usa-button--unstyled');
                return classNames.join(' ');
            }
            
            return el('div', { className: 'regulatory-section' },
                el('div', { className: 'section-content' },
                    el(InnerBlocks.Content),
                    attributes.hasButton && 
                        el('a', {
                            href: attributes.buttonUrl || '#',
                            target: attributes.buttonOpensInNewTab ? '_blank' : undefined,
                            rel: attributes.buttonOpensInNewTab ? 'noopener noreferrer' : undefined,
                            className: getButtonClassNames(),
                        }, attributes.buttonText)
                )
            );
        }
    });
    
    // Add styles for button preview and core heading integration
    var style = document.createElement('style');
    style.innerHTML = `
        .uswds-button-preview {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .regulatory-section {
            padding: 10px;
            border: 1px solid #eee;
        }
        .regulatory-section .section-content {
            min-height: 100px;
            position: relative;
        }
        .regulatory-section .block-editor-button-block-appender {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        
        /* Style for core heading when it's the first child */
        .regulatory-section .wp-block-heading:first-child {
            background-color: #f8f8f8;
            border-left: 4px solid #005ea2;
            padding: 10px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        /* Info text styling */
        .heading-info {
            padding: 10px;
            background-color: #f8f8f8;
            border-left: 4px solid #005ea2;
            margin-bottom: 15px;
        }
        .heading-info p {
            margin: 0;
            font-style: italic;
        }
    `;
    document.head.appendChild(style);
    
})(window.wp);