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
	TextControl,
} from '@wordpress/block-editor';

/**
 * Block registration
 */
registerBlockType('password-protect-elite/password-entry', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, buttonText, placeholder, redirectUrl } = attributes;

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

		// Debug: Check if components are available
		console.log('InspectorControls:', InspectorControls);
		console.log('PanelBody:', PanelBody);
		console.log('SelectControl:', SelectControl);
		console.log('TextControl:', TextControl);

		return createElement(
			'div',
			{ className: 'ppe-password-entry-editor' },
			createElement(
				'div',
				{ className: 'ppe-block-preview' },
				createElement('h4', null, __('Password Entry Form', 'password-protect-elite')),
				createElement('p', { className: 'ppe-block-description' }, __('This block will render a password entry form on the frontend.', 'password-protect-elite')),
				createElement(
					'div',
					{ className: 'ppe-form-preview' },
					createElement('input', {
						type: 'password',
						placeholder: placeholder,
						disabled: true,
						className: 'ppe-preview-input'
					}),
					createElement('button', {
						type: 'button',
						disabled: true,
						className: 'ppe-preview-button'
					}, buttonText)
				)
			)
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
});
