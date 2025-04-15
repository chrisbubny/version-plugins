<?php
/**
 * Workflow Management
 *
 * @package TestMethodWorkflow
 */

// Exit if accessed directly
if (!defined("ABSPATH")) {
	exit();
}

/**
 * Test Method workflow class
 */
class TestMethod_Workflow
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Add meta boxes
		add_action("add_meta_boxes", [$this, "add_workflow_meta_boxes"]);

		// Save post meta
		add_action(
			"save_post_test_method",
			[$this, "save_workflow_meta"],
			10,
			2
		);

		// AJAX actions
		add_action("wp_ajax_submit_for_review", [$this, "submit_for_review"]);
		add_action("wp_ajax_approve_test_method", [
			$this,
			"approve_test_method",
		]);
		add_action("wp_ajax_reject_test_method", [$this, "reject_test_method"]);
		add_action("wp_ajax_publish_approved_post", [
			$this,
			"publish_approved_post",
		]);
		add_action("wp_ajax_submit_for_final_approval", [
			$this,
			"submit_for_final_approval",
		]);

		// Prevent unauthorized publishing
		add_action(
			"save_post",
			[$this, "prevent_unauthorized_publishing"],
			10,
			2
		);

		// Custom admin notices
		add_action("admin_notices", [$this, "workflow_admin_notices"]);
	}

	/**
	 * Add workflow meta boxes
	 */
	public function add_workflow_meta_boxes()
	{
		add_meta_box(
			"test_method_workflow",
			__("Test Method Workflow", "test-method-workflow"),
			[$this, "workflow_meta_box_callback"],
			"test_method",
			"side",
			"high"
		);

		add_meta_box(
			"test_method_approvals",
			__("Approvals & Comments", "test-method-workflow"),
			[$this, "approvals_meta_box_callback"],
			"test_method",
			"normal",
			"high"
		);
	}

	/**
	 * Workflow meta box callback
	 */
	public function workflow_meta_box_callback($post)
	{
		wp_nonce_field(
			"test_method_workflow_meta_box",
			"test_method_workflow_nonce"
		);

		$workflow_status = get_post_meta($post->ID, "_workflow_status", true);
		$is_locked = get_post_meta($post->ID, "_is_locked", true);
		$approvals = get_post_meta($post->ID, "_approvals", true);
		$approval_count = is_array($approvals) ? count($approvals) : 0;
		$awaiting_final_approval = get_post_meta(
			$post->ID,
			"_awaiting_final_approval",
			true
		);
		$is_revision = get_post_meta($post->ID, "_is_revision", true);
		if (!$workflow_status) {
			$workflow_status = "draft";
		}

		$statuses = [
			"draft" => __("Draft", "test-method-workflow"),
			"pending_review" => __("Pending Review", "test-method-workflow"),
			"pending_final_approval" => __(
				"Awaiting Final Approval",
				"test-method-workflow"
			),
			"approved" => __("Approved", "test-method-workflow"),
			"rejected" => __("Rejected", "test-method-workflow"),
			"publish" => __("Published", "test-method-workflow"),
			"locked" => __("Locked", "test-method-workflow"),
		];

		echo '<div class="workflow-status-container">';
		echo "<p><strong>" .
			__("Current Status:", "test-method-workflow") .
			"</strong> " .
			(isset($statuses[$workflow_status])
				? $statuses[$workflow_status]
				: ucfirst($workflow_status)) .
			"</p>";

		// Show revision info if applicable
		if ($is_revision) {
			$parent_id = get_post_meta($post->ID, "_revision_parent", true);
			$parent_post = get_post($parent_id);
			if ($parent_post) {
				echo "<p><strong>" .
					__("Revision of:", "test-method-workflow") .
					"</strong> " .
					'<a href="' .
					get_edit_post_link($parent_id) .
					'">' .
					esc_html($parent_post->post_title) .
					"</a></p>";
			}
		}
		// Show approval count if there are any approvals
		if ($approval_count > 0) {
			echo "<p><strong>" .
				__("Approvals:", "test-method-workflow") .
				"</strong> " .
				$approval_count .
				" " .
				__("of 2 required", "test-method-workflow") .
				"</p>";
		}

		// Get current user role
		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		$user_id = get_current_user_id();

		// Check if current user has already approved
		$user_has_approved = false;
		if (is_array($approvals)) {
			foreach ($approvals as $approval) {
				if (
					$approval["user_id"] == $user_id &&
					$approval["status"] == "approved"
				) {
					$user_has_approved = true;
					break;
				}
			}
		}

		// Initial submit for review button - only for draft or rejected status
		if ($workflow_status == "draft" || $workflow_status == "rejected") {
			if (
				array_intersect($user_roles, [
					"tp_contributor",
					"tp_approver",
					"tp_admin",
					"administrator",
				])
			) {
				echo '<button type="button" class="button button-primary submit-for-review" data-post-id="' .
					$post->ID .
					'">' .
					__("Submit for Review", "test-method-workflow") .
					"</button>";
			}
		}
		// Final approval buttons - for TP Approvers and above
		if (
			($workflow_status == "pending_review" ||
				$workflow_status == "pending_final_approval") &&
			array_intersect($user_roles, [
				"tp_approver",
				"tp_admin",
				"administrator",
			])
		) {
			// If user hasn't approved yet, show approve/reject buttons
			if (!$user_has_approved) {
				echo '<div class="approval-actions">';
				echo "<p>" .
					__("Review this test method:", "test-method-workflow") .
					"</p>";
				echo '<button type="button" class="button button-primary approve-test-method" data-post-id="' .
					$post->ID .
					'">' .
					__("Approve", "test-method-workflow") .
					"</button> ";
				echo '<button type="button" class="button button-secondary reject-test-method" data-post-id="' .
					$post->ID .
					'">' .
					__("Reject", "test-method-workflow") .
					"</button>";
				echo "</div>";
			}
		}

		// Submit for final approval button - only if one approval exists and not already awaiting final approval
		if (
			$workflow_status == "pending_review" &&
			$approval_count == 1 &&
			!$awaiting_final_approval
		) {
			if (
				array_intersect($user_roles, [
					"tp_contributor",
					"tp_approver",
					"tp_admin",
					"administrator",
				])
			) {
				echo '<button type="button" class="button button-primary submit-for-final-approval" data-post-id="' .
					$post->ID .
					'">' .
					__("Submit for Final Approval", "test-method-workflow") .
					"</button>";
			}
		}
		// If awaiting final approval, show message
		if (
			$workflow_status == "pending_final_approval" ||
			$awaiting_final_approval
		) {
			echo '<p class="final-approval-message">' .
				__(
					"This test method is awaiting final approval. Another approver needs to review it.",
					"test-method-workflow"
				) .
				"</p>";
		}

	// If post is approved and user is TP admin or admin, show publish button
	if ($workflow_status == "approved" && $approval_count >= 2) {
		if (array_intersect($user_roles, ["tp_admin", "administrator"])) {
			echo '<p class="description">' .
				__(
					"This test method has been approved and is ready to publish.",
					"test-method-workflow"
				) .
				"</p>";
			
			// Check if this is a revision
			if ($is_revision) {
				echo '<button type="button" class="button button-primary publish-approved-revision" data-post-id="' .
					$post->ID .
					'">' .
					__("Publish Approved Revision", "test-method-workflow") .
					"</button>";
			} else {
				echo '<button type="button" class="button button-primary publish-approved-post" data-post-id="' .
					$post->ID .
					'">' .
					__("Publish Approved Test Method", "test-method-workflow") .
					"</button>";
			}
		} else {
			echo '<p class="description">' .
				__(
					"This test method has been approved and is awaiting publishing by an administrator.",
					"test-method-workflow"
				) .
				"</p>";
		}
	}

		// If post is locked and user is TP admin or admin, show unlock button
		if (
			$is_locked &&
			array_intersect($user_roles, ["tp_admin", "administrator"])
		) {
			echo '<div class="unlock-wrapper" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
			echo '<button type="button" class="button unlock-test-method" data-post-id="' .
				$post->ID .
				'">' .
				__("Unlock Test Method", "test-method-workflow") .
				"</button>";
			echo '<p class="description">' .
				__(
					"Unlocking will allow contributors to edit this test method again.",
					"test-method-workflow"
				) .
				"</p>";
			echo "</div>";
		}

		echo "</div>";
	}
	
	/**
	 * Approvals meta box callback
	 */
	public function approvals_meta_box_callback($post)
	{
		$approvals = $this->get_post_approvals($post->ID);
		$revision_history = $this->get_revision_history($post->ID);
		$current_version = get_post_meta(
			$post->ID,
			"_current_version_number",
			true
		);
	
		// Current version display
		echo '<div class="version-info">';
		echo "<h3>" .
			__("Version Information", "test-method-workflow") .
			"</h3>";
		echo "<p><strong>" .
			__("Current Version:", "test-method-workflow") .
			"</strong> " .
			(!empty($current_version) ? esc_html($current_version) : "0.1") .
			"</p>";
	
		// Version note
		$version_note = get_post_meta($post->ID, "_cpt_version_note", true);
		if (!empty($version_note)) {
			echo "<p><strong>" .
				__("Version Note:", "test-method-workflow") .
				"</strong> " .
				esc_html($version_note) .
				"</p>";
		}
		echo "</div>";
	
		// Approval history
		echo '<div class="approval-history">';
		echo "<h3>" . __("Approval History", "test-method-workflow") . "</h3>";
	
		if (empty($approvals) || !is_array($approvals)) {
			echo "<p>" .
				__("No approvals yet.", "test-method-workflow") .
				"</p>";
		} else {
			echo '<table class="widefat" style="margin-bottom: 20px;">';
			echo "<thead>";
			echo "<tr>";
			echo "<th>" . __("User", "test-method-workflow") . "</th>";
			echo "<th>" . __("Date", "test-method-workflow") . "</th>";
			echo "<th>" . __("Status", "test-method-workflow") . "</th>";
			echo "<th>" . __("Version", "test-method-workflow") . "</th>"; // Added Version column
			echo "<th>" . __("Comments", "test-method-workflow") . "</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			foreach ($approvals as $approval) {
				$user_info = get_userdata($approval["user_id"]);
				$username = $user_info
					? $user_info->display_name
					: __("Unknown User", "test-method-workflow");
				$status_class =
					$approval["status"] == "approved"
						? "status-approved"
						: "status-rejected";
				$version = isset($approval["version"]) ? $approval["version"] : $current_version;
	
				echo "<tr>";
				echo "<td>" . esc_html($username) . "</td>";
				echo "<td>" .
					date_i18n(
						get_option("date_format") .
							" " .
							get_option("time_format"),
						$approval["date"]
					) .
					"</td>";
				echo '<td><span class="' .
					$status_class .
					'">' .
					ucfirst($approval["status"]) .
					"</span></td>";
				echo "<td>" . esc_html($version) . "</td>"; // Display version
				echo "<td>" . esc_html($approval["comment"]) . "</td>";
				echo "</tr>";
			}
	
			echo "</tbody>";
			echo "</table>";
		}
		echo "</div>";
	
		// Add comment field for approvers
		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		$workflow_status = get_post_meta($post->ID, "_workflow_status", true);
	
		if (
			($workflow_status == "pending_review" ||
				$workflow_status == "pending_final_approval") &&
			array_intersect($user_roles, [
				"tp_approver",
				"tp_admin",
				"administrator",
			])
		) {
			// Check if user has already approved/rejected
			$user_id = get_current_user_id();
			$already_reviewed = false;
	
			if (is_array($approvals)) {
				foreach ($approvals as $approval) {
					if ($approval["user_id"] == $user_id) {
						$already_reviewed = true;
						break;
					}
				}
			}
			if (!$already_reviewed) {
				echo '<div class="approval-comment-container">';
				echo "<h3>" .
					__("Add Your Review", "test-method-workflow") .
					"</h3>";
				echo '<p><textarea id="approval-comment" placeholder="' .
					esc_attr__(
						"Add your approval or rejection comments",
						"test-method-workflow"
					) .
					'" rows="4" style="width: 100%;"></textarea></p>';
				echo "</div>";
				} else {
								echo "<p><em>" .
									__(
										"You have already reviewed this test method.",
										"test-method-workflow"
									) .
									"</em></p>";
							}
						}
					
						// Revision history
						echo '<div class="revision-history">';
						echo "<h3>" . __("Revision History", "test-method-workflow") . "</h3>";
					
						if (empty($revision_history) || !is_array($revision_history)) {
							echo "<p>" .
								__("No revision history available.", "test-method-workflow") .
								"</p>";
						} else {
							echo '<table class="widefat">';
							echo "<thead>";
							echo "<tr>";
							echo "<th>" . __("Version", "test-method-workflow") . "</th>";
							echo "<th>" . __("User", "test-method-workflow") . "</th>";
							echo "<th>" . __("Date", "test-method-workflow") . "</th>";
							echo "<th>" . __("Status", "test-method-workflow") . "</th>";
							echo "<th>" . __("Version Number", "test-method-workflow") . "</th>"; // Make sure version number is displayed
							echo "</tr>";
							echo "</thead>";
							echo "<tbody>";
					
							foreach ($revision_history as $revision) {
								$user_info = get_userdata($revision["user_id"]);
								$username = $user_info
									? $user_info->display_name
									: __("Unknown User", "test-method-workflow");
								$version_number = isset($revision["version_number"]) ? $revision["version_number"] : "-";
					
								echo "<tr>";
								echo "<td>" .
									(isset($revision["version"]) ? $revision["version"] : "-") .
									"</td>";
								echo "<td>" . esc_html($username) . "</td>";
								echo "<td>" .
									date_i18n(
										get_option("date_format") .
											" " .
											get_option("time_format"),
										$revision["date"]
									) .
									"</td>";
								echo "<td>" . ucfirst($revision["status"]) . "</td>";
								echo "<td>" . esc_html($version_number) . "</td>"; // Display version number
								echo "</tr>";
							}
					
							echo "</tbody>";
							echo "</table>";
						}
						echo "</div>";
					}
				
				     /**
					 * Save workflow meta with handling for admin edits
					 */
					public function save_workflow_meta($post_id, $post) {
						// Check if our nonce is set
						if (!isset($_POST["test_method_workflow_nonce"])) {
							return;
						}
					
						// Verify the nonce
						if (!wp_verify_nonce($_POST["test_method_workflow_nonce"], "test_method_workflow_meta_box")) {
							return;
						}
					
						// If this is an autosave, we don't want to do anything
						if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
							return;
						}
					
						// Check user permissions
						if (!current_user_can("edit_test_method", $post_id)) {
							return;
						}
					
						// Get the current workflow status
						$workflow_status = get_post_meta($post_id, "_workflow_status", true);
						$is_locked = get_post_meta($post_id, "_is_locked", true);
						$post_status = get_post_status($post_id);
					
						// Check if this is an admin editing a published post
						$user = wp_get_current_user();
						$user_roles = (array) $user->roles;
						$is_admin = array_intersect($user_roles, array('administrator', 'tp_admin'));
						$is_published_edit = $is_admin && $post_status === 'publish' && $is_locked;
						
						// If no workflow status is set yet, initialize it to 'draft'
						if (!$workflow_status) {
							update_post_meta($post_id, "_workflow_status", "draft");
						}
					
						// Initialize approvals array if it doesn't exist
						$approvals = get_post_meta($post_id, "_approvals", true);
						if (!is_array($approvals)) {
							update_post_meta($post_id, "_approvals", []);
						}
						
						// Initialize locked status if it doesn't exist
						if ($is_locked === "") {
							update_post_meta($post_id, "_is_locked", false);
						}
					
						// If this is a normal save (not an admin editing a published post), handle version data
						if (!$is_published_edit) {
							// Save version data if provided
							if (isset($_POST["version_update_type"]) && $_POST["version_update_type"] !== "none") {
								$current_version = get_post_meta($post_id, "_current_version_number", true);
								if (empty($current_version)) {
									$current_version = "0.1";
								}
					
								// Parse version into major and minor
								$version_parts = explode(".", $current_version);
								$major = isset($version_parts[0]) ? intval($version_parts[0]) : 0;
								$minor = isset($version_parts[1]) ? intval($version_parts[1]) : 0;
					
								$new_version = $current_version; // Default to no change
					
								switch ($_POST["version_update_type"]) {
									case "minor":
										$new_version = $major . "." . ($minor + 1);
										break;
					
									case "major":
										$new_version = $major + 1 . ".0";
										break;
					
									case "custom":
										if (!empty($_POST["custom_version"])) {
											$new_version = sanitize_text_field($_POST["custom_version"]);
										}
										break;
								}
					
								// Update version if changed
								if ($new_version !== $current_version) {
									update_post_meta($post_id, "_current_version_number", $new_version);
								}
							}
						} else {
							// For admin edits of published posts, add an entry to revision history
							$current_version = get_post_meta($post_id, "_current_version_number", true);
							$this->add_to_revision_history($post_id, "admin edit without version change");
						}
					
						// Save version note
						if (isset($_POST["version_note"])) {
							$version_note = sanitize_textarea_field($_POST["version_note"]);
							update_post_meta($post_id, "_cpt_version_note", $version_note);
						}
					}
					
					/**
					 * Prevent unauthorized publishing
					 */
	public function prevent_unauthorized_publishing($post_id, $post) {
		// Skip if this is an autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		// Skip if this is our custom AJAX publishing or if we're currently handling an approved publish
		if ((defined("DOING_AJAX") && DOING_AJAX && isset($_POST["action"]) && $_POST["action"] === "publish_approved_post") || 
			defined("TMW_PUBLISHING_APPROVED")) {
			return;
		}
		
		// Only apply to test_method post type
		if ($post->post_type !== "test_method") {
			return;
		}
		
		// Only check on status transition to publish
		if ($post->post_status !== "publish") {
			return;
		}
		
		// Get current user role
		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;
		
		// If user is admin or tp_admin, check if post is already published (updating an existing published post)
		if (array_intersect($user_roles, ["administrator", "tp_admin"])) {
			// Get the previous status
			$previous_status = get_post_meta($post_id, '_previous_status', true);
			
			// Check if this is a previously published post (an update to existing content)
			if ($previous_status === 'publish') {
				// Allow the update to proceed without requiring approval
				// Still lock the post
				update_post_meta($post_id, '_is_locked', true);
				update_post_meta($post_id, '_workflow_status', "publish");
				return;
			}
			
			// For new posts, check approval status
			$workflow_status = get_post_meta($post_id, "_workflow_status", true);
			$approvals = get_post_meta($post_id, "_approvals", true);
			$approval_count = is_array($approvals) ? count($approvals) : 0;
			
			if ($workflow_status !== "approved" || $approval_count < 2) {
				// Stop publishing
				remove_action('save_post', [$this, "prevent_unauthorized_publishing"], 10);
				
				wp_update_post([
					'ID' => $post_id,
					'post_status' => 'draft',
				]);
				
				// Add flag for admin notice
				set_transient("tmw_approval_required_" . $post_id, true, 60);
				
				add_action('save_post', [$this, "prevent_unauthorized_publishing"], 10, 2);
				
				return;
			}
			
			// Post is approved and being published by admin/tp_admin
			// Lock the post
			update_post_meta($post_id, "_is_locked", true);
			update_post_meta($post_id, "_workflow_status", "publish");
			return;
		}
		
		// For non-admin users, prevent publishing entirely
		remove_action('save_post', [$this, "prevent_unauthorized_publishing"], 10);
		
		wp_update_post([
			'ID' => $post_id,
			'post_status' => 'draft',
		]);
		
		// Add flag for admin notice
		set_transient("tmw_publish_denied_" . $post_id, true, 60);
		
		add_action('save_post', [$this, "prevent_unauthorized_publishing"], 10, 2);
	}
	
	public function track_post_status($new_status, $old_status, $post) {
		if ($post->post_type !== 'test_method') {
			return;
		}
		
		// Store previous status for reference
		update_post_meta($post->ID, '_previous_status', $old_status);
	}
					
					/**
					 * Display admin notices for workflow actions
					 */
				public function workflow_admin_notices() {
					global $post;
				
					if (!$post) {
						return;
					}
				
					$post_id = $post->ID;
					
					// Check if this is a test method post
					if ($post->post_type !== 'test_method') {
						return;
					}
				
					// Publish denied notice
					if (get_transient("tmw_publish_denied_" . $post_id)) {
						delete_transient("tmw_publish_denied_" . $post_id);
						?>
						<div class="notice notice-error is-dismissible">
							<p><?php _e("You do not have permission to publish Test Methods. Only TP Admin or Administrator can publish approved test methods.", "test-method-workflow"); ?></p>
						</div>
						<?php
					}
					
					// Approval required notice
					if (get_transient("tmw_approval_required_" . $post_id)) {
						delete_transient("tmw_approval_required_" . $post_id);
						?>
						<div class="notice notice-error is-dismissible">
							<p><?php _e("This Test Method requires two approvals before it can be published.", "test-method-workflow"); ?></p>
						</div>
						<?php
					}
					
					// Version restored notice
					if (isset($_GET["version_restored"]) && $_GET["version_restored"] == 1) {
						?>
						<div class="notice notice-success is-dismissible">
							<p><?php _e("Previous version successfully restored. Save to keep these changes.", "test-method-workflow"); ?></p>
						</div>
						<?php
					}
					
					// Admin direct edit notice - show to tp_admin and administrator
					$user = wp_get_current_user();
					$user_roles = (array) $user->roles;
					$is_admin = array_intersect($user_roles, array('administrator', 'tp_admin'));
					$post_status = get_post_status($post);
					$is_published = $post_status === 'publish';
					
					if ($is_admin && $is_published) {
						?>
						<div class="notice notice-info is-dismissible">
							<p><strong><?php _e("Admin Edit Mode", "test-method-workflow"); ?></strong>: <?php _e("As an administrator, you can directly edit this published test method without creating a revision. These changes will not increment the version number. For significant changes that require versioning, create a revision instead.", "test-method-workflow"); ?></p>
						</div>
						<?php
					}
				}
					/**
					 * AJAX handler for submitting for review
					 */
	public function submit_for_review() {
		// Check nonce for security
		check_ajax_referer("test_method_workflow", "nonce");
	
		// Check permissions
		if (!current_user_can("edit_test_methods")) {
			wp_send_json_error("Permission denied");
			return;
		}
	
		$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
	
		if (!$post_id) {
			wp_send_json_error("Invalid post ID");
			return;
		}
		
		// Get version information if provided
		$version_type = isset($_POST["version_type"])
			? sanitize_text_field($_POST["version_type"])
			: "no_change";
		
		// Get current version number
		$current_version = get_post_meta($post_id, "_current_version_number", true);
		
		// Check if this is a revision with already incremented version number
		$already_incremented = get_post_meta($post_id, "_version_already_incremented", true);
		
		if (empty($current_version) || $current_version === '0.0') {
			// For first submission, always increment to 0.1
			update_post_meta($post_id, "_current_version_number", "0.1");
		} else if ($version_type !== "no_change" && $already_incremented !== 'yes') {
			// Only increment version if not already done and a change is requested
			// Parse version parts
			$version_parts = explode(".", $current_version);
			$major = isset($version_parts[0]) ? intval($version_parts[0]) : 0;
			$minor = isset($version_parts[1]) ? intval($version_parts[1]) : 0;
	
			// Update version based on type
			if ($version_type === "minor") {
				$new_version = $major . "." . ($minor + 1);
				update_post_meta($post_id, "_current_version_number", $new_version);
				update_post_meta($post_id, "_cpt_version", "minor");
			} elseif ($version_type === "major") {
				$new_version = $major + 1 . ".0";
				update_post_meta($post_id, "_current_version_number", $new_version);
				update_post_meta($post_id, "_cpt_version", "major");
			}
		}
	
		// First save all post data to ensure content is preserved
		if (isset($_POST["post_title"]) && isset($_POST["post_content"])) {
			$post_data = [
				"ID" => $post_id,
				"post_title" => sanitize_text_field($_POST["post_title"]),
				"post_content" => wp_kses_post($_POST["post_content"]),
			];
			wp_update_post($post_data);
		}
		
		// Update workflow status
		update_post_meta($post_id, "_workflow_status", "pending_review");
	
		// Update post status - use 'pending' for WordPress native status
		wp_update_post([
			"ID" => $post_id,
			"post_status" => "pending",
		]);
	
		// Reset approvals
		update_post_meta($post_id, "_approvals", []);
	
		// Add to revision history
		$this->add_to_revision_history($post_id, "submitted for review");
	
		// Send notification to approvers
		do_action("tmw_send_notification", $post_id, "submitted_for_review");
	
		wp_send_json_success([
			"message" => __(
				"Test method submitted for review",
				"test-method-workflow"
			),
			"reload" => true,
		]);
	}
				
				/**
					 * AJAX handler for approving test method
					 */
					public function approve_test_method()
					{
						// Check nonce for security
						check_ajax_referer("test_method_workflow", "nonce");
					
						// Check permissions
						if (!current_user_can("approve_test_methods")) {
							wp_send_json_error("Permission denied");
							return;
						}
					
						$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
						$comment = isset($_POST["comment"])
							? sanitize_textarea_field($_POST["comment"])
							: "";
					
						if (!$post_id) {
							wp_send_json_error("Invalid post ID");
							return;
						}
						// Get current approvals
						$approvals = $this->get_post_approvals($post_id);
						$current_user_id = get_current_user_id();
					
						// Get current version
						$current_version = get_post_meta($post_id, "_current_version_number", true);
						if (empty($current_version)) {
							$current_version = '0.1';
						}
					
						// Check if user has already approved
						foreach ($approvals as $key => $approval) {
							if ($approval["user_id"] == $current_user_id) {
								// Update existing approval
								$approvals[$key] = [
									"user_id" => $current_user_id,
									"date" => time(),
									"status" => "approved",
									"comment" => $comment,
									"version" => $current_version  // Add version info
								];
					
								update_post_meta($post_id, "_approvals", $approvals);
					
								wp_send_json_success([
													"message" => __(
														"Your approval has been updated",
														"test-method-workflow"
													),
													"approval_count" => count($approvals),
												]);
												return;
											}
										}
									
										// Add new approval
										$approvals[] = [
											"user_id" => $current_user_id,
											"date" => time(),
											"status" => "approved",
											"comment" => $comment,
											"version" => $current_version  // Add version info
										];
									
										update_post_meta($post_id, "_approvals", $approvals);
									
										// Update workflow status if we have two approvals
										if (count($approvals) >= 2) {
											update_post_meta($post_id, "_workflow_status", "approved");
											update_post_meta($post_id, "_awaiting_final_approval", false);
									
											// Add to revision history
											$this->add_to_revision_history($post_id, "approved");
									
											// Send notification to TP Admins
											do_action("tmw_send_notification", $post_id, "approved");
										} elseif (count($approvals) == 1) {
											// First approval
											$awaiting_final = get_post_meta(
												$post_id,
												"_awaiting_final_approval",
												true
											);
											if ($awaiting_final) {
												update_post_meta(
													$post_id,
													"_workflow_status",
													"pending_final_approval"
												);
											}
										}
										wp_send_json_success([
											"message" => __(
												"Test method approved successfully",
												"test-method-workflow"
											),
											"approval_count" => count($approvals),
										]);
									}
									
									/**
									 * AJAX handler for rejecting test method
									 */
									public function reject_test_method()
									{
										// Check nonce for security
										check_ajax_referer("test_method_workflow", "nonce");
									
										// Check permissions
										if (!current_user_can("reject_test_methods")) {
											wp_send_json_error("Permission denied");
											return;
										}
									
										$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
										$comment = isset($_POST["comment"])
											? sanitize_textarea_field($_POST["comment"])
											: "";
									
										if (!$post_id) {
											wp_send_json_error("Invalid post ID");
											return;
										}
									
										if (empty($comment)) {
											wp_send_json_error(
												__("Please provide rejection comments", "test-method-workflow")
											);
											return;
										}
									
										// Get current approvals
										$approvals = $this->get_post_approvals($post_id);
										$current_user_id = get_current_user_id();
									
										// Get current version
										$current_version = get_post_meta($post_id, "_current_version_number", true);
										if (empty($current_version)) {
											$current_version = '0.1';
										}
									
										// Check if user has already approved/rejected
										$updated = false;
										foreach ($approvals as $key => $approval) {
											if ($approval["user_id"] == $current_user_id) {
												// Update existing entry instead of adding a new one
												$approvals[$key] = [
													"user_id" => $current_user_id,
													"date" => time(),
													"status" => "rejected",
													"comment" => $comment,
													"version" => $current_version  // Add version info
												];
												$updated = true;
												break;
											}
										}
										if (!$updated) {
											// Add new rejection
											$approvals[] = [
												"user_id" => $current_user_id,
												"date" => time(),
												"status" => "rejected",
												"comment" => $comment,
												"version" => $current_version  // Add version info
											];
										}
									
										update_post_meta($post_id, "_approvals", $approvals);
										update_post_meta($post_id, "_workflow_status", "rejected");
										update_post_meta($post_id, "_awaiting_final_approval", false);
									
										// Update post status
										wp_update_post([
											"ID" => $post_id,
											"post_status" => "draft",
										]);
									
										// Add to revision history
										$this->add_to_revision_history($post_id, "rejected");
									
										// Send notification
										do_action("tmw_send_notification", $post_id, "rejected");
									
										wp_send_json_success([
											"message" => __("Test method rejected", "test-method-workflow"),
											"reload" => true,
										]);
									}
								
									/**
									 * AJAX handler for submitting for final approval
									 */
									public function submit_for_final_approval()
									{
										// Check nonce for security
										check_ajax_referer("test_method_workflow", "nonce");
								
										// Check permissions
										if (!current_user_can("edit_test_methods")) {
											wp_send_json_error("Permission denied");
											return;
										}
								
										$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
								
										if (!$post_id) {
											wp_send_json_error("Invalid post ID");
											return;
										}
										// Update meta to indicate awaiting final approval
										update_post_meta($post_id, "_awaiting_final_approval", true);
										update_post_meta(
											$post_id,
											"_workflow_status",
											"pending_final_approval"
										);
								
										// Add to revision history
										$this->add_to_revision_history(
											$post_id,
											"submitted for final approval"
										);
								
										// Send notification
										do_action(
											"tmw_send_notification",
											$post_id,
											"final_approval_requested"
										);
								
										wp_send_json_success([
											"message" => __(
												"Request for final approval sent successfully",
												"test-method-workflow"
											),
											"reload" => true,
										]);
									}
								
