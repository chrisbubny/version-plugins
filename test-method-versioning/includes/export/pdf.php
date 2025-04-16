<?php
/**
 * Handles PDF document export
 */

/**
 * Generate a PDF document for a specific version
 */
function tm_generate_pdf_document($post_id, $version = '') {
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
	$filename = sanitize_title($title) . '-' . $version . '.pdf';
	
	// Include TCPDF library
	require_once TM_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
	
	// Create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	
	// Set document information
	$pdf->SetCreator('Test Method Versioning');
	$pdf->SetAuthor('HealthIT.gov');
	$pdf->SetTitle($title);
	$pdf->SetSubject('Version ' . $version);
	
	// Set header and footer
	$pdf->setHeaderData('', 0, $title, 'Version: ' . $version);
	$pdf->setFooterData(array(0,0,0), array(0,0,0));
	
	// Set margins
	$pdf->SetMargins(15, 27, 15);
	$pdf->SetHeaderMargin(5);
	$pdf->SetFooterMargin(10);
	
	// Set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, 25);
	
	// Add a page
	$pdf->AddPage();
	
	// Process content
	$blocks = parse_blocks($content);
	
	foreach ($blocks as $block) {
		tm_process_block_for_pdf($pdf, $block);
	}
	
	// Get WordPress upload directory
	$upload_dir = wp_upload_dir();
	$target_path = $upload_dir['path'] . '/' . $filename;
	
	// Save PDF to file
	$pdf->Output($target_path, 'F');
	
	// Create attachment in media library
	$attachment = array(
		'post_mime_type' => 'application/pdf',
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
		
		$documents[$version]['pdf'] = $attachment_id;
		
		update_post_meta($post_id, 'documents', $documents);
		
		do_action('tm_document_generated', $post_id, $version, 'pdf');
		
		return $attachment_id;
	}
	
	return false;
}

/**
 * Process a block for PDF document
 */
function tm_process_block_for_pdf($pdf, $block) {
	// Process different block types
	switch ($block['blockName']) {
		case 'core/paragraph':
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$pdf->Write(0, $content, '', 0, 'L', true, 0, false, false, 0);
			$pdf->Ln(5);
			break;
			
		case 'core/heading':
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
			
			// Set heading style based on level
			switch ($level) {
				case 1:
					$pdf->SetFont('helvetica', 'B', 18);
					break;
				case 2:
					$pdf->SetFont('helvetica', 'B', 16);
					break;
				case 3:
					$pdf->SetFont('helvetica', 'B', 14);
					break;
				default:
					$pdf->SetFont('helvetica', 'B', 12);
					break;
			}
			
			$pdf->Write(0, $content, '', 0, 'L', true, 0, false, false, 0);
			$pdf->Ln(8);
			
			// Reset font
			$pdf->SetFont('helvetica', '', 11);
			break;
			
		case 'core/list':
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				foreach ($block['innerBlocks'] as $index => $item) {
					if ($item['blockName'] === 'core/list-item') {
						$content = isset($item['innerHTML']) ? strip_tags($item['innerHTML']) : '';
						$prefix = isset($block['attrs']['ordered']) && $block['attrs']['ordered'] ? 
							($index + 1) . '. ' : '• ';
						$pdf->Write(0, $prefix . $content, '', 0, 'L', true, 0, false, false, 0);
						$pdf->Ln(2);
					}
				}
				$pdf->Ln(3);
			}
			break;
			
		case 'core/table':
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				$html = '<table border="1" cellpadding="5">';
				
				foreach ($block['innerBlocks'] as $tableBlock) {
					if ($tableBlock['blockName'] === 'core/table-row') {
						$html .= '<tr>';
						
						if (isset($tableBlock['innerBlocks']) && is_array($tableBlock['innerBlocks'])) {
							foreach ($tableBlock['innerBlocks'] as $cellBlock) {
								if ($cellBlock['blockName'] === 'core/table-cell') {
									$content = isset($cellBlock['innerHTML']) ? $cellBlock['innerHTML'] : '';
									$html .= '<td>' . $content . '</td>';
								}
							}
						}
						
						$html .= '</tr>';
					}
				}
				
				$html .= '</table>';
				
				$pdf->writeHTML($html, true, false, true, false, '');
				$pdf->Ln(5);
			}
			break;
			
		default:
			// For other block types or nested blocks
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				foreach ($block['innerBlocks'] as $innerBlock) {
					tm_process_block_for_pdf($pdf, $innerBlock);
				}
			}
			break;
	}
}