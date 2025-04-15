/**
 * Dependencies Block with Inner Blocks Support
 * 
 * This implements a flexible parent/child block structure for better content management
 */
(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, RichText, useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    
    // Register Parent Container Block
    registerBlockType('uswds-gutenberg/dependencies-block', {
        title: 'Dependencies',
        icon: 'list-view',
        category: 'astp-ccg',
        
        attributes: {
            title: {
                type: 'string',
                default: 'Certification Dependencies'
            },
            subtitle: {
                type: 'string',
                default: 'Conditions and Maintenance of Certification'
            },
            showSidebar: {
                type: 'boolean',
                default: true
            },
            // Add a stable blockId attribute
            blockId: {
                type: 'string',
                default: 'deps-container'
            }
        },
        
        // Support options
        supports: {
            html: false,
            reusable: false,
            align: ['wide', 'full'],
        },
        
        // Edit function
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { title, subtitle, showSidebar, blockId } = attributes;
            const blockProps = useBlockProps({
                className: 'wp-block-uswds-gutenberg-dependencies-block dependencies-tab'
            });
            
            // Set a stable block ID based on the clientId if not already set
            if (blockId === 'deps-container') {
                // Only take the first 8 characters of the clientId for a cleaner ID
                const stableId = 'deps-container-' + clientId.substring(0, 8);
                setAttributes({ blockId: stableId });
            }
            
            // Template for child blocks - ensure at least one section block
            const TEMPLATE = [
                ['uswds-gutenberg/section-block', {}]
            ];
            
            return el(Fragment, null,
                // Inspector controls for block settings
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Block Settings', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Show Sidebar Navigation',
                            checked: showSidebar,
                            onChange: (value) => setAttributes({ showSidebar: value })
                        })
                    )
                ),
                
                // Main block edit UI
                el('div', blockProps,
                    // Header section
                    el('div', { className: 'heading' },
                        el(RichText, {
                            tagName: 'h2',
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            placeholder: 'Enter block title...'
                        }),
                        el(RichText, {
                            tagName: 'p',
                            value: subtitle,
                            onChange: (value) => setAttributes({ subtitle: value }),
                            className: 'text-left'
                        })
                    ),
                    
                    // Main content area
                    el('div', { className: 'dependencies-tab grid-row grid-gap' },
                        el('div', { className: 'grid-col' + (showSidebar ? '-8' : '') }, 
                            el('div', { className: 'content' },
                                el(InnerBlocks, {
                                    template: TEMPLATE,
                                    allowedBlocks: ['uswds-gutenberg/section-block'],
                                    templateLock: false,
                                    renderAppender: InnerBlocks.ButtonBlockAppender
                                })
                            )
                        ),
                        
                        // Sidebar (only if enabled)
                        showSidebar && el('div', { className: 'grid-col-4' },
                            el('div', { className: 'block-sidebar editor-sidebar' },
                                el('h4', null, 'IN THIS SECTION'),
                                el('p', { className: 'sidebar-note' }, 'Navigation will be automatically generated from section headings.')
                            )
                        )
                    )
                )
            );
        },
        
        // Save function
        save: function(props) {
            const { attributes } = props;
            const { title, subtitle, showSidebar, blockId } = attributes;
            const blockProps = useBlockProps.save({
                className: 'wp-block-uswds-gutenberg-dependencies-block dependencies-tab'
            });
            
            return el('div', blockProps,
                // Header section
                el('div', { className: 'heading' },
                    el('h2', null, title),
                    el('p', null, subtitle)
                ),
                
                // Main content area
                el('div', { className: 'dependencies-tab__content', id: blockId },
                    el('div', { className: 'content' },
                        el(InnerBlocks.Content)
                    ),
                    
                    // Sidebar navigation (only if enabled)
                    showSidebar && el('nav', {
                        'aria-label': 'Page Navigation',
                        className: 'tabs-panel__its-sidebar hit-ccg-single-js-sidebar'
                    },
                        el('h4', null, 'IN THIS SECTION'),
                        el('ul', { className: 'js-sidebar-list' },
                            el('div', {
                                className: 'border-indicator',
                                style: { transform: 'translateY(0px)' }
                            })
                            // Navigation items will be populated by JavaScript at runtime
                        )
                    )
                ),
                
                // Add script to handle navigation in the frontend
                showSidebar && el('script', {
                    dangerouslySetInnerHTML: {
                        __html: `
                        (function() {
                            document.addEventListener('DOMContentLoaded', function() {
                                const container = document.getElementById('${blockId}');
                                if (!container) return;
                                
                                const sidebarList = container.querySelector('.js-sidebar-list');
                                if (!sidebarList) return;
                                
                                const sections = container.querySelectorAll('.content-block');
                                
                                sections.forEach((section, index) => {
                                    const heading = section.querySelector('h3');
                                    if (!heading) return;
                                    
                                    const headingId = heading.id || heading.textContent.toLowerCase().replace(/\\s+/g, '-');
                                    heading.id = headingId;
                                    
                                    const li = document.createElement('li');
                                    li.className = index === 0 ? 'current' : '';
                                    
                                    const a = document.createElement('a');
                                    a.href = '#' + headingId;
                                    a.className = index === 0 ? 'current' : '';
                                    a.textContent = heading.textContent;
                                    
                                    li.appendChild(a);
                                    sidebarList.appendChild(li);
                                    
                                    // Add click event to update current class
                                    a.addEventListener('click', function(e) {
                                        const allLinks = sidebarList.querySelectorAll('a');
                                        allLinks.forEach(link => link.classList.remove('current'));
                                        const allItems = sidebarList.querySelectorAll('li');
                                        allItems.forEach(item => item.classList.remove('current'));
                                        
                                        a.classList.add('current');
                                        li.classList.add('current');
                                    });
                                });
                            });
                        })();
                        `
                    }
                })
            );
        },
        
        // Deprecated versions to handle existing content
        deprecated: [
            {
                attributes: {
                    title: {
                        type: 'string',
                        default: 'Certification Dependencies'
                    },
                    subtitle: {
                        type: 'string',
                        default: 'Conditions and Maintenance of Certification'
                    },
                    showSidebar: {
                        type: 'boolean',
                        default: true
                    }
                },
                
                save: function(props) {
                    const { attributes } = props;
                    const { title, subtitle, showSidebar } = attributes;
                    
                    // Generate a random ID to match existing content pattern
                    const blockId = `deps-container-${Math.floor(Math.random() * 10000)}`;
                    
                    return el('div', { 
                        className: 'wp-block-uswds-gutenberg-dependencies-block wp-block-uswds-gutenberg-dependencies-container dependencies-tab'
                    },
                        // Header section
                        el('div', { className: 'heading' },
                            el('h2', null, title),
                            el('p', null, subtitle)
                        ),
                        
                        // Main content area
                        el('div', { className: 'dependencies-tab__content', id: blockId },
                            el('div', { className: 'content' },
                                el(InnerBlocks.Content)
                            ),
                            
                            // Sidebar navigation (only if enabled)
                            showSidebar && el('nav', {
                                'aria-label': 'Page Navigation',
                                className: 'tabs-panel__its-sidebar hit-ccg-single-js-sidebar'
                            },
                                el('h4', null, 'IN THIS SECTION'),
                                el('ul', { className: 'js-sidebar-list' },
                                    el('div', {
                                        className: 'border-indicator',
                                        style: { transform: 'translateY(0px)' }
                                    })
                                )
                            )
                        ),
                        
                        // Add script
                        showSidebar && el('script', {
                            dangerouslySetInnerHTML: {
                                __html: `
                                (function() {
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const container = document.getElementById('${blockId}');
                                        if (!container) return;
                                        
                                        const sidebarList = container.querySelector('.js-sidebar-list');
                                        if (!sidebarList) return;
                                        
                                        const sections = container.querySelectorAll('.content-block');
                                        
                                        sections.forEach((section, index) => {
                                            const heading = section.querySelector('h3');
                                            if (!heading) return;
                                            
                                            const headingId = heading.id || heading.textContent.toLowerCase().replace(/\\s+/g, '-');
                                            heading.id = headingId;
                                            
                                            const li = document.createElement('li');
                                            li.className = index === 0 ? 'current' : '';
                                            
                                            const a = document.createElement('a');
                                            a.href = '#' + headingId;
                                            a.className = index === 0 ? 'current' : '';
                                            a.textContent = heading.textContent;
                                            
                                            li.appendChild(a);
                                            sidebarList.appendChild(li);
                                            
                                            // Add click event to update current class
                                            a.addEventListener('click', function(e) {
                                                const allLinks = sidebarList.querySelectorAll('a');
                                                allLinks.forEach(link => link.classList.remove('current'));
                                                const allItems = sidebarList.querySelectorAll('li');
                                                allItems.forEach(item => item.classList.remove('current'));
                                                
                                                a.classList.add('current');
                                                li.classList.add('current');
                                            });
                                        });
                                    });
                                })();
                                `
                            }
                        })
                    );
                },
                
                migrate: function(attributes) {
                    return {
                        ...attributes,
                        blockId: 'deps-container'
                    };
                }
            }
        ]
    });
})(window.wp);