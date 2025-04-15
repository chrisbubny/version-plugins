/**
 * USWDS Blocks Loader
 * 
 * This file ensures all blocks are properly registered with the WordPress block editor.
 * It provides a unified entry point and ensures proper dependency loading.
 */

// WordPress dependencies
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

// Make sure the blocks get registered
document.addEventListener('DOMContentLoaded', function() {
    // If blocks aren't appearing in the editor, uncomment these logs for debugging
    if (window.wp && window.wp.blocks) {
        console.log('WordPress Blocks API available:', window.wp.blocks);
        console.log('Block categories:', window.wp.blocks.getCategories());
        console.log('Registered blocks:', window.wp.blocks.getBlockTypes());
    } else {
        console.error('WordPress Blocks API not available');
    }
    
    // Import all block files
    // This is already being done in uswds-blocks.js
    
    // Force editor to refresh block list
    if (window.wp && window.wp.blocks && window.wp.blocks.updateCategory) {
        // Update each category to force a refresh of the editor UI
        const categories = window.wp.blocks.getCategories();
        categories.forEach(category => {
            window.wp.blocks.updateCategory(category.slug, {});
        });
        
        console.log('USWDS blocks loaded and editor refreshed');
    }
});

// Check if blocks are registered 
window.setTimeout(function() {
    if (window.wp && window.wp.blocks) {
        const registeredBlocks = window.wp.blocks.getBlockTypes().map(block => block.name);
        
        // List of expected USWDS blocks
        const uswdsBlocks = [
            'uswds-gutenberg/clarification-block',
            'uswds-gutenberg/regulatory-block',
            'uswds-gutenberg/standards-block',
            'uswds-gutenberg/dependencies-block',
            'uswds-gutenberg/resources-block',
            'uswds-gutenberg/testing-component-block',
            'uswds-gutenberg/testing-steps-block',
            'uswds-gutenberg/testing-tools-block',
            'uswds-gutenberg/changelog-block'
        ];
        
        // Check which blocks are missing
        const missingBlocks = uswdsBlocks.filter(block => !registeredBlocks.includes(block));
        
        if (missingBlocks.length > 0) {
            console.warn('Missing USWDS blocks:', missingBlocks);
        } else {
            console.log('All USWDS blocks successfully registered');
        }
    }
}, 1000); // Check after 1 second to allow time for registration 