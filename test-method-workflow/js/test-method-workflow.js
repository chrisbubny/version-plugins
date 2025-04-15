/**
 * Test Method Workflow JavaScript
 */
jQuery(document).ready(function($) {
	// Submit for review
$('.submit-for-review').on('click', function(e) {
		e.preventDefault();
		
		if (confirm(testMethodWorkflow.strings.confirm_submit)) {
			var postId = $(this).data('post-id');
			
			// Get the current post title and content
			var postTitle = '';
			var postContent = '';
			var versionType = 'minor'; // Default to minor version change for new submissions
			
			// For new submissions, don't ask about basic updates - always require version type
			var versionOption = prompt("Select version type: minor or major", "minor");
			if (versionOption === 'minor' || versionOption === 'major') {
				versionType = versionOption;
			}
			
			// Check if we're in the classic editor
			if ($("#title").length) {
				postTitle = $("#title").val();
				
				// Get content from the active editor (classic or text mode)
				if (typeof tinyMCE !== "undefined" && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
					postContent = tinyMCE.activeEditor.getContent();
				} else {
					postContent = $("#content").val();
				}
			} 
			// For Gutenberg
			else if (wp.data && wp.data.select("core/editor")) {
				// Trigger a save in Gutenberg
				wp.data.dispatch("core/editor").savePost();
				
				// Get the post title and content from the editor
				postTitle = wp.data.select("core/editor").getEditedPostAttribute("title");
				postContent = wp.data.select("core/editor").getEditedPostAttribute("content");
			}
			
			// Now make the AJAX call with the post content
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "submit_for_review",
					post_id: postId,
					post_title: postTitle,
					post_content: postContent,
					version_type: versionType,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
						if (response.success) {
							alert(response.data.message || "Test method approved successfully");
							location.reload();
						} else {
							alert(response.data || "An error occurred");
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error:', { xhr: xhr, status: status, error: error });
						alert("An error occurred. Please try again.");
					}
				});
		}
	});
	
	// Submit for final approval
	$('.submit-for-final-approval').on('click', function(e) {
		e.preventDefault();
		
		if (confirm("Are you sure you want to submit this test method for final approval?")) {
			var postId = $(this).data('post-id');
			
			// Show a loading message or disable the button
			$(this).prop("disabled", true).text("Submitting...");
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "submit_for_final_approval",
					post_id: postId,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						
						// Check if we should reload the page
						if (response.data.reload) {
							location.reload();
						}
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function(xhr, status, error) {
					console.error(xhr, status, error);
					alert("An error occurred. Please try again.");
					$('.submit-for-final-approval').prop("disabled", false).text("Submit for Final Approval");
				}
			});
		}
	});
	
	// Approve test method
	$('.approve-test-method').on('click', function(e) {
		e.preventDefault();
		
		var comment = $('#approval-comment').val();
		if (!comment) {
			alert("Please add approval comments before approving.");
			return;
		}
		
		if (confirm(testMethodWorkflow.strings.confirm_approve)) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "approve_test_method",
					post_id: postId,
					comment: comment,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Test method approved successfully");
						location.reload();
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
	
	// Reject test method
	$('.reject-test-method').on('click', function(e) {
		e.preventDefault();
		
		var comment = $('#approval-comment').val();
		if (!comment) {
			alert("Please add rejection comments before rejecting.");
			return;
		}
		
		if (confirm(testMethodWorkflow.strings.confirm_reject)) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "reject_test_method",
					post_id: postId,
					comment: comment,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Test method rejected");
						location.reload();
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
	
	// Publish approved post
	$('.publish-approved-post').on('click', function(e) {
		e.preventDefault();
		
		if (confirm("Are you sure you want to publish this approved test method?")) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "publish_approved_post",
					post_id: postId,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Test method published successfully.");
						location.reload();
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
	
	// Publish approved revision
	$('.publish-approved-revision').on('click', function(e) {
		e.preventDefault();
		
		if (confirm("Are you sure you want to publish this approved revision? It will replace the original test method.")) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "publish_approved_revision",
					post_id: postId,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Revision published successfully. The content has been applied to the original test method.");
						if (response.data && response.data.view_url) {
							window.location.href = response.data.view_url;
						} else {
							location.reload();
						}
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
	
	// Unlock test method
	$('.unlock-test-method').on('click', function(e) {
		e.preventDefault();
		
		if (confirm(testMethodWorkflow.strings.confirm_unlock)) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "unlock_test_method",
					post_id: postId,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Test method unlocked successfully.");
						location.reload();
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
	
	// Lock test method
	$('.lock-test-method').on('click', function(e) {
		e.preventDefault();
		
		if (confirm("Are you sure you want to lock this test method?")) {
			var postId = $(this).data('post-id');
			
			$.ajax({
				url: testMethodWorkflow.ajaxurl,
				type: "POST",
				data: {
					action: "lock_test_method",
					post_id: postId,
					nonce: testMethodWorkflow.nonce
				},
				success: function(response) {
					if (response.success) {
						alert("Test method locked successfully.");
						location.reload();
					} else {
						alert(response.data || "An error occurred");
					}
				},
				error: function() {
					alert("An error occurred. Please try again.");
				}
			});
		}
	});
});