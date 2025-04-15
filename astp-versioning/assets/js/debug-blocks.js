/**
 * Debug script for Test Method Blocks
 */
(function($) {
    $(document).ready(function() {
        console.log('Debug script initialized');
        
        // Check if we're in the block editor
        if (typeof wp === 'undefined') {
            console.log('WordPress API not detected');
            return;
        }
        
        if (typeof wp.blocks === 'undefined') {
            console.log('WordPress Blocks API not detected, but WordPress API exists');
            // Log what WordPress components are available
            console.log('Available WordPress components:', Object.keys(wp));
            return;
        }
        
        console.log('WordPress Block Editor detected');
        
        // Check what dependencies are loaded
        console.log('wp.blockEditor available:', typeof wp.blockEditor !== 'undefined');
        console.log('wp.editor available:', typeof wp.editor !== 'undefined');
        
        // List all registered block categories
        console.log('Block Categories:', wp.blocks.getCategories());
        
        // Check if our custom category exists
        const hasCustomCategory = wp.blocks.getCategories().some(
            category => category.slug === 'astp-test-method'
        );
        console.log('Custom category exists:', hasCustomCategory);
        
        // List all registered blocks
        const allBlocks = wp.blocks.getBlockTypes();
        console.log('All registered blocks:', allBlocks);
        
        // Check for our custom blocks
        const customBlocks = allBlocks.filter(block => block.name.startsWith('astp/'));
        console.log('Custom blocks found:', customBlocks);
        console.log('Custom block count:', customBlocks.length);
        
        if (customBlocks.length === 0) {
            console.log('No custom blocks found. Checking if "astp" namespace exists in any blocks...');
            const anyAstpBlocks = allBlocks.filter(block => block.name.includes('astp'));
            console.log('Any blocks with "astp" in name:', anyAstpBlocks);
        }
        
        // Additional debugging information
        console.log('WordPress version:', wpVersion || 'Unknown');
        console.log('Block editor version:', wp.blockEditor?.version || wp.editor?.version || 'Unknown');
        console.log('Dependencies loaded:',  {
            jquery: typeof $ !== 'undefined',
            react: typeof React !== 'undefined',
            lodash: typeof _ !== 'undefined'
        });
        
        // Log if our script with blocks is loaded
        console.log('Script "astp-test-method-blocks" registered in wp.editor:', 
                   $('script[src*="test-method-blocks.js"]').length > 0);
    });
})(jQuery); 