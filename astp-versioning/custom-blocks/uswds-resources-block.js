(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps } = blockEditor;
    const { TextControl, Button } = components;

    registerBlockType('uswds-gutenberg/resources-block', {
        title: 'Resources Block',
        icon: 'book',
        category: 'uswds-custom-blocks',
        attributes: {
            title: { type: 'string', default: 'Resources' },
            description: { type: 'string', default: 'The following resources can offer assistance or additional information:' },
            subheading: { type: 'string', default: '§ 170.315(g)(10) Standardized API for patient and population services' },
            resources: { type: 'array', default: [] },
            lastUpdatedLabel: { type: 'string', default: 'Last Updated' }
        },

        edit: ({ attributes, setAttributes }) => {
            const { title, description, subheading, resources, lastUpdatedLabel } = attributes;
            const blockProps = useBlockProps();

            const addResource = () => {
                setAttributes({
                    resources: [...resources, { type: '', title: '', url: '', lastUpdated: '' }]
                });
            };

            const updateResource = (index, key, value) => {
                const updatedResources = [...resources];
                updatedResources[index][key] = value;
                setAttributes({ resources: updatedResources });
            };

            const removeResource = (index) => {
                setAttributes({ resources: resources.filter((_, i) => i !== index) });
            };

            return el(Fragment, null,
                el('div', { ...blockProps, className: 'grid-container' },
                    el(RichText, {
                        tagName: 'h2',
                        value: title,
                        onChange: (value) => setAttributes({ title: value }),
                        placeholder: 'Enter block title...'
                    }),
                    el('div', { ...blockProps, className: 'resources-block' },
                        el(RichText, {
                            tagName: 'p',
                            value: description,
                            onChange: (value) => setAttributes({ description: value }),
                            placeholder: 'Enter description...',
                            className: 'resource-description text'
                        }),

                        el('p', { className: 'resource-subheading' },
                            el('strong', null,
                                el(RichText, {
                                    tagName: 'span',
                                    value: subheading,
                                    onChange: (value) => setAttributes({ subheading: value }),
                                    placeholder: 'Enter subheading...',
                                    className: 'resource-subheading-text'
                                })
                            )
                        ),                                           

                        el('div', { className: 'resources-list padding-1' },
                            resources.map((resource, index) =>
                                el('div', { key: index, className: 'resource-item padding-top-1' },
                                    el('strong', { className: 'resource-number' }, `${index + 1}.`), // Adding numbered bullets
                                    el(TextControl, {
                                        label: 'Type',
                                        value: resource.type,
                                        onChange: (value) => updateResource(index, 'type', value),
                                        placeholder: 'Enter type (e.g., PDF Document, Website, GitHub)'
                                    }),
                                    el(RichText, {
                                        tagName: 'h3',
                                        value: resource.title,
                                        onChange: (value) => updateResource(index, 'title', value),
                                        placeholder: 'Enter resource title...',
                                        className: 'resource-title-input'
                                    }),
                                    el(TextControl, {
                                        label: 'URL',
                                        value: resource.url,
                                        onChange: (value) => updateResource(index, 'url', value)
                                    }),
                                    el(TextControl, {
                                        label: lastUpdatedLabel,
                                        value: resource.lastUpdated,
                                        type: 'date', // This makes it a date input
                                        onChange: (value) => updateResource(index, 'lastUpdated', value)
                                    }),
                                    el(Button, { onClick: () => removeResource(index), isDestructive: true, className: 'remove-resource usa-button usa-button--outline btn btn-secondary margin-top-2' }, 'Remove')
                                )
                            )
                        ),
                        el(Button, { onClick: addResource, className: 'add-resource usa-button margin-top-2' }, '+ Add Resource')
                    )
                )
            );
        },

        save: ({ attributes }) => {
            const { title, description, subheading, resources, lastUpdatedLabel } = attributes;
            const blockProps = useBlockProps.save();
        
            return el('div', { ...blockProps, className: 'wp-block-uswds-gutenberg-resources-block grid-container' },
                el('div', { className: 'resources-tab' },
                    el('h2', {}, title),
                    el('p', {}, 
                        description,
                        el('strong', {}, ` ${subheading}`)
                    ),
                    el('ul', { className: 'cards' },
                        resources.map((resource, index) =>
                            el('li', { key: index },
                                el('a', { 
                                    href: resource.url || '#',
                                    className: 'hit-card__link',
                                    target: '_blank',
                                    rel: 'noopener noreferrer'
                                },
                                    el('div', { className: 'type' }, resource.type.toUpperCase()),
                                    resource.title,
                                    resource.lastUpdated ? 
                                        el('div', { className: 'last-updated' },
                                            el('strong', {}, `${lastUpdatedLabel}:`), 
                                            ` ${resource.lastUpdated}`
                                        ) : null
                                )
                            )
                        )
                    )
                )
            );
        }
    });
})(window.wp);