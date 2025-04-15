/**
 * Approach Block for the Dependencies Block
 * 
 * Provides rich content capabilities for approach descriptions
 */
(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, RichText, useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;

    // Register Approach Block
    registerBlockType('uswds-gutenberg/approach-block', {
        title: 'Approach Block',
        icon: 'clipboard',
        category: 'astp-ccg',
        parent: ['uswds-gutenberg/section-block'],
        
        attributes: {
            heading: {
                type: 'string',
                default: 'Approach 1'
            },
            approachVersion: {
                type: 'string',
                default: ''
            }
        },

        supports: {
            html: false,
            reusable: false,
        },

        // Edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { heading, approachVersion } = attributes;
            const blockProps = useBlockProps({
                className: 'approach grid-col-6 border padding-top-1'
            });

            const CONTENT_TEMPLATE = [
                ['core/paragraph', { placeholder: 'Enter approach description...' }]
            ];

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Approach Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Approach Version',
                            value: approachVersion,
                            onChange: (value) => setAttributes({ approachVersion: value }),
                            placeholder: 'e.g., § 170.404'
                        })
                    )
                ),

                el('div', blockProps,
                    el(RichText, {
                        tagName: 'p',
                        value: approachVersion,
                        onChange: (value) => setAttributes({ approachVersion: value }),
                        className: 'approach-version-heading margin-bottom-0',
                        placeholder: 'Approach version (e.g., § 170.404)'
                    }),

                    el(RichText, {
                        tagName: 'h5',
                        value: heading,
                        onChange: (value) => setAttributes({ heading: value }),
                        className: 'approach-heading',
                        placeholder: 'Approach heading'
                    }),

                    el('div', { className: 'approach-description' },
                        el(InnerBlocks, {
                            template: CONTENT_TEMPLATE,
                            allowedBlocks: [
                                'core/paragraph', 
                                'core/list', 
                                'core/image',
                                'uswds-gutenberg/uswds-button',
                                'uswds-gutenberg/uswds-button-group'
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
            const { heading, approachVersion } = attributes;
            const blockProps = useBlockProps.save({
                className: 'approach grid-col-6 border padding-top-1'
            });

            return el('div', blockProps,
                approachVersion && el('p', { className: 'approach-version-heading margin-bottom-0' }, approachVersion),
                el('h5', null, heading),
                el('div', { className: 'approach-description' },
                    el(InnerBlocks.Content)
                )
            );
        }
    });
})(window.wp);
