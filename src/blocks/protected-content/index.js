/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls, InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextareaControl, ToolbarGroup, ToolbarButton } from '@wordpress/components';

/**
 * Block registration
 */
registerBlockType('password-protect-elite/protected-content', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, fallbackMessage } = attributes;

		// Get global string settings
		const globalStrings = (typeof ppeBlocks !== 'undefined' && ppeBlocks?.globalStrings) || {};
		const blockProps = useBlockProps();

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
						<TextareaControl
							label={__('Fallback Message', 'password-protect-elite')}
							help={__('Message shown when content is locked.', 'password-protect-elite')}
							value={fallbackMessage || globalStrings.default_fallback_message || __('This content is protected by password.', 'password-protect-elite')}
							placeholder={globalStrings.default_fallback_message || __('This content is protected by password.', 'password-protect-elite')}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ fallbackMessage: value })}
						/>
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
							['core/paragraph', { content: __('This content is protected by password. Add your content here.', 'password-protect-elite') }]
						]}
					/>
				</div>
			</div>
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
});
