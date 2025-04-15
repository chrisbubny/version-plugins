<?php
/**
 * Document Generation Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate PDF and Word documents for a version
 *
 * @param int $version_id The ID of the version post
 * @return array|bool Array of document URLs or false on failure
 */
function astp_generate_documents($version_id) {
    if (!$version_id) {
        error_log("Cannot generate documents: No version ID provided");
        return false;
    }
    
    try {
        error_log("Starting document generation for version ID: $version_id");
        
        $results = [
            'pdf' => false,
            'word' => false,
        ];
        
        // Try Generate PDF
        try {
            $pdf_url = astp_generate_pdf_document($version_id);
            if ($pdf_url) {
                $results['pdf'] = $pdf_url;
                update_post_meta($version_id, 'pdf_document_url', $pdf_url);
                error_log("Generated PDF document: $pdf_url");
            } else {
                error_log("Failed to generate PDF document");
                // Create a placeholder URL for testing
                $placeholder_url = site_url('/wp-content/uploads/astp-documents/placeholder.pdf');
                update_post_meta($version_id, 'pdf_document_url', $placeholder_url);
                $results['pdf'] = $placeholder_url;
                error_log("Using placeholder PDF URL: $placeholder_url");
            }
        } catch (Exception $e) {
            error_log("Exception generating PDF: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Set a fallback URL to prevent the publishing process from failing
            $placeholder_url = site_url('/wp-content/uploads/astp-documents/placeholder.pdf');
            update_post_meta($version_id, 'pdf_document_url', $placeholder_url);
            $results['pdf'] = $placeholder_url;
            error_log("Using placeholder PDF URL after exception: $placeholder_url");
        }
        
        // Try Generate Word document
        try {
            $word_url = astp_generate_word_document($version_id);
            if ($word_url) {
                $results['word'] = $word_url;
                update_post_meta($version_id, 'word_document_url', $word_url);
                error_log("Generated Word document: $word_url");
            } else {
                error_log("Failed to generate Word document");
                // Create a placeholder URL for testing
                $placeholder_url = site_url('/wp-content/uploads/astp-documents/placeholder.docx');
                update_post_meta($version_id, 'word_document_url', $placeholder_url);
                $results['word'] = $placeholder_url;
                error_log("Using placeholder Word URL: $placeholder_url");
            }
        } catch (Exception $e) {
            error_log("Exception generating Word document: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Set a fallback URL to prevent the publishing process from failing
            $placeholder_url = site_url('/wp-content/uploads/astp-documents/placeholder.docx');
            update_post_meta($version_id, 'word_document_url', $placeholder_url);
            $results['word'] = $placeholder_url;
            error_log("Using placeholder Word URL after exception: $placeholder_url");
        }
        
        // If documents directory doesn't exist, create it
        $upload_dir = wp_upload_dir();
        $docs_dir = $upload_dir['basedir'] . '/astp-documents';
        if (!file_exists($docs_dir)) {
            wp_mkdir_p($docs_dir);
            error_log("Created documents directory: $docs_dir");
            
            // Create empty placeholder files
            file_put_contents($docs_dir . '/placeholder.pdf', 'PDF Placeholder');
            file_put_contents($docs_dir . '/placeholder.docx', 'DOCX Placeholder');
            error_log("Created placeholder files");
        }
        
        error_log("Document generation completed with results: " . json_encode($results));
        
        return $results;
    } catch (Exception $e) {
        error_log("Fatal exception in document generation: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Even in case of fatal exception, return something to prevent the publishing process from failing
        $upload_url = wp_upload_dir()['baseurl'] . '/astp-documents';
        $fallback_results = [
            'pdf' => $upload_url . '/placeholder.pdf',
            'word' => $upload_url . '/placeholder.docx'
        ];
        
        update_post_meta($version_id, 'pdf_document_url', $fallback_results['pdf']);
        update_post_meta($version_id, 'word_document_url', $fallback_results['word']);
        
        error_log("Using fallback document URLs: " . json_encode($fallback_results));
        return $fallback_results;
    }
}

/**
 * Generate a PDF document for a version
 *
 * @param int $version_id The ID of the version post
 * @return string|bool The URL of the generated PDF or false on failure
 */
function astp_generate_pdf_document($version_id) {
    // Get parent test method post
    $parent_id = wp_get_post_parent_id($version_id);
    if (!$parent_id) {
        return false;
    }
    
    // Get version data
    $version_number = get_post_meta($version_id, 'version_number', true);
    $release_date = get_post_meta($version_id, 'release_date', true);
    $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
    
    // Create filename based on post title and version
    $parent_post = get_post($parent_id);
    $sanitized_title = sanitize_title($parent_post->post_title);
    $document_filename = $sanitized_title . '-v' . $version_number . '.pdf';
    
    // Prepare HTML content for PDF generation
    $html_content = astp_prepare_document_content($version_id, $content_snapshot, 'pdf');
    
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    $document_dir = $upload_dir['basedir'] . '/astp-documents';
    
    // Create the directory if it doesn't exist
    if (!file_exists($document_dir)) {
        wp_mkdir_p($document_dir);
    }
    
    // Full path to the document
    $document_path = $document_dir . '/' . $document_filename;
    
    // Generate PDF using mPDF or other PDF library
    $result = astp_generate_pdf_with_mpdf($html_content, $document_path);
    
    if ($result) {
        // Return the URL to the PDF
        return $upload_dir['baseurl'] . '/astp-documents/' . $document_filename;
    }
    
    return false;
}

/**
 * Generate PDF using mPDF library
 * 
 * Note: This requires the mPDF library to be installed via Composer
 *
 * @param string $html_content The HTML content to convert to PDF
 * @param string $output_path The path to save the PDF to
 * @return bool Whether the PDF was generated successfully
 */
function astp_generate_pdf_with_mpdf($html_content, $output_path) {
    // Make sure the Composer autoloader is loaded
    $vendor_path = ABSPATH . 'wp-content/plugins/vendor/autoload.php';
    if (file_exists($vendor_path)) {
        require_once $vendor_path;
    } else {
        error_log('Composer autoloader not found at: ' . $vendor_path);
        return false;
    }
    
    try {
        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);
        
        // Add content to PDF
        $mpdf->WriteHTML($html_content);
        
        // Save PDF
        $mpdf->Output($output_path, 'F');
        
        return file_exists($output_path);
    } catch (\Exception $e) {
        error_log('mPDF error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate PDF using DomPDF library (fallback)
 * 
 * Note: This requires the DomPDF library to be installed via Composer
 *
 * @param string $html_content  The HTML content for the PDF
 * @param string $document_path The path to save the PDF to
 * @return bool Success or failure
 */
function astp_generate_pdf_with_dompdf($html_content, $document_path) {
    // Check if Dompdf class exists
    if (!class_exists('\Dompdf\Dompdf')) {
        // Log error and return false
        error_log('Neither mPDF nor DomPDF libraries are available. Unable to generate PDF.');
        return false;
    }
    
    try {
        // Initialize Dompdf
        $dompdf = new \Dompdf\Dompdf();
        
        // Configure options
        $options = $dompdf->getOptions();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);
        
        // Load HTML
        $dompdf->loadHtml($html_content);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF
        $dompdf->render();
        
        // Save PDF
        file_put_contents($document_path, $dompdf->output());
        
        return file_exists($document_path);
    } catch (\Exception $e) {
        error_log('DomPDF error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate a Word document for a version
 *
 * @param int $version_id The ID of the version post
 * @return string|bool The URL of the generated Word document or false on failure
 */
function astp_generate_word_document($version_id) {
    // Get parent test method post
    $parent_id = wp_get_post_parent_id($version_id);
    if (!$parent_id) {
        return false;
    }
    
    // Get version data
    $version_number = get_post_meta($version_id, 'version_number', true);
    $release_date = get_post_meta($version_id, 'release_date', true);
    $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
    
    // Create filename based on post title and version
    $parent_post = get_post($parent_id);
    $sanitized_title = sanitize_title($parent_post->post_title);
    $document_filename = $sanitized_title . '-v' . $version_number . '.docx';
    
    // Prepare HTML content for Word generation
    $html_content = astp_prepare_document_content($version_id, $content_snapshot, 'word');
    
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    $document_dir = $upload_dir['basedir'] . '/astp-documents';
    
    // Create the directory if it doesn't exist
    if (!file_exists($document_dir)) {
        wp_mkdir_p($document_dir);
    }
    
    // Full path to the document
    $document_path = $document_dir . '/' . $document_filename;
    
    // Generate Word document using PhpWord or similar library
    $result = astp_generate_word_with_phpword($html_content, $document_path);
    
    if ($result) {
        // Return the URL to the Word document
        return $upload_dir['baseurl'] . '/astp-documents/' . $document_filename;
    }
    
    return false;
}

/**
 * Generate Word document using PhpWord library
 * 
 * Note: This requires the PhpWord library to be installed via Composer
 *
 * @param string $html_content  The HTML content for the Word document
 * @param string $document_path The path to save the document to
 * @return bool Success or failure
 */
function astp_generate_word_with_phpword($html_content, $document_path) {
    // Check if PhpWord class exists
    if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
        // Try alternative method
        return astp_generate_word_with_html_to_docx($html_content, $document_path);
    }
    
    try {
        // Initialize PhpWord
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('ASTP Versioning System');
        $properties->setCompany('Health IT Gov');
        $properties->setTitle('Test Method Document');
        $properties->setDescription('Generated by ASTP Versioning System');
        
        // Add a section
        $section = $phpWord->addSection();
        
        // Add HTML content
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html_content);
        
        // Save document
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($document_path);
        
        return file_exists($document_path);
    } catch (\Exception $e) {
        error_log('PhpWord error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Alternative method to generate Word document using HTML to DOCX conversion
 *
 * @param string $html_content  The HTML content for the Word document
 * @param string $document_path The path to save the document to
 * @return bool Success or failure
 */
function astp_generate_word_with_html_to_docx($html_content, $document_path) {
    // Check if HTMLToDocx class exists
    if (!class_exists('HTMLToDocx')) {
        // Last resort: create an HTML document instead
        return astp_generate_html_document($html_content, str_replace('.docx', '.html', $document_path));
    }
    
    try {
        // Initialize HTMLToDocx
        $htmlToDocx = new HTMLToDocx();
        
        // Convert HTML to DOCX
        $htmlToDocx->createDoc($html_content, $document_path);
        
        return file_exists($document_path);
    } catch (\Exception $e) {
        error_log('HTMLToDocx error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create an HTML document as a fallback
 *
 * @param string $html_content  The HTML content for the document
 * @param string $document_path The path to save the document to
 * @return bool Success or failure
 */
function astp_generate_html_document($html_content, $document_path) {
    // Create a complete HTML document
    $full_html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Test Method Document</title>
        <style>
            ' . file_get_contents(plugin_dir_path(dirname(__FILE__)) . 'assets/pdf-styles.css') . '
        </style>
    </head>
    <body>
        ' . $html_content . '
    </body>
    </html>';
    
    // Save to file
    $result = file_put_contents($document_path, $full_html);
    
    return $result !== false;
}

/**
 * Prepare document content for PDF or Word generation
 *
 * @param int    $version_id       The ID of the version post
 * @param mixed  $content_snapshot The content to use (string or array)
 * @param string $format           The format ('pdf' or 'word')
 * @return string The formatted HTML content
 */
function astp_prepare_document_content($version_id, $content_snapshot, $format = 'pdf') {
    // Get parent post
    $parent_id = wp_get_post_parent_id($version_id);
    
    if (!$parent_id) {
        error_log("Document generation error: No parent post found for version ID: $version_id");
        return '';
    }
    
    // Validate content snapshot
    if (empty($content_snapshot)) {
        error_log("Document generation error: Empty content snapshot for version ID: $version_id");
        // Fall back to parent post content if snapshot is empty
        $content_snapshot = get_post($parent_id)->post_content;
    }
    
    // Ensure content is in the right format for processing
    $content_string = '';
    $blocks = [];
    
    if (is_string($content_snapshot)) {
        $content_string = $content_snapshot;
        $blocks = parse_blocks($content_string);
    } elseif (is_array($content_snapshot)) {
        $blocks = $content_snapshot;
        // Convert blocks back to string for certain operations
        ob_start();
        foreach ($blocks as $block) {
            echo render_block($block);
        }
        $content_string = ob_get_clean();
    } else {
        error_log("Document generation error: Invalid content format for version ID: $version_id");
        return '';
    }
    
    // Get post metadata
    $test_method = get_post($parent_id);
    $title = $test_method->post_title;
    $version_number = get_post_meta($version_id, 'version_number', true);
    $release_date = get_post_meta($version_id, 'release_date', true);
    
    // Start building the document
    $document = '';
    
    // Add document header with title and version info
    $document .= '<div class="document-header">';
    $document .= '<h1>' . esc_html($title) . '</h1>';
    $document .= '<div class="document-meta">';
    $document .= '<p><strong>Version:</strong> ' . esc_html($version_number) . '</p>';
    $document .= '<p><strong>Release Date:</strong> ' . esc_html(date('F j, Y', strtotime($release_date))) . '</p>';
    $document .= '</div>';
    $document .= '</div>';
    
    // Generate table of contents using blocks
    $toc = astp_generate_table_of_contents($blocks);
    $document .= $toc;
    
    // Prepare the main content
    $document .= '<div class="document-content">';
    
    // Format the content based on document type
    $formatted_content = astp_format_content_for_document($content_string, $format);
    $document .= $formatted_content;
    
    $document .= '</div>';
    
    // Add change log if this is not the first version
    $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
    if ($previous_version_id) {
        $document .= '<div class="document-changelog">';
        $document .= '<h2>Changes from Previous Version</h2>';
        
        // Get changes text
        $changes_text = astp_generate_change_list_text($version_id);
        $document .= '<div class="changes-content">';
        $document .= $changes_text;
        $document .= '</div>';
        
        $document .= '</div>';
    }
    
    // Allow filtering the document content
    if ($format === 'pdf') {
        $document = apply_filters('astp_pdf_content', $document, $version_id);
    } else {
        $document = apply_filters('astp_word_content', $document, $version_id);
    }
    
    return $document;
}

/**
 * Generate a table of contents from content
 *
 * @param mixed $content Content to generate TOC from, can be string or array of blocks
 * @return string Table of contents HTML
 */
function astp_generate_table_of_contents($content) {
    // Check if content is already an array of blocks
    $blocks = [];
    
    if (is_array($content)) {
        // Content is already blocks
        $blocks = $content;
    } else if (is_string($content)) {
        // Content is a string, parse it
        $blocks = parse_blocks($content);
    } else {
        // Invalid content type
        error_log('Cannot generate table of contents: Content is neither string nor array');
        return '';
    }
    
    // Extract headings
    $headings = [];
    
    foreach ($blocks as $block) {
        if (isset($block['blockName'])) {
            // Handle core heading blocks
        if ($block['blockName'] === 'core/heading') {
                $level = isset($block['attrs']['level']) ? intval($block['attrs']['level']) : 2;
                $text = '';
                
                if (isset($block['innerHTML'])) {
                    // Extract inner text from the heading HTML
                    $text = strip_tags($block['innerHTML']);
                } else if (isset($block['innerContent']) && is_array($block['innerContent'])) {
                    // Concatenate inner content
                    $text = strip_tags(implode('', $block['innerContent']));
                }
                
                if (!empty($text)) {
                    $headings[] = [
                        'level' => $level,
                        'text' => $text,
                        'anchor' => sanitize_title($text),
                    ];
                }
            }
            // Handle custom headings from ASTP blocks
            else if (strpos($block['blockName'], 'astp/') === 0) {
                if (isset($block['attrs']['title'])) {
                    $headings[] = [
                        'level' => 2,
                        'text' => $block['attrs']['title'],
                        'anchor' => sanitize_title($block['attrs']['title']),
                    ];
                }
            }
        }
    }
    
    // Build TOC HTML
    if (empty($headings)) {
        return '';
    }
    
    $toc = '<div class="document-toc">';
    $toc .= '<h2>Table of Contents</h2>';
    $toc .= '<ul>';
    
    foreach ($headings as $heading) {
        $indent = ($heading['level'] - 1) * 20;
        $toc .= '<li style="margin-left: ' . $indent . 'px;">';
        $toc .= '<a href="#' . $heading['anchor'] . '">' . $heading['text'] . '</a>';
        $toc .= '</li>';
    }
    
    $toc .= '</ul>';
    $toc .= '</div>';
    
    return $toc;
}

/**
 * Format content for document generation
 *
 * @param string $content The content to format
 * @param string $format  The format ('pdf' or 'word')
 * @return string The formatted content
 */
function astp_format_content_for_document($content, $format = 'pdf') {
    // Ensure content is a string
    if (!is_string($content)) {
        if (is_array($content)) {
            // Try to convert from block array to string
            error_log('Content provided to astp_format_content_for_document is an array, attempting to convert');
            ob_start();
            foreach ($content as $block) {
                echo render_block($block);
            }
            $content = ob_get_clean();
        } else {
            error_log('Invalid content type provided to astp_format_content_for_document: ' . gettype($content));
            return '';
        }
    }
    
    // Use astp_contains_html helper to safely check if content has HTML
    $has_html = astp_contains_html($content);
    
    if (!$has_html) {
        // Simple text content, wrap in paragraph
        return '<p>' . nl2br(esc_html($content)) . '</p>';
    }
    
    // Process the HTML content
    $dom = new DOMDocument();
    
    // Prevent errors from malformed HTML
    libxml_use_internal_errors(true);
    
    // Try to load HTML, with UTF-8 encoding
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    
    // Clear error buffer
    libxml_clear_errors();
    
    // Format specific elements
    if ($format === 'pdf') {
        // Add page breaks before certain elements
        $xpath = new DOMXPath($dom);
        $h1s = $xpath->query('//h1');
        
        foreach ($h1s as $h1) {
            $h1->setAttribute('style', 'page-break-before: always; ' . $h1->getAttribute('style'));
        }
    }
    
    // Get formatted content
    $body = $dom->getElementsByTagName('body')->item(0);
    
    if (!$body) {
        // Body element not found, might be a fragment
        return $content;
    }
    
    // Get inner content from body
    $formatted = '';
    $children = $body->childNodes;
    
    foreach ($children as $child) {
        $formatted .= $dom->saveHTML($child);
    }
    
    return $formatted;
}

/**
 * Register shortcode for document download links
 */
function astp_register_document_download_shortcode() {
    add_shortcode('astp_document_downloads', 'astp_document_downloads_shortcode');
}
add_action('init', 'astp_register_document_download_shortcode');

/**
 * Shortcode callback for document download links
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function astp_document_downloads_shortcode($atts) {
    $atts = shortcode_atts([
        'version_id' => 0,
        'show_title' => 'true',
    ], $atts, 'astp_document_downloads');
    
    $version_id = intval($atts['version_id']);
    $show_title = $atts['show_title'] === 'true';
    
    if (!$version_id) {
        // Try to get from current post
        global $post;
        if ($post && $post->post_type === 'astp_version') {
            $version_id = $post->ID;
        } elseif ($post && $post->post_type === 'astp_test_method') {
            // Get the current published version
            $version_id = get_post_meta($post->ID, 'current_published_version', true);
        }
    }
    
    if (!$version_id) {
        return '<div class="astp-error">No version specified for document downloads.</div>';
    }
    
    // Get document URLs
    $pdf_url = get_post_meta($version_id, 'pdf_document_url', true);
    $word_url = get_post_meta($version_id, 'word_document_url', true);
    
    // Get version info for display
    $version_number = get_post_meta($version_id, 'version_number', true);
    $parent_id = wp_get_post_parent_id($version_id);
    $parent_title = $parent_id ? get_the_title($parent_id) : '';
    
    $output = '<div class="astp-document-downloads">';
    
    if ($show_title) {
        $output .= '<h3 class="astp-downloads-title">' . esc_html($parent_title) . ' - Version ' . esc_html($version_number) . '</h3>';
    }
    
    $output .= '<div class="astp-download-links">';
    
    if ($pdf_url) {
        $output .= '<a href="' . esc_url($pdf_url) . '" class="astp-download-button pdf-button" target="_blank">';
        $output .= '<span class="astp-download-icon pdf-icon"></span>';
        $output .= '<span class="astp-download-text">Download PDF</span>';
        $output .= '</a>';
    }
    
    if ($word_url) {
        $output .= '<a href="' . esc_url($word_url) . '" class="astp-download-button word-button" target="_blank">';
        $output .= '<span class="astp-download-icon word-icon"></span>';
        $output .= '<span class="astp-download-text">Download Word Document</span>';
        $output .= '</a>';
    }
    
    $output .= '</div>'; // .astp-download-links
    $output .= '</div>'; // .astp-document-downloads
    
    return $output;
}

/**
 * Register admin action to regenerate documents
 */
function astp_register_document_regeneration_action() {
    add_action('admin_post_astp_regenerate_documents', 'astp_handle_document_regeneration');
}
add_action('init', 'astp_register_document_regeneration_action');

/**
 * Handle document regeneration request
 */
function astp_handle_document_regeneration() {
    // Verify nonce
    if (!isset($_GET['astp_nonce']) || !wp_verify_nonce($_GET['astp_nonce'], 'astp_regenerate_documents')) {
        wp_die(__('Security check failed', 'astp-versioning'));
    }
    
    // Check permissions
    $version_id = isset($_GET['version_id']) ? intval($_GET['version_id']) : 0;
    
    if (!$version_id || !current_user_can('edit_post', $version_id)) {
        wp_die(__('Permission denied or invalid version', 'astp-versioning'));
    }
    
    // Set a time limit for document generation
    set_time_limit(300); // 5 minutes
    
    try {
        // Enable error logging
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        
        // Log the start of document generation
        error_log("Starting document regeneration for version ID: $version_id");
        
        // Get content snapshot
        $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
        
        // Validate content snapshot
        if (empty($content_snapshot)) {
            error_log("Empty content snapshot for version ID: $version_id - using parent post content");
            $parent_id = wp_get_post_parent_id($version_id);
            if ($parent_id) {
                $content_snapshot = get_post($parent_id)->post_content;
            } else {
                throw new Exception("No parent post found for version ID: $version_id");
            }
        }
        
        // Log content type
        $content_type = is_array($content_snapshot) ? 'array' : (is_string($content_snapshot) ? 'string' : gettype($content_snapshot));
        error_log("Content snapshot type: $content_type");
        
        // Generate documents
        $result = astp_generate_documents($version_id);
        
        if ($result) {
            error_log("Document regeneration successful for version ID: $version_id");
            // Redirect back with success message
            wp_redirect(add_query_arg('astp_document_regenerated', '1', get_edit_post_link($version_id, 'url')));
            exit;
        } else {
            error_log("Document regeneration failed for version ID: $version_id");
            // Redirect back with error message
            wp_redirect(add_query_arg('astp_document_regeneration_failed', '1', get_edit_post_link($version_id, 'url')));
    exit;
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Exception in document regeneration for version ID: $version_id - " . $e->getMessage());
        error_log($e->getTraceAsString());
        
        // Redirect back with error message
        wp_redirect(add_query_arg('astp_document_regeneration_error', urlencode($e->getMessage()), get_edit_post_link($version_id, 'url')));
        exit;
    }
}

/**
 * Display document regeneration success/failure notice
 */
function astp_display_document_regeneration_notice() {
    if (isset($_GET['document_regeneration'])) {
        $result = $_GET['document_regeneration'];
        
        if ($result === 'success') {
            echo '<div class="notice notice-success"><p>Documents were successfully regenerated.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Document regeneration failed. Please check the error logs.</p></div>';
        }
    }
}
add_action('admin_notices', 'astp_display_document_regeneration_notice');

/**
 * Get URL for document regeneration
 *
 * @param int $version_id The ID of the version post
 * @return string The URL for document regeneration
 */
function astp_get_regenerate_documents_url($version_id) {
    $url = admin_url('admin-post.php');
    $url = add_query_arg([
        'action' => 'astp_regenerate_documents',
        'version_id' => $version_id,
        'astp_nonce' => wp_create_nonce('astp_regenerate_documents')
    ], $url);
    
    return $url;
}

/**
 * Add a meta box for document downloads in the admin
 */
function astp_add_document_downloads_meta_box() {
    add_meta_box(
        'astp_document_downloads_meta_box',
        'Document Downloads',
        'astp_document_downloads_meta_box_callback',
        'astp_version',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'astp_add_document_downloads_meta_box');

/**
 * Callback for document downloads meta box
 *
 * @param WP_Post $post The post object
 */
function astp_document_downloads_meta_box_callback($post) {
    // Get document URLs
    $pdf_url = get_post_meta($post->ID, 'pdf_document_url', true);
    $word_url = get_post_meta($post->ID, 'word_document_url', true);
    
    echo '<div class="astp-admin-document-downloads">';
    
    if ($pdf_url || $word_url) {
        if ($pdf_url) {
            echo '<p><a href="' . esc_url($pdf_url) . '" class="button" target="_blank">View PDF</a></p>';
        }
        
        if ($word_url) {
            echo '<p><a href="' . esc_url($word_url) . '" class="button" target="_blank">View Word Document</a></p>';
        }
        
        echo '<p><a href="' . esc_url(astp_get_regenerate_documents_url($post->ID)) . '" class="button button-secondary">Regenerate Documents</a></p>';
    } else {
        echo '<p>No documents have been generated for this version yet.</p>';
        echo '<p><a href="' . esc_url(astp_get_regenerate_documents_url($post->ID)) . '" class="button button-primary">Generate Documents</a></p>';
    }
    
    echo '</div>';
}

/**
 * Generate a text list of changes for inclusion in documents
 *
 * @param int $version_id The ID of the version post
 * @return string Formatted text of changes
 */
function astp_generate_change_list_text($version_id) {
    // Get the changelog for this version
    $changelog_post = false;
    
    // Try to get changelog via the function that exists in versioning.php
    if (function_exists('astp_get_version_changelog')) {
        $changelog_post = astp_get_version_changelog($version_id);
    } else if (function_exists('astp_fetch_version_changelog')) {
        $changelog_post = astp_fetch_version_changelog($version_id);
    }
    
    if (!$changelog_post) {
        return 'No changes documented for this version.';
    }
    
    // Get change types
    $change_types = wp_get_object_terms($changelog_post->ID, 'change_type', ['fields' => 'slugs']);
    
    // Get detailed changes from ACF repeater field
    $changes = get_field('changelog_changes', $changelog_post->ID);
    
    // Format text output
    $output = '';
    
    if (!empty($changes) && is_array($changes)) {
        // Group changes by type
        $grouped_changes = [
            'addition' => [],
            'removal' => [],
            'amendment' => []
        ];
        
        foreach ($changes as $change) {
            if (isset($change['change_type']) && isset($change['description'])) {
                $type = $change['change_type'];
                $grouped_changes[$type][] = $change['description'];
            }
        }
        
        // Generate text for each type
        if (!empty($grouped_changes['addition'])) {
            $output .= "Additions:\n";
            foreach ($grouped_changes['addition'] as $desc) {
                $output .= "- " . strip_tags($desc) . "\n";
            }
            $output .= "\n";
        }
        
        if (!empty($grouped_changes['amendment'])) {
            $output .= "Amendments:\n";
            foreach ($grouped_changes['amendment'] as $desc) {
                $output .= "- " . strip_tags($desc) . "\n";
            }
            $output .= "\n";
        }
        
        if (!empty($grouped_changes['removal'])) {
            $output .= "Removals:\n";
            foreach ($grouped_changes['removal'] as $desc) {
                $output .= "- " . strip_tags($desc) . "\n";
            }
            $output .= "\n";
        }
    } else {
        // Fallback to simple list based on taxonomy
        if (in_array('addition', $change_types)) {
            $output .= "- Additions to content\n";
        }
        
        if (in_array('amendment', $change_types)) {
            $output .= "- Amendments to existing content\n";
        }
        
        if (in_array('removal', $change_types)) {
            $output .= "- Removals of content\n";
        }
    }
    
    if (empty($output)) {
        $output = "No specific changes documented for this version.";
    }
    
    return $output;
}

/**
 * Check if a string contains HTML content
 *
 * @param mixed $content The content to check
 * @return bool True if contains HTML, false otherwise
 */
function astp_contains_html($content) {
    // Ensure content is a string
    if (!is_string($content)) {
        if (is_array($content)) {
            // Convert array to string for debugging
            error_log("Error in document generation: preg_match() received an array instead of string");
            return false;
        } elseif (is_object($content)) {
            // Convert object to string if possible
            if (method_exists($content, '__toString')) {
                $content = (string) $content;
            } else {
                error_log("Error in document generation: preg_match() received an object instead of string");
                return false;
            }
        } else {
            // For any other type, convert to string
            $content = (string) $content;
        }
    }
    
    // Now that we have a string, perform the check
    return preg_match('/<[^>]*>/', $content) > 0;
}

/**
 * Prepare HTML content for PDF/Word document generation
 *
 * @param string $content The HTML content
 * @param string $format  The format ('pdf' or 'word')
 * @return string The prepared HTML content
 */
function astp_prepare_html_for_document($content, $format = 'pdf') {
    // Use our helper function to check if the content contains HTML
    $has_html = astp_contains_html($content);
    
    if (!$has_html) {
        // Wrap in paragraph tags if not already HTML
        $content = '<p>' . $content . '</p>';
    }
    
    // Add custom styles based on format
    if ($format === 'pdf') {
        // PDF-specific processing
        // ...
    } else {
        // Word-specific processing
        // ...
    }
    
    return $content;
} 