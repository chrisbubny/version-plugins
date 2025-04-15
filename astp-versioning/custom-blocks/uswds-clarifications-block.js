/**
 * Parent Clarification Block
 */
(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InspectorControls, InnerBlocks } = blockEditor;
    const { PanelBody, ToggleControl, SelectControl, Button } = components;
    const { useState, useEffect } = wp.element;

    registerBlockType('uswds-gutenberg/clarification-block', {
        title: 'Clarifications Block',
        icon: 'book',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            title: { type: 'string', default: 'Certification Clarifications' },
            subtitle: { type: 'string', default: 'Technical Explanations and Clarifications' },
            enableSearch: { type: 'boolean', default: true },
            enableFilter: { type: 'boolean', default: true },
            enableSorting: { type: 'boolean', default: true },
            enableExpandAll: { type: 'boolean', default: true },
            sortOptions: {
                type: 'array',
                default: [
                    { label: 'Default Order', value: 'default' },
                    { label: 'A-Z', value: 'az' },
                    { label: 'Z-A', value: 'za' }
                ]
            },
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
                sortOptions,
                defaultSortOrder,
                filterOptions
            } = attributes;

            // Only allow our custom accordion item blocks as children
            const ALLOWED_BLOCKS = ['uswds-gutenberg/clarification-item'];
            
            // Create a template with an initial child block
            const TEMPLATE = [
                ['uswds-gutenberg/clarification-item', {}]
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
                            options: sortOptions,
                            onChange: value => setAttributes({ defaultSortOrder: value })
                        })
                    )
                ),
                el('div', blockProps,
                    el(RichText, {
                        tagName: 'h2',
                        value: title,
                        onChange: value => setAttributes({ title: value }),
                        placeholder: 'Enter block title...'
                    }),
                    el(RichText, {
                        tagName: 'p',
                        value: subtitle,
                        onChange: value => setAttributes({ subtitle: value }),
                        placeholder: 'Enter subtitle...'
                    }),
                    el('div', { className: 'clarification-items-container' },
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