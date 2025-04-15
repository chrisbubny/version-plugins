(function ({ blocks, element, blockEditor, components } ) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InspectorControls } = blockEditor;
    const { PanelBody, CheckboxControl } = components;

    const PREDEFINED_COMPONENTS = [
        {
            key: 'documentation',
            iconUrl: 'https://master--660b1758fb138f04b86241be.chromatic.com/icons/doc-icon.svg',
            title: 'Documentation',
            description: 'Documentation is an approved method to demonstrate conformance. This may include documents from the health IT developer or third-party that demonstrate/attest to the compliance with the criterion.',
            url: ''
        },
        {
            key: 'visual',
            iconUrl: 'https://master--660b1758fb138f04b86241be.chromatic.com/icons/show-icon.svg',
            title: 'Visual Inspection',
            description: 'Visual inspection is an approved method to demonstrate conformance. Most commonly, this will be accomplished via a live demonstration of functionality that meets the criterion.',
            url: ''
        },
        {
            key: 'tools',
            iconUrl: 'https://master--660b1758fb138f04b86241be.chromatic.com/icons/tool-wrench-icon.svg',
            title: 'Testing Tools',
            description: 'Testing tools indicate that a test tool(s) exists and must be used to test a portion or all of a Health IT Module’s conformance.',
            url: '#testing-tools'
        },
        {
            key: 'svap',
            iconUrl: 'https://master--660b1758fb138f04b86241be.chromatic.com/icons/gear-icon.svg',
            title: 'SVAP',
            description: 'Indicates that the Standards Version Advancement Process (SVAP) is applicable to the criterion.',
            url: '#svap-slideout'
        },
        {
            key: 'onc-tools',
            iconUrl: 'https://master--660b1758fb138f04b86241be.chromatic.com/icons/tool-wrench-icon.svg',
            title: 'ONC Testing Tools',
            description: 'Indicates that test data supplied by ONC or as required by the tool(s) must be used during the test.',
            url: ''
        }
    ];

    registerBlockType('uswds-gutenberg/testing-components', {
        title: 'Testing Components',
        icon: 'admin-tools',
        category: 'astp-tp',
        attributes: {
            title: { type: 'string', default: 'Testing Components' },
            subtitle: { type: 'string', default: '§ 170.315(g)(10) Standardized API for patient and population services' },
            selectedKeys: { type: 'array', default: [] }
        },

        edit: ({ attributes, setAttributes }) => {
            const blockProps = useBlockProps();
            const { title, subtitle, selectedKeys } = attributes;

            const toggleComponent = (key) => {
                const updated = selectedKeys.includes(key)
                    ? selectedKeys.filter(k => k !== key)
                    : [...selectedKeys, key];
                setAttributes({ selectedKeys: updated });
            };

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: "Select Testing Components", initialOpen: true },
                        PREDEFINED_COMPONENTS.map((component) =>
                            el(CheckboxControl, {
                                key: component.key,
                                label: component.title,
                                checked: selectedKeys.includes(component.key),
                                onChange: () => toggleComponent(component.key)
                            })
                        )
                    )
                ),
                el('div', { ...blockProps, className: 'grid-container' },
                    el('div', { className: 'testing-components-block' },
                        el(RichText, {
                            tagName: 'h2',
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            placeholder: 'Enter title...',
                            className: 'testing-title'
                        }),
                        el(RichText, {
                            tagName: 'p',
                            value: subtitle,
                            onChange: (value) => setAttributes({ subtitle: value }),
                            placeholder: 'Enter subtitle...',
                            className: 'testing-subtitle'
                        }),
                        el('ul', { className: 'usa-card-group grid-row' },
                            selectedKeys.map((key) => {
                                const component = PREDEFINED_COMPONENTS.find(c => c.key === key);
                                return component && el('li', { key: key, className: 'usa-card desktop:grid-col-6 card card-w-icon' },
                                    el('div', { className: 'usa-card__container' },
                                        el('div', { className: 'usa-card__header' },
                                            el('img', { src: component.iconUrl, className: 'card-w-icon__icon', alt: `${component.title} icon` }),
                                            el('h3', { className: 'usa-card__heading' }, component.title)
                                        ),
                                        el('div', { className: 'usa-card__body' },
                                            el('p', {}, component.description)
                                        ),
                                        component.url && el('div', { className: 'usa-card__footer' },
                                            el('a', {
                                                href: component.url,
                                                className: 'usa-button',
                                                target: '_blank',
                                                rel: 'noopener noreferrer'
                                            }, 'Learn More')
                                        )
                                    )
                                );
                            })
                        )
                    )
                )
            );
        },

   save: () => {
        return null;
       }
    });
})(window.wp);
