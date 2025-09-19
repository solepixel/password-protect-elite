<?php
/**
 * Gutenberg blocks class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks class.
 */
class Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register blocks
	 */
	public function register_blocks() {
		// Register password entry block.
		register_block_type(
			PPE_PLUGIN_PATH . 'src/blocks/password-entry/block.json',
			array(
				'render_callback' => array( $this, 'render_password_entry_block' ),
			)
		);

		// Register password protected content block.
		register_block_type(
			PPE_PLUGIN_PATH . 'src/blocks/protected-content/block.json',
			array(
				'render_callback' => array( $this, 'render_protected_content_block' ),
			)
		);
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		// Prevent duplicate loading
		if ( wp_script_is( 'ppe-blocks', 'enqueued' ) ) {
			return;
		}

		// Enqueue combined block JavaScript file
		wp_enqueue_script(
			'ppe-blocks',
			PPE_PLUGIN_URL . 'src/blocks/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n' ),
			PPE_VERSION,
			true
		);

		// Get password groups for localization.
		$password_groups = Database::get_password_groups();
		$groups_data     = array();

		foreach ( $password_groups as $group ) {
			$groups_data[] = array(
				'id'   => $group->id,
				'name' => $group->name,
				'type' => $group->protection_type,
			);
		}

		// Localize script with password groups for the block editor.
		wp_localize_script(
			'ppe-blocks',
			'ppeBlocks',
			array(
				'passwordGroups' => $groups_data,
				'strings'        => array(
					'passwordEntry'        => __( 'Password Entry', 'password-protect-elite' ),
					'protectedContent'     => __( 'Protected Content', 'password-protect-elite' ),
					'selectPasswordGroups' => __( 'Select Password Groups', 'password-protect-elite' ),
					'buttonText'           => __( 'Button Text', 'password-protect-elite' ),
					'placeholder'          => __( 'Placeholder Text', 'password-protect-elite' ),
					'redirectUrl'          => __( 'Redirect URL', 'password-protect-elite' ),
					'fallbackMessage'      => __( 'Fallback Message', 'password-protect-elite' ),
					'noPasswordGroups'     => __( 'No password groups available. Create some in the plugin settings.', 'password-protect-elite' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_script(
			'ppe-frontend',
			PPE_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			PPE_VERSION,
			true
		);

		wp_enqueue_style(
			'ppe-blocks',
			PPE_PLUGIN_URL . 'assets/css/blocks.css',
			array(),
			PPE_VERSION
		);

		wp_localize_script(
			'ppe-frontend',
			'ppeFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ppe_validate_password' ),
				'strings' => array(
					'passwordRequired' => __( 'Password is required', 'password-protect-elite' ),
					'invalidPassword'  => __( 'Invalid password', 'password-protect-elite' ),
					'validating'       => __( 'Validating...', 'password-protect-elite' ),
				),
			)
		);
	}

	/**
	 * Render password entry block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string
	 */
	public function render_password_entry_block( $attributes, $content ) {
		$allowed_groups = $attributes['allowedGroups'] ?? array();
		$button_text    = $attributes['buttonText'] ?? __( 'Submit', 'password-protect-elite' );
		$placeholder    = $attributes['placeholder'] ?? __( 'Enter password', 'password-protect-elite' );
		$redirect_url   = $attributes['redirectUrl'] ?? '';
		$class_name     = $attributes['className'] ?? '';

		// If no groups are selected, allow all content-type and general groups.
		if ( empty( $allowed_groups ) ) {
			$content_groups = Database::get_password_groups( 'content' );
			$general_groups = Database::get_password_groups( 'general' );
			$all_groups     = array_merge( $content_groups, $general_groups );
			$allowed_groups = wp_list_pluck( $all_groups, 'id' );
		}

		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => $redirect_url,
			'button_text'    => $button_text,
			'placeholder'    => $placeholder,
			'class'          => 'ppe-password-form ' . $class_name,
		);

		$password_manager = new PasswordManager();
		$form_html        = $password_manager->get_password_form( $form_args );

		return '<div class="ppe-password-entry-block">' . $form_html . '</div>';
	}

	/**
	 * Render protected content block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string
	 */
	public function render_protected_content_block( $attributes, $content ) {
		$allowed_groups   = $attributes['allowedGroups'] ?? array();
		$fallback_message = $attributes['fallbackMessage'] ?? __( 'This content is password protected.', 'password-protect-elite' );
		$class_name       = $attributes['className'] ?? '';

		// If no groups are selected, allow all content-type and general groups.
		if ( empty( $allowed_groups ) ) {
			$content_groups = Database::get_password_groups( 'content' );
			$general_groups = Database::get_password_groups( 'general' );
			$all_groups     = array_merge( $content_groups, $general_groups );
			$allowed_groups = wp_list_pluck( $all_groups, 'id' );
		}

		$password_manager = new PasswordManager();

		// Check if content is accessible.
		if ( $password_manager->is_content_accessible( $allowed_groups ) ) {
			return '<div class="ppe-protected-content-block ' . esc_attr( $class_name ) . '">' . $content . '</div>';
		} else {
			// Show password form.
			$form_args = array(
				'type'           => 'content',
				'allowed_groups' => $allowed_groups,
				'redirect_url'   => '',
				'button_text'    => __( 'Unlock Content', 'password-protect-elite' ),
				'placeholder'    => __( 'Enter password to view content', 'password-protect-elite' ),
				'class'          => 'ppe-password-form ppe-protected-content-form',
			);

			$form_html = $password_manager->get_password_form( $form_args );

			return '<div class="ppe-protected-content-block ppe-locked ' . esc_attr( $class_name ) . '">
				<div class="ppe-protected-message">' . esc_html( $fallback_message ) . '</div>
				' . $form_html . '
			</div>';
		}
	}
}
