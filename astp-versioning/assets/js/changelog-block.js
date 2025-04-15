/**
 * Changelog Block Frontend JavaScript
 * Handles tab switching and filtering for the changelog block
 */
(function($) {
    // Initialize when the document is ready
    $(document).ready(function() {
        initChangelogBlock();
    });

    /**
     * Initialize the changelog block functionality
     */
    function initChangelogBlock() {
        // Tab switching
        initTabSwitching();
        
        // Filtering
        initChangeFiltering();
        
        // Mobile select
        initMobileSelect();
    }

    /**
     * Initialize tab switching functionality
     */
    function initTabSwitching() {
        // Handle tab clicks
        $('.tabs-primary a').on('click', function(e) {
            e.preventDefault();
            
            // Get the target panel ID
            var targetPanel = $(this).attr('href');
            
            // Remove active class from all tabs
            $('.tabs-primary a').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('active');
            
            // Hide all panels
            $('.tabs-panel').hide();
            
            // Show the target panel
            $(targetPanel).show();
            
            // Reset filters to "All" when switching tabs
            $('.filter-btn').removeClass('selected');
            $('.filter-btn[data-filter="all"]').addClass('selected');
            showAllChanges();
        });
    }

    /**
     * Initialize change filtering functionality
     */
    function initChangeFiltering() {
        // Handle filter button clicks (both sidebar and mobile)
        $('.filter-btn').on('click', function() {
            // Get the filter type
            var filter = $(this).data('filter');
            
            // Remove selected class from all filter buttons
            $('.filter-btn').removeClass('selected');
            
            // Add selected class to clicked button
            $('.filter-btn[data-filter="' + filter + '"]').addClass('selected');
            
            // Apply the filter
            applyFilter(filter);
        });
    }

    /**
     * Apply filter to the changelog
     * 
     * @param {string} filter The filter to apply ('all', 'added', 'removed', or 'amended')
     */
    function applyFilter(filter) {
        // Get the active tab panel
        var activePanel = $('.tabs-panel:visible');
        
        if (filter === 'all') {
            // Show all changes
            showAllChanges();
        } else {
            // Hide all change items first
            activePanel.find('.changes li').hide();
            
            // Find and show change items that have the selected change type
            activePanel.find('.changes li').each(function() {
                var $changeItem = $(this);
                var hasChangeType = $changeItem.find('.action:contains("' + filter + '")').length > 0;
                
                if (hasChangeType) {
                    $changeItem.show();
                }
            });
            
            // Check if any items are visible, if not show "no changes" message
            checkForEmptyResults(activePanel);
        }
    }

    /**
     * Show all changes (reset filters)
     */
    function showAllChanges() {
        // Get the active tab panel
        var activePanel = $('.tabs-panel:visible');
        
        // Show all change items
        activePanel.find('.changes li').show();
        
        // Remove any "no changes" message
        activePanel.find('.no-changes').remove();
    }

    /**
     * Check if there are any visible changes after filtering
     * If not, show a "no changes" message
     * 
     * @param {jQuery} $activePanel The active tab panel
     */
    function checkForEmptyResults($activePanel) {
        // Check each version group
        $activePanel.find('.change').each(function() {
            var $versionGroup = $(this);
            var hasVisibleChanges = $versionGroup.find('.changes li:visible').length > 0;
            
            if (!hasVisibleChanges) {
                $versionGroup.hide();
            } else {
                $versionGroup.show();
            }
        });
        
        // Check if any version groups are visible
        var hasVisibleGroups = $activePanel.find('.change:visible').length > 0;
        
        if (!hasVisibleGroups) {
            // If no "no changes" message exists, add one
            if ($activePanel.find('.no-changes').length === 0) {
                var filter = $('.filter-btn.selected').data('filter');
                var activeTab = $('.tabs-primary a.active').text().trim();
                
                $activePanel.find('.timeline ul').append(
                    '<li class="no-changes">' +
                    '<div class="empty-message">No ' + filter + ' changes found in ' + activeTab + ' versions</div>' +
                    '</li>'
                );
            }
        } else {
            // Remove any "no changes" message
            $activePanel.find('.no-changes').remove();
        }
    }

    /**
     * Initialize the mobile section select functionality
     */
    function initMobileSelect() {
        // Get the select element
        var $select = $('#section-select');
        
        // Populate the select with tab options
        $('.tabs-primary a').each(function() {
            var $tab = $(this);
            var tabText = $tab.text().trim();
            var tabId = $tab.attr('href');
            
            $select.append('<option value="' + tabId + '">' + tabText + '</option>');
        });
        
        // Handle select change
        $select.on('change', function() {
            var targetPanel = $(this).val();
            
            // Update the active tab
            $('.tabs-primary a[href="' + targetPanel + '"]').click();
        });
    }
})(jQuery); 