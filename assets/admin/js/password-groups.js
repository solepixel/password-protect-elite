/**
 * Password Groups CPT Admin JavaScript
 *
 * @package PasswordProtectElite
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Password strength meter helpers
        function getStrengthLabel(score) {
            var labels = ppeCptAdmin && ppeCptAdmin.strings ? ppeCptAdmin.strings : {};
            switch (score) {
                case 4: return labels.veryStrong || 'Very strong';
                case 3: return labels.strong || 'Strong';
                case 2: return labels.medium || 'Medium';
                case 1: return labels.weak || 'Weak';
                default: return labels.veryWeak || 'Very weak';
            }
        }

        function ensureStrengthUi($input) {
            if ($input.length === 0) return $();
            var $existing = $input.next('.ppe-password-strength');
            if ($existing.length) return $existing;

            var $ui = $('<div class="ppe-password-strength">' +
                '<div class="ppe-strength-bar" aria-hidden="true"></div>' +
                '<span class="ppe-strength-text"></span>' +
            '</div>');
            $input.after($ui);
            return $ui;
        }

        function meterScore(password) {
            try {
                if (typeof wp !== 'undefined' && wp.passwordStrength && typeof wp.passwordStrength.meter === 'function') {
                    // Third argument is confirm field; we don't use it here
                    return wp.passwordStrength.meter(password, [], '');
                }
            } catch (e) {}
            // Fallback naive scoring
            var score = 0;
            if (!password) return 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            return Math.max(0, Math.min(4, score));
        }

        function attachStrengthMeter($input) {
            var $ui = ensureStrengthUi($input);
            var $text = $ui.find('.ppe-strength-text');

            function update() {
                var val = ($input.val() || '').trim();
                var score = meterScore(val);
                var label = getStrengthLabel(score);
                $ui.removeClass('strength-0 strength-1 strength-2 strength-3 strength-4')
                   .addClass('strength-' + score);
                $text.text(label);
            }

            $input.on('input keyup change', update);
            update();
        }

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

            // Attach strength meter to the new additional password input
            attachStrengthMeter(newField.find('input'));
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

        // Handle unauthenticated behavior changes
        $('#ppe_unauthenticated_behavior').on('change', function() {
            var selectedBehavior = $(this).val();

            // Hide all unauthenticated redirect fields
            $('.ppe-unauthenticated-redirect-field').hide();

            // Show relevant fields based on selection
            if (selectedBehavior === 'redirect') {
                $('.ppe-unauthenticated-redirect-type-field').show();
                // Trigger the redirect type change to show the appropriate fields
                $('#ppe_unauthenticated_redirect_type').trigger('change');
            }
        });

        // Handle unauthenticated redirect type changes
        $('#ppe_unauthenticated_redirect_type').on('change', function() {
            var selectedType = $(this).val();
            var selectedBehavior = $('#ppe_unauthenticated_behavior').val();

            // If behavior is not redirect, ensure redirect-specific fields are hidden and exit.
            if (selectedBehavior !== 'redirect') {
                $('.ppe-unauthenticated-redirect-page-field, .ppe-unauthenticated-redirect-custom-url-field').hide();
                return;
            }

            // Hide all unauthenticated redirect specific fields
            $('.ppe-unauthenticated-redirect-page-field, .ppe-unauthenticated-redirect-custom-url-field').hide();

            // Show relevant field based on selection
            if (selectedType === 'page') {
                $('.ppe-unauthenticated-redirect-page-field').show();
            } else if (selectedType === 'custom_url') {
                $('.ppe-unauthenticated-redirect-custom-url-field').show();
            }
        });

        // Handle protection type changes
        $('#ppe_protection_type').on('change', function() {
            var selectedType = $(this).val();

            // Reset all URL protection fields visibility
            $('.ppe-url-protection-field').show();

            // Hide fields based on protection type
            if (selectedType === 'block') {
                // Content Block Specific: Hide both Exclude URLs and Auto-Protect URLs
                $('.ppe-exclude-urls-field, .ppe-auto-protect-urls-field').hide();
            } else if (selectedType === 'global_site') {
                // Global Site: Hide Auto-Protect URLs (not applicable for global protection)
                $('.ppe-auto-protect-urls-field').hide();
            }
            // For 'general' and 'section' types, show all fields (default behavior)
        });

        // Initialize all fields on page load to set correct initial state
        $('#ppe_redirect_type').trigger('change');
        $('#ppe_unauthenticated_behavior').trigger('change');
        $('#ppe_protection_type').trigger('change');

        // Initialize remove button states
        $('.ppe-additional-password-item input').each(function() {
            var $item = $(this).closest('.ppe-additional-password-item');
            var $removeBtn = $item.find('.ppe-remove-additional-password');

            if ($(this).val().trim() === '') {
                $removeBtn.prop('disabled', true);
            }
        });

        // Attach strength meters to existing fields
        attachStrengthMeter($('#ppe_master_password'));
        $('input[name="ppe_additional_passwords[]"]').each(function() {
            attachStrengthMeter($(this));
        });
    });

})(jQuery);
