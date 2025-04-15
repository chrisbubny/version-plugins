(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InnerBlocks, InspectorControls } = blockEditor;
    const { PanelBody, ToggleControl, TextControl } = components;

    registerBlockType('uswds-gutenberg/testing-steps-accordion', {
        title: 'Testing Steps Accordion',
        description: 'An accordion block used within Testing Steps Tool to structure test instructions.',
        icon: 'menu',
        category: 'astp-tp',
        parent: ['uswds-gutenberg/testing-steps-tool'],
        supports: {
            html: false,
            reusable: false,
            multiple: true,
            inserter: true
        },
        attributes: {
            title: { type: 'string', default: 'Accordion Title' },
            uniqueId: { type: 'string', default: '' },
            hasExpiryDate: { type: 'boolean', default: false },
            expiryDate: { type: 'string', default: '' },
            expiryText: { type: 'string', default: '' },
            expiryInfo: { type: 'string', default: '' },
            lastExpiryDate: { type: 'string', default: '' }
        },

        edit: function({ attributes, setAttributes, clientId }) {
            const blockProps = useBlockProps({ className: 'testing-steps-accordion-editor' });
            const {
                title, uniqueId, hasExpiryDate, expiryDate,
                expiryText, expiryInfo
            } = attributes;

            // Generate unique ID if not already set
            if (!uniqueId || uniqueId.indexOf(clientId) === -1) {
                setAttributes({ uniqueId: 'accordion-' + clientId });
            }

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Accordion Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Accordion Title',
                            value: title,
                            onChange: (value) => setAttributes({ title: value })
                        }),
                        el(TextControl, {
                            label: 'Unique ID',
                            value: uniqueId,
                            onChange: (value) => setAttributes({ uniqueId: value }),
                            help: 'ID used for accordion functionality'
                        }),
                        el(ToggleControl, {
                            label: 'Has Expiry Date',
                            checked: hasExpiryDate,
                            onChange: (value) => setAttributes({ hasExpiryDate: value })
                        }),
                        hasExpiryDate && el('div', { className: 'date-picker-wrapper' },
                            el(TextControl, {
                                label: 'Expiry Date',
                                type: 'date',
                                value: expiryDate,
                                onChange: (value) => setAttributes({ expiryDate: value }),
                                help: 'Set to 2025-12-31 for "Expires on Dec 31, 2025" or 2026-01-01 for "Required by Jan 1, 2026"'
                            }),
                            el(TextControl, {
                                label: 'Display Text',
                                value: expiryText,
                                onChange: (value) => setAttributes({ expiryText: value })
                            }),
                            el(TextControl, {
                                label: 'Additional Info Text',
                                value: expiryInfo || '',
                                onChange: (value) => setAttributes({ expiryInfo: value }),
                                help: 'Additional text to display below the main expiry date text (e.g., "SMART App Launch 2.0.0")'
                            })
                        )
                    )
                ),
                el('div', blockProps,
                    el('div', { className: 'accordion-header non-interactive' },
                        el(RichText, {
                            tagName: 'h4',
                            className: 'accordion-title',
                            value: title,
                            onChange: value => setAttributes({ title: value }),
                            placeholder: 'Accordion Title'
                        }),
                        hasExpiryDate && expiryText && el('div', { className: 'expires' }, expiryText),
                        hasExpiryDate && expiryInfo && el('div', { className: 'expires extra' }, expiryInfo)
                    ),
                    el('div', { className: 'accordion-content always-open' },
                        el(InnerBlocks, {
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        })
                    )
                )
            );
        },

        // Important: Save the InnerBlocks content to preserve it
        save: function({ attributes }) {
            // We're not rendering the HTML here since that's handled by the PHP render_callback,
            // but we need to preserve the InnerBlocks content in the block's saved markup
            return el(InnerBlocks.Content);
        }
    });
})(window.wp);