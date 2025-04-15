/**
 * Testing Steps Parent Block
 */
(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InspectorControls, InnerBlocks } = blockEditor;
    const { PanelBody, ToggleControl, SelectControl, Button } = components;

    registerBlockType('uswds-gutenberg/testing-steps-block', {
        title: 'Testing Steps Block',
        description: 'Wrapper block that holds one or more Test Step Accordions (Items).',
        icon: 'list-view',
        category: 'astp-tp',
        supports: {
            html: false
        },
        attributes: {
            title: { type: 'string', default: 'Testing Steps' },
            subtitle: { type: 'string', default: 'Note: The test steps are listed to reflect the order in which the tests should take place.' },
            enableSearch: { type: 'boolean', default: true },
            enableFilter: { type: 'boolean', default: true },
            enableSorting: { type: 'boolean', default: true },
            enableExpandAll: { type: 'boolean', default: true },
            defaultSortOrder: { type: 'string', default: 'default' },
            filterOptions: {
                type: 'array',
                default: [
                    { label: 'All', value: 'all' }
                ]
            }
        },

        edit: function({ attributes, setAttributes, clientId }) {
            const blockProps = useBlockProps();
            const {
                title,
                subtitle,
                enableSearch,
                enableFilter,
                enableSorting,
                enableExpandAll,
                defaultSortOrder,
                filterOptions
            } = attributes;

            // Only allow our custom testing steps item blocks as children
            const ALLOWED_BLOCKS = ['uswds-gutenberg/testing-steps-item'];
            
            // Create a template with an initial child block
            const TEMPLATE = [
                ['uswds-gutenberg/testing-steps-item', {}]
            ];
            
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Settings', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Enable Search',
                            checked: enableSearch,
                            onChange: value => setAttributes({ enableSearch: value })
                        }),
                        el(ToggleControl, {
                            label: 'Enable Filter',
                            checked: enableFilter,
                            onChange: value => setAttributes({ enableFilter: value })
                        }),
                        el(ToggleControl, {
                            label: 'Enable Sorting',
                            checked: enableSorting,
                            onChange: value => setAttributes({ enableSorting: value })
                        }),
                        el(ToggleControl, {
                            label: 'Enable Expand All',
                            checked: enableExpandAll,
                            onChange: value => setAttributes({ enableExpandAll: value })
                        })
                    ),
                    el(PanelBody, { title: 'Sorting Options', initialOpen: false },
                        el(SelectControl, {
                            label: 'Default Sort Order',
                            value: defaultSortOrder,
                            options: [
                                { label: 'Default Order', value: 'default' },
                                { label: 'A-Z', value: 'az' },
                                { label: 'Z-A', value: 'za' }
                            ],
                            onChange: value => setAttributes({ defaultSortOrder: value })
                        })
                    )
                ),
                el('div', blockProps,
                    el(RichText, {
                        tagName: 'h2',
                        value: title,
                        onChange: value => setAttributes({ title: value }),
                        placeholder: 'Enter block title...',
                        className: 'text-bold'
                    }),
                    el(RichText, {
                        tagName: 'p',
                        value: subtitle,
                        onChange: value => setAttributes({ subtitle: value }),
                        placeholder: 'Enter subtitle...',
                        className: 'text-left'
                    }),
                    el('div', { className: 'testing-steps-items-container' },
                        el(InnerBlocks, {
                            allowedBlocks: ALLOWED_BLOCKS,
                            template: TEMPLATE,
                            templateLock: false,
                            renderAppender: () => el(InnerBlocks.ButtonBlockAppender)
                        })
                    )
                )
            );
        },

        save: function() {
            return el(InnerBlocks.Content);
        }
    });
})(window.wp);