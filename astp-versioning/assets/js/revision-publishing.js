/**
 * Revision Publishing JavaScript
 */
(function($) {
	$(document).ready(function() {
		var postId = $('#post_ID').val();
		
		// Handle Compare Changes button
		$('#compare-revision-button').on('click', function() {
			compareRevision();
		});
		
		// Handle Publish Revision button
		$('#publish-revision-button').on('click', function() {
			if (confirm(astpPublishing.i18n.confirm_publish)) {
				publishRevision();
			}
		});
		
		/**
		 * Compare revision
		 */
		function compareRevision() {
			var data = {
				action: 'astp_compare_revision',
				nonce: astpPublishing.nonce,
				revision_id: postId
			};
			
			showStatus(astpPublishing.i18n.processing);
			
			$.ajax({
				url: astpPublishing.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					hideStatus();
					
					if (response.success) {
						renderComparison(response.data.comparison, response.data.revision_type);
						$('#publish-revision-button').prop('disabled', false);
					} else {
						showError(response.data.message);
					}
				},
				error: function() {
					hideStatus();
					showError('An error occurred during comparison.');
				}
			});
		}
		
		/**
		 * Publish revision
		 */
		function publishRevision() {
			var data = {
				action: 'astp_publish_revision',
				nonce: astpPublishing.nonce,
				revision_id: postId
			};
			
			showStatus(astpPublishing.i18n.processing);
			
			$.ajax({
				url: astpPublishing.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					hideStatus();
					
					if (response.success) {
						alert(response.data.message);
						
						// Redirect to parent test method
						if (response.data.parent_url) {
							window.location.href = response.data.parent_url;
						}
					} else {
						showError(response.data.message);
					}
				},
				error: function() {
					hideStatus();
					showError('An error occurred during publishing.');
				}
			});
		}
		
		/**
		 * Render comparison results
		 */
		function renderComparison(comparison, revisionType) {
			var $results = $('#comparison-results');
			var html = '';
			
			html += '<h3>Comparison Results</h3>';
			
			if (revisionType === 'ccg' || revisionType === 'both') {
				html += '<div class="ccg-comparison comparison-section">';
				html += '<h4>CCG Changes</h4>';
				html += renderChangesSection(comparison.ccg);
				html += '</div>';
			}
			
			if (revisionType === 'tp' || revisionType === 'both') {
				html += '<div class="tp-comparison comparison-section">';
				html += '<h4>TP Changes</h4>';
				html += renderChangesSection(comparison.tp);
				html += '</div>';
			}
			
			$results.html(html);
		}
		
		/**
		 * Render changes section
		 */
		function renderChangesSection(changes) {
			var html = '';
			
			if (!changes) {
				return '<p>No changes detected.</p>';
			}
			
			// Added blocks
			if (changes.added && changes.added.length > 0) {
				html += '<div class="added-blocks">';
				html += '<h5>Added Content (' + changes.added.length + ')</h5>';
				html += '<ul>';
				
				for (var i = 0; i < changes.added.length; i++) {
					var block = changes.added[i].block;
					html += '<li>';
					html += '<div class="block-content added">';
					html += '<strong>Added:</strong> ';
					html += getBlockDescription(block);
					html += '</div>';
					html += '</li>';
				}
				
				html += '</ul>';
				html += '</div>';
			}
			
			// Removed blocks
			if (changes.removed && changes.removed.length > 0) {
				html += '<div class="removed-blocks">';
				html += '<h5>Removed Content (' + changes.removed.length + ')</h5>';
				html += '<ul>';
				
				for (var i = 0; i < changes.removed.length; i++) {
					var block = changes.removed[i].block;
					html += '<li>';
					html += '<div class="block-content removed">';
					html += '<strong>Removed:</strong> ';
					html += getBlockDescription(block);
					html += '</div>';
					html += '</li>';
				}
				
				html += '</ul>';
				html += '</div>';
			}
			
			// Modified blocks
			if (changes.modified && changes.modified.length > 0) {
				html += '<div class="modified-blocks">';
				html += '<h5>Modified Content (' + changes.modified.length + ')</h5>';
				html += '<ul>';
				
				for (var i = 0; i < changes.modified.length; i++) {
					var oldBlock = changes.modified[i].old;
					var newBlock = changes.modified[i].new;
					
					html += '<li>';
					html += '<div class="block-comparison">';
					html += '<div class="block-content old">';
					html += '<strong>Before:</strong> ';
					html += getBlockDescription(oldBlock);
					html += '</div>';
					html += '<div class="block-content new">';
					html += '<strong>After:</strong> ';
					html += getBlockDescription(newBlock);
					html += '</div>';
					html += '</div>';
					html += '</li>';
				}
				
				html += '</ul>';
				html += '</div>';
			}
			
			if (html === '') {
				html = '<p>No changes detected.</p>';
			}
			
			return html;
		}
		
		/**
		 * Get block description
		 */
		function getBlockDescription(block) {
			var desc = '';
			
			if (block.blockName) {
				// Get block type
				var blockType = block.blockName.split('/').pop();
				desc += 'Block type: ' + blockType + ' ';
			}
			
			// Extract text content (this is simplified, you might need to customize this based on your block structure)
			if (block.innerHTML) {
				var textContent = $('<div>').html(block.innerHTML).text();
				if (textContent) {
					// Trim and limit text length
					textContent = textContent.trim();
					if (textContent.length > 100) {
						textContent = textContent.substring(0, 100) + '...';
					}
					desc += 'Content: ' + textContent;
				}
			}
			
			return desc || 'Empty block';
		}
		
		/**
		 * Show status message
		 */
		function showStatus(message) {
			$('#publishing-status-message').text(message);
			$('#publishing-status').show();
		}
		
		/**
		 * Hide status message
		 */
		function hideStatus() {
			$('#publishing-status').hide();
		}
		
		/**
		 * Show error message
		 */
		function showError(message) {
			$('#comparison-results').html(
				'<div class="notice notice-error"><p>' + message + '</p></div>'
			);
		}
	});
})(jQuery);