/**
 * USWDS Custom Blocks
 * This file now serves as a registration confirmation for blocks.
 * 
 * IMPORTANT: Each block script is now individually registered and enqueued by WordPress,
 * so we no longer need to use JavaScript imports which can be problematic in some WordPress environments.
 */

// Make sure we're in WordPress
if (typeof window.wp !== 'undefined') {
    console.log('USWDS Custom Blocks: Main entry point loaded');
    
    // List all blocks that should be available
    var expectedBlocks = [
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
    
    // Add logging when this file loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof wp.blocks !== 'undefined') {
                var registeredBlocks = wp.blocks.getBlockTypes().map(function(block) {
                    return block.name;
                });
                
                console.log('USWDS Blocks: Registration check complete');
                console.log('- Registered blocks:', registeredBlocks);
                
                // Check which expected blocks are missing
                var missingBlocks = expectedBlocks.filter(function(blockName) {
                    return !registeredBlocks.includes(blockName);
                });
                
                if (missingBlocks.length > 0) {
                    console.warn('USWDS Blocks: Some blocks are missing:', missingBlocks);
                } else {
                    console.log('USWDS Blocks: All expected blocks are registered');
                }
            }
        }, 1000);
    });
} 