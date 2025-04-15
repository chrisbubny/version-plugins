/**
 * USWDS Blocks Debug Helper
 * 
 * This file directly registers blocks that might have issues with registration
 * to ensure they appear in the editor.
 */

// Only run debug mode if explicitly enabled
if (window.ASTP_DEBUG_MODE === true) {
    (function() {
        // Wait for WordPress to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                console.log('USWDS Blocks Debug: Attempting to fix block registration');
                
                if (!window.wp || !window.wp.blocks || !window.wp.blocks.registerBlockType) {
                    console.error('USWDS Blocks Debug: WordPress blocks API not available');
                    return;
                }
                
                const { registerBlockType } = window.wp.blocks;
                const { createElement } = window.wp.element;
                const registeredBlocks = window.wp.blocks.getBlockTypes().map(block => block.name);
                
                // Problematic blocks to check and fix
                const problemBlocks = [
                    'uswds-gutenberg/testing-component-block',
                    'uswds-gutenberg/testing-steps-block',
                    'uswds-gutenberg/testing-tools-block',
                    'uswds-gutenberg/changelog-block'
                ];
                
                // Direct registration for problematic blocks
                problemBlocks.forEach(blockName => {
                    // Skip if already registered
                    if (registeredBlocks.includes(blockName)) {
                        console.log(`USWDS Blocks Debug: Block ${blockName} is already registered`);
                        return;
                    }
                    
                    console.log(`USWDS Blocks Debug: Attempting to register ${blockName}`);
                    
                    try {
                        // Simplified registration to force the block to appear
                        registerBlockType(blockName, {
                            title: blockName.split('/')[1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                            icon: 'admin-generic',
                            category: blockName.includes('changelog') ? 'common' : (blockName.includes('tp') ? 'astp-tp' : 'astp-ccg'),
                            
                            // Very simple edit and save functions
                            edit: function() {
                                return createElement('div', { className: 'block-debugging-wrapper' },
                                    createElement('h3', {}, `${blockName} (Debug Mode)`),
                                    createElement('p', {}, 'This block is currently in debug mode. Refresh the editor to load the full block.')
                                );
                            },
                            save: function() {
                                return null; // Let server-side rendering handle it
                            }
                        });
                        
                        console.log(`USWDS Blocks Debug: Successfully registered ${blockName}`);
                    } catch (error) {
                        console.error(`USWDS Blocks Debug: Failed to register ${blockName}`, error);
                    }
                });
                
                // Force editor refresh
                if (window.wp.blocks.updateCategory) {
                    const categories = window.wp.blocks.getCategories();
                    categories.forEach(category => {
                        window.wp.blocks.updateCategory(category.slug, {});
                    });
                    console.log('USWDS Blocks Debug: Editor refreshed');
                }
            }, 1500); // Wait 1.5 seconds after DOM load
        });
    })();
} else {
    console.log('USWDS Blocks Debug: Debug mode disabled');
} 