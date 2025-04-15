/**
 * Changelog Block Wrapper
 * This script attempts to load the full-featured changelog block,
 * and if it fails, falls back to the simplified version.
 */
(function(wp) {
    if (!wp) {
        console.error('WordPress not available');
        return;
    }
    
    // Check if block is already registered
    const isBlockRegistered = () => {
        if (!wp.blocks || !wp.blocks.getBlockTypes) return false;
        return wp.blocks.getBlockTypes().some(block => 
            block.name === 'uswds-gutenberg/changelog-block'
        );
    };
    
    // Main function to ensure the block is registered
    const ensureBlockRegistered = () => {
        // If block is already registered, we're done
        if (isBlockRegistered()) {
            console.log('Changelog block is already registered');
            return;
        }
        
        console.log('Attempting to register changelog block');
        
        // Try to load the full-featured version
        try {
            // We're in a script that's already loaded, so we don't need to 
            // dynamically load the scripts - they should have been enqueued by WordPress
            
            // Check again after a short delay to see if the registration was successful
            setTimeout(() => {
                if (!isBlockRegistered()) {
                    console.log('Full changelog block failed to register, falling back to simplified version');
                    loadFallbackBlock();
                } else {
                    console.log('Full changelog block successfully registered');
                }
            }, 500);
        } catch (error) {
            console.error('Error loading full changelog block:', error);
            loadFallbackBlock();
        }
    };
    
    // Load the fallback block if the main one fails
    const loadFallbackBlock = () => {
        if (isBlockRegistered()) return;
        
        try {
            console.log('Registering fallback changelog block');
            
            wp.blocks.registerBlockType('uswds-gutenberg/changelog-block', {
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
                
                edit: function({ attributes, setAttributes }) {
                    const blockProps = wp.blockEditor.useBlockProps();
                    
                    return wp.element.createElement(
                        'div', 
                        blockProps,
                        wp.element.createElement(
                            'div', 
                            { style: { padding: '20px', border: '1px solid #ddd', backgroundColor: '#f8f8f8' } },
                            wp.element.createElement('h2', {}, 'Changelog Block'),
                            wp.element.createElement('p', {}, 'This block will display version history with a timeline interface.'),
                            wp.element.createElement('p', {}, 'Server-side rendering will be used to display the actual content.')
                        )
                    );
                },
                
                save: function() {
                    return null; // Server-side rendering
                }
            });
            
            console.log('Fallback changelog block registered successfully');
        } catch (error) {
            console.error('Error registering fallback changelog block:', error);
        }
    };
    
    // Initialize when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureBlockRegistered);
    } else {
        ensureBlockRegistered();
    }
})(window.wp); 