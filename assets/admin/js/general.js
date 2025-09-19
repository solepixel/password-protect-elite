/**
 * Admin JavaScript for Password Protect Elite
 */

(function($) {
    'use strict';

    var PPEAdmin = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add new group button
            $(document).on('click', '#ppe-add-group', this.showAddGroupModal);

            // Edit group button
            $(document).on('click', '.ppe-edit-group', this.showEditGroupModal);

            // Delete group button
            $(document).on('click', '.ppe-delete-group', this.deleteGroup);

            // Modal close
            $(document).on('click', '.ppe-modal-close, .ppe-modal-cancel', this.hideModal);

            // Modal backdrop click
            $(document).on('click', '.ppe-modal', function(e) {
                if (e.target === this) {
                    PPEAdmin.hideModal();
                }
            });

            // Form submission
            $(document).on('submit', '#ppe-group-form', this.saveGroup);

            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    PPEAdmin.hideModal();
                }
            });
        },

        showAddGroupModal: function() {
            $('#ppe-modal-title').text(ppeAdmin.strings.addGroup || 'Add Password Group');
            $('#ppe-group-form')[0].reset();
            $('#ppe-group-id').val('');
            $('#ppe-group-active').prop('checked', true);
            $('#ppe-group-modal').show();
            $('#ppe-group-name').focus();
        },

        showEditGroupModal: function() {
            var groupId = $(this).data('group-id');
            var row = $('tr[data-group-id="' + groupId + '"]');

            // Get data from the row (in a real implementation, you'd fetch from server)
            var name = row.find('td:first strong').text();
            var type = row.find('.ppe-type-badge').text().toLowerCase();
            var password = row.find('code').text();
            var redirect = row.find('a').attr('href') || '';
            var isActive = row.find('.ppe-status-active').length > 0;

            $('#ppe-modal-title').text(ppeAdmin.strings.editGroup || 'Edit Password Group');
            $('#ppe-group-id').val(groupId);
            $('#ppe-group-name').val(name);
            $('#ppe-group-password').val(password);
            $('#ppe-group-type').val(type);
            $('#ppe-group-redirect').val(redirect);
            $('#ppe-group-active').prop('checked', isActive);

            $('#ppe-group-modal').show();
            $('#ppe-group-name').focus();
        },

        hideModal: function() {
            $('#ppe-group-modal').hide();
        },

        saveGroup: function(e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();

            // Show loading state
            submitBtn.text(ppeAdmin.strings.saving || 'Saving...').prop('disabled', true);
            form.addClass('ppe-loading');

            var formData = {
                action: 'ppe_save_password_group',
                nonce: ppeAdmin.nonce,
                id: $('#ppe-group-id').val(),
                name: $('#ppe-group-name').val(),
                description: $('#ppe-group-description').val(),
                password: $('#ppe-group-password').val(),
                protection_type: $('#ppe-group-type').val(),
                redirect_url: $('#ppe-group-redirect').val(),
                is_active: $('#ppe-group-active').is(':checked') ? 1 : 0
            };

            $.ajax({
                url: ppeAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        PPEAdmin.showNotice(response.data, 'success');
                        PPEAdmin.hideModal();
                        location.reload(); // Refresh to show updated data
                    } else {
                        PPEAdmin.showNotice(response.data || ppeAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    PPEAdmin.showNotice(ppeAdmin.strings.error, 'error');
                },
                complete: function() {
                    // Reset button state
                    submitBtn.text(originalText).prop('disabled', false);
                    form.removeClass('ppe-loading');
                }
            });
        },

        deleteGroup: function() {
            if (!confirm(ppeAdmin.strings.confirmDelete)) {
                return;
            }

            var groupId = $(this).data('group-id');
            var row = $('tr[data-group-id="' + groupId + '"]');

            // Show loading state
            row.addClass('ppe-loading');

            $.ajax({
                url: ppeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ppe_delete_password_group',
                    nonce: ppeAdmin.nonce,
                    id: groupId
                },
                success: function(response) {
                    if (response.success) {
                        PPEAdmin.showNotice(response.data, 'success');
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        PPEAdmin.showNotice(response.data || ppeAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    PPEAdmin.showNotice(ppeAdmin.strings.error, 'error');
                },
                complete: function() {
                    row.removeClass('ppe-loading');
                }
            });
        },

        showNotice: function(message, type) {
            var notice = $('<div class="ppe-notice ppe-notice-' + type + '">' + message + '</div>');

            // Remove existing notices
            $('.ppe-notice').remove();

            // Add new notice
            $('.wrap h1').after(notice);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        PPEAdmin.init();
    });

})(jQuery);
