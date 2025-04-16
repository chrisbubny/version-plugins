<?php
/**
 * Handles block versioning
 */

/**
 * Process a block for versioning
 */
function tm_process_block_for_versioning($block_content, $block) {
	// Only process blocks that need versioning
	$block_types_to_version = array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/table',
		// Add other block types as needed
	);
	
	if (!in_array($block['blockName'], $block_types_to_version)) {
		return $block_content;
	}
	
	// Check if block has ID
	if (!isset($block['attrs']['blockId'])) {
		// Generate a unique ID for the block
		$block_id = wp_generate_uuid4();
		
		// Add the ID to the block
		$block_content = str_replace('<div class="', '<div data-block-id="' . $block_id . '" class="', $block_content);
	}
	
	return $block_content;
}
add_filter('render_block', 'tm_process_block_for_versioning', 10, 2);

/**
 * Compare blocks between versions
 */
function tm_compare_blocks($old_blocks, $new_blocks) {
	$changes = array(
		'added' => array(),
		'deleted' => array(),
		'modified' => array()
	);
	
	// Create a map of blocks by ID for easier comparison
	$old_blocks_map = array();
	$new_blocks_map = array();
	
	// Map old blocks
	foreach ($old_blocks as $block) {
		if (isset($block['attrs']['blockId'])) {
			$old_blocks_map[$block['attrs']['blockId']] = $block;
		}
	}
	
	// Map new blocks
	foreach ($new_blocks as $block) {
		if (isset($block['attrs']['blockId'])) {
			$new_blocks_map[$block['attrs']['blockId']] = $block;
		}
	}
	
	// Find added and modified blocks
	foreach ($new_blocks_map as $id => $block) {
		if (!isset($old_blocks_map[$id])) {
			$changes['added'][] = $block;
		} else {
			// Check if content or attributes changed
			$old_block = $old_blocks_map[$id];
			
			// Compare inner HTML or content
			$old_content = isset($old_block['innerHTML']) ? $old_block['innerHTML'] : (isset($old_block['content']) ? $old_block['content'] : '');
			$new_content = isset($block['innerHTML']) ? $block['innerHTML'] : (isset($block['content']) ? $block['content'] : '');
			
			// Compare attributes
			$old_attrs = isset($old_block['attrs']) ? $old_block['attrs'] : array();
			$new_attrs = isset($block['attrs']) ? $block['attrs'] : array();
			
			// Exclude blockId from comparison
			unset($old_attrs['blockId']);
			unset($new_attrs['blockId']);
			
			if ($old_content !== $new_content || json_encode($old_attrs) !== json_encode($new_attrs)) {
				$changes['modified'][] = array(
					'old' => $old_block,
					'new' => $block
				);
			}
		}
	}
	
	// Find deleted blocks
	foreach ($old_blocks_map as $id => $block) {
		if (!isset($new_blocks_map[$id])) {
			$changes['deleted'][] = $block;
		}
	}
	
	return $changes;
}