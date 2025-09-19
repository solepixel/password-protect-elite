/**
 * Password Groups CPT Admin JavaScript
 *
 * @package PasswordProtectElite
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle adding additional passwords
        $('#ppe-add-additional-password').on('click', function(e) {
            e.preventDefault();

            var wrapper = $('#ppe-additional-passwords-wrapper');
            var newField = $('<div class="ppe-additional-password-item">' +
                '<input type="text" name="ppe_additional_passwords[]" value="" class="regular-text" placeholder="' + ppeCptAdmin.strings.enterPassword + '">' +
                '<button type="button" class="button button-secondary ppe-remove-additional-password">' + ppeCptAdmin.strings.remove + '</button>' +
                '</div>');

            wrapper.append(newField);

            // Focus on the new input field
            newField.find('input').focus();
        });

        // Handle removing additional passwords
        $(document).on('click', '.ppe-remove-additional-password', function(e) {
            e.preventDefault();

            $(this).closest('.ppe-additional-password-item').remove();
        });

        // Handle redirect type changes
        $('#ppe_redirect_type').on('change', function() {
            var selectedType = $(this).val();

            // Hide all redirect fields
            $('.ppe-redirect-field').hide();

            // Show relevant field based on selection
            if (selectedType === 'page') {
                $('.ppe-redirect-page-field').show();
            } else if (selectedType === 'custom_url') {
                $('.ppe-redirect-custom-url-field').show();
            }
        });

        // Trigger change event on page load to set initial state
        $('#ppe_redirect_type').trigger('change');

        // Handle form validation
        $('#post').on('submit', function(e) {
            var masterPassword = $('#ppe_master_password').val().trim();

            if (!masterPassword) {
                e.preventDefault();
                alert(ppeCptAdmin.strings.masterPasswordRequired);
                $('#ppe_master_password').focus();
                return false;
            }

            // Validate additional passwords (remove empty ones)
            $('.ppe-additional-password-item input').each(function() {
                if ($(this).val().trim() === '') {
                    $(this).closest('.ppe-additional-password-item').remove();
                }
            });
        });

        // Add some visual feedback for the additional passwords section
        $('#ppe-additional-passwords-wrapper').on('input', 'input[name="ppe_additional_passwords[]"]', function() {
            var $item = $(this).closest('.ppe-additional-password-item');
            var $removeBtn = $item.find('.ppe-remove-additional-password');

            if ($(this).val().trim() !== '') {
                $removeBtn.prop('disabled', false);
            } else {
                $removeBtn.prop('disabled', true);
            }
        });

        // Initialize remove button states
        $('.ppe-additional-password-item input').each(function() {
            var $item = $(this).closest('.ppe-additional-password-item');
            var $removeBtn = $item.find('.ppe-remove-additional-password');

            if ($(this).val().trim() === '') {
                $removeBtn.prop('disabled', true);
            }
        });
    });

})(jQuery);
