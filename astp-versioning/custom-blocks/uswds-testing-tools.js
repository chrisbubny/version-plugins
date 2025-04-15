(function ({ blocks, element, blockEditor }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, InspectorControls, useBlockProps, MediaUpload } = blockEditor;
    const { Button, PanelBody, TextControl, ToggleControl } = wp.components;

    registerBlockType('uswds-gutenberg/testing-tools-block', {
        title: 'Testing Tools Block',
        icon: 'admin-tools',
        category: 'astp-tp',
        attributes: {
            title: { type: 'string', default: 'Testing Tools' },
            subtitle: { 
                type: 'string', 
                default: '§ 170.315(g)(10) Standardized API for patient and population services' 
            },
            sections: {
                type: 'array',
                default: [
                    { 
                        heading: 'New Section', 
                        eyebrowText: '',
                        content: 'Section content here...', 
                        buttons: [],
                        imageUrl: ''
                    } 
                ]
            },
            // New attribute for documentation links
            documentationLinks: {
                type: 'array',
                default: [
                    {
                        title: 'Sample Documentation',
                        url: '',
                        isExternal: false
                    }
                ]
            }
        },
        edit: ({ attributes, setAttributes }) => {
            const blockProps = useBlockProps();

            // Function to check if URL is external
            const isExternalUrl = (url) => {
                if (!url) return false;
                
                try {
                    const currentHost = window.location.hostname;
                    const urlObj = new URL(url);
                    return urlObj.hostname !== currentHost;
                } catch (e) {
                    return false;
                }
            };

            const updateSection = (index, key, value) => {
                const updatedSections = [...attributes.sections];
                updatedSections[index] = { ...updatedSections[index], [key]: value };
                setAttributes({ sections: updatedSections });
            };

            const addSection = () => {
                setAttributes({ 
                    sections: [...attributes.sections, 
                        { 
                            heading: 'New Section', 
                            eyebrowText: '',
                            content: 'Section content here...', 
                            buttons: [], 
                            imageUrl: '' 
                        } 
                    ] 
                });
            };

            const removeSection = (index) => {
                const updatedSections = attributes.sections.filter((_, i) => i !== index);
                setAttributes({ sections: updatedSections });
            };

            const addButton = (sectionIndex) => {
                const updatedSections = [...attributes.sections];
                updatedSections[sectionIndex].buttons.push({ 
                    label: '', 
                    url: '', 
                    enabled: true,
                    isSecondary: false,
                    isThirdParty: false
                });
                setAttributes({ sections: updatedSections });
            };

            const updateButton = (sectionIndex, buttonIndex, key, value) => {
                const updatedSections = [...attributes.sections];
                updatedSections[sectionIndex].buttons[buttonIndex] = { 
                    ...updatedSections[sectionIndex].buttons[buttonIndex], 
                    [key]: value 
                };
                setAttributes({ sections: updatedSections });
            };

            const removeButton = (sectionIndex, buttonIndex) => {
                const updatedSections = [...attributes.sections];
                updatedSections[sectionIndex].buttons = updatedSections[sectionIndex].buttons.filter((_, i) => i !== buttonIndex);
                setAttributes({ sections: updatedSections });
            };

            // New functions for documentation links
            const addDocumentationLink = () => {
                const updatedLinks = [...attributes.documentationLinks, {
                    title: 'New Documentation Link',
                    url: '',
                    isExternal: false
                }];
                setAttributes({ documentationLinks: updatedLinks });
            };

            const updateDocumentationLink = (index, key, value) => {
                const updatedLinks = [...attributes.documentationLinks];
                updatedLinks[index] = { ...updatedLinks[index], [key]: value };
                
                // Update isExternal automatically when URL changes
                if (key === 'url') {
                    updatedLinks[index].isExternal = isExternalUrl(value);
                }
                
                setAttributes({ documentationLinks: updatedLinks });
            };

            const removeDocumentationLink = (index) => {
                const updatedLinks = attributes.documentationLinks.filter((_, i) => i !== index);
                setAttributes({ documentationLinks: updatedLinks });
            };

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Block Settings', initialOpen: true },
                        el(Button, { onClick: addSection, className: 'button button-primary' }, '+ Add Section')
                    ),
                    // Documentation Links Panel
                    el(PanelBody, { 
                        title: 'Documentation Links', 
                        initialOpen: true
                    },
                        el('div', { className: 'documentation-links-panel' },
                            el('p', {}, 'Add documentation links for the sidebar navigation'),
                            el(Button, { 
                                onClick: addDocumentationLink, 
                                className: 'button button-primary', 
                                isSmall: true,
                                style: { marginBottom: '15px' }
                            }, '+ Add Documentation Link'),
                            
                            attributes.documentationLinks.map((link, index) => 
                                el('div', { 
                                    key: `doc-link-${index}`,
                                    className: 'doc-link-panel',
                                    style: { 
                                        marginBottom: '15px', 
                                        padding: '10px', 
                                        backgroundColor: '#f8f8f8',
                                        border: '1px solid #ddd',
                                        borderRadius: '4px'
                                    }
                                },
                                    el('h4', { style: { margin: '0 0 10px 0' } }, `Link ${index + 1}`),
                                    
                                    el(TextControl, {
                                        label: 'Title',
                                        value: link.title,
                                        onChange: (value) => updateDocumentationLink(index, 'title', value)
                                    }),
                                    
                                    el(TextControl, {
                                        label: 'URL',
                                        value: link.url,
                                        onChange: (value) => updateDocumentationLink(index, 'url', value)
                                    }),
                                    
                                    // Display external link indicator if the URL is external
                                    link.url && link.isExternal && 
                                    el('div', { 
                                        style: { 
                                            marginTop: '5px', 
                                            marginBottom: '10px',
                                            fontSize: '12px',
                                            color: '#666'
                                        } 
                                    }, 
                                        el('span', {}, 'External link will show an icon'),
                                        el('img', { 
                                            src: '/wp-content/themes/healthit/assets/dist/icons/third-party-link-icon.svg',
                                            alt: 'External link icon',
                                            width: 14,
                                            height: 14,
                                            style: { 
                                                verticalAlign: 'middle',
                                                marginLeft: '5px'
                                            }
                                        })
                                    ),
                                    
                                    el(Button, { 
                                        onClick: () => removeDocumentationLink(index), 
                                        className: 'button button-link-delete',
                                        isDestructive: true,
                                        isSmall: true,
                                        style: { marginTop: '10px' }
                                    }, 'Remove Link')
                                )
                            ),
                            attributes.documentationLinks.length === 0 && 
                                el('p', { style: { fontStyle: 'italic' } }, 'No documentation links yet. Click "Add Documentation Link" to add one.')
                        )
                    ),
                    // Add button management panels
                    attributes.sections.map((section, sectionIndex) => 
                        el(PanelBody, { 
                            title: `${section.heading || 'Section'} - Buttons`, 
                            initialOpen: false,
                            key: `section-buttons-${sectionIndex}`
                        },
                            el(Button, { 
                                onClick: () => addButton(sectionIndex), 
                                className: 'button button-primary', 
                                isSmall: true,
                                style: { marginBottom: '10px' }
                            }, '+ Add Button'),
                            
                            section.buttons.map((button, buttonIndex) => 
                                el('div', { 
                                    key: `button-${buttonIndex}`,
                                    className: 'button-panel',
                                    style: { 
                                        marginBottom: '15px', 
                                        padding: '10px', 
                                        backgroundColor: '#f8f8f8',
                                        border: '1px solid #ddd',
                                        borderRadius: '4px'
                                    }
                                },
                                    el('h4', { style: { margin: '0 0 10px 0' } }, `Button ${buttonIndex + 1}`),
                                    
                                    el(TextControl, {
                                        label: 'Button Label',
                                        value: button.label,
                                        onChange: (value) => updateButton(sectionIndex, buttonIndex, 'label', value)
                                    }),
                                    
                                    el(TextControl, {
                                        label: 'Button URL',
                                        value: button.url,
                                        onChange: (value) => updateButton(sectionIndex, buttonIndex, 'url', value)
                                    }),
                                    
                                    el(ToggleControl, {
                                        label: 'Enabled',
                                        checked: button.enabled,
                                        onChange: (value) => updateButton(sectionIndex, buttonIndex, 'enabled', value)
                                    }),
                                    
                                    el(ToggleControl, {
                                        label: 'Secondary Style',
                                        checked: button.isSecondary || false,
                                        onChange: (value) => updateButton(sectionIndex, buttonIndex, 'isSecondary', value)
                                    }),
                                    
                                    el(ToggleControl, {
                                        label: 'Third Party',
                                        checked: button.isThirdParty || false,
                                        onChange: (value) => updateButton(sectionIndex, buttonIndex, 'isThirdParty', value)
                                    }),
                                    
                                    el(Button, { 
                                        onClick: () => removeButton(sectionIndex, buttonIndex), 
                                        className: 'button button-link-delete',
                                        isDestructive: true,
                                        isSmall: true,
                                        style: { marginTop: '10px' }
                                    }, 'Remove Button')
                                )
                            ),
                            section.buttons.length === 0 && 
                                el('p', { style: { fontStyle: 'italic' } }, 'No buttons yet. Click "Add Button" to add one.')
                        )
                    )
                ),
                el('div', { ...blockProps, className: 'grid-container' },
                    el('div', { className: 'testing-tools-container' },
                        el(RichText, {
                            tagName: 'h2',
                            value: attributes.title,
                            onChange: (value) => setAttributes({ title: value }),
                            className: 'testing-tools-title'
                        }),
                        el(RichText, {
                            tagName: 'p',
                            value: attributes.subtitle,
                            onChange: (value) => setAttributes({ subtitle: value }),
                            className: 'leading-p',
                            placeholder: 'Enter subtitle...'
                        }),
                        // Documentation Links Preview
                        el('div', {
                            className: 'documentation-links-preview',
                            style: {
                                marginBottom: '20px',
                                padding: '15px',
                                backgroundColor: '#f8f9fa',
                                borderRadius: '4px'
                            }
                        },
                            el('h4', {}, 'TEST TOOL DOCUMENTATION'),
                            el('p', { style: { fontStyle: 'italic', fontSize: '13px' } }, 
                                'Documentation links will display in the sidebar. Manage them in the "Documentation Links" panel in the Block Settings.'
                            ),
                            attributes.documentationLinks.length > 0 && 
                                el('ul', { style: { marginLeft: '20px' } },
                                    attributes.documentationLinks.map((link, index) => 
                                        el('li', { key: index, style: { marginBottom: '8px' } },
                                            `${link.title}${link.url ? ' - ' + link.url : ''}`,
                                            link.isExternal && el('span', { 
                                                style: {
                                                    marginLeft: '5px',
                                                    color: '#666',
                                                    fontSize: '12px'
                                                }
                                            }, '(external)')
                                        )
                                    )
                                )
                        ),
                        // Full width section editing, without the sidebar
                        attributes.sections.map((section, sectionIndex) =>
                            el('div', { 
                                key: sectionIndex, 
                                className: 'border-1px border-base-lighter padding-105 testing-tools-section margin-bottom-2' 
                            },
                                // Section header with remove button
                                el('div', { 
                                    className: 'section-header',
                                    style: {
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        marginBottom: '15px'
                                    }
                                },
                                    el(Button, { 
                                        onClick: () => removeSection(sectionIndex), 
                                        className: 'usa-button usa-button--outline',
                                        isDestructive: true
                                    }, 'Remove Section')
                                ),
                                
                                // Image upload
                                el(MediaUpload, {
                                    onSelect: (media) => updateSection(sectionIndex, 'imageUrl', media.url),
                                    allowedTypes: ['image'],
                                    render: ({ open }) => 
                                        el('div', { style: { marginBottom: '15px' } },
                                            section.imageUrl && el('img', { 
                                                src: section.imageUrl, 
                                                style: { 
                                                    maxWidth: '100%', 
                                                    height: 'auto',
                                                    marginBottom: '10px',
                                                    maxHeight: '200px'
                                                } 
                                            }),
                                            el(Button, { onClick: open, className: 'usa-button upload-icon-button' }, 
                                                section.imageUrl ? 'Change Image' : 'Upload Image'
                                            )
                                        )
                                }),
                                
                                // Section heading
                                el(RichText, {
                                    tagName: 'h3',
                                    value: section.heading || 'Default Heading',
                                    onChange: (value) => updateSection(sectionIndex, 'heading', value),
                                    className: 'section-heading',
                                    placeholder: 'Enter section heading...',
                                    style: { marginBottom: '15px' }
                                }),
                                
                                // Eyebrow text
                                el(TextControl, {
                                    value: section.eyebrowText || '',
                                    onChange: (value) => updateSection(sectionIndex, 'eyebrowText', value),
                                    placeholder: 'Optional eyebrow text above content',
                                    style: { marginBottom: '15px' }
                                }),
                                
                                // Section content (main description)
                                el('div', { style: { marginBottom: '20px' } },
                                    el('p', { 
                                        style: { 
                                            fontSize: '14px', 
                                            fontWeight: 'bold', 
                                            marginBottom: '8px',
                                            color: '#555'
                                        } 
                                    }, 'Section Content:'),
                                    el(wp.blockEditor.RichText, {
                                        tagName: 'p',
                                        value: section.content || '',
                                        onChange: (value) => updateSection(sectionIndex, 'content', value),
                                        placeholder: 'Enter section content here...',
                                        keepPlaceholderOnFocus: true,
                                        style: {
                                            minHeight: '100px',
                                            padding: '10px',
                                            border: '1px solid #ddd',
                                            borderRadius: '4px',
                                            backgroundColor: '#fff'
                                        }
                                    })
                                ),
                                
                                // Button preview
                                section.buttons && section.buttons.length > 0 && el('div', { 
                                    className: 'section-buttons-preview', 
                                    style: { marginTop: '20px' } 
                                },
                                    el('div', { className: 'button-preview-container' },
                                        section.buttons.filter(button => button.enabled).map((button, buttonIndex) => {
                                            let buttonClasses = 'usa-button btn btn--icon-right';
                                            if (button.isSecondary) {
                                                buttonClasses = 'usa-button usa-button--outline btn btn-secondary btn--icon-right';
                                            }
                                            if (button.isThirdParty) {
                                                buttonClasses += ' btn--third-party';
                                            }
                                            
                                            return el('button', { 
                                                key: buttonIndex,
                                                className: buttonClasses,
                                                style: { marginRight: '10px', marginBottom: '10px' },
                                                onClick: (e) => e.preventDefault()
                                            }, button.label || 'Button');
                                        }),
                                        el('p', { 
                                            style: { 
                                                marginTop: '10px', 
                                                fontStyle: 'italic',
                                                fontSize: '13px'
                                            } 
                                        }, 'Manage buttons in the sidebar settings →')
                                    )
                                ),
                                
                                // Info about button management
                                section.buttons.length === 0 && el('div', { 
                                    className: 'buttons-info',
                                    style: {
                                        padding: '15px',
                                        backgroundColor: '#f8f9fa',
                                        borderRadius: '4px',
                                        marginTop: '20px'
                                    }
                                },
                                    el('p', { style: { margin: 0 } }, 
                                        'You can add and manage buttons in the sidebar settings. ' + 
                                        'Click the "Block" tab in the right sidebar and look for ' +
                                        `"${section.heading || 'Section'} - Buttons" panel.`
                                    )
                                )
                            )
                        ),
                        // Add Section button at the bottom
                        el(Button, { 
                            onClick: addSection, 
                            className: 'usa-button',
                            style: {
                                display: 'block',
                                margin: '20px auto'
                            }
                        }, '+ Add New Section')
                    )
                )
            );
        },
        save: () => {
            // Return null because we're using server-side rendering
            return null;
        }
    });
})(window.wp);