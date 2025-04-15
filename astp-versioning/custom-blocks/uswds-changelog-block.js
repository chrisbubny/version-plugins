/**
 * Changelog Block
 * Displays version history and changes in a unified chronological timeline
 */
(function() {
    // Access WordPress globals directly
    if (!window.wp) {
        console.error('WordPress API not available!');
        return;
    }
    
    const { blocks, element, blockEditor, components, data } = window.wp;
    
    if (!blocks || !element || !blockEditor || !components || !data) {
        console.error('Required WordPress dependencies not available!');
        return;
    }
    
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InspectorControls } = blockEditor;
    const { PanelBody, ToggleControl, SelectControl, TextControl } = components;
    const { useSelect } = data;
    const { useState, useEffect } = window.wp.element;
    
    // Log registration attempt
    console.log('Registering changelog block: uswds-gutenberg/changelog-block');

    try {
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
                const [activeTab, setActiveTab] = useState('ccg');
                const [activeFilter, setActiveFilter] = useState('all');
                
                // Get current post ID if not already set
                const currentPostId = useSelect(select => {
                    if (postId) return postId;
                    
                    const currentPostId = select('core/editor').getCurrentPostId();
                    if (currentPostId && !postId) {
                        setAttributes({ postId: currentPostId });
                    }
                    return currentPostId;
                }, [postId]);
                
                // Sample data - in production this would come from API
                const mockVersions = {
                    ccg: [
                        {
                            id: 1,
                            date: '2024-12-12',
                            version: '1.4',
                            changes: [
                                {
                                    id: 1,
                                    paragraph: 'Paragraph (g)(10)(i)(A)',
                                    title: 'Data response - single patient',
                                    changes: [
                                        {
                                            type: 'added',
                                            content: 'All data elements and operations indicated as "mandatory" and "must support" by the standards and implementation specifications must be supported and are in-scope for testing. <mark class="added">Health IT Modules must support provenance according to the "Basic Provenance Guidance" section of the US Core IG.</mark>'
                                        },
                                        {
                                            type: 'removed',
                                            content: 'To meet ONC Certification requirements, Health IT developers must document how the Health IT Module enforces TLS version 1.2 or <mark class="removed">above to</mark> meet the API documentation requirements.'
                                        }
                                    ]
                                },
                                {
                                    id: 2,
                                    paragraph: 'Paragraph (g)(10)(ii)(A)',
                                    title: 'Supported search operations - single patient',
                                    changes: [
                                        {
                                            type: 'amended',
                                            content: '(B) Establishing <mark class="amended">Establish</mark> a secure and trusted connection with an application that requests data for system scopes in accordance with an implementation specification adopted in § 170.215(d).'
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            id: 2,
                            date: '2024-12-05',
                            version: '1.3.5',
                            changes: [
                                {
                                    id: 3,
                                    paragraph: 'Paragraph (g)(10)(i)(A)',
                                    title: 'Data response - single patient',
                                    changes: [
                                        {
                                            type: 'added',
                                            content: 'All data elements and operations indicated as "mandatory" and "must support" by the standards and implementation specifications must be supported and are in-scope for testing. <mark class="added">Health IT Modules must support provenance according to the "Basic Provenance Guidance" section of the US Core IG.</mark>'
                                        },
                                        {
                                            type: 'removed',
                                            content: 'To meet ONC Certification requirements, Health IT developers must document how the Health IT Module enforces TLS version 1.2 or <mark class="removed">above to</mark> meet the API documentation requirements.'
                                        }
                                    ]
                                }
                            ]
                        }
                    ],
                    tp: [
                        {
                            id: 3,
                            date: '2024-12-15',
                            version: '1.8.9',
                            changes: [
                                {
                                    id: 4,
                                    paragraph: 'Paragraph (g)(10)(i)(A)',
                                    title: 'Data response - single patient',
                                    changes: [
                                        {
                                            type: 'added',
                                            content: 'All data elements and operations indicated as "mandatory" and "must support" by the standards and implementation specifications must be supported and are in-scope for testing. <mark class="added">Health IT Modules must support provenance according to the "Basic Provenance Guidance" section of the US Core IG.</mark>'
                                        },
                                        {
                                            type: 'removed',
                                            content: 'To meet ONC Certification requirements, Health IT developers must document how the Health IT Module enforces TLS version 1.2 or <mark class="removed">above to</mark> meet the API documentation requirements.'
                                        }
                                    ]
                                },
                                {
                                    id: 5,
                                    paragraph: 'Paragraph (g)(10)(ii)(A)',
                                    title: 'Supported search operations - single patient',
                                    changes: [
                                        {
                                            type: 'amended',
                                            content: '(B) Establishing <mark class="amended">Establish</mark> a secure and trusted connection with an application that requests data for system scopes in accordance with an implementation specification adopted in § 170.215(d).'
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            id: 4,
                            date: '2024-12-07',
                            version: '1.6.7',
                            changes: [
                                {
                                    id: 6,
                                    paragraph: 'Paragraph (g)(10)(i)(A)',
                                    title: 'Data response - single patient',
                                    changes: [
                                        {
                                            type: 'added',
                                            content: 'All data elements and operations indicated as "mandatory" and "must support" by the standards and implementation specifications must be supported and are in-scope for testing. <mark class="added">Health IT Modules must support provenance according to the "Basic Provenance Guidance" section of the US Core IG.</mark>'
                                        },
                                        {
                                            type: 'removed',
                                            content: 'To meet ONC Certification requirements, Health IT developers must document how the Health IT Module enforces TLS version 1.2 or <mark class="removed">above to</mark> meet the API documentation requirements.'
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                };
                
                // Get the version data based on active tab
                const versions = mockVersions[activeTab] || [];
                
                // Filter changes based on active filter
                const filterChanges = (versions, filter) => {
                    if (filter === 'all') return versions;
                    
                    return versions.map(version => {
                        // Deep clone the version object
                        const filteredVersion = {...version};
                        
                        // Filter the changes within each version
                        filteredVersion.changes = version.changes.map(change => {
                            // Clone the change object
                            const filteredChange = {...change};
                            
                            // Filter the specific changes by type
                            filteredChange.changes = change.changes.filter(c => c.type === filter);
                            
                            return filteredChange;
                        }).filter(change => change.changes.length > 0);
                        
                        return filteredVersion;
                    }).filter(version => version.changes.length > 0);
                };
                
                const filteredVersions = filterChanges(versions, activeFilter);
                
                // Calculate recent versions for sidebar
                const recentVersions = [...new Set([...mockVersions.ccg, ...mockVersions.tp].map(v => v.version))].slice(0, 5);
                
                return el(Fragment, null,
                    el(InspectorControls, null,
                        el(PanelBody, { title: 'Changelog Settings', initialOpen: true },
                            el(TextControl, {
                                label: 'Block Title',
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
                    ),
                    
                    // Main block content
                    el('div', blockProps,
                        el('div', { className: 'tabs-panel__changelog' },
                            el('div', { className: 'mobile-its-select' },
                                el('label', { htmlFor: 'section-select' }, 'IN THIS SECTION'),
                                el('select', { id: 'section-select' }),
                                el('a', { 
                                    className: 'usa-button btn btn--icon-right view-all mobile-only',
                                    href: '#',
                                    'aria-label': 'Title of link'
                                }, 'View Archives')
                            ),
                            
                            el('div', { className: 'changelog-tab' },
                                // Top header section
                                el('div', { className: 'top' },
                                    el(RichText, {
                                        tagName: 'h2',
                                        value: title,
                                        onChange: (value) => setAttributes({ title: value }),
                                        placeholder: 'Changelog Title'
                                    }),
                                    el('p', {},
                                        el(RichText, {
                                            tagName: 'span',
                                            value: subtitle,
                                            onChange: (value) => setAttributes({ subtitle: value }),
                                            placeholder: 'Subtitle'
                                        }),
                                        el('br'),
                                        el(RichText, {
                                            tagName: 'strong',
                                            value: subtitleEmphasis,
                                            onChange: (value) => setAttributes({ subtitleEmphasis: value }),
                                            placeholder: 'Subtitle Emphasis'
                                        })
                                    )
                                ),
                                
                                // Document type tabs (CCG/TP)
                                enableTabSwitching && el('div', { className: 'tabs hit-ccg-single__pills' },
                                    el('ul', { className: 'tabs-primary' },
                                        el('li', {},
                                            el('a', { 
                                                href: '#tab-cert-changelog5',
                                                className: activeTab === 'ccg' ? 'active' : '',
                                                onClick: (e) => {
                                                    e.preventDefault();
                                                    setActiveTab('ccg');
                                                }
                                            }, 'Certification Companion Guide')
                                        ),
                                        el('li', {},
                                            el('a', { 
                                                href: '#testing-changelog',
                                                className: activeTab === 'tp' ? 'active' : '',
                                                onClick: (e) => {
                                                    e.preventDefault();
                                                    setActiveTab('tp');
                                                }
                                            }, 'Test Procedures')
                                        )
                                    )
                                ),
                                
                                // Mobile filters
                                enableFilters && el('div', { className: 'mobile-filter-by-type mobile-only' },
                                    el('h4', {}, 'FILTER BY TYPE'),
                                    el('ul', { className: 'filter-type' },
                                        el('li', {},
                                            el('button', { 
                                                className: activeFilter === 'all' ? 'selected filter-btn' : 'filter-btn',
                                                type: 'button',
                                                onClick: () => setActiveFilter('all')
                                            }, 'All')
                                        ),
                                        el('li', {},
                                            el('button', {
                                                className: activeFilter === 'added' ? 'selected filter-btn' : 'filter-btn',
                                                type: 'button',
                                                onClick: () => setActiveFilter('added')
                                            }, 'Added')
                                        ),
                                        el('li', {},
                                            el('button', {
                                                className: activeFilter === 'amended' ? 'selected filter-btn' : 'filter-btn',
                                                type: 'button',
                                                onClick: () => setActiveFilter('amended')
                                            }, 'Amended')
                                        ),
                                        el('li', {},
                                            el('button', {
                                                className: activeFilter === 'removed' ? 'selected filter-btn' : 'filter-btn',
                                                type: 'button',
                                                onClick: () => setActiveFilter('removed')
                                            }, 'Removed')
                                        )
                                    )
                                ),
                                
                                // Tabs content
                                el('div', { className: 'tabs-panels' },
                                    // CCG Tab Content
                                    el('div', { 
                                        className: 'tabs-panel', 
                                        id: 'tab-cert-changelog5',
                                        style: { display: activeTab === 'ccg' ? 'block' : 'none' }
                                    },
                                        el('div', { className: 'changelog-container' },
                                            // Main timeline content
                                            el('div', { className: 'timeline' },
                                                el('ul', {},
                                                    filteredVersions.length > 0 
                                                        ? filteredVersions.map((version) => 
                                                            el('li', { className: 'change', key: version.id },
                                                                el('div', { className: 'date' }, 
                                                                    new Date(version.date).toLocaleDateString('en-US', { 
                                                                        month: 'long', 
                                                                        day: 'numeric', 
                                                                        year: 'numeric' 
                                                                    })
                                                                ),
                                                                el('div', { className: 'version' },
                                                                    el('div', { className: 'inner' },
                                                                        el('h3', { className: 'version-number' }, `Version ${version.version}`),
                                                                        el('ul', { className: 'changes' },
                                                                            version.changes.map((change) => 
                                                                                el('li', { key: change.id },
                                                                                    el('div', { className: 'changelog-info' },
                                                                                        el('div', { className: 'paragraph' }, change.paragraph),
                                                                                        el('h4', { className: 'title' }, change.title),
                                                                                        change.changes.map((c, idx) => 
                                                                                            el(Fragment, { key: idx },
                                                                                                el('h5', { className: 'action' }, `${c.type}:`),
                                                                                                el('p', { 
                                                                                                    className: 'content',
                                                                                                    dangerouslySetInnerHTML: { __html: c.content }
                                                                                                })
                                                                                            )
                                                                                        )
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                        : el('li', { className: 'no-changes' },
                                                            el('div', { className: 'empty-message' }, 
                                                                `No ${activeFilter} changes found in ${activeTab.toUpperCase()} versions`
                                                            )
                                                        )
                                                )
                                            ),
                                            
                                            // Sidebar with filters
                                            enableFilters && el('div', { className: 'sidebar' },
                                                el('h4', {}, 'FILTER BY TYPE'),
                                                el('ul', { className: 'filter-type' },
                                                    el('li', {},
                                                        el('button', { 
                                                            className: activeFilter === 'all' ? 'selected filter-btn' : 'filter-btn',
                                                            type: 'button',
                                                            onClick: () => setActiveFilter('all')
                                                        }, 'All')
                                                    ),
                                                    el('li', {},
                                                        el('button', {
                                                            className: activeFilter === 'added' ? 'selected filter-btn' : 'filter-btn',
                                                            type: 'button',
                                                            onClick: () => setActiveFilter('added')
                                                        }, 'Added')
                                                    ),
                                                    el('li', {},
                                                        el('button', {
                                                            className: activeFilter === 'amended' ? 'selected filter-btn' : 'filter-btn',
                                                            type: 'button',
                                                            onClick: () => setActiveFilter('amended')
                                                        }, 'Amended')
                                                    ),
                                                    el('li', {},
                                                        el('button', {
                                                            className: activeFilter === 'removed' ? 'selected filter-btn' : 'filter-btn',
                                                            type: 'button',
                                                            onClick: () => setActiveFilter('removed')
                                                        }, 'Removed')
                                                    )
                                                ),
                                                el('h4', { className: 'recent-releases-title' }, 'RECENT RELEASES'),
                                                el('ul', { className: 'recent-releases' },
                                                    el('div', { className: 'indicator' }),
                                                    recentVersions.map((v, i) => 
                                                        el('li', { key: i },
                                                            el('a', { href: '#' }, `Version ${v}`)
                                                        )
                                                    )
                                                ),
                                                el('a', {
                                                    className: 'usa-button btn btn--icon-right',
                                                    href: '#',
                                                    'aria-label': 'Title of link'
                                                }, 'View Archives')
                                            )
                                        )
                                    ),
                                
                                // TP Tab Content - Similar structure to CCG tab
                                el('div', { 
                                    className: 'tabs-panel', 
                                    id: 'testing-changelog',
                                    style: { display: activeTab === 'tp' ? 'block' : 'none' }
                                },
                                    el('div', { className: 'changelog-container' },
                                        // Main timeline content
                                        el('div', { className: 'timeline' },
                                            el('ul', {},
                                                filteredVersions.length > 0 
                                                    ? filteredVersions.map((version) => 
                                                        el('li', { className: 'change', key: version.id },
                                                            el('div', { className: 'date' }, 
                                                                new Date(version.date).toLocaleDateString('en-US', { 
                                                                    month: 'long', 
                                                                    day: 'numeric', 
                                                                    year: 'numeric' 
                                                                })
                                                            ),
                                                            el('div', { className: 'version' },
                                                                el('div', { className: 'inner' },
                                                                    el('h3', { className: 'version-number' }, `Version ${version.version}`),
                                                                    el('ul', { className: 'changes' },
                                                                        version.changes.map((change) => 
                                                                            el('li', { key: change.id },
                                                                                el('div', { className: 'changelog-info' },
                                                                                    el('div', { className: 'paragraph' }, change.paragraph),
                                                                                    el('h4', { className: 'title' }, change.title),
                                                                                    change.changes.map((c, idx) => 
                                                                                        el(Fragment, { key: idx },
                                                                                            el('h5', { className: 'action' }, `${c.type}:`),
                                                                                            el('p', { 
                                                                                                className: 'content',
                                                                                                dangerouslySetInnerHTML: { __html: c.content }
                                                                                            })
                                                                                        )
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                    : el('li', { className: 'no-changes' },
                                                        el('div', { className: 'empty-message' }, 
                                                            `No ${activeFilter} changes found in ${activeTab.toUpperCase()} versions`
                                                        )
                                                    )
                                            )
                                        ),
                                        
                                        // Sidebar with filters
                                        enableFilters && el('div', { className: 'sidebar' },
                                            el('h4', {}, 'FILTER BY TYPE'),
                                            el('ul', { className: 'filter-type' },
                                                el('li', {},
                                                    el('button', { 
                                                        className: activeFilter === 'all' ? 'selected filter-btn' : 'filter-btn',
                                                        type: 'button',
                                                        onClick: () => setActiveFilter('all')
                                                    }, 'All')
                                                ),
                                                el('li', {},
                                                    el('button', {
                                                        className: activeFilter === 'added' ? 'selected filter-btn' : 'filter-btn',
                                                        type: 'button',
                                                        onClick: () => setActiveFilter('added')
                                                    }, 'Added')
                                                ),
                                                el('li', {},
                                                    el('button', {
                                                        className: activeFilter === 'amended' ? 'selected filter-btn' : 'filter-btn',
                                                        type: 'button',
                                                        onClick: () => setActiveFilter('amended')
                                                    }, 'Amended')
                                                ),
                                                el('li', {},
                                                    el('button', {
                                                        className: activeFilter === 'removed' ? 'selected filter-btn' : 'filter-btn',
                                                        type: 'button',
                                                        onClick: () => setActiveFilter('removed')
                                                    }, 'Removed')
                                                )
                                            ),
                                            el('h4', { className: 'recent-releases-title' }, 'RECENT RELEASES'),
                                            el('ul', { className: 'recent-releases' },
                                                el('div', { className: 'indicator' }),
                                                recentVersions.map((v, i) => 
                                                    el('li', { key: i },
                                                        el('a', { href: '#' }, `Version ${v}`)
                                                    )
                                                )
                                            ),
                                            el('a', {
                                                className: 'usa-button btn btn--icon-right',
                                                href: '#',
                                                'aria-label': 'Title of link'
                                            }, 'View Archives')
                                        )
                                    )
                                )
                            )
                        )
                    )
                );
            },
            
            // The save function returns null because we'll handle rendering on the server
            save: function() {
                return null;
            }
        });
    } catch (error) {
        console.error('Error registering changelog block:', error);
    }
})(); 