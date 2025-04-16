<?php
/**
 * Handles Word document export
 */

/**
 * Generate a Word document for a specific version
 */
function tm_generate_word_document($post_id, $version = '') {
	// If no version specified, use current
	if (empty($version)) {
		$ccg_version = get_post_meta($post_id, 'ccg_version', true);
		$tp_version = get_post_meta($post_id, 'tp_version', true);
		$version = $ccg_version . '-' . $tp_version;
	}
	
	// Get post data
	$post = get_post($post_id);
	$title = $post->post_title;
	$content = $post->post_content;
	
	// Check if we need to use version content
	$versions = get_post_meta($post_id, 'versions', true);
	if (is_array($versions) && isset($versions[$version])) {
		$content = $versions[$version]['content'];
	}
	
	// Generate filename
	$filename = sanitize_title($title) . '-' . $version . '.docx';
	
	// Include PHPWord library
	require_once TM_PLUGIN_DIR . 'vendor/autoload.php';
	
	// Create new Word document
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	
	// Add document properties
	$phpWord->getDocInfo()->setCreator('Test Method Versioning');
	$phpWord->getDocInfo()->setCompany('HealthIT.gov');
	$phpWord->getDocInfo()->setTitle($title);
	$phpWord->getDocInfo()->setDescription('Version ' . $version);
	
	// Create a new section
	$section = $phpWord->addSection();
	
	// Add title
	$section->addTitle($title, 1);
	
	// Add version info
	$section->addText('Version: ' . $version);
	$section->addText('Generated: ' . current_time('mysql'));
	
	// Process content
	$blocks = parse_blocks($content);
	
	foreach ($blocks as $block) {
		tm_process_block_for_word($section, $block);
	}
	
	// Create temp file
	$temp_file = wp_tempnam($filename);
	
	// Save document to temp file
	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
	$objWriter->save($temp_file);
	
	// Get WordPress upload directory
	$upload_dir = wp_upload_dir();
	$target_path = $upload_dir['path'] . '/' . $filename;
	
	// Move temp file to upload directory
	copy($temp_file, $target_path);
	unlink($temp_file);
	
	// Create attachment in media library
	$attachment = array(
		'post_mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'post_title' => $title . ' - Version ' . $version,
		'post_content' => '',
		'post_status' => 'inherit',
		'guid' => $upload_dir['url'] . '/' . $filename
	);
	
	$attachment_id = wp_insert_attachment($attachment, $target_path, $post_id);
	
	if (!is_wp_error($attachment_id)) {
		// Generate metadata and thumbnails
		$attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
		wp_update_attachment_metadata($attachment_id, $attachment_data);
		
		// Store attachment ID in post meta
		$documents = get_post_meta($post_id, 'documents', true);
		if (!is_array($documents)) {
			$documents = array();
		}
		
		$documents[$version]['word'] = $attachment_id;
		
		update_post_meta($post_id, 'documents', $documents);
		
		do_action('tm_document_generated', $post_id, $version, 'word');
		
		return $attachment_id;
	}
	
	return false;
}

/**
 * Process a block for Word document
 */
function tm_process_block_for_word($section, $block) {
	// Process different block types
	switch ($block['blockName']) {
		case 'core/paragraph':
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$section->addText($content);
			break;
			
		case 'core/heading':
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
			$section->addTitle($content, $level);
			break;
			
		case 'core/list':
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				$listItems = array();
				
				foreach ($block['innerBlocks'] as $item) {
					if ($item['blockName'] === 'core/list-item') {
						$content = isset($item['innerHTML']) ? strip_tags($item['innerHTML']) : '';
						$listItems[] = $content;
					}
				}
				
				// Determine list type
				$listType = isset($block['attrs']['ordered']) && $block['attrs']['ordered'] ? 
					\PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER : 
					\PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET;
				
				foreach ($listItems as $item) {
					$section->addListItem($item, 0, null, $listType);
				}
			}
			break;
			
		case 'core/table':
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				$tableStyle = array(
					'borderSize' => 6,
					'borderColor' => '000000',
					'cellMargin' => 50
				);
				
				$table = $section->addTable($tableStyle);
				
				foreach ($block['innerBlocks'] as $tableBlock) {
					if ($tableBlock['blockName'] === 'core/table-row') {
						$table->addRow();
						
						if (isset($tableBlock['innerBlocks']) && is_array($tableBlock['innerBlocks'])) {
							foreach ($tableBlock['innerBlocks'] as $cellBlock) {
								if ($cellBlock['blockName'] === 'core/table-cell') {
									$content = isset($cellBlock['innerHTML']) ? strip_tags($cellBlock['innerHTML']) : '';
									$table->addCell()->addText($content);
								}
							}
						}
					}
				}
			}
			break;
			
		default:
			// For other block types or nested blocks
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				foreach ($block['innerBlocks'] as $innerBlock) {
					tm_process_block_for_word($section, $innerBlock);
				}
			}
			break;
	}
}