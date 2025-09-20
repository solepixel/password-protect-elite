/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToolbarGroup, ToolbarButton, SelectControl, TextControl } from '@wordpress/components';

/**
 * Block registration
 */
registerBlockType('password-protect-elite/password-entry', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { allowedGroups, buttonText, placeholder, redirectUrl } = attributes;
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
			<div {...blockProps} className={`ppe-password-entry-wrapper ${blockProps.className || ''}`}>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="edit"
							label={__('Edit Password Entry', 'password-protect-elite')}
							onClick={() => {}}
						>
							{__('Password Entry', 'password-protect-elite')}
						</ToolbarButton>
					</ToolbarGroup>
				</BlockControls>
				<InspectorControls>
					<PanelBody
						title={__('Password Entry Settings', 'password-protect-elite')}
						initialOpen={true}
					>
						<SelectControl
							label={__('Allowed Password Groups', 'password-protect-elite')}
							help={__('Select which password groups can be used with this form. Leave empty to allow all content groups.', 'password-protect-elite')}
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
						<TextControl
							label={__('Button Text', 'password-protect-elite')}
							value={buttonText}
							__next40pxDefaultSize={true}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ buttonText: value })}
						/>
						<TextControl
							label={__('Placeholder Text', 'password-protect-elite')}
							value={placeholder}
							__next40pxDefaultSize={true}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ placeholder: value })}
						/>
						<TextControl
							label={__('Redirect URL', 'password-protect-elite')}
							help={__('Optional URL to redirect users after successful password entry.', 'password-protect-elite')}
							value={redirectUrl}
							__next40pxDefaultSize={true}
							__nextHasNoMarginBottom={true}
							onChange={(value) => setAttributes({ redirectUrl: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<div className="ppe-password-entry-editor">
					<div className="ppe-block-preview">
						<h4>{__('Password Entry Form', 'password-protect-elite')}</h4>
						<p className="ppe-block-description">
							{__('This block will render a password entry form on the frontend.', 'password-protect-elite')}
						</p>
						<div className="ppe-form-preview">
							<input
								type="password"
								placeholder={placeholder}
								disabled={true}
								className="ppe-preview-input"
							/>
							<button
								type="button"
								disabled={true}
								className="ppe-preview-button"
							>
								{buttonText}
							</button>
						</div>
					</div>
				</div>
			</div>
		);
	},

	save: function() {
		return null; // Server-side rendering
	}
});
