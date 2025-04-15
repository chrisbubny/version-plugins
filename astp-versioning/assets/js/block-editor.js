/**
 * ASTP Versioning - Block Editor Integration
 */
(function($) {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { createElement, Fragment } = wp.element;
    const { InspectorControls, InnerBlocks } = wp.blockEditor;
    const { PanelBody, ToggleControl, RangeControl, SelectControl } = wp.components;
    const { select, dispatch, subscribe } = wp.data;
    const { withSelect } = wp.data;
    
    // Wait for DOM ready
    $(document).ready(function() {
        initVersioningUI();
        registerVersionBlocks();
        initVersionEditorControls();
    });
    
    /**
     * Initialize versioning UI
     */
    function initVersioningUI() {
        // Check if we're in an editor context
        if (!wp || !wp.data || !wp.data.select('core/editor')) {
            return;
        }
        
        // Add version warning notice if necessary
        const currentPostId = wp.data.select('core/editor').getCurrentPostId();
        const currentPostType = wp.data.select('core/editor').getCurrentPostType();
        
        if (currentPostType !== 'astp_test_method') {
            return;
        }
        
        // Add version notice
        addVersionNotice();
        
        // Check version status
        checkVersionStatus(currentPostId);
    }
    
    /**
     * Add version notice to the editor
     */
    function addVersionNotice() {
        const hasDevVersion = astp_editor.in_dev_version ? true : false;
        
        if (hasDevVersion) {
            // Show that this is a development version
            wp.data.dispatch('core/notices').createNotice(
                'info',
                astp_editor.strings.version_editing,
                {
                    id: 'astp-version-notice',
                    isDismissible: false
                }
            );
        } else {
            // Show warning about changes not affecting published version
            wp.data.dispatch('core/notices').createNotice(
                'warning',
                astp_editor.strings.version_warning,
                {
                    id: 'astp-version-notice',
                    isDismissible: false
                }
            );
        }
    }
    
    /**
     * Check version status via AJAX
     */
    function checkVersionStatus(postId) {
        $.ajax({
            url: astp_editor.ajax_url,
            type: 'POST',
            data: {
                action: 'astp_check_version_status',
                post_id: postId,
                nonce: astp_editor.nonce
            },
            success: function(response) {
                if (response.success) {
                    const versionStatus = $('#astp-editor-version-status');
                    if (response.data.has_dev_version) {
                        const versionNumber = response.data.dev_version_id;
                        versionStatus.html('<p class="description">' + astp_editor.strings.version_exists + '</p>');
                    }
                }
            }
        });
    }
    
    /**
     * Register version-related blocks
     */
    function registerVersionBlocks() {
        // Version Info Block
        registerBlockType('astp/version-info', {
            title: 'Version Information',
            icon: 'info-outline',
            category: 'formatting',
            description: 'Display version information for this test method',
            attributes: {
                showDownloads: {
                    type: 'boolean',
                    default: true
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                
                return createElement(
                    Fragment,
                    {},
                    createElement(
                        InspectorControls,
                        {},
                        createElement(
                            PanelBody,
                            { title: 'Block Settings', initialOpen: true },
                            createElement(
                                ToggleControl,
                                {
                                    label: 'Show Download Links',
                                    checked: attributes.showDownloads,
                                    onChange: function(value) {
                                        setAttributes({ showDownloads: value });
                                    }
                                }
                            )
                        )
                    ),
                    createElement(
                        'div',
                        { className: 'astp-version-info-preview' },
                        createElement(
                            'div',
                            { className: 'components-placeholder' },
                            createElement(
                                'div',
                                { className: 'components-placeholder__label' },
                                'Version Information'
                            ),
                            createElement(
                                'div',
                                { className: 'components-placeholder__fieldset' },
                                'Shows the current version information for this test method.'
                            )
                        )
                    )
                );
            },
            save: function() {
                // Server-side rendering
                return null;
            }
        });
        
        // Version History Block
        registerBlockType('astp/version-history', {
            title: 'Version History',
            icon: 'backup',
            category: 'formatting',
            description: 'Display version history for this test method',
            attributes: {
                limit: {
                    type: 'number',
                    default: 5
                },
                showDownloads: {
                    type: 'boolean',
                    default: true
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                
                return createElement(
                    Fragment,
                    {},
                    createElement(
                        InspectorControls,
                        {},
                        createElement(
                            PanelBody,
                            { title: 'Block Settings', initialOpen: true },
                            createElement(
                                RangeControl,
                                {
                                    label: 'Number of Versions',
                                    value: attributes.limit,
                                    min: 1,
                                    max: 20,
                                    onChange: function(value) {
                                        setAttributes({ limit: value });
                                    }
                                }
                            ),
                            createElement(
                                ToggleControl,
                                {
                                    label: 'Show Download Links',
                                    checked: attributes.showDownloads,
                                    onChange: function(value) {
                                        setAttributes({ showDownloads: value });
                                    }
                                }
                            )
                        )
                    ),
                    createElement(
                        'div',
                        { className: 'astp-version-history-preview' },
                        createElement(
                            'div',
                            { className: 'components-placeholder' },
                            createElement(
                                'div',
                                { className: 'components-placeholder__label' },
                                'Version History'
                            ),
                            createElement(
                                'div',
                                { className: 'components-placeholder__fieldset' },
                                'Shows up to ' + attributes.limit + ' previous versions.'
                            )
                        )
                    )
                );
            },
            save: function() {
                // Server-side rendering
                return null;
            }
        });
        
        // Version Changes Block
        registerBlockType('astp/version-changes', {
            title: 'Version Changes',
            icon: 'visibility',
            category: 'formatting',
            description: 'Display changes between two versions',
            attributes: {
                fromVersion: {
                    type: 'string',
                    default: ''
                },
                toVersion: {
                    type: 'string',
                    default: ''
                },
                format: {
                    type: 'string',
                    default: 'detailed'
                }
            },
            edit: withSelect(function(select) {
                // Get all versions for select options
                return {
                    versions: select('core').getEntityRecords('postType', 'astp_version', {
                        per_page: -1
                    }) || []
                };
            })(function(props) {
                const { attributes, setAttributes, versions } = props;
                
                // Create version options
                const versionOptions = [];
                if (versions.length > 0) {
                    versionOptions.push({ label: 'Select a version', value: '' });
                    versions.forEach(version => {
                        const versionNumber = version.meta && version.meta.version_number 
                            ? version.meta.version_number 
                            : 'Unknown';
                        versionOptions.push({
                            label: 'v' + versionNumber,
                            value: '' + version.id // Convert to string
                        });
                    });
                } else {
                    versionOptions.push({ label: 'Loading versions...', value: '' });
                }
                
                return createElement(
                    Fragment,
                    {},
                    createElement(
                        InspectorControls,
                        {},
                        createElement(
                            PanelBody,
                            { title: 'Comparison Settings', initialOpen: true },
                            createElement(
                                SelectControl,
                                {
                                    label: 'From Version',
                                    value: attributes.fromVersion,
                                    options: versionOptions,
                                    onChange: function(value) {
                                        setAttributes({ fromVersion: value });
                                    }
                                }
                            ),
                            createElement(
                                SelectControl,
                                {
                                    label: 'To Version',
                                    value: attributes.toVersion,
                                    options: versionOptions,
                                    onChange: function(value) {
                                        setAttributes({ toVersion: value });
                                    }
                                }
                            ),
                            createElement(
                                SelectControl,
                                {
                                    label: 'Display Format',
                                    value: attributes.format,
                                    options: [
                                        { label: 'Summary', value: 'summary' },
                                        { label: 'Detailed', value: 'detailed' },
                                        { label: 'Full', value: 'full' }
                                    ],
                                    onChange: function(value) {
                                        setAttributes({ format: value });
                                    }
                                }
                            )
                        )
                    ),
                    createElement(
                        'div',
                        { className: 'astp-version-changes-preview' },
                        createElement(
                            'div',
                            { className: 'components-placeholder' },
                            createElement(
                                'div',
                                { className: 'components-placeholder__label' },
                                'Version Changes'
                            ),
                            createElement(
                                'div',
                                { className: 'components-placeholder__fieldset' },
                                !attributes.fromVersion || !attributes.toVersion
                                    ? 'Please select both from and to versions in the block settings.'
                                    : 'Shows changes between versions in ' + attributes.format + ' format.'
                            )
                        )
                    )
                );
            }),
            save: function() {
                // Server-side rendering
                return null;
            }
        });
    }
    
    /**
     * Initialize version controls in the editor
     */
    function initVersionEditorControls() {
        // Create version button
        $(document).on('click', '#astp-create-editor-version', function(e) {
            e.preventDefault();
            
            const postId = $(this).data('post-id');
            const versionType = $('#astp-editor-version-type').val();
            
            if (!confirm('Are you sure you want to create a new ' + versionType + ' version?')) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text('Creating...').prop('disabled', true);
            
            $.ajax({
                url: astp_editor.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_create_version',
                    post_id: postId,
                    version_type: versionType,
                    nonce: astp_editor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Version created successfully! Version number: ' + response.data.version_number);
                        location.reload();
                    } else {
                        alert('Error creating version: ' + (response.data.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error connecting to server. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Publish version button
        $(document).on('click', '#astp-publish-editor-version', function(e) {
            e.preventDefault();
            
            const versionId = $(this).data('version-id');
            
            if (!confirm('Are you sure you want to publish this version? This will create the changelog and documents.')) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text('Publishing...').prop('disabled', true);
            
            $.ajax({
                url: astp_editor.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_publish_version',
                    version_id: versionId,
                    nonce: astp_editor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Version published successfully!');
                        location.reload();
                    } else {
                        alert('Error publishing version: ' + (response.data.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error connecting to server. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
    }
    
})(jQuery); 