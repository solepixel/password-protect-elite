/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import {
	InspectorControls,
	PanelBody,
	SelectControl,
	TextareaControl,
	InnerBlocks,
} from '@wordpress/block-editor';

/**
 * Block registration
 */
registerBlockType('password-protect-elite/protected-content', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, fallbackMessage } = attributes;

		// Get password groups from localized data
		const passwordGroups = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.passwordGroups) || [];
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

		return createElement(
			'div',
			null,
			createElement(
				InspectorControls,
				null,
				createElement(
					PanelBody,
					{
						title: __('Protected Content Settings', 'password-protect-elite'),
						initialOpen: true
					},
					createElement(SelectControl, {
						label: __('Allowed Password Groups', 'password-protect-elite'),
						help: __('Select which password groups can unlock this content.', 'password-protect-elite'),
						value: allowedGroups.length > 0 ? allowedGroups.join(',') : '',
						options: groupOptions,
						onChange: (value) => {
							setAttributes({
								allowedGroups: value ? value.split(',').map(id => parseInt(id)) : []
							});
						}
					}),
					createElement(TextareaControl, {
						label: __('Fallback Message', 'password-protect-elite'),
						help: __('Message shown when content is locked.', 'password-protect-elite'),
						value: fallbackMessage,
						onChange: (value) => setAttributes({ fallbackMessage: value })
					})
				)
			),
			createElement(
				'div',
				{ className: 'ppe-protected-content-editor' },
				createElement(
					'div',
					{ className: 'ppe-block-header' },
					createElement('h4', null, __('Protected Content', 'password-protect-elite')),
					createElement('p', { className: 'ppe-block-description' }, __('Add content below. It will be hidden until the correct password is entered.', 'password-protect-elite'))
				),
				createElement(InnerBlocks, {
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
