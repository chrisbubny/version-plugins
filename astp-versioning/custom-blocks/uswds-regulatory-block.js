(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var RichText = wp.blockEditor.RichText;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InnerBlocks = wp.blockEditor.InnerBlocks;
    var useSelect = wp.data.useSelect;

    registerBlockType('uswds-gutenberg/regulatory-block', {
        title: 'Regulatory Block',
        icon: 'book',
        category: 'astp-ccg',
        supports: { inserter: true },
        attributes: {
            title: {
                type: 'string',
                default: 'Regulation Text'
            },
            subtitle: {
                type: 'string',
                default: '§ 170.315(g)(10) Standardized API for patient and population services'
            },
            mainDescription: {
                type: 'string',
                default: 'The following technical outcomes and conditions must be met through the demonstration of application programming interface technology.'
            },
            // Add flag to control whether the mobile dropdown should be included
            includeMobileDropdown: {
                type: 'boolean',
                default: false
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var title = attributes.title;
            var subtitle = attributes.subtitle;
            var mainDescription = attributes.mainDescription;
            var blockProps = useBlockProps();
            var clientId = props.clientId;

            // Get innerBlocks (regulatory-section children)
            var childBlocks = useSelect(
                function(select) {
                    var getBlock = select('core/block-editor').getBlock;
                    var parent = getBlock(clientId);
                    return parent?.innerBlocks || [];
                },
                [clientId]
            );

            return el(Fragment, null,
                el('div', { ...blockProps, className: 'grid-container' },
                    el(RichText, {
                        tagName: 'h2',
                        value: title,
                        onChange: function(value) { setAttributes({ title: value }); },
                        placeholder: 'Enter block title...'
                    }),
                    el(RichText, {
                        tagName: 'p',
                        value: subtitle,
                        onChange: function(value) { setAttributes({ subtitle: value }); },
                        placeholder: 'Enter subtitle...',
                        className: 'regulatory-subtitle'
                    }),

                    el('div', { className: 'regulatory-block grid-row grid-gap' },
                        el('div', { className: 'grid-col-8' },
                            el(RichText, {
                                tagName: 'p',
                                value: mainDescription,
                                onChange: function(value) { setAttributes({ mainDescription: value }); },
                                placeholder: 'Enter main regulatory description...',
                                className: 'regulatory-main-description'
                            }),
                            el('div', { className: 'regulatory-main' },
                                el(InnerBlocks, {
                                    allowedBlocks: ['uswds-gutenberg/regulatory-section'],
                                    templateLock: false,
                                    renderAppender: function() { return el(InnerBlocks.ButtonBlockAppender); }
                                })
                            )
                        ),
                        el('div', { className: 'grid-col-4' },
                            el('nav', {
                                'aria-label': 'Page Navigation',
                                className: 'tabs-panel__its-sidebar hit-ccg-single-js-sidebar'
                            },
                                el('h4', null, 'IN THIS SECTION'),
                                el('ul', null,
                                    el('div', {
                                        className: 'border-indicator',
                                        style: { transform: 'translateY(0px)' }
                                    }),
                                    childBlocks.map(function(block) {
                                        var heading = block.attributes.heading || 'Untitled Section';
                                        var id = 'section-' + (block.attributes.sectionId || '');
                                        return el('li', { key: id },
                                            el('a', { href: '#' + id }, heading)
                                        );
                                    })
                                )
                            )
                        )
                    )
                )
            );
        },

        save: function(props) {
            var attributes = props.attributes;
            var title = attributes.title;
            var subtitle = attributes.subtitle;
            var mainDescription = attributes.mainDescription;
            var includeMobileDropdown = attributes.includeMobileDropdown;
            
            return el('div', { className: 'tabs-content__regular' },
                el('div', { className: 'heading' },
                    el('h2', null, title),
                    el('p', { className: 'leading' }, subtitle)
                ),
                el('div', { className: 'tabs-content__regular__content' },
                    el('div', { className: 'inner' },
                        el('p', null, mainDescription),
                        el(InnerBlocks.Content)
                    ),
        
                    // SIDEBAR NAV - Static version for save
                    el('nav', {
                        'aria-label': 'Page Navigation',
                        className: 'tabs-panel__its-sidebar hit-ccg-single-js-sidebar'
                    },
                        el('h4', null, 'IN THIS SECTION'),
                        el('ul', { className: 'js-regulatory-sidebar' },
                            el('div', {
                                className: 'border-indicator',
                                style: { transform: 'translateY(0px)' }
                            })
                            // No sections.map here - this will be populated via JS
                        )
                    )
                    
                    // Don't render the mobile dropdown in the block output
                    // since it's already in the template
                )
            );
        }
    });
})(window.wp);