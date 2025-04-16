/**
 * Test Method Workflow and Versioning Sidebar
 */
(function(wp) {
	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, Button, SelectControl, TextareaControl, Spinner, Notice } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { useState, useEffect } = wp.element;
	const { apiFetch } = wp;

	/**
	 * Test Method Sidebar Component
	 */
	const TestMethodSidebar = () => {
		// State for UI components
		const [isLoading, setIsLoading] = useState(false);
		const [notice, setNotice] = useState(null);
		const [versions, setVersions] = useState({ current: { ccg: '', tp: '', combined: '' }, history: [] });
		const [approvals, setApprovals] = useState([]);
		const [versionType, setVersionType] = useState('minor');
		const [approvalComment, setApprovalComment] = useState('');
		
		// Get post data from store
		const { postId, postType, postStatus, currentUser } = useSelect(select => {
			const { getCurrentPostId, getCurrentPostType, getEditedPostAttribute } = select('core/editor');
			const { getCurrentUser } = select('core');
			
			return {
				postId: getCurrentPostId(),
				postType: getCurrentPostType(),
				postStatus: getEditedPostAttribute('status'),
				currentUser: getCurrentUser()
			};
		});
		
		// Check if this is a test_method post
		const isTestMethod = postType === 'test_method';
		
		// User roles and capabilities
		const userRoles = currentUser?.roles || [];
		const userCaps = tmVersioningData?.userCaps || {};
		
		const canApprove = userRoles.includes('tp_approver') || userRoles.includes('tp_admin') || 
						  userRoles.includes('administrator') || userCaps.canApprove;
		
		const canPublish = userRoles.includes('tp_admin') || userRoles.includes('administrator') ||
						  userCaps.canPublish;
		
		// Get workflow status
		const [workflowStatus, setWorkflowStatus] = useState('');
		
		// Load workflow status
		useEffect(() => {
			if (!isTestMethod || !postId) return;
			
			apiFetch({ path: `/wp/v2/test_method/${postId}` })
				.then(post => {
					const status = post.meta?.workflow_status || '';
					setWorkflowStatus(status || (postStatus === 'publish' ? 'published' : 'draft'));
				})
				.catch(error => {
					console.error('Error loading workflow status:', error);
				});
		}, [isTestMethod, postId, postStatus]);
		
		// Load versions
		useEffect(() => {
			if (!isTestMethod || !postId) return;
			
			setIsLoading(true);
			apiFetch({ path: `/tm-versioning/v1/versions/${postId}` })
				.then(data => {
					setVersions(data);
					setIsLoading(false);
				})
				.catch(error => {
					console.error('Error loading versions:', error);
					setIsLoading(false);
				});
		}, [isTestMethod, postId]);
		
		// Load approvals
		useEffect(() => {
			if (!isTestMethod || !postId) return;
			
			setIsLoading(true);
			apiFetch({ path: `/tm-versioning/v1/approvals/${postId}` })
				.then(data => {
					setApprovals(data);
					setIsLoading(false);
				})
				.catch(error => {
					console.error('Error loading approvals:', error);
					setIsLoading(false);
				});
		}, [isTestMethod, postId]);
		
		/**
		 * Update workflow status
		 */
		const updateWorkflowStatus = (status, comment = '', version = '') => {
			setIsLoading(true);
			apiFetch({
				path: `/tm-versioning/v1/workflow/${postId}`,
				method: 'POST',
				data: {
					status,
					comment,
					version,
					version_type: versionType
				}
			})
			.then(response => {
				setNotice({
					status: 'success',
					message: response.message
				});
				setWorkflowStatus(status);
				
				// Refresh approvals if needed
				if (status === 'approved' || status === 'rejected') {
					apiFetch({ path: `/tm-versioning/v1/approvals/${postId}` })
						.then(data => {
							setApprovals(data);
						});
				}
				
				setIsLoading(false);
			})
			.catch(error => {
				setNotice({
					status: 'error',
					message: error.message || __('An error occurred.', 'test-method-versioning')
				});
				setIsLoading(false);
			});
		};
		
		/**
		 * Handle version action
		 */
		const handleVersionAction = (action, version = '') => {
			setIsLoading(true);
			apiFetch({
				path: `/tm-versioning/v1/version/${postId}`,
				method: 'POST',
				data: {
					action,
					version_type: versionType,
					version
				}
			})
			.then(response => {
				setNotice({
					status: 'success',
					message: response.message
				});
				
				// Refresh versions
				apiFetch({ path: `/tm-versioning/v1/versions/${postId}` })
					.then(data => {
						setVersions(data);
					});
				
				setIsLoading(false);
			})
			.catch(error => {
				setNotice({
					status: 'error',
					message: error.message || __('An error occurred.', 'test-method-versioning')
				});
				setIsLoading(false);
			});
		};
		
		/**
		 * Generate document
		 */
		const generateDocument = (type) => {
			setIsLoading(true);
			apiFetch({
				path: `/tm-versioning/v1/export/${postId}`,
				method: 'POST',
				data: {
					type,
					version: versions.current.combined
				}
			})
			.then(response => {
				setNotice({
					status: 'success',
					message: response.message
				});
				
				// Open document in new tab if URL is available
				if (response.url) {
					window.open(response.url, '_blank');
				}
				
				// Refresh versions to update document links
				apiFetch({ path: `/tm-versioning/v1/versions/${postId}` })
					.then(data => {
						setVersions(data);
					});
				
				setIsLoading(false);
			})
			.catch(error => {
				setNotice({
					status: 'error',
					message: error.message || __('An error occurred.', 'test-method-versioning')
				});
				setIsLoading(false);
			});
		};
		
		// Only show for test_method post type
		if (!isTestMethod) return null;
		
		return (
			<>
				<PluginSidebarMoreMenuItem
					target="test-method-sidebar"
				>
					{__('Test Method Workflow', 'test-method-versioning')}
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="test-method-sidebar"
					title={__('Test Method Workflow', 'test-method-versioning')}
				>
					{notice && (
						<Notice 
							status={notice.status}
							onRemove={() => setNotice(null)}
							isDismissible={true}
						>
							{notice.message}
						</Notice>
					)}
					
					{isLoading && (
						<div style={{ textAlign: 'center', padding: '20px' }}>
							<Spinner />
						</div>
					)}
					
					<PanelBody
						title={__('Current Status', 'test-method-versioning')}
						initialOpen={true}
					>
						<div style={{ marginBottom: '10px' }}>
							<strong>{__('Post Status:', 'test-method-versioning')}</strong> {postStatus}
						</div>
						<div style={{ marginBottom: '10px' }}>
							<strong>{__('Workflow Status:', 'test-method-versioning')}</strong> {workflowStatus || __('Draft', 'test-method-versioning')}
						</div>
						<div style={{ marginBottom: '10px' }}>
							<strong>{__('Current Version:', 'test-method-versioning')}</strong> {versions.current.combined}
						</div>
					</PanelBody>
					
					<PanelBody
						title={__('Workflow Actions', 'test-method-versioning')}
						initialOpen={true}
					>
						{/* Submit for Review */}
						{(postStatus === 'draft' || workflowStatus === 'rejected') && (
							<Button
								isPrimary
								onClick={() => updateWorkflowStatus('submitted')}
								style={{ marginBottom: '10px', width: '100%' }}
							>
								{__('Submit for Review', 'test-method-versioning')}
							</Button>
						)}
						
						{/* Approve/Reject */}
						{workflowStatus === 'submitted' && canApprove && (
							<>
								<TextareaControl
									label={__('Approval Comment', 'test-method-versioning')}
									value={approvalComment}
									onChange={setApprovalComment}
									help={__('Required for approval or rejection.', 'test-method-versioning')}
								/>
								<div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
									<Button
										isPrimary
										onClick={() => {
											if (!approvalComment) {
												setNotice({
													status: 'error',
													message: __('Comment is required.', 'test-method-versioning')
												});
												return;
											}
											updateWorkflowStatus('approved', approvalComment, versions.current.combined);
											setApprovalComment('');
										}}
										style={{ width: '48%' }}
									>
										{__('Approve', 'test-method-versioning')}
									</Button>
									<Button
										isSecondary
										onClick={() => {
											if (!approvalComment) {
												setNotice({
													status: 'error',
													message: __('Comment is required.', 'test-method-versioning')
												});
												return;
											}
											updateWorkflowStatus('rejected', approvalComment);
											setApprovalComment('');
										}}
										style={{ width: '48%' }}
									>
										{__('Reject', 'test-method-versioning')}
									</Button>
								</div>
							</>
						)}
						
						{/* Publish */}
						{workflowStatus === 'approved' && canPublish && (
							<>
								<SelectControl
									label={__('Version Type', 'test-method-versioning')}
									value={versionType}
									options={[
										{ label: __('Basic (No Version Change)', 'test-method-versioning'), value: 'basic' },
										{ label: __('Minor Version (x.Y.0)', 'test-method-versioning'), value: 'minor' },
										{ label: __('Major Version (X.0.0)', 'test-method-versioning'), value: 'major' },
										{ label: __('Hotfix (x.y.Z)', 'test-method-versioning'), value: 'hotfix' }
									]}
									onChange={setVersionType}
								/>
								<Button
									isPrimary
									onClick={() => updateWorkflowStatus('published')}
									style={{ marginBottom: '10px', width: '100%' }}
								>
									{__('Publish', 'test-method-versioning')}
								</Button>
							</>
						)}
						
						{/* Unlock for Editing */}
						{workflowStatus === 'published' && canPublish && (
							<>
								<SelectControl
									label={__('Version Type for Next Edit', 'test-method-versioning')}
									value={versionType}
									options={[
										{ label: __('Basic (No Version Change)', 'test-method-versioning'), value: 'basic' },
										{ label: __('Minor Version (x.Y.0)', 'test-method-versioning'), value: 'minor' },
										{ label: __('Major Version (X.0.0)', 'test-method-versioning'), value: 'major' },
										{ label: __('Hotfix (x.y.Z)', 'test-method-versioning'), value: 'hotfix' }
									]}
									onChange={setVersionType}
								/>
								<Button
									isSecondary
									onClick={() => updateWorkflowStatus('revision')}
									style={{ marginBottom: '10px', width: '100%' }}
								>
									{__('Unlock for Editing', 'test-method-versioning')}
								</Button>
							</>
						)}
					</PanelBody>
					
					<PanelBody
						title={__('Versions', 'test-method-versioning')}
						initialOpen={false}
					>
						<div style={{ marginBottom: '10px' }}>
							<strong>{__('Current Version:', 'test-method-versioning')}</strong> {versions.current.combined}
						</div>
						
						{canPublish && (
							<div style={{ marginBottom: '20px' }}>
								<Button
									isPrimary
									onClick={() => generateDocument('word')}
									style={{ marginRight: '10px' }}
								>
									{__('Generate Word', 'test-method-versioning')}
								</Button>
								<Button
									isSecondary
									onClick={() => generateDocument('pdf')}
								>
									{__('Generate PDF', 'test-method-versioning')}
								</Button>
							</div>
						)}
						
						<h3>{__('Version History', 'test-method-versioning')}</h3>
						{versions.history && versions.history.length === 0 ? (
							<p>{__('No version history available.', 'test-method-versioning')}</p>
						) : (
							<div style={{ maxHeight: '300px', overflow: 'auto' }}>
								{versions.history && versions.history.map((version, index) => (
									<div key={index} style={{ 
										padding: '10px', 
										borderBottom: '1px solid #ddd', 
										marginBottom: '10px'
									}}>
										<div><strong>{__('Version:', 'test-method-versioning')}</strong> {version.combined}</div>
										<div><strong>{__('Date:', 'test-method-versioning')}</strong> {new Date(version.date).toLocaleString()}</div>
										<div style={{ marginTop: '5px' }}>
											{version.documents && version.documents.word && (
												<a 
													href={version.documents.word} 
													target="_blank" 
													rel="noopener noreferrer"
													style={{ marginRight: '10px' }}
												>
													{__('Word', 'test-method-versioning')}
												</a>
											)}
											{version.documents && version.documents.pdf && (
												<a 
													href={version.documents.pdf} 
													target="_blank" 
													rel="noopener noreferrer"
													style={{ marginRight: '10px' }}
												>
													{__('PDF', 'test-method-versioning')}
												</a>
											)}
											{canPublish && (
												<Button
													isLink
													onClick={() => handleVersionAction('rollback', version.combined)}
												>
													{__('Rollback', 'test-method-versioning')}
												</Button>
											)}
										</div>
									</div>
								))}
							</div>
						)}
					</PanelBody>
					
					<PanelBody
						title={__('Approval History', 'test-method-versioning')}
						initialOpen={false}
					>
						{approvals.length === 0 ? (
							<p>{__('No approval history available.', 'test-method-versioning')}</p>
						) : (
							<div style={{ maxHeight: '300px', overflow: 'auto' }}>
								{approvals.map((approval, index) => (
									<div key={index} style={{ 
										padding: '10px', 
										borderBottom: '1px solid #ddd', 
										marginBottom: '10px',
										backgroundColor: approval.status === 'approved' ? '#f0f9eb' : '#fef0f0'
									}}>
										<div><strong>{__('User:', 'test-method-versioning')}</strong> {approval.user.name}</div>
										<div><strong>{__('Date:', 'test-method-versioning')}</strong> {new Date(approval.date).toLocaleString()}</div>
										<div><strong>{__('Status:', 'test-method-versioning')}</strong> {
											approval.status === 'approved' ? 
												__('Approved', 'test-method-versioning') : 
												__('Rejected', 'test-method-versioning')
										}</div>
										{approval.version && (
											<div><strong>{__('Version:', 'test-method-versioning')}</strong> {approval.version}</div>
										)}
										<div><strong>{__('Comments:', 'test-method-versioning')}</strong> {approval.comments}</div>
									</div>
								))}
							</div>
						)}
					</PanelBody>
				</PluginSidebar>
			</>
		);
	};

	// Register the plugin
	registerPlugin('test-method-sidebar', {
		render: TestMethodSidebar,
		icon: 'clipboard'
	});
})(window.wp);