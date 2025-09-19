/**
 * Block registration
 */
// Use a safe approach to avoid conflicts with other plugins
(function() {
	// Check if registerBlockType is available and not already declared
	if (typeof wp !== 'undefined' && wp.blocks && wp.blocks.registerBlockType) {
		/**
		 * WordPress dependencies
		 */
		const { createElement: el, Fragment } = wp.element;
		const { __ } = wp.i18n;
		const {
			InspectorControls,
			PanelBody,
			SelectControl,
			TextControl,
		} = wp.blockEditor;

		wp.blocks.registerBlockType('password-protect-elite/password-entry', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, buttonText, placeholder, redirectUrl } = attributes;

		// Get password groups from localized data
		const passwordGroups = ppeBlocks?.passwordGroups || [];
		const contentGroups = passwordGroups.filter(group =>
			group.type === 'content' || group.type === 'general'
		);

		// Create options for select control
		const groupOptions = [
			{ label: __('All Content Groups', 'password-protect-elite'), value: '' }
		].concat(
			contentGroups.map(group => ({
				label: group.name,
				value: group.id.toString()
			}))
		);

		return el(Fragment, null,
			el(InspectorControls, null,
				el(PanelBody, {
					title: __('Password Entry Settings', 'password-protect-elite'),
					initialOpen: true
				},
					el(SelectControl, {
						label: __('Allowed Password Groups', 'password-protect-elite'),
						help: __('Select which password groups can be used with this form. Leave empty to allow all content groups.', 'password-protect-elite'),
						value: allowedGroups.length > 0 ? allowedGroups.join(',') : '',
						options: groupOptions,
						onChange: function(value) {
							setAttributes({
								allowedGroups: value ? value.split(',').map(id => parseInt(id)) : []
							});
						},
					}),
					el(TextControl, {
						label: __('Button Text', 'password-protect-elite'),
						value: buttonText,
						onChange: function(value) {
							setAttributes({ buttonText: value });
						},
					}),
					el(TextControl, {
						label: __('Placeholder Text', 'password-protect-elite'),
						value: placeholder,
						onChange: function(value) {
							setAttributes({ placeholder: value });
						},
					}),
					el(TextControl, {
						label: __('Redirect URL', 'password-protect-elite'),
						help: __('Optional URL to redirect users after successful password entry.', 'password-protect-elite'),
						value: redirectUrl,
						onChange: function(value) {
							setAttributes({ redirectUrl: value });
						},
					})
				)
			),
			el('div', { className: 'ppe-password-entry-editor' },
				el('div', { className: 'ppe-block-preview' },
					el('h4', null, __('Password Entry Form', 'password-protect-elite')),
					el('p', { className: 'ppe-block-description' },
						__('This block will render a password entry form on the frontend.', 'password-protect-elite')
					),
					el('div', { className: 'ppe-form-preview' },
						el('input', {
							type: 'password',
							placeholder: placeholder,
							disabled: true,
							className: 'ppe-preview-input'
						}),
						el('button', {
							type: 'button',
							disabled: true,
							className: 'ppe-preview-button'
						}, buttonText)
					)
				)
			)
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
		});
	}
})();
