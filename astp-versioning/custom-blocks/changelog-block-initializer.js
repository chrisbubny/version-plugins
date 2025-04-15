/**
 * Changelog Block Initializer
 * This file ensures the WordPress environment is properly initialized
 * before attempting to register the changelog block
 */
(function() {
    // Disable debug mode
    window.ASTP_DEBUG_MODE = false;
    
    console.log('USWDS Changelog Block Initializer: Starting initialization');
    
    // Make sure WordPress is available
    if (!window.wp) {
        console.error('WordPress not available');
        return;
    }
    
    // Function to initialize block
    function initializeBlock() {
        // Check if the block has already been registered
        const isBlockRegistered = window.wp.blocks.getBlockTypes().some(
            block => block.name === 'uswds-gutenberg/changelog-block'
        );
        
        if (isBlockRegistered) {
            console.log('Changelog block is already registered');
            return;
        }
        
        console.log('Attempting to register Changelog block directly');
        
        try {
            // Set up WordPress framework dependencies
            const { registerBlockType } = window.wp.blocks;
            const { createElement: el } = window.wp.element;
            
            // Register a simple fallback version if needed
            registerBlockType('uswds-gutenberg/changelog-block', {
                title: 'Changelog Block',
                icon: 'backup',
                category: 'common',
                attributes: {
                    postId: { type: 'number', default: 0 },
                    title: { type: 'string', default: 'Certification Companion Guide Changelog' },
                    subtitle: { type: 'string', default: 'The following changelog applies to:' },
                    subtitleEmphasis: { type: 'string', default: '§ 170.315(g)(10) Standardized API for patient and population services' },
                    enableFilters: { type: 'boolean', default: true },
                    enableTabSwitching: { type: 'boolean', default: true }
                },
                
                edit: function() {
                    // Simple placeholder version
                    return el('div', { style: { padding: '20px', border: '1px solid #ddd' } },
                        el('h2', {}, 'Changelog Block'),
                        el('p', {}, 'The full functionality of this block will be rendered on the frontend.'),
                        el('p', {}, 'This block displays version history with a timeline interface.')
                    );
                },
                
                save: function() {
                    return null; // Server-side rendering
                }
            });
            
            console.log('Successfully registered simple Changelog block');
        } catch (error) {
            console.error('Error registering Changelog block:', error);
        }
    }
    
    // Initialize when the DOM is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a short moment for WordPress to initialize
            setTimeout(initializeBlock, 500);
        });
    } else {
        // Document already loaded, init immediately with a small delay
        setTimeout(initializeBlock, 500);
    }
})(); 