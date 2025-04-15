<?php
/**
 * Block Setup and Registration
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the Test Method block category
 */
function astp_register_block_category($categories, $post) {
    $new_categories = array();
    $category_slugs = array('astp-test-method', 'astp-ccg', 'astp-tp');
    
    // Check if our categories already exist
    foreach ($category_slugs as $slug) {
        $found = false;
        foreach ($categories as $category) {
            if ($category['slug'] === $slug) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            switch ($slug) {
                case 'astp-test-method':
                    $new_categories[] = array(
                        'slug'  => 'astp-test-method',
                        'title' => __('Test Method Sections', 'astp-versioning'),
                        'icon'  => 'clipboard',
                    );
                    break;
                case 'astp-ccg':
                    $new_categories[] = array(
                        'slug'  => 'astp-ccg',
                        'title' => __('Certification Companion Guide', 'astp-versioning'),
                        'icon'  => 'clipboard',
                    );
                    break;
                case 'astp-tp':
                    $new_categories[] = array(
                        'slug'  => 'astp-tp',
                        'title' => __('Test Procedure', 'astp-versioning'),
                        'icon'  => 'edit',
                    );
                    break;
            }
        }
    }
    
    return array_merge($categories, $new_categories);
}

// Use the appropriate filter based on WordPress version
if (version_compare(get_bloginfo('version'), '5.8', '>=')) {
    // WordPress 5.8+ - use the new filter
    add_filter('block_categories_all', 'astp_register_block_category', 5, 2);
} else {
    // WordPress < 5.8 - use the old filter
    add_filter('block_categories', 'astp_register_block_category', 5, 2);
}

/**
 * Initialize blocks with custom actions
 */
function astp_init_blocks() {
    do_action('astp_before_blocks_init');
    
    // Run any block initialization code here
    // This action is called before test-method-blocks.php is loaded
    
    do_action('astp_after_blocks_init');
}
add_action('init', 'astp_init_blocks', 5); 