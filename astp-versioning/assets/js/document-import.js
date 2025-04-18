/**
 * Document Import JavaScript
 */
(function($) {
	$(document).ready(function() {
		var postId = $('#post_ID').val();
		
		// Handle import source selection
		$('input[name="import_source"]').on('change', function() {
			var source = $(this).val();
			
			// Hide all option contents
			$('.astp-import-option-content').hide();
			
			// Show selected option content
			$('#' + source + '-option-content').show();
		});
		
		// Handle document upload
		$('#import-document-button').on('click', function() {
			var file = $('#document-file')[0].files[0];
			
			if (!file) {
				alert(astpImport.i18n.select_file || 'Please select a file to upload.');
				return;
			}
			
			uploadDocument(file);
		});
		
		// Handle current document import
		$('#use-current-document-button').on('click', function() {
			var parentId = $('#parent_test_method').val();
			
			if (!parentId) {
				alert(astpImport.i18n.select_parent || 'Please select a parent test method.');
				return;
			}
			
			importFromCurrentDocument(parentId);
		});
		
		// Handle current content import
		$('#use-current-content-button').on('click', function() {
			var parentId = $('#parent_test_method').val();
			
			if (!parentId) {
				alert(astpImport.i18n.select_parent || 'Please select a parent test method.');
				return;
			}
			
			importFromCurrentContent(parentId);
		});
		
		// Create Revision functionality (on test_method edit screen)
		$('#create-revision-button').on('click', function() {
			$('#create-revision-dialog').toggle();
		});
		
		$('#cancel-create-revision').on('click', function() {
			$('#create-revision-dialog').hide();
		});
		
		$('#confirm-create-revision').on('click', function() {
			var postId = $('#create-revision-button').data('post-id');
			var revisionType = $('input[name="quick_revision_type"]:checked').val();
			
			createRevision(postId, revisionType);
		});
		
		/**
		 * Upload document
		 */
		function uploadDocument(file) {
			var formData = new FormData();
			formData.append('action', 'astp_import_document');
			formData.append('nonce', astpImport.nonce);
			formData.append('source', 'upload');
			formData.append('post_id', postId);
			formData.append('document', file);
			
			showImportStatus();
			
			$.ajax({
				url: astpImport.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					hideImportStatus();
					
					if (response.success) {
						showImportResult('success', response.data.message);
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						showImportResult('error', response.data.message);
					}
				},
				error: function() {
					hideImportStatus();
					showImportResult('error', astpImport.i18n.import_error || 'An error occurred during import.');
				}
			});
		}
		
		/**
		 * Import from current document
		 */
		function importFromCurrentDocument(parentId) {
			var data = {
				action: 'astp_import_document',
				nonce: astpImport.nonce,
				source: 'current',
				post_id: postId,
				parent_id: parentId
			};
			
			showImportStatus();
			
			$.ajax({
				url: astpImport.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					hideImportStatus();
					
					if (response.success) {
						showImportResult('success', response.data.message);
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						showImportResult('error', response.data.message);
					}
				},
				error: function() {
					hideImportStatus();
					showImportResult('error', astpImport.i18n.import_error || 'An error occurred during import.');
				}
			});
		}
		
		/**
		 * Import from current content
		 */
		function importFromCurrentContent(parentId) {
			var data = {
				action: 'astp_import_document',
				nonce: astpImport.nonce,
				source: 'content',
				post_id: postId,
				parent_id: parentId
			};
			
			showImportStatus();
			
			$.ajax({
				url: astpImport.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					hideImportStatus();
					
					if (response.success) {
						showImportResult('success', response.data.message);
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						showImportResult('error', response.data.message);
					}
				},
				error: function() {
					hideImportStatus();
					showImportResult('error', astpImport.i18n.import_error || 'An error occurred during import.');
				}
			});
		}
		
		/**
		 * Create a new revision
		 */
		function createRevision(parentId, revisionType) {
			var data = {
				action: 'astp_create_revision',
				nonce: astpImport.createRevisionNonce,
				parent_id: parentId,
				revision_type: revisionType
			};
			
			$('#create-revision-dialog').html('<div class="spinner is-active" style="float: none; margin: 0;"></div> Creating revision...');
			
			$.ajax({
				url: astpImport.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success && response.data.redirect) {
						window.location.href = response.data.redirect;
					} else {
						$('#create-revision-dialog').html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error creating revision.') + '</p></div>');
					}
				},
				error: function() {
					$('#create-revision-dialog').html('<div class="notice notice-error inline"><p>' + (astpImport.i18n.revision_error || 'Error creating revision.') + '</p></div>');
				}
			});
		}
		
		/**
		 * Show import status
		 */
		function showImportStatus() {
			$('#import-status').show();
			$('#import-result').hide();
		}
		
		/**
		 * Hide import status
		 */
		function hideImportStatus() {
			$('#import-status').hide();
		}
		
		/**
		 * Show import result
		 */
		function showImportResult(type, message) {
			var $result = $('#import-result');
			$result.empty().show();
			
			if (type === 'success') {
				$result.html('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
			} else {
				$result.html('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
			}
		}
	});
})(jQuery);