/**
 * ASTP Versioning Frontend JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Version history - version selector
        $('#astp-version-selector').on('change', function() {
            var versionId = $(this).val();
            
            // Show loading state
            $('#astp-revision-content').addClass('astp-loading');
            
            // AJAX call to get version content
            $.ajax({
                url: astp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'astp_get_version_changes',
                    version_id: versionId,
                    security: astp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the revision content
                        $('#astp-revision-content').html(response.data);
                    } else {
                        // Show error
                        $('#astp-revision-content').html('<div class="astp-error">Error loading version data.</div>');
                    }
                    
                    // Remove loading state
                    $('#astp-revision-content').removeClass('astp-loading');
                },
                error: function() {
                    // Show error
                    $('#astp-revision-content').html('<div class="astp-error">Error loading version data.</div>');
                    $('#astp-revision-content').removeClass('astp-loading');
                }
            });
        });
    });
    
})(jQuery); 