/**
									 * AJAX handler for publishing approved post with version handling fix
									 */
									public function publish_approved_post() {
										// Check nonce for security
										check_ajax_referer("test_method_workflow", "nonce");
									
										// Check if user can publish
										if (!current_user_can("publish_test_methods")) {
											wp_send_json_error("Permission denied");
											return;
										}
									
										$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
									
										if (!$post_id) {
											wp_send_json_error("Invalid post ID");
											return;
										}
									
										// Verify test method is approved
										$workflow_status = get_post_meta($post_id, "_workflow_status", true);
										$approvals = get_post_meta($post_id, "_approvals", true);
										$approval_count = is_array($approvals) ? count($approvals) : 0;
									
										if ($workflow_status !== "approved" || $approval_count < 2) {
											wp_send_json_error(
												__(
													"This test method has not been fully approved.",
													"test-method-workflow"
												)
											);
											return;
										}
										
										// Define constant to bypass the prevention hook
										// This constant is also used to prevent double version incrementation
										if (!defined("TMW_PUBLISHING_APPROVED")) {
											define("TMW_PUBLISHING_APPROVED", true);
										}
									
										// First update the workflow status and lock the post BEFORE publishing
										update_post_meta($post_id, "_workflow_status", "publish");
										update_post_meta($post_id, "_is_locked", true);
									
										// Now publish the post
										$result = wp_update_post(
											[
												"ID" => $post_id,
												"post_status" => "publish",
											],
											true
										);
									
										// Check if there was an error updating the post
										if (is_wp_error($result)) {
											wp_send_json_error(
												__("Error publishing post:", "test-method-workflow") .
													" " .
													$result->get_error_message()
											);
											return;
										}
									
										// Add to revision history
										$this->add_to_revision_history($post_id, 'published');
									
										// Send notification
										do_action("tmw_send_notification", $post_id, "published");
									
										wp_send_json_success([
											"message" => __(
												"Test method published successfully.",
												"test-method-workflow"
											),
											"reload" => true,
										]);
									}
												
									/**
									 * Get post approvals
									 */
									private function get_post_approvals($post_id)
									{
										$approvals = get_post_meta($post_id, "_approvals", true);
										return is_array($approvals) ? $approvals : [];
									}
								
									/**
									 * Get revision history
									 */
									private function get_revision_history($post_id)
									{
										$revision_history = get_post_meta($post_id, "_revision_history", true);
										return is_array($revision_history) ? $revision_history : [];
									}
									
									/**
									 * Add entry to revision history
									 */
									private function add_to_revision_history($post_id, $status) {
										$revision_history = $this->get_revision_history($post_id);
										$current_version = get_post_meta($post_id, "_current_version_number", true);
										
										// Ensure we always have a valid version number
										if (empty($current_version)) {
											$current_version = '0.1';
										}
										
										$revision_history[] = array(
											"version" => count($revision_history) + 1,
											"user_id" => get_current_user_id(),
											"date" => time(),
											"status" => $status,
											"version_number" => $current_version  // Make sure this is consistently set
										);
										
										update_post_meta($post_id, "_revision_history", $revision_history);
									}
								}