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
			TextareaControl,
			InnerBlocks,
		} = wp.blockEditor;

		wp.blocks.registerBlockType('password-protect-elite/protected-content', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, fallbackMessage } = attributes;

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
					title: __('Protected Content Settings', 'password-protect-elite'),
					initialOpen: true
				},
					el(SelectControl, {
						label: __('Allowed Password Groups', 'password-protect-elite'),
						help: __('Select which password groups can unlock this content.', 'password-protect-elite'),
						value: allowedGroups.length > 0 ? allowedGroups.join(',') : '',
						options: groupOptions,
						onChange: function(value) {
							setAttributes({
								allowedGroups: value ? value.split(',').map(id => parseInt(id)) : []
							});
						},
					}),
					el(TextareaControl, {
						label: __('Fallback Message', 'password-protect-elite'),
						help: __('Message shown when content is locked.', 'password-protect-elite'),
						value: fallbackMessage,
						onChange: function(value) {
							setAttributes({ fallbackMessage: value });
						},
					})
				)
			),
			el('div', { className: 'ppe-protected-content-editor' },
				el('div', { className: 'ppe-block-header' },
					el('h4', null, __('Protected Content', 'password-protect-elite')),
					el('p', { className: 'ppe-block-description' },
						__('Add content below. It will be hidden until the correct password is entered.', 'password-protect-elite')
					)
				),
				el(InnerBlocks, {
					allowedBlocks: true,
					template: [
						['core/paragraph', { content: __('This content is protected by password. Add your content here.', 'password-protect-elite') }]
					]
				})
			)
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
		});
	}
})();
