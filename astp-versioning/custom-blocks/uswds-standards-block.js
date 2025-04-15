(function ({ blocks, element, blockEditor, components }) {
    const { registerBlockType } = blocks;
    const { createElement: el, Fragment } = element;
    const { RichText, useBlockProps, InspectorControls } = blockEditor;
    const { PanelBody, ToggleControl, Button, TextControl } = components;

    // Unregister the existing block if it's already registered
    if (blocks.getBlockType('uswds-gutenberg/standards-block')) {
        blocks.unregisterBlockType('uswds-gutenberg/standards-block');
    }

    registerBlockType('uswds-gutenberg/standards-block', {
        title: 'Standards Block',
        icon: 'book',
        category: 'astp-ccg',
        attributes: {
            title: { type: 'string', default: 'Standards & References' },
            heading: { type: 'string', default: 'The following standards are referenced within the certification criteria for:' },
            subheading: { type: 'string', default: '§ 170.315(g)(10) Standardized API for patient and population services' },
            buttonEnabled: { type: 'boolean', default: true },
            buttonText: { type: 'string', default: 'SVAP Approved Version' },
            buttonURL: { type: 'string', default: '#' },
            viewByFilterEnabled: { type: 'boolean', default: true },
            sortByDateFilterEnabled: { type: 'boolean', default: true },            
            sections: {
                type: 'array',
                default: [
                    {
                        id: 's1',
                        standard_reference_number: '§ 170.215(b)(1)(i)',
                        standard_reference_title: 'HL7® Version 4.0.1 FHIR® Release 4',
                        standard_reference_url: '', // URL attribute for the title
                        external_link: true, // Flag for external link
                        expiryDate: '2026-01-01',
                        referencedIn: [],
                        svapApprovedVersions: []
                    }
                ]
            }
        },
        edit: ({ attributes, setAttributes }) => {
            const blockProps = useBlockProps();
            const { title, heading, subheading, buttonEnabled, buttonText, buttonURL, viewByFilterEnabled, sortByDateFilterEnabled, sections } = attributes;

            // Function to check if URL is external
            const isExternalUrl = (url) => {
                // Skip empty URLs
                if (!url) return false;
                
                try {
                    // Parse the URL
                    const currentHost = window.location.hostname;
                    const urlObj = new URL(url);
                    
                    // If hostname is different, it's external
                    return urlObj.hostname !== currentHost;
                } catch (e) {
                    // If URL is invalid, consider it internal
                    return false;
                }
            };

            // Add new section
            const addSection = () => {
                const newId = `s${sections.length + 1}`;
                
                setAttributes({
                    sections: [...sections, {
                        id: newId,
                        standard_reference_number: '§ 170.215(a)(1)',
                        standard_reference_title: 'New Standards',
                        standard_reference_url: '', // Initialize with empty URL
                        external_link: false, // Default to internal link
                        expiryDate: '', 
                        referencedIn: [],
                        svapApprovedVersions: []
                    }]
                });
            };

            // Remove section
            const removeSection = (index) => {
                const updatedSections = [...sections];
                updatedSections.splice(index, 1);
                setAttributes({ sections: updatedSections });
            };

            // Add referenced source field
            const addReferencedSource = (index) => {
                const updatedSections = JSON.parse(JSON.stringify(sections)); 
                if (!updatedSections[index].referencedIn) {
                    updatedSections[index].referencedIn = [];
                }
                updatedSections[index].referencedIn.push({
                    number: '',
                    title: ''
                });
                setAttributes({ sections: updatedSections });
            };

            // Remove referenced source field
            const removeReferencedSource = (index, fieldIndex) => {
                const updatedSections = JSON.parse(JSON.stringify(sections)); 
                updatedSections[index].referencedIn.splice(fieldIndex, 1);
                setAttributes({ sections: updatedSections });
            };

            // Handle changes in the referenced source input
            const handleReferencedSourceChange = (index, fieldIndex, key, value) => {
                const updatedSections = JSON.parse(JSON.stringify(sections)); 
                
                // Make sure the reference is an object with number and title
                if (typeof updatedSections[index].referencedIn[fieldIndex] === 'string') {
                    // Convert old string format to new object format
                    const oldValue = updatedSections[index].referencedIn[fieldIndex];
                    updatedSections[index].referencedIn[fieldIndex] = {
                        number: oldValue,
                        title: ''
                    };
                }
                
                // Update the specified key
                updatedSections[index].referencedIn[fieldIndex][key] = value;
                setAttributes({ sections: updatedSections });
            };

            // Handle date change
            const handleDateChange = (index, value) => {
                const updatedSections = JSON.parse(JSON.stringify(sections)); 
                updatedSections[index].expiryDate = value;
                setAttributes({ sections: updatedSections });
            };

            // Add SVAP-approved Version entry
            const addSvapVersion = (index) => {
                const updatedSections = JSON.parse(JSON.stringify(sections));
                if (!updatedSections[index].svapApprovedVersions) {
                    updatedSections[index].svapApprovedVersions = [];
                }
                updatedSections[index].svapApprovedVersions.push({ 
                    title: '', 
                    url: '', 
                    external_link: false, // Add external_link flag for SVAP links too
                    date: '',      
                    subtext: ''    
                });
                setAttributes({ sections: updatedSections });
            };

            // Remove SVAP-approved Version entry
            const removeSvapVersion = (index, fieldIndex) => {
                const updatedSections = JSON.parse(JSON.stringify(sections));
                updatedSections[index].svapApprovedVersions.splice(fieldIndex, 1);
                setAttributes({ sections: updatedSections });
            };

            // Handle change in SVAP-approved Version fields
            const handleSvapChange = (index, fieldIndex, key, value) => {
                const updatedSections = JSON.parse(JSON.stringify(sections));
                updatedSections[index].svapApprovedVersions[fieldIndex][key] = value;
                
                // If updating URL, also update external_link flag
                if (key === 'url') {
                    updatedSections[index].svapApprovedVersions[fieldIndex].external_link = 
                        isExternalUrl(value);
                }
                
                setAttributes({ sections: updatedSections });
            };

            // Handle URL change for standard reference title
            const handleUrlChange = (index, value) => {
                const updatedSections = JSON.parse(JSON.stringify(sections));
                updatedSections[index].standard_reference_url = value;
                // Set external_link flag based on URL
                updatedSections[index].external_link = isExternalUrl(value);
                setAttributes({ sections: updatedSections });
            };

            return el(Fragment, null,
                // Sidebar settings
                el(InspectorControls, null,
                    el(PanelBody, { title: 'General Settings', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Enable Button',
                            checked: buttonEnabled,
                            onChange: (value) => setAttributes({ buttonEnabled: value })
                        }),
                        el(TextControl, {
                            label: 'Button Text',
                            value: buttonText,
                            onChange: (value) => setAttributes({ buttonText: value })
                        }),
                        el(TextControl, {
                            label: 'Button URL',
                            value: buttonURL,
                            onChange: (value) => setAttributes({ buttonURL: value })
                        })
                    ),
                    el(PanelBody, { title: 'Filters Settings', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Enable View By Filter',
                            checked: viewByFilterEnabled,
                            onChange: (value) => setAttributes({ viewByFilterEnabled: value })
                        }),
                        el(ToggleControl, {
                            label: 'Enable Sorting By Date Filter',
                            checked: sortByDateFilterEnabled,
                            onChange: (value) => setAttributes({ sortByDateFilterEnabled: value })
                        })
                    )
                ),
                // Block content
                el('div', { ...blockProps, className: 'grid-container' },
                    el('div', { className: 'standards-block' },
                        el(RichText, {
                            tagName: 'h2',
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            className: 'text-bold'
                        }),
                        el(RichText, {
                            tagName: 'p',
                            value: heading,
                            onChange: (value) => setAttributes({ heading: value }),
                            className: 'text-left'
                        }),
                        el(RichText, {
                            tagName: 'p',
                            value: subheading,
                            onChange: (value) => setAttributes({ subheading: value }),
                            className: 'text-bold'
                        }),
                        sections.map((section, index) =>
                            el('div', { key: section.id, className: 'uswds-standard-item' }, 
                                el(Fragment, { key: section.id },
                                    el(RichText, {
                                        tagName: 'p',
                                        value: section.standard_reference_number || section.standard_refernce_number, // Handle both property names
                                        onChange: (value) => {
                                            const updatedSections = JSON.parse(JSON.stringify(sections));
                                            updatedSections[index].standard_reference_number = value; // Use correct property name
                                            setAttributes({ sections: updatedSections });
                                        },
                                        className: 'text-bold margin-top-0 margin-bottom-0'
                                    }),
                                    // Standard Title with URL field
                                    el('div', { className: 'standard-title-container' },
                                        el(RichText, {
                                            tagName: 'h4',
                                            value: section.standard_reference_title || section.standard_refernce_title, // Handle both property names
                                            onChange: (value) => {
                                                const updatedSections = JSON.parse(JSON.stringify(sections));
                                                updatedSections[index].standard_reference_title = value; // Use correct property name
                                                setAttributes({ sections: updatedSections });
                                            },
                                            className: 'uswds-standard-reference-title margin-top-0 margin-bottom-0'
                                        }),
                                        el('div', { className: 'standard-title-url margin-top-1' },
                                            el('label', null, 'Title URL:'),
                                            el(TextControl, {
                                                value: section.standard_reference_url || '',
                                                onChange: (value) => handleUrlChange(index, value),
                                                placeholder: 'Enter URL for the title (e.g., https://example.com)',
                                                className: 'standard-url-field'
                                            }),
                                            // Show a preview of external link indicator if URL is external
                                            section.standard_reference_url && section.external_link && 
                                            el('div', { className: 'external-link-preview' },
                                                el('span', null, 'External link will display with an icon: '),
                                                el('img', { 
                                                    src: '/wp-content/themes/healthit/assets/dist/icons/third-party-link-icon.svg',
                                                    alt: 'External link icon',
                                                    width: 14,
                                                    height: 14,
                                                    style: { verticalAlign: 'middle' }
                                                })
                                            )
                                        )
                                    ),

                                    // Date Input Field
                                    el('div', { className: 'uswds-date-field margin-top-2 margin-bottom-2' },
                                        el('label', { className: 'margin-right-2' }, 'Standard expires on:'),
                                        el('input', {
                                            type: 'date',
                                            value: section.expiryDate || '',
                                            onChange: (event) => handleDateChange(index, event.target.value),
                                            className: 'margin-top-2'
                                        })
                                    ),
                                    el('h6', { className: 'margin-right-2 margin-top-2 margin-bottom-0' }, 'Referenced in the Following:'),
                                    el(Button, {
                                        className: 'btn usa-button-secondary margin-top-2',
                                        onClick: () => addReferencedSource(index)
                                    }, '+ Add Referenced Source'),
                                    
                                    // Table for "Referenced In the Following" with Number and Title
                                    el('table', { className: 'usa-table' },
                                        el('thead', null,
                                            el('tr', null,
                                                el('th', null, 'Paragraph Number'),
                                                el('th', null, 'Paragraph Title'),
                                                el('th', null, 'Actions')
                                            )
                                        ),
                                        el('tbody', null,
                                            section.referencedIn && section.referencedIn.map((reference, fieldIndex) => {
                                                // Handle both string (old format) and object (new format)
                                                const refNumber = typeof reference === 'string' ? reference : (reference.number || '');
                                                const refTitle = typeof reference === 'string' ? '' : (reference.title || '');
                                                
                                                return el('tr', { key: fieldIndex },
                                                    el('td', null,
                                                        el(TextControl, {
                                                            value: refNumber,
                                                            onChange: (value) => handleReferencedSourceChange(index, fieldIndex, 'number', value),
                                                            placeholder: 'e.g., Paragraph (g)(10)(i)(A)'
                                                        })
                                                    ),
                                                    el('td', null,
                                                        el(TextControl, {
                                                            value: refTitle,
                                                            onChange: (value) => handleReferencedSourceChange(index, fieldIndex, 'title', value),
                                                            placeholder: 'e.g., Data response - single patient'
                                                        })
                                                    ),
                                                    el('td', null,
                                                        el(Button, {
                                                            className: 'usa-button-secondary border-1px',
                                                            onClick: () => removeReferencedSource(index, fieldIndex),
                                                            isDestructive: true
                                                        }, 'Remove')
                                                    )
                                                );
                                            })
                                        )
                                    ),
                                    el('h6', null, 'SVAP-approved Versions:'),
                                    el(Button, {className: 'usa-button btn', onClick: () => addSvapVersion(index) }, '+ Add SVAP-approved Version'),

                                    // Table for SVAP-approved Versions with all fields
                                    el('table', { className: 'usa-table' },
                                        el('thead', null,
                                            el('tr', null,
                                                el('th', null, 'Title'),
                                                el('th', null, 'URL'),
                                                el('th', null, 'Date (e.g., JUNE 2021)'),
                                                el('th', null, 'Subtext'),
                                                el('th', null, 'Actions')
                                            )
                                        ),
                                        el('tbody', null,
                                            section.svapApprovedVersions && section.svapApprovedVersions.map((svap, fieldIndex) =>
                                                el('tr', { key: fieldIndex },
                                                    el('td', null,
                                                        el(TextControl, {
                                                            value: svap.title,
                                                            onChange: (value) => handleSvapChange(index, fieldIndex, 'title', value)
                                                        })
                                                    ),
                                                    el('td', null,
                                                        el('div', { className: 'svap-url-field' },
                                                            el(TextControl, {
                                                                value: svap.url,
                                                                onChange: (value) => handleSvapChange(index, fieldIndex, 'url', value)
                                                            }),
                                                            // Show indicator for external links in SVAP URLs too
                                                            svap.url && isExternalUrl(svap.url) && 
                                                            el('img', { 
                                                                src: '/wp-content/themes/healthit/assets/dist/icons/third-party-link-icon.svg',
                                                                alt: 'External link icon',
                                                                width: 14,
                                                                height: 14,
                                                                style: { verticalAlign: 'middle', marginLeft: '5px' }
                                                            })
                                                        )
                                                    ),
                                                    el('td', null,
                                                        el(TextControl, {
                                                            value: svap.date,
                                                            onChange: (value) => handleSvapChange(index, fieldIndex, 'date', value),
                                                            placeholder: 'e.g., JUNE 2021'
                                                        })
                                                    ),
                                                    el('td', null,
                                                        el(TextControl, {
                                                            value: svap.subtext,
                                                            onChange: (value) => handleSvapChange(index, fieldIndex, 'subtext', value),
                                                            placeholder: 'e.g., Adoption expires...'
                                                        })
                                                    ),
                                                    el('td', null,
                                                        el(Button, {
                                                            className: 'usa-button-secondary border-1px',
                                                            onClick: () => removeSvapVersion(index, fieldIndex),
                                                            isDestructive: true
                                                        }, 'Remove')
                                                    )
                                                )
                                            )
                                        )
                                    ),
                                    // Remove section button
                                    el(Button, { className: 'btn usa-button-secondary margin-top-2 float-right', onClick: () => removeSection(index), isDestructive: true }, 'Remove Standard')
                                )
                            )
                        ),
                        el(Button, { className: 'btn float-right usa-button margin-top-2', onClick: addSection }, '+ Add Standard')
                    )
                )
            );
        },
        // We'll use render_callback in PHP, so return null here
        save: () => {
            return null;
        }
    });

    // Add some styling for the URL field and external link indicator
    const style = document.createElement('style');
    style.innerHTML = `
        .standard-title-container {
            margin-bottom: 10px;
        }
        .standard-title-url {
            margin-top: 5px;
        }
        .standard-url-field {
            width: 100%;
        }
        .external-link-preview {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        .svap-url-field {
            display: flex;
            align-items: center;
        }
    `;
    document.head.appendChild(style);
})(window.wp);