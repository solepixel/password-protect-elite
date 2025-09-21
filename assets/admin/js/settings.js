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
        // Prefer explicit containers if present, otherwise fallback to heading heuristic.
        var colorContainer = $('#ppe-color-fields');
        var colorDesc = $('#ppe-color-desc');
        if (colorContainer.length) {
            if ((mode || '').toLowerCase() === 'all') {
                colorContainer.show();
                if (colorDesc.length) { colorDesc.show(); }
            } else {
                colorContainer.hide();
                if (colorDesc.length) { colorDesc.hide(); }
            }
            return;
        }

        // Fallback: Find the color customization section by heading.
        var colorSection = $('h2:contains("Color Customization")').nextUntil('h2').addBack();
        if ((mode || '').toLowerCase() === 'all') {
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
