// Register and enqueue block scripts
foreach ($blocks as $block => $config) {
    $script_handle = "astp-{$block}";
    $script_path = "custom-blocks/uswds-{$block}.js";
    $script_url = ASTP_VERSIONING_URL . $script_path;
    $script_dir = ASTP_VERSIONING_DIR . $script_path;
    
    // Define dependencies including core and any child components
    $dependencies = $core_deps;
    if (isset($config['deps']) && is_array($config['deps'])) {
        foreach ($config['deps'] as $dep) {
            $dependencies[] = "astp-{$dep}";
        }
    }
    
    // Register the script
    if (file_exists($script_dir)) {
        wp_register_script(
            $script_handle,
            $script_url,
            $dependencies,
            filemtime($script_dir),
            true
        );
        
        // Special handling for changelog block since it has server-side rendering
        if ($block === 'changelog-block') {
            // Don't register here - it's handled in changelog-render.php
            if (WP_DEBUG) {
                error_log('Changelog block script registered as: ' . $script_handle);
            }
            continue;
        }
        
        // Register the block type
        register_block_type( "uswds-gutenberg/{$block}", array(
            'editor_script' => $script_handle,
            'category' => $config['category'],
        ) );
    }
} 