/**
 * Changelog Block
 * Displays version history and changes in a unified chronological timeline
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { RichText, useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl, TextControl } = wp.components;
    const { useSelect } = wp.data;
    const { useState, useEffect } = wp.element;
    
    // Log registration attempt
    console.log('Registering changelog block (fixed version)');

    registerBlockType('uswds-gutenberg/changelog-block', {
        title: 'Changelog Block',
        icon: 'backup',
        category: 'common',
        supports: {
            html: false
        },
        attributes: {
            postId: { type: 'number', default: 0 },
            title: { type: 'string', default: 'Certification Companion Guide Changelog' },
            subtitle: { type: 'string', default: 'The following changelog applies to:' },
            subtitleEmphasis: { type: 'string', default: '§ 170.315(g)(10) Standardized API for patient and population services' },
            enableFilters: { type: 'boolean', default: true },
            enableTabSwitching: { type: 'boolean', default: true }
        },

        edit: function({ attributes, setAttributes }) {
            const { postId, title, subtitle, subtitleEmphasis, enableFilters, enableTabSwitching } = attributes;
            const blockProps = useBlockProps();
            
            // Simple placeholder version for testing
            return el('div', blockProps,
                el('div', { style: { padding: '20px', border: '1px solid #ddd' } },
                    el('h2', {}, 'Changelog Block'),
                    el('p', {}, 'This is a simplified version to test registration'),
                    el(InspectorControls, null,
                        el(PanelBody, { title: 'Settings', initialOpen: true },
                            el(TextControl, {
                                label: 'Title',
                                value: title,
                                onChange: (value) => setAttributes({ title: value })
                            }),
                            el(TextControl, {
                                label: 'Subtitle',
                                value: subtitle,
                                onChange: (value) => setAttributes({ subtitle: value })
                            }),
                            el(TextControl, {
                                label: 'Subtitle Emphasis',
                                value: subtitleEmphasis,
                                onChange: (value) => setAttributes({ subtitleEmphasis: value })
                            }),
                            el(ToggleControl, {
                                label: 'Enable Filters',
                                checked: enableFilters,
                                onChange: (value) => setAttributes({ enableFilters: value })
                            }),
                            el(ToggleControl, {
                                label: 'Enable Tab Switching',
                                checked: enableTabSwitching,
                                onChange: (value) => setAttributes({ enableTabSwitching: value })
                            })
                        )
                    )
                )
            );
        },
        
        save: function() {
            return null; // Server-side rendering
        }
    });
})(window.wp); 