/**
 * Test Method Dashboard JavaScript
 */
jQuery(document).ready(function($) {
	// Variables
	let currentPostId = 0;
	let currentAction = '';
	
	// Modal functions
	function openModal(modalId) {
		$('#' + modalId).show();
	}
	
	function closeModal() {
		$('.tm-modal').hide();
		resetActionModal();
	}
	
	function resetActionModal() {
		$('#tm-action-title').text('');
		$('#tm-action-description').text('');
		$('#tm-version-type-container').hide();
		$('#tm-comment-container').hide();
		$('#tm-action-confirm').text('');
		currentPostId = 0;
		currentAction = '';
	}
	
	// Close modals
	$('.tm-modal-close, #tm-action-cancel').on('click', function() {
		closeModal();
	});
	
	// Close modal on outside click
	$(window).on('click', function(event) {
		if ($(event.target).hasClass('tm-modal')) {
			closeModal();
		}
	});
	
	// View approvals
	$('.tm-view-approvals').on('click', function(e) {
		e.preventDefault();
		const postId = $(this).data('post-id');
		
		// Show loading
		$('#tm-approvals-content').html('<p>Loading...</p>');
		openModal('tm-approvals-modal');
		
		// Fetch approvals
		wp.apiFetch({
			path: '/tm-versioning/v1/approvals/' + postId
		}).then(function(response) {
			let html = '';
			
			if (response.length === 0) {
				html = '<p>No approval history available.</p>';
			} else {
				html = '<div class="tm-approvals-list">';
				
				response.forEach(function(approval) {
					const statusClass = approval.status === 'approved' ? 'tm-approval-approved' : 'tm-approval-rejected';
					const statusLabel = approval.status === 'approved' ? 'Approved' : 'Rejected';
					
					html += '<div class="tm-approval-item ' + statusClass + '">';
					html += '<div class="tm-approval-header">';
					html += '<span class="tm-approval-user">' + approval.user.name + '</span>';
					html += '<span class="tm-approval-date">' + new Date(approval.date).toLocaleString() + '</span>';
					html += '<span class="tm-approval-status">' + statusLabel + '</span>';
					html += '</div>';
					
					if (approval.version) {
						html += '<div class="tm-approval-version">Version: ' + approval.version + '</div>';
					}
					
					html += '<div class="tm-approval-comments">' + approval.comments + '</div>';
					html += '</div>';
				});
				
				html += '</div>';
			}
			
			$('#tm-approvals-content').html(html);
		}).catch(function(error) {
			$('#tm-approvals-content').html('<p>Error loading approvals: ' + error.message + '</p>');
		});
	});
	
	// Approve action
	$('.tm-action-approve').on('click', function() {
		currentPostId = $(this).data('post-id');
		currentAction = 'approve';
		
		$('#tm-action-title').text('Approve Test Method');
		$('#tm-action-description').text('Please provide a comment for your approval.');
		$('#tm-comment-container').show();
		$('#tm-action-confirm').text('Approve');
		
		openModal('tm-action-modal');
	});
	
	// Reject action
	$('.tm-action-reject').on('click', function() {
		currentPostId = $(this).data('post-id');
		currentAction = 'reject';
		
		$('#tm-action-title').text('Reject Test Method');
		$('#tm-action-description').text('Please provide a reason for rejection.');
		$('#tm-comment-container').show();
		$('#tm-action-confirm').text('Reject');
		
		openModal('tm-action-modal');
	});
	
	// Publish action
	$('.tm-action-publish').on('click', function() {
		currentPostId = $(this).data('post-id');
		currentAction = 'publish';
		
		$('#tm-action-title').text('Publish Test Method');
		$('#tm-action-description').text('Please select the version type for publishing.');
		$('#tm-version-type-container').show();
		$('#tm-action-confirm').text('Publish');
		
		openModal('tm-action-modal');
	});
	
	// Unlock action
	$('.tm-action-unlock').on('click', function() {
		currentPostId = $(this).data('post-id');
		currentAction = 'unlock';
		
		$('#tm-action-title').text('Unlock Test Method');
		$('#tm-action-description').text('Please select the version type for this revision.');
		$('#tm-version-type-container').show();
		$('#tm-action-confirm').text('Unlock');
		
		openModal('tm-action-modal');
	});
	
	// Confirm action
	$('#tm-action-confirm').on('click', function() {
		if (!currentPostId || !currentAction) {
			return;
		}
		
		const comment = $('#tm-comment').val();
		const versionType = $('#tm-version-type').val();
		
		// Validate inputs
		if ((currentAction === 'approve' || currentAction === 'reject') && !comment) {
			alert('Please provide a comment.');
			return;
		}
		
		// Show loading
		$(this).text('Processing...').prop('disabled', true);
		
		// Prepare request data
		let requestData = {};
		
		switch (currentAction) {
			case 'approve':
				requestData = {
					status: 'approved',
					comment: comment
				};
				break;
				
			case 'reject':
				requestData = {
					status: 'rejected',
					comment: comment
				};
				break;
				
			case 'publish':
				requestData = {
					status: 'published',
					version_type: versionType
				};
				break;
				
			case 'unlock':
				requestData = {
					status: 'revision',
					version_type: versionType
				};
				break;
		}
		
		// Send request
		wp.apiFetch({
			path: '/tm-versioning/v1/workflow/' + currentPostId,
			method: 'POST',
			data: requestData
		}).then(function(response) {
			alert(response.message);
			location.reload();
		}).catch(function(error) {
			alert('Error: ' + error.message);
			$('#tm-action-confirm').text('Try Again').prop('disabled', false);
		});
	});
});