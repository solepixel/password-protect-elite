/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls, InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextareaControl, ToolbarGroup, ToolbarButton, ToggleControl } from '@wordpress/components';

/**
 * Block registration
 */
registerBlockType('password-protect-elite/protected-content', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, fallbackMessage, accessMode, allowedRoles, allowedCapabilities, disableForm, renderDynamically } = attributes;

		// Get global string settings
		const globalStrings = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.globalStrings) || {};
		const blockProps = useBlockProps();

		// Get password groups from localized data
		const passwordGroups = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.passwordGroups) || [];
		const contentGroups = passwordGroups.filter(group =>
			group.type === 'content' || group.type === 'general'
		);

		// Create options for select controls
		const groupOptions = [
			{ label: __('All Content Groups', 'password-protect-elite'), value: '' }
		].concat(
			contentGroups.map(group => ({
				label: group.name,
				value: group.id.toString()
			}))
		);

		const accessModeOptions = [
			{ label: __('Password Groups', 'password-protect-elite'), value: 'groups' },
			{ label: __('Role-based Access', 'password-protect-elite'), value: 'roles' },
			{ label: __('Capability-based Access', 'password-protect-elite'), value: 'caps' }
		];

		// WordPress roles/capabilities from localization (if provided), otherwise let users type
		const wpRoles = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.wpRoles) || [];
		const wpCaps  = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.wpCapabilities) || [];

		return (
			<div {...blockProps} className={`ppe-protected-content-wrapper ${blockProps.className || ''}`}>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="hidden"
							label={__('Edit Protected Content', 'password-protect-elite')}
							onClick={() => {}}
						>
							{__('Protected Content', 'password-protect-elite')}
						</ToolbarButton>
					</ToolbarGroup>
				</BlockControls>
				<InspectorControls>
				<PanelBody
						title={__('Protected Content Settings', 'password-protect-elite')}
						initialOpen={true}
					>
					<SelectControl
						label={__('Access Mode', 'password-protect-elite')}
						help={__('Decide how users can view this content.', 'password-protect-elite')}
						value={accessMode || 'groups'}
						options={accessModeOptions}
						__next40pxDefaultSize={true}
						__nextHasNoMarginBottom={true}
						onChange={(value) => setAttributes({ accessMode: value })}
					/>

					{(accessMode === 'groups' || !accessMode) && (
						<SelectControl
							label={__('Allowed Password Groups', 'password-protect-elite')}
							help={__('Select which password groups can unlock this content.', 'password-protect-elite')}
							value={allowedGroups.length > 0 ? allowedGroups.join(',') : ''}
							options={groupOptions}
							__next40pxDefaultSize={true}
							__nextHasNoMarginBottom={true}
							onChange={(value) => {
								setAttributes({
									allowedGroups: value ? value.split(',').map(id => parseInt(id)) : []
								});
							}}
						/>
					)}

					{(accessMode === 'groups' || !accessMode) && (
						<ToggleControl
							label={__('Disable Form', 'password-protect-elite')}
							help={__('Hide the password form and only show content after the user has authenticated by other means.', 'password-protect-elite')}
							checked={!!disableForm}
							onChange={(value) => setAttributes({ disableForm: !!value })}
						/>
					)}

					{(accessMode === 'groups' || !accessMode) && (
						<ToggleControl
							label={__('Render Dynamically', 'password-protect-elite')}
							help={__('Only render content when the current URL matches the Auto-Protect URLs setting of the selected password group(s), while respecting Exclude URLs.', 'password-protect-elite')}
							checked={!!renderDynamically}
							onChange={(value) => setAttributes({ renderDynamically: !!value })}
						/>
					)}

					{accessMode === 'roles' && (
						<TextareaControl
							label={__('Allowed Roles', 'password-protect-elite')}
							help={__('Comma-separated list of role slugs that can view this content.', 'password-protect-elite')}
							value={(allowedRoles || []).join(',')}
							placeholder={(wpRoles.length ? wpRoles.join(', ') : 'administrator, editor, author')}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ allowedRoles: value.split(',').map(s => s.trim()).filter(Boolean) })}
						/>
					)}

					{accessMode === 'caps' && (
						<TextareaControl
							label={__('Allowed Capabilities', 'password-protect-elite')}
							help={__('Comma-separated list of capabilities required to view content.', 'password-protect-elite')}
							value={(allowedCapabilities || []).join(',')}
							placeholder={(wpCaps.length ? wpCaps.join(', ') : 'read, edit_posts')}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ allowedCapabilities: value.split(',').map(s => s.trim()).filter(Boolean) })}
						/>
					)}
					{(!accessMode || accessMode === 'groups') && !disableForm && (
						<TextareaControl
							label={__('Fallback Message', 'password-protect-elite')}
							help={__('Message shown when content is locked.', 'password-protect-elite')}
							value={fallbackMessage || globalStrings.default_fallback_message || __('This content is protected by password.', 'password-protect-elite')}
							placeholder={globalStrings.default_fallback_message || __('This content is protected by password.', 'password-protect-elite')}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ fallbackMessage: value })}
						/>
					)}
					</PanelBody>
				</InspectorControls>
				<div className="ppe-protected-content-editor">
					<div className="ppe-block-header">
						<h4>{globalStrings.protected_content_header || __('Protected Content', 'password-protect-elite')}</h4>
						<p className="ppe-block-description">
							{globalStrings.protected_content_description || __('Add content below. It will be hidden until the correct password is entered.', 'password-protect-elite')}
						</p>
					</div>
					<InnerBlocks
						allowedBlocks={true}
						template={[
							['core/paragraph', { placeholder: __('Add Secure Content Here...', 'password-protect-elite') }]
						]}
					/>
				</div>
			</div>
		);
	},

	save: function() {
		// Save inner block content so it persists and is available in PHP via $content.
		return <InnerBlocks.Content />;
	}
});
