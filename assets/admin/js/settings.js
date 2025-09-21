/**
 * Settings page JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle block styles mode change
        $('#block_styles_mode').on('change', function() {
            var selectedMode = $(this).val();
            toggleColorSection(selectedMode);
        });

        // Initial state - hide color section if not in 'all' mode
        var initialMode = $('#block_styles_mode').val();
        toggleColorSection(initialMode);

        // Handle tab navigation
        handleTabNavigation();
    });

    function toggleColorSection(mode) {
        // Find the color customization section
        var colorSection = $('h2:contains("Color Customization")').nextUntil('h2').addBack();

        if (mode === 'all') {
            colorSection.show();
        } else {
            colorSection.hide();
        }
    }

    function handleTabNavigation() {
        // The tab navigation is handled by WordPress URL parameters
        // No need for custom JavaScript as the tabs will reload the page with the correct content
        // The CSS and PHP handle the tab switching based on the URL parameter
    }

})(jQuery);
