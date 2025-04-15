/**
 * ASTP Versioning - Admin UI JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('ASTP Admin UI initialized');
        
        // Remove any existing bindings to prevent duplicates
        $(document).off('click', '#astp-create-version-btn');
        $(document).off('click', '.astp-create-version');
        $('#astp_version_manager').off('click', '.astp-create-version');
        $(document).off('click', '.astp-publish-version');
        $(document).off('click', '#astp-create-hotfix-btn');
        $(document).off('click', '.astp-create-hotfix');
        
        initVersionActions();
        initComparisonTools();
        initDocumentActions();
        initSettingsPage();
    });
    
    /**
     * Initialize version action buttons and controls
     */
    function initVersionActions() {
        // Create version button - direct ID binding (new style)
        $('#astp-create-version-btn').on('click', function(e) {
            e.preventDefault();
            
            const postId = $(this).data('post-id');
            const versionType = $('#astp-version-type').val() || 'minor';
            
            if (!confirm(astp_admin.strings.confirmCreate)) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text(astp_admin.strings.creating).prop('disabled', true);
            
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_create_version',
                    post_id: postId,
                    version_type: versionType,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    console.log('Create version response:', response);
                    if (response.success) {
                        // Show success message and reload page
                        const versionNumber = response.data.version_number || 'New';
                        alert('Version created successfully! Version number: ' + versionNumber);
                        window.location.reload();
                    } else {
                        alert('Error creating version: ' + (response.data?.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Create version error:', status, error);
                    console.log('Response text:', xhr.responseText);
                    alert('Error connecting to server. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Create version button - legacy class binding from post-types.php
        $(document).on('click', '.astp-create-version', function(e) {
            e.preventDefault();
            
            const postId = $(this).data('post-id');
            const documentType = $(this).data('document-type') || null;
            const versionTypeSelector = documentType ? 
                '#astp-' + documentType + '-version-type' : 
                '#astp-version-type';
            const versionType = $(versionTypeSelector).val() || 'minor';
            
            if (!confirm(astp_admin.strings.confirmCreate)) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text(astp_admin.strings.creating).prop('disabled', true);
            
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_create_new_version',
                    post_id: postId,
                    version_type: versionType,
                    document_type: documentType,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    console.log('Create version response:', response);
                    if (response.success) {
                        // Show success message and reload page
                        const versionNumber = response.data.version_number || 'New';
                        alert('Version created successfully! Version number: ' + versionNumber);
                        window.location.reload();
                    } else {
                        alert('Error creating version: ' + (response.data?.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Create version error:', status, error);
                    console.log('Response text:', xhr.responseText);
                    alert('Error connecting to server. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Create hotfix version button
        $('#astp-create-hotfix-btn, .astp-create-hotfix').on('click', function(e) {
            e.preventDefault();
            
            const versionId = $(this).data('version') || $(this).data('version-id');
            const postId = $(this).data('post') || $(this).data('post-id');
            const documentType = $(this).data('document-type') || null;
            
            if (!confirm(astp_admin.strings.confirmHotfix)) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text(astp_admin.strings.creating).prop('disabled', true);
            
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_create_hotfix',
                    post_id: postId,
                    version_id: versionId,
                    document_type: documentType,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    console.log('Create hotfix response:', response);
                    if (response.success) {
                        // Show success message and reload page
                        const versionNumber = response.data.version_number || 'New';
                        alert('Hotfix created successfully! Version number: ' + versionNumber);
                        window.location.reload();
                    } else {
                        alert('Error creating hotfix: ' + (response.data?.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Create hotfix error:', status, error);
                    console.log('Response text:', xhr.responseText);
                    alert('Error connecting to server. Please try again.');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Publish version button
        $(document).on('click', '.astp-publish-version', function(e) {
            e.preventDefault();
            
            var postId = $(this).data('post-id');
            var versionId = $(this).data('version-id');
            var documentType = $(this).data('document-type') || null;
            
            if (!confirm(astp_admin.strings.confirmPublish)) {
                return;
            }
            
            // Disable button and show loading state
            $(this).addClass('updating-message').text('Publishing...').prop('disabled', true);
            
            // Publish version via AJAX
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_publish_version',
                    post_id: postId,
                    version_id: versionId,
                    document_type: documentType,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error publishing version: ' + (response.data?.message || 'Unknown error'));
                        $('.astp-publish-version').removeClass('updating-message').text('Publish Version').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error publishing version: ' + error);
                    $('.astp-publish-version').removeClass('updating-message').text('Publish Version').prop('disabled', false);
                }
            });
        });
        
        // View version details button
        $('.astp-view-version').on('click', function(e) {
            e.preventDefault();
            const versionId = $(this).data('version-id');
            window.location.href = astp_admin.admin_url + 'post.php?post=' + versionId + '&action=edit';
        });
        
        // View published versions
        $('#astp-view-published-versions').on('click', function(e) {
            e.preventDefault();
            $('#astp-published-versions').slideToggle();
        });
        
        // View development versions
        $('#astp-view-dev-versions').on('click', function(e) {
            e.preventDefault();
            $('#astp-development-versions').slideToggle();
        });
        
        // View version history button
        $(document).on('click', '.astp-view-version-history', function(e) {
            e.preventDefault();
            const postId = $(this).data('post-id');
            const documentType = $(this).data('document-type');
            
            let url = astp_admin.admin_url + 'edit.php?post_type=astp_test_method&page=astp-version-history&post_id=' + postId;
            
            // Add document type if available
            if (documentType) {
                url += '&document_type=' + documentType;
            }
            
            window.location.href = url;
        });
        
        // Scroll to version history
        $('.astp-scroll-to-history').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#astp-version-history').offset().top - 50
            }, 500);
        });
    }
    
    /**
     * Initialize comparison tools
     */
    function initComparisonTools() {
        const $form = $('.astp-version-comparison-form');
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const fromVersionId = $('#astp-compare-from').val();
            const toVersionId = $('#astp-compare-to').val();
            const displayFormat = $('#astp-compare-format').val();
            
            if (!fromVersionId || !toVersionId) {
                alert(astp_admin.strings.selectBothVersions);
                return;
            }
            
            loadComparison(fromVersionId, toVersionId, displayFormat, $('.astp-comparison-results'));
        });
        
        // Helper function to load comparison via AJAX
        function loadComparison(fromId, toId, format, container) {
            container.html('<p>' + astp_admin.strings.loadingComparison + '</p>');
            
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_compare_versions',
                    from_version_id: fromId,
                    to_version_id: toId,
                    display_format: format,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data.html);
                    } else {
                        container.html('<p>Error: ' + (response.data.message || 'Unknown error') + '</p>');
                    }
                },
                error: function() {
                    container.html('<p>' + astp_admin.strings.serverError + '</p>');
                }
            });
        }
    }
    
    /**
     * Initialize document generation and download actions
     */
    function initDocumentActions() {
        // Document generation buttons
        $(document).off('click', '.astp-generate-document');
        $(document).on('click', '.astp-generate-document', function(e) {
            e.preventDefault();
            
            const versionId = $(this).data('version-id');
            const format = $(this).data('format');
            
            if (!confirm(astp_admin.strings.confirmGenerate)) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.text();
            $button.text(astp_admin.strings.generating).prop('disabled', true);
            
            $.ajax({
                url: astp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_generate_document',
                    version_id: versionId,
                    format: format,
                    nonce: astp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(astp_admin.strings.documentGenerated);
                        
                        // If there's a download URL, offer to download it
                        if (response.data.download_url) {
                            if (confirm(astp_admin.strings.downloadNow)) {
                                window.location.href = response.data.download_url;
                            }
                        }
                        
                        // Reload page to show updated document list
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert(astp_admin.strings.serverError);
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Document download buttons
        $(document).off('click', '.astp-download-document');
        $(document).on('click', '.astp-download-document', function(e) {
            e.preventDefault();
            
            const documentUrl = $(this).data('document-url');
            if (documentUrl) {
                window.location.href = documentUrl;
            }
        });
    }
    
    /**
     * Initialize settings page controls
     */
    function initSettingsPage() {
        const $form = $('#astp-settings-form');
        
        if ($form.length) {
            // Settings form submission
            $form.on('submit', function(e) {
                e.preventDefault();
                
                const $submitButton = $(this).find('button[type="submit"]');
                const originalText = $submitButton.text();
                $submitButton.text(astp_admin.strings.saving).prop('disabled', true);
                
                // Serialize form data
                const formData = $(this).serialize();
                
                $.ajax({
                    url: astp_admin.ajax_url,
                    type: 'POST',
                    data: formData + '&action=astp_save_settings&nonce=' + astp_admin.nonce,
                    success: function(response) {
                        if (response.success) {
                            alert(astp_admin.strings.settingsSaved);
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                        }
                        $submitButton.text(originalText).prop('disabled', false);
                    },
                    error: function() {
                        alert(astp_admin.strings.serverError);
                        $submitButton.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Toggle dependent fields based on checkboxes
            $('.astp-toggle-control').on('change', function() {
                const target = $(this).data('toggle-target');
                if (target) {
                    if ($(this).is(':checked')) {
                        $(target).slideDown();
                    } else {
                        $(target).slideUp();
                    }
                }
            });
            
            // Initialize all toggles on page load
            $('.astp-toggle-control').each(function() {
                const target = $(this).data('toggle-target');
                if (target) {
                    if ($(this).is(':checked')) {
                        $(target).show();
                    } else {
                        $(target).hide();
                    }
                }
            });
        }
    }
    
    /**
     * Version History actions
     */
    $('.astp-create-from-version').on('click', function(e) {
        e.preventDefault();
        
        // Confirm action
        if (!confirm(astp_admin.strings.confirmCreate)) {
            return;
        }
        
        const versionId = $(this).data('version');
        const postId = $(this).data('post');
        const button = $(this);
        
        button.text(astp_admin.strings.creating).prop('disabled', true);
        
        // AJAX request to create new version
        $.ajax({
            url: astp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'astp_create_version',
                post_id: postId,
                version_id: versionId,
                version_type: 'minor',
                nonce: astp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to new version
                    window.location.href = response.data.version_edit_url;
                } else {
                    alert(response.data.message || astp_admin.strings.serverError);
                    button.text('New Version').prop('disabled', false);
                }
            },
            error: function() {
                alert(astp_admin.strings.serverError);
                button.text('New Version').prop('disabled', false);
            }
        });
    });
    
    $('.astp-create-hotfix').on('click', function(e) {
        e.preventDefault();
        
        // Confirm action
        if (!confirm(astp_admin.strings.confirmHotfix)) {
            return;
        }
        
        const versionId = $(this).data('version');
        const postId = $(this).data('post');
        const button = $(this);
        
        button.text(astp_admin.strings.creating).prop('disabled', true);
        
        // AJAX request to create hotfix
        $.ajax({
            url: astp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'astp_create_hotfix',
                post_id: postId,
                version_id: versionId,
                nonce: astp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to new hotfix
                    window.location.href = response.data.version_edit_url;
                } else {
                    alert(response.data.message || astp_admin.strings.serverError);
                    button.text('Hotfix').prop('disabled', false);
                }
            },
            error: function() {
                alert(astp_admin.strings.serverError);
                button.text('Hotfix').prop('disabled', false);
            }
        });
    });
    
    $('.astp-publish-version').on('click', function(e) {
        e.preventDefault();
        
        // Confirm action
        if (!confirm(astp_admin.strings.confirmPublish)) {
            return;
        }
        
        const versionId = $(this).data('version-id');
        const postId = $(this).data('post-id');
        const button = $(this);
        
        button.text(astp_admin.strings.publishing).prop('disabled', true);
        
        // AJAX request to publish version
        $.ajax({
            url: astp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'astp_publish_version',
                version_id: versionId,
                post_id: postId,
                nonce: astp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to show updated status
                    window.location.reload();
                } else {
                    console.error('Publish error:', response.data);
                    alert(response.data.message || astp_admin.strings.serverError);
                    button.text('Publish').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert(astp_admin.strings.serverError);
                button.text('Publish').prop('disabled', false);
            }
        });
    });
    
    /**
     * Version comparison tool
     */
    $('#astp-comparison-form').on('submit', function(e) {
        const fromVersion = $('#astp-from-version').val();
        const toVersion = $('#astp-to-version').val();
        
        if (!fromVersion || !toVersion) {
            e.preventDefault();
            alert(astp_admin.strings.selectBothVersions);
        }
    });
    
    /**
     * GitHub test connection
     */
    $('#astp-test-github').on('click', function() {
        const button = $(this);
        const resultDisplay = $('#astp-github-test-result');
        
        button.prop('disabled', true).text('Testing connection...');
        resultDisplay.removeClass('notice-success notice-error').hide();
        
        $.ajax({
            url: astp_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'astp_test_github',
                nonce: astp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDisplay.addClass('notice-success').text(response.data.message).show();
                } else {
                    resultDisplay.addClass('notice-error').text(response.data.message).show();
                }
                button.prop('disabled', false).text('Test GitHub Connection');
            },
            error: function() {
                resultDisplay.addClass('notice-error').text(astp_admin.strings.serverError).show();
                button.prop('disabled', false).text('Test GitHub Connection');
            }
        });
    });
    
    /**
     * Changes display filtering 
     */
    // Add filter buttons to the admin change display if they don't exist
    if ($('.astp-admin-changes-display').length && !$('.astp-admin-filter-controls').length) {
        $('.astp-changes-summary').after(
            '<div class="astp-admin-filter-controls">' +
            '<h4>Filter Changes</h4>' +
            '<div class="astp-filter-buttons">' +
            '<button type="button" class="button button-secondary astp-filter-btn active" data-filter="all">All Changes</button> ' +
            '<button type="button" class="button button-secondary astp-filter-btn" data-filter="added">Additions</button> ' +
            '<button type="button" class="button button-secondary astp-filter-btn" data-filter="removed">Removals</button> ' +
            '<button type="button" class="button button-secondary astp-filter-btn" data-filter="amended">Amendments</button>' +
            '</div>' +
            '</div>'
        );
    }
    
    // Handle filter button clicks
    $(document).on('click', '.astp-filter-btn', function() {
        const filter = $(this).data('filter');
        
        // Update active button
        $('.astp-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            $('.astp-changes-type').show();
        } else {
            $('.astp-changes-type').hide();
            $('.astp-changes-type.astp-' + filter).show();
        }
    });
    
    /**
     * Highlight changes in diffs
     */
    function enhanceChangeDiffs() {
        $('.astp-change-diff').each(function() {
            // Find all the ins and del elements in the diff
            const $ins = $(this).find('ins');
            const $del = $(this).find('del');
            
            // Add visual enhancements
            $ins.addClass('astp-highlight-added');
            $del.addClass('astp-highlight-removed');
        });
    }
    
    // Run on page load
    enhanceChangeDiffs();
})(jQuery); 