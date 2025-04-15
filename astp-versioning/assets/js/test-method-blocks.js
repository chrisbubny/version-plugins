/**
 * ASTP Versioning - Test Method Specialized Blocks
 */
(function(blocks, element, blockEditor, components, i18n) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { RichText, InspectorControls, MediaUpload } = blockEditor;
    const { PanelBody, TextControl, ToggleControl, Button, DatePicker } = components;
    const { Fragment } = element;
    const el = element.createElement;

    console.log('ASTP Test Method Blocks - Initializing');

    // Log that we're attempting to register blocks
    console.log('Registering ASTP blocks...');

    /**
     * Block Icon for Test Method Blocks
     */
    const blockIcon = el('svg', { 
        viewBox: '0 0 24 24'
    }, el('path', { 
        d: 'M20 3H4c-1.1 0-1.99.9-1.99 2L2 15c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 12H4V5h16v10zM7 15v-9h10v9c-2.8 0-5.2 0-7 .01C8.7 15 7.85 15 7 15z'
    }));

    /**
     * Title Section Block
     */
    registerBlockType('astp/title-section', {
        title: __('Title Section', 'astp-versioning'),
        icon: 'welcome-write-blog',
        category: 'astp-test-method',
        supports: {
            html: false
        },
        attributes: {
            documentTitle: {
                type: 'string',
                default: ''
            },
            documentNumber: {
                type: 'string',
                default: ''
            },
            documentDate: {
                type: 'string',
                default: ''
            },
            content: {
                type: 'string',
                default: ''
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { documentTitle, documentNumber, documentDate, content } = attributes;

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Document Information', 'astp-versioning') },
                        el(TextControl, {
                            label: __('Document Title', 'astp-versioning'),
                            value: documentTitle,
                            onChange: function(value) {
                                setAttributes({ documentTitle: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Document Number', 'astp-versioning'),
                            value: documentNumber,
                            onChange: function(value) {
                                setAttributes({ documentNumber: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Document Date', 'astp-versioning'),
                            value: documentDate,
                            onChange: function(value) {
                                setAttributes({ documentDate: value });
                            },
                            help: __('Format: YYYY-MM-DD', 'astp-versioning')
                        })
                    )
                ),

                // Block content
                el('div', { className: 'astp-title-section-editor' },
                    el('div', { className: 'astp-document-header-editor' },
                        el('h1', { className: 'astp-document-title-editor' }, 
                            documentTitle || __('Document Title', 'astp-versioning')
                        ),
                        el('div', { className: 'astp-document-info-editor' },
                            el('div', { className: 'astp-document-number-editor' }, 
                                documentNumber ? __('Document #: ', 'astp-versioning') + documentNumber : __('Document #: [Enter in sidebar]', 'astp-versioning')
                            ),
                            el('div', { className: 'astp-document-date-editor' }, 
                                documentDate ? __('Date: ', 'astp-versioning') + documentDate : __('Date: [Enter in sidebar]', 'astp-versioning')
                            )
                        )
                    ),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-document-introduction-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter document introduction text...', 'astp-versioning')
                    })
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Regulation Text Block
     */
    registerBlockType('astp/regulation-text', {
        title: __('Regulation Text', 'astp-versioning'),
        icon: 'text',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            reference: {
                type: 'string',
                default: ''
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, reference } = attributes;

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Regulation Reference', 'astp-versioning') },
                        el(TextControl, {
                            label: __('Reference', 'astp-versioning'),
                            value: reference,
                            onChange: function(value) {
                                setAttributes({ reference: value });
                            },
                            help: __('e.g., 45 CFR 170.315(g)(10)', 'astp-versioning')
                        })
                    )
                ),

                // Block content
                el('div', { className: 'astp-regulation-text-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, __('Regulation Text', 'astp-versioning')),
                    reference ? el('div', { className: 'astp-regulation-reference-editor' }, __('Reference: ', 'astp-versioning') + reference) : null,
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-regulation-content-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter regulation text...', 'astp-versioning')
                    })
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Standards Referenced Block
     */
    registerBlockType('astp/standards-referenced', {
        title: __('Standards Referenced', 'astp-versioning'),
        icon: 'list-view',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            standardsList: {
                type: 'array',
                default: []
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, standardsList } = attributes;

            const addStandard = function() {
                const standards = [...standardsList];
                standards.push({
                    name: '',
                    description: ''
                });
                setAttributes({ standardsList: standards });
            };

            const removeStandard = function(index) {
                const standards = [...standardsList];
                standards.splice(index, 1);
                setAttributes({ standardsList: standards });
            };

            const updateStandard = function(index, key, value) {
                const standards = [...standardsList];
                standards[index][key] = value;
                setAttributes({ standardsList: standards });
            };

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Standards List', 'astp-versioning') },
                        el('div', { className: 'astp-standards-list-editor' },
                            standardsList.map(function(standard, index) {
                                return el('div', { className: 'astp-standard-item-editor', key: index },
                                    el(TextControl, {
                                        label: __('Standard Name', 'astp-versioning'),
                                        value: standard.name,
                                        onChange: function(value) {
                                            updateStandard(index, 'name', value);
                                        }
                                    }),
                                    el(TextControl, {
                                        label: __('Description', 'astp-versioning'),
                                        value: standard.description,
                                        onChange: function(value) {
                                            updateStandard(index, 'description', value);
                                        }
                                    }),
                                    el(Button, {
                                        isDestructive: true,
                                        isSmall: true,
                                        onClick: function() {
                                            removeStandard(index);
                                        }
                                    }, __('Remove', 'astp-versioning'))
                                );
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: addStandard
                            }, __('Add Standard', 'astp-versioning'))
                        )
                    )
                ),

                // Block content
                el('div', { className: 'astp-standards-referenced-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, __('Standards Referenced', 'astp-versioning')),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-standards-content-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter standards referenced introduction...', 'astp-versioning')
                    }),
                    standardsList.length > 0 && el('div', { className: 'astp-standards-list-preview' },
                        el('ul', {},
                            standardsList.map(function(standard, index) {
                                return el('li', { key: index },
                                    standard.name && el('strong', {}, standard.name + ': '),
                                    standard.description
                                );
                            })
                        )
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Required Update Deadlines Block
     */
    registerBlockType('astp/update-deadlines', {
        title: __('Required Update Deadlines', 'astp-versioning'),
        icon: 'calendar',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            deadlines: {
                type: 'array',
                default: []
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, deadlines } = attributes;

            const addDeadline = function() {
                const newDeadlines = [...deadlines];
                newDeadlines.push({
                    requirement: '',
                    date: '',
                    description: ''
                });
                setAttributes({ deadlines: newDeadlines });
            };

            const removeDeadline = function(index) {
                const newDeadlines = [...deadlines];
                newDeadlines.splice(index, 1);
                setAttributes({ deadlines: newDeadlines });
            };

            const updateDeadline = function(index, key, value) {
                const newDeadlines = [...deadlines];
                newDeadlines[index][key] = value;
                setAttributes({ deadlines: newDeadlines });
            };

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Deadlines', 'astp-versioning') },
                        el('div', { className: 'astp-deadlines-editor' },
                            deadlines.map(function(deadline, index) {
                                return el('div', { className: 'astp-deadline-item-editor', key: index },
                                    el(TextControl, {
                                        label: __('Requirement', 'astp-versioning'),
                                        value: deadline.requirement,
                                        onChange: function(value) {
                                            updateDeadline(index, 'requirement', value);
                                        }
                                    }),
                                    el(TextControl, {
                                        label: __('Deadline Date', 'astp-versioning'),
                                        value: deadline.date,
                                        onChange: function(value) {
                                            updateDeadline(index, 'date', value);
                                        },
                                        help: __('Format: YYYY-MM-DD', 'astp-versioning')
                                    }),
                                    el(TextControl, {
                                        label: __('Description', 'astp-versioning'),
                                        value: deadline.description,
                                        onChange: function(value) {
                                            updateDeadline(index, 'description', value);
                                        }
                                    }),
                                    el(Button, {
                                        isDestructive: true,
                                        isSmall: true,
                                        onClick: function() {
                                            removeDeadline(index);
                                        }
                                    }, __('Remove', 'astp-versioning'))
                                );
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: addDeadline
                            }, __('Add Deadline', 'astp-versioning'))
                        )
                    )
                ),

                // Block content
                el('div', { className: 'astp-update-deadlines-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, __('Required Update Deadlines', 'astp-versioning')),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-deadlines-content-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter deadlines introduction...', 'astp-versioning')
                    }),
                    deadlines.length > 0 && el('div', { className: 'astp-deadlines-table-preview' },
                        el('table', { className: 'astp-deadlines-table' },
                            el('thead', {},
                                el('tr', {},
                                    el('th', {}, __('Requirement', 'astp-versioning')),
                                    el('th', {}, __('Deadline', 'astp-versioning')),
                                    el('th', {}, __('Description', 'astp-versioning'))
                                )
                            ),
                            el('tbody', {},
                                deadlines.map(function(deadline, index) {
                                    return el('tr', { key: index },
                                        el('td', {}, deadline.requirement),
                                        el('td', {}, deadline.date),
                                        el('td', {}, deadline.description)
                                    );
                                })
                            )
                        )
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Certification Dependencies Block
     */
    registerBlockType('astp/certification-dependencies', {
        title: __('Certification Dependencies', 'astp-versioning'),
        icon: 'networking',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            dependencies: {
                type: 'array',
                default: []
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, dependencies } = attributes;

            const addDependency = function() {
                const newDependencies = [...dependencies];
                newDependencies.push({
                    name: '',
                    description: ''
                });
                setAttributes({ dependencies: newDependencies });
            };

            const removeDependency = function(index) {
                const newDependencies = [...dependencies];
                newDependencies.splice(index, 1);
                setAttributes({ dependencies: newDependencies });
            };

            const updateDependency = function(index, key, value) {
                const newDependencies = [...dependencies];
                newDependencies[index][key] = value;
                setAttributes({ dependencies: newDependencies });
            };

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Dependencies', 'astp-versioning') },
                        el('div', { className: 'astp-dependencies-editor' },
                            dependencies.map(function(dependency, index) {
                                return el('div', { className: 'astp-dependency-item-editor', key: index },
                                    el(TextControl, {
                                        label: __('Dependency Name', 'astp-versioning'),
                                        value: dependency.name,
                                        onChange: function(value) {
                                            updateDependency(index, 'name', value);
                                        }
                                    }),
                                    el(TextControl, {
                                        label: __('Description', 'astp-versioning'),
                                        value: dependency.description,
                                        onChange: function(value) {
                                            updateDependency(index, 'description', value);
                                        }
                                    }),
                                    el(Button, {
                                        isDestructive: true,
                                        isSmall: true,
                                        onClick: function() {
                                            removeDependency(index);
                                        }
                                    }, __('Remove', 'astp-versioning'))
                                );
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: addDependency
                            }, __('Add Dependency', 'astp-versioning'))
                        )
                    )
                ),

                // Block content
                el('div', { className: 'astp-certification-dependencies-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, __('Certification Dependencies', 'astp-versioning')),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-dependencies-content-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter dependencies introduction...', 'astp-versioning')
                    }),
                    dependencies.length > 0 && el('div', { className: 'astp-dependencies-list-preview' },
                        el('ul', {},
                            dependencies.map(function(dependency, index) {
                                return el('li', { key: index },
                                    dependency.name && el('strong', {}, dependency.name),
                                    dependency.description && el('p', {}, dependency.description)
                                );
                            })
                        )
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Technical Explanations Block
     */
    registerBlockType('astp/technical-explanations', {
        title: __('Technical Explanations', 'astp-versioning'),
        icon: 'editor-code',
        category: 'astp-tp',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            sections: {
                type: 'array',
                default: []
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, sections } = attributes;

            const addSection = function() {
                const newSections = [...sections];
                newSections.push({
                    title: '',
                    content: ''
                });
                setAttributes({ sections: newSections });
            };

            const removeSection = function(index) {
                const newSections = [...sections];
                newSections.splice(index, 1);
                setAttributes({ sections: newSections });
            };

            const updateSection = function(index, key, value) {
                const newSections = [...sections];
                newSections[index][key] = value;
                setAttributes({ sections: newSections });
            };

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Explanation Sections', 'astp-versioning') },
                        el('div', { className: 'astp-explanation-sections-editor' },
                            sections.map(function(section, index) {
                                return el('div', { className: 'astp-explanation-section-editor', key: index },
                                    el(TextControl, {
                                        label: __('Section Title', 'astp-versioning'),
                                        value: section.title,
                                        onChange: function(value) {
                                            updateSection(index, 'title', value);
                                        }
                                    }),
                                    el(Button, {
                                        isDestructive: true,
                                        isSmall: true,
                                        onClick: function() {
                                            removeSection(index);
                                        }
                                    }, __('Remove Section', 'astp-versioning'))
                                );
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: addSection
                            }, __('Add Section', 'astp-versioning'))
                        )
                    )
                ),

                // Block content
                el('div', { className: 'astp-technical-explanations-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, __('Technical Explanations', 'astp-versioning')),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-explanations-introduction-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter explanations introduction...', 'astp-versioning')
                    }),
                    sections.map(function(section, index) {
                        return el('div', { className: 'astp-explanation-section-editor', key: index },
                            el('h3', { className: 'astp-explanation-title-editor' }, 
                                section.title || __('Section Title', 'astp-versioning')
                            ),
                            el(RichText, {
                                tagName: 'div',
                                className: 'astp-explanation-content-editor',
                                value: section.content,
                                onChange: function(value) {
                                    updateSection(index, 'content', value);
                                },
                                placeholder: __('Enter explanation content...', 'astp-versioning')
                            })
                        );
                    })
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * CCG Revision History Block
     */
    registerBlockType('astp/ccg-revision-history', {
        title: __('CCG Revision History', 'astp-versioning'),
        icon: 'backup',
        category: 'astp-ccg',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            showAllHistory: {
                type: 'boolean',
                default: true
            },
            limit: {
                type: 'number',
                default: 5
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, showAllHistory, limit } = attributes;

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('CCG Revision History Settings', 'astp-versioning') },
                        el(ToggleControl, {
                            label: __('Show All History', 'astp-versioning'),
                            checked: showAllHistory,
                            onChange: function(value) {
                                setAttributes({ showAllHistory: value });
                            },
                            help: showAllHistory ? 
                                __('Showing all CCG revision history entries', 'astp-versioning') : 
                                __('Showing limited number of entries', 'astp-versioning')
                        }),
                        !showAllHistory && el(RangeControl, {
                            label: __('Number of entries to show', 'astp-versioning'),
                            value: limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),

                // Block content
                el('div', { className: 'astp-revision-history-editor astp-ccg-history-editor' },
                    el('h2', { className: 'astp-section-title-editor astp-ccg-title' }, 
                        __('Certification Companion Guide Version History', 'astp-versioning')
                    ),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-revision-introduction-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter CCG revision history introduction...', 'astp-versioning')
                    }),
                    el('div', { className: 'astp-revision-history-notice' },
                        __('CCG revision history will be automatically generated.', 'astp-versioning')
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * TP Revision History Block
     */
    registerBlockType('astp/tp-revision-history', {
        title: __('TP Revision History', 'astp-versioning'),
        icon: 'backup',
        category: 'astp-tp',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            showAllHistory: {
                type: 'boolean',
                default: true
            },
            limit: {
                type: 'number',
                default: 5
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, showAllHistory, limit } = attributes;

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('TP Revision History Settings', 'astp-versioning') },
                        el(ToggleControl, {
                            label: __('Show All History', 'astp-versioning'),
                            checked: showAllHistory,
                            onChange: function(value) {
                                setAttributes({ showAllHistory: value });
                            },
                            help: showAllHistory ? 
                                __('Showing all Test Procedure revision history entries', 'astp-versioning') : 
                                __('Showing limited number of entries', 'astp-versioning')
                        }),
                        !showAllHistory && el(RangeControl, {
                            label: __('Number of entries to show', 'astp-versioning'),
                            value: limit,
                            onChange: function(value) {
                                setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 20
                        })
                    )
                ),

                // Block content
                el('div', { className: 'astp-revision-history-editor astp-tp-history-editor' },
                    el('h2', { className: 'astp-section-title-editor astp-tp-title' }, 
                        __('Test Procedure Version History', 'astp-versioning')
                    ),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-revision-introduction-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter Test Procedure revision history introduction...', 'astp-versioning')
                    }),
                    el('div', { className: 'astp-revision-history-notice' },
                        __('Test Procedure revision history will be automatically generated.', 'astp-versioning')
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

    /**
     * Revision History Block (Legacy)
     */
    registerBlockType('astp/revision-history', {
        title: __('Revision History', 'astp-versioning'),
        icon: 'backup',
        category: 'astp-test-method',
        supports: {
            html: false
        },
        attributes: {
            content: {
                type: 'string',
                default: ''
            },
            showAllHistory: {
                type: 'boolean',
                default: true
            },
            documentType: {
                type: 'string',
                default: ''
            }
        },

        // Block edit function
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { content, showAllHistory, documentType } = attributes;

            return el(Fragment, {},
                // Inspector controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Revision History Settings', 'astp-versioning') },
                        el(ToggleControl, {
                            label: __('Show All History', 'astp-versioning'),
                            checked: showAllHistory,
                            onChange: function(value) {
                                setAttributes({ showAllHistory: value });
                            },
                            help: showAllHistory ? 
                                __('Showing all revision history entries', 'astp-versioning') : 
                                __('Showing only the most recent 5 entries', 'astp-versioning')
                        }),
                        el('div', { className: 'astp-document-type-selector' },
                            el('label', {}, __('Document Type', 'astp-versioning')),
                            el('select', {
                                value: documentType,
                                onChange: function(e) {
                                    setAttributes({ documentType: e.target.value });
                                }
                            },
                                el('option', { value: '' }, __('All Versions', 'astp-versioning')),
                                el('option', { value: 'ccg' }, __('Certification Companion Guide', 'astp-versioning')),
                                el('option', { value: 'tp' }, __('Test Procedure', 'astp-versioning'))
                            ),
                            el('p', { className: 'components-base-control__help' },
                                __('Select a document type to show only specific version history', 'astp-versioning')
                            )
                        )
                    )
                ),

                // Block content
                el('div', { className: 'astp-revision-history-editor' },
                    el('h2', { className: 'astp-section-title-editor' }, 
                        documentType === 'ccg' ? 
                            __('Certification Companion Guide Version History', 'astp-versioning') :
                            (documentType === 'tp' ? 
                                __('Test Procedure Version History', 'astp-versioning') :
                                __('Version History', 'astp-versioning'))
                    ),
                    el(RichText, {
                        tagName: 'div',
                        className: 'astp-revision-introduction-editor',
                        value: content,
                        onChange: function(value) {
                            setAttributes({ content: value });
                        },
                        placeholder: __('Enter revision history introduction...', 'astp-versioning')
                    }),
                    el('div', { className: 'astp-revision-history-notice' },
                        __('Revision history will be automatically generated based on version history.', 'astp-versioning')
                    )
                )
            );
        },

        // Block save function - outputs placeholder, replaced with PHP render
        save: function() {
            return null; // Server-side rendering
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.i18n
); 