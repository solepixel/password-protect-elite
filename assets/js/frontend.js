/**
 * Frontend JavaScript for Password Protect Elite
 */

(function($) {
    'use strict';

    var PPEFrontend = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Password form submission
            $(document).on('submit', '.ppe-password-form', this.handlePasswordSubmit);

            // Enter key in password field
            $(document).on('keypress', '.ppe-password-input', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    $(this).closest('form').submit();
                }
            });
        },

        handlePasswordSubmit: function(e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('.ppe-submit-button');
            var passwordInput = form.find('.ppe-password-input');
            var errorMessage = form.find('.ppe-error-message');
            var originalText = submitBtn.text();

            var password = passwordInput.val().trim();

            // Validate password
            if (!password) {
                PPEFrontend.showError(form, ppeFrontend.strings.passwordRequired);
                return;
            }

            // Show loading state
            submitBtn.text(ppeFrontend.strings.validating).prop('disabled', true);
            form.addClass('ppe-loading');
            errorMessage.hide();

            // Get form data
            var formData = {
                action: 'ppe_validate_password',
                nonce: ppeFrontend.nonce,
                password: password,
                type: form.data('type') || '',
                redirect_url: form.data('redirect-url') || '',
                allowed_groups: form.data('allowed-groups') || ''
            };

            // Make AJAX request
            $.ajax({
                url: ppeFrontend.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Password validated successfully
                        PPEFrontend.handleSuccess(response.data, form);
                    } else {
                        // Invalid password
                        PPEFrontend.showError(form, response.data || ppeFrontend.strings.invalidPassword);
                    }
                },
                error: function() {
                    PPEFrontend.showError(form, ppeFrontend.strings.error || 'An error occurred. Please try again.');
                },
                complete: function() {
                    // Reset button state
                    submitBtn.text(originalText).prop('disabled', false);
                    form.removeClass('ppe-loading');
                }
            });
        },

        handleSuccess: function(data, form) {
            // Clear password field
            form.find('.ppe-password-input').val('');

            // Hide error message
            form.find('.ppe-error-message').hide();

            // Show success message briefly
            var successMessage = $('<div class="ppe-success-message">' + data.message + '</div>');
            form.append(successMessage);

            // Redirect if URL provided
            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 1000);
            } else {
                // Reload page to show unlocked content
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            }
        },

        showError: function(form, message) {
            var errorMessage = form.find('.ppe-error-message');
            errorMessage.text(message).show();

            // Focus password input
            form.find('.ppe-password-input').focus();

            // Hide error after 5 seconds
            setTimeout(function() {
                errorMessage.fadeOut();
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        PPEFrontend.init();
    });

})(jQuery);
