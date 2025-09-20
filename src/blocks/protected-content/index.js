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

		// Debug: Check if components are available
		console.log('InspectorControls:', InspectorControls);
		console.log('PanelBody:', PanelBody);
		console.log('SelectControl:', SelectControl);
		console.log('TextareaControl:', TextareaControl);
		console.log('InnerBlocks:', InnerBlocks);

		return createElement(
			'div',
			{ className: 'ppe-protected-content-editor' },
			createElement(
				'div',
				{ className: 'ppe-block-header' },
				createElement('h4', null, __('Protected Content', 'password-protect-elite')),
				createElement('p', { className: 'ppe-block-description' }, __('Add content below. It will be hidden until the correct password is entered.', 'password-protect-elite'))
			),
			createElement('div', { className: 'ppe-content-placeholder' }, __('This content is protected by password. Add your content here.', 'password-protect-elite'))
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
});
