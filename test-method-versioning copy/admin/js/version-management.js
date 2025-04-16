(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Create version button click handler
		$('.astp-create-version').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var postId = $button.data('post-id');
			var documentType = $button.data('document-type');
			var versionType = $('#astp-' + documentType + '-version-type').val();
			
			if (!confirm(tm_version_params.text_confirm_create)) {
				return;
			}
			
			$button.text(tm_version_params.text_creating).prop('disabled', true);
			
			$.ajax({
				url: tm_version_params.ajax_url,
				type: 'POST',
				data: {
					action: 'tm_create_version',
					post_id: postId,
					document_type: documentType,
					version_type: versionType,
					nonce: tm_version_params.nonce
				},
				success: function(response) {
					if (response.success) {
						window.location.href = response.data.edit_url;
					} else {
						alert(tm_version_params.text_error);
						$button.text(tm_version_params.text_create_version).prop('disabled', false);
					}
				},
				error: function() {
					alert(tm_version_params.text_error);
					$button.text(tm_version_params.text_create_version).prop('disabled', false);
				}
			});
		});
		
		// Create hotfix button click handler
		$('.astp-create-hotfix').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var postId = $button.data('post-id');
			var documentType = $button.data('document-type');
			var versionId = $button.data('version-id');
			
			if (!confirm(tm_version_params.text_confirm_hotfix)) {
				return;
			}
			
			$button.text(tm_version_params.text_creating).prop('disabled', true);
			
			$.ajax({
				url: tm_version_params.ajax_url,
				type: 'POST',
				data: {
					action: 'tm_create_hotfix',
					post_id: postId,
					document_type: documentType,
					version_id: versionId,
					nonce: tm_version_params.nonce
				},
				success: function(response) {
					if (response.success) {
						window.location.href = response.data.edit_url;
					} else {
						alert(tm_version_params.text_error);
						$button.text(tm_version_params.text_create_hotfix).prop('disabled', false);
					}
				},
				error: function() {
					alert(tm_version_params.text_error);
					$button.text(tm_version_params.text_create_hotfix).prop('disabled', false);
				}
			});
		});
		
		// View version history button click handler
		$('.astp-view-version-history').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var postId = $button.data('post-id');
			var documentType = $button.data('document-type');
			
			// Create modal if it doesn't exist
			if ($('#astp-version-history-modal').length === 0) {
				$('body').append('<div id="astp-version-history-modal" class="astp-modal"><div class="astp-modal-content"><span class="astp-modal-close">&times;</span><div id="astp-version-history-content"></div></div></div>');
				
				// Close modal when clicking the close button
				$(document).on('click', '.astp-modal-close', function() {
					$('.astp-modal').hide();
				});
				
				// Close modal when clicking outside of it
				$(window).on('click', function(event) {
					if ($(event.target).hasClass('astp-modal')) {
						$('.astp-modal').hide();
					}
				});
			}
			
			// Show loading
			$('#astp-version-history-content').html('<p>Loading...</p>');
			$('#astp-version-history-modal').show();
			
			$.ajax({
				url: tm_version_params.ajax_url,
				type: 'POST',
				data: {
					action: 'tm_view_version_history',
					post_id: postId,
					document_type: documentType,
					nonce: tm_version_params.nonce
				},
				success: function(response) {
					if (response.success) {
						$('#astp-version-history-content').html(response.data.history_html);
					} else {
						$('#astp-version-history-content').html('<p>Error loading version history.</p>');
					}
				},
				error: function() {
					$('#astp-version-history-content').html('<p>Error loading version history.</p>');
				}
			});
		});
	});
})(jQuery);

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Initialize version modal
		var $modal = $('<div class="astp-modal"><div class="astp-modal-content"><span class="astp-modal-close">&times;</span><div class="astp-modal-body"></div></div></div>');
		$('body').append($modal);
		
		// Close modal on X click
		$('.astp-modal-close').on('click', function() {
			$('.astp-modal').hide();
		});
		
		// Close modal when clicking outside
		$(window).on('click', function(e) {
			if ($(e.target).hasClass('astp-modal')) {
				$('.astp-modal').hide();
			}
		});
		
		// Create version button click
		$(document).on('click', '.astp-create-version', function(e) {
			e.preventDefault();
			
			var post_id = tm_version_params.post_id;
			var document_type = $(this).data('document-type');
			var version_type = $('#astp-' + document_type + '-version-type').val();
			
			if (!confirm(tm_version_params.text_confirm_create)) {
				return;
			}
			
			var $button = $(this);
			$button.prop('disabled', true).text(tm_version_params.text_creating);
			
			$.post(ajaxurl, {
				action: 'tm_create_version',
				post_id: post_id,
				document_type: document_type,
				version_type: version_type,
				nonce: tm_version_params.nonce
			}, function(response) {
				$button.prop('disabled', false).text(tm_version_params.text_create_version);
				
				if (response.success) {
					// Refresh the page to show new version
					location.reload();
				} else {
					alert(tm_version_params.text_error);
				}
			}).fail(function() {
				$button.prop('disabled', false).text(tm_version_params.text_create_version);
				alert(tm_version_params.text_error);
			});
		});
		
		// Create hotfix button click
		$(document).on('click', '.astp-create-hotfix', function(e) {
			e.preventDefault();
			
			var post_id = tm_version_params.post_id;
			var document_type = $(this).data('document-type');
			var version = $(this).data('version');
			
			if (!version) {
				version = $(document).find('#' + document_type + '_version').val();
			}
			
			if (!confirm(tm_version_params.text_confirm_hotfix)) {
				return;
			}
			
			var $button = $(this);
			$button.prop('disabled', true).text(tm_version_params.text_creating);
			
			$.post(ajaxurl, {
				action: 'tm_create_hotfix',
				post_id: post_id,
				document_type: document_type,
				version: version,
				nonce: tm_version_params.nonce
			}, function(response) {
				$button.prop('disabled', false).text(tm_version_params.text_create_hotfix);
				
				if (response.success) {
					// Refresh the page to show new version
					location.reload();
				} else {
					alert(tm_version_params.text_error);
				}
			}).fail(function() {
				$button.prop('disabled', false).text(tm_version_params.text_create_hotfix);
				alert(tm_version_params.text_error);
			});
		});
		
		// View version details button click
		$(document).on('click', '.astp-view-version-details', function(e) {
			e.preventDefault();
			
			var post_id = tm_version_params.post_id;
			var document_type = $(this).data('document-type');
			var version = $(this).data('version');
			
			// Show modal with loading indicator
			$('.astp-modal-body').html('<p>' + tm_version_params.text_loading + '</p>');
			$('.astp-modal').show();
			
			$.post(ajaxurl, {
				action: 'tm_view_version_details',
				post_id: post_id,
				document_type: document_type,
				version: version,
				nonce: tm_version_params.nonce
			}, function(response) {
				if (response.success) {
					$('.astp-modal-body').html(response.data.html);
				} else {
					$('.astp-modal-body').html('<p>' + tm_version_params.text_error + '</p>');
				}
			}).fail(function() {
				$('.astp-modal-body').html('<p>' + tm_version_params.text_error + '</p>');
			});
		});
		
		// View version history button click
		$(document).on('click', '.astp-view-version-history', function(e) {
			e.preventDefault();
			
			var post_id = tm_version_params.post_id;
			var document_type = $(this).data('document-type');
			
			// Show modal with loading indicator
			$('.astp-modal-body').html('<p>' + tm_version_params.text_loading + '</p>');
			$('.astp-modal').show();
			
			$.post(ajaxurl, {
				action: 'tm_view_version_history',
				post_id: post_id,
				document_type: document_type,
				nonce: tm_version_params.nonce
			}, function(response) {
				if (response.success) {
					$('.astp-modal-body').html(response.data.html);
				} else {
					$('.astp-modal-body').html('<p>' + tm_version_params.text_error + '</p>');
				}
			}).fail(function() {
				$('.astp-modal-body').html('<p>' + tm_version_params.text_error + '</p>');
			});
		});
		
		// Restore version button click
		$(document).on('click', '.astp-restore-version', function(e) {
			e.preventDefault();
			
			var post_id = tm_version_params.post_id;
			var document_type = $(this).data('document-type');
			var version = $(this).data('version');
			
			if (!confirm(tm_version_params.text_confirm_restore)) {
				return;
			}
			
			var $button = $(this);
			$button.prop('disabled', true).text(tm_version_params.text_restoring);
			
			$.post(ajaxurl, {
				action: 'tm_restore_version',
				post_id: post_id,
				document_type: document_type,
				version: version,
				nonce: tm_version_params.nonce
			}, function(response) {
				$button.prop('disabled', false).text(tm_version_params.text_restore);
				
				if (response.success) {
					// Refresh the page to show restored version
					location.reload();
				} else {
					alert(tm_version_params.text_error);
				}
			}).fail(function() {
				$button.prop('disabled', false).text(tm_version_params.text_restore);
				alert(tm_version_params.text_error);
			});
		});
	});
})(jQuery);