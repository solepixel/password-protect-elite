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
		// Register scripts as modules first.
		$this->register_block_scripts();

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
	 * Register block scripts
	 */
	private function register_block_scripts() {
		// Register password entry block script.
		wp_register_script(
			'ppe-password-entry-block',
			PPE_PLUGIN_URL . 'build/password-entry.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ),
			PPE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Register protected content block script.
		wp_register_script(
			'ppe-protected-content-block',
			PPE_PLUGIN_URL . 'build/protected-content.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ),
			PPE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue password entry block (already registered as module).
		wp_enqueue_script( 'ppe-password-entry-block' );

		// Enqueue protected content block (already registered as module).
		wp_enqueue_script( 'ppe-protected-content-block' );

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


		// Get global strings for localization.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$global_strings = $string_manager->get_all_strings();

		// Localize scripts with password groups for the block editor.
		$localization_data = array(
			'passwordGroups' => $groups_data,
			'globalStrings' => $global_strings,
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
		);

		wp_localize_script( 'ppe-password-entry-block', 'ppeBlocks', $localization_data );
		wp_localize_script( 'ppe-protected-content-block', 'ppeBlocks', $localization_data );
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

		// Note: Block styles are now handled by the BlockStyles class
		// based on the user's settings preference

		// Get debug mode setting
		$settings = get_option( 'ppe_settings', array() );
		$debug_mode = isset( $settings['debug_mode'] ) && $settings['debug_mode'];

		wp_localize_script(
			'ppe-frontend',
			'ppeFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ppe_validate_password' ),
				'debugMode' => $debug_mode,
				'strings' => array(
					'passwordRequired' => __( 'Password is required', 'password-protect-elite' ),
					'invalidPassword'  => __( 'Invalid password', 'password-protect-elite' ),
					'validating'       => __( 'Validating...', 'password-protect-elite' ),
					'error'            => __( 'An error occurred. Please try again.', 'password-protect-elite' ),
				),
			)
		);
	}

	/**
	 * Render password entry block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content (unused for this block).
	 * @return string
	 */
	public function render_password_entry_block( $attributes, $content ) {
		unset( $content );
		$allowed_groups = $attributes['allowedGroups'] ?? array();

		// Get global strings for defaults.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$button_text    = $attributes['buttonText'] ?? $string_manager->get_string( 'default_button_text' );
		$placeholder    = $attributes['placeholder'] ?? $string_manager->get_string( 'default_placeholder' );
		$redirect_url   = $attributes['redirectUrl'] ?? '';
		$class_name     = $attributes['className'] ?? '';

		// If no groups are selected, allow all content-type and general groups.
		if ( empty( $allowed_groups ) ) {
			$content_groups = Database::get_password_groups( 'content' );
			$general_groups = Database::get_password_groups( 'general' );
			$all_groups     = array_merge( $content_groups, $general_groups );
			$allowed_groups = wp_list_pluck( $all_groups, 'id' );
		}

		$password_manager = new PasswordManager();

		// Check if user is already authenticated for any of the allowed groups.
		$is_authenticated = false;
		$authenticated_group = null;
		foreach ( $allowed_groups as $group_id ) {
			if ( $password_manager->is_password_validated( $group_id ) ) {
				$is_authenticated = true;
				$authenticated_group = $group_id;
				break;
			}
		}

		// If user is already authenticated, show message instead of form.
		if ( $is_authenticated ) {
			$authenticated_message = $string_manager->get_string( 'already_authenticated_message' );
			if ( empty( $authenticated_message ) ) {
				$authenticated_message = __( 'You have already authenticated for this content.', 'password-protect-elite' );
			}

			// Get redirect URL from block settings or password group.
			$final_redirect_url = $redirect_url;
			if ( empty( $final_redirect_url ) && $authenticated_group ) {
				$password_group = Database::get_password_group( $authenticated_group );
				if ( $password_group ) {
					$final_redirect_url = $password_manager->get_redirect_url( $password_group );
				}
			}

			$output = '<div class="ppe-password-entry-block ppe-authenticated ' . esc_attr( $class_name ) . '">';
			$output .= '<div class="ppe-authenticated-message">';
			$output .= '<p>' . esc_html( $authenticated_message ) . '</p>';

			// Add redirect link if redirect URL is available.
			if ( ! empty( $final_redirect_url ) ) {
				$output .= '<p><a href="' . esc_url( $final_redirect_url ) . '" class="ppe-redirect-link">' . esc_html__( 'Continue to protected content', 'password-protect-elite' ) . '</a></p>';
			}

			$output .= '</div>';
			$output .= '</div>';

			return $output;
		}

		// User is not authenticated, show the password form.
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => $redirect_url,
			'button_text'    => $button_text,
			'placeholder'    => $placeholder,
			'class'          => 'ppe-password-form ' . $class_name,
		);

		$form_html = $password_manager->get_password_form( $form_args );

		// If redirected due to authentication requirement, and this form supports the same group,
		// populate the existing error message element so it inherits the same styling.
		if ( isset( $_GET['ppe_auth_required'] ) && '1' === $_GET['ppe_auth_required'] ) {
			$redirect_group = isset( $_GET['ppe_group'] ) ? absint( $_GET['ppe_group'] ) : 0;
			if ( $redirect_group && ( empty( $allowed_groups ) || in_array( $redirect_group, $allowed_groups, true ) ) ) {
				$auth_required_message = $string_manager->get_string( 'auth_required_message' );
				$replacement = '<div class="ppe-error-message" style="display: block;">' . esc_html( $auth_required_message ) . '</div>';
				$form_html  = str_replace( '<div class="ppe-error-message" style="display: none;"></div>', $replacement, $form_html );
			}
		}

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

		// Get global strings for defaults.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$fallback_message = $attributes['fallbackMessage'] ?? $string_manager->get_string( 'default_fallback_message' );
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
				'button_text'    => $string_manager->get_string( 'default_button_text' ),
				'placeholder'    => $string_manager->get_string( 'default_placeholder' ),
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
