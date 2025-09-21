<?php
/**
 * String Manager for global text customization.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles global string management for customizable text in blocks.
 */
class StringManager {

	/**
	 * Get all customizable strings with their configurations.
	 *
	 * @return array Array of string configurations.
	 */
	public function get_customizable_strings() {
		return array(
			'default_button_text' => array(
				'label' => __( 'Default Button Text', 'password-protect-elite' ),
				'default' => __( 'Submit', 'password-protect-elite' ),
				'description' => __( 'Default text for password entry buttons.', 'password-protect-elite' ),
			),
			'default_placeholder' => array(
				'label' => __( 'Default Placeholder Text', 'password-protect-elite' ),
				'default' => __( 'Enter password', 'password-protect-elite' ),
				'description' => __( 'Default placeholder text for password input fields.', 'password-protect-elite' ),
			),
			'default_fallback_message' => array(
				'label' => __( 'Default Fallback Message', 'password-protect-elite' ),
				'default' => __( 'This content is protected by password.', 'password-protect-elite' ),
				'description' => __( 'Default message shown when content is locked.', 'password-protect-elite' ),
			),
			'password_incorrect_message' => array(
				'label' => __( 'Incorrect Password Message', 'password-protect-elite' ),
				'default' => __( 'Incorrect password. Please try again.', 'password-protect-elite' ),
				'description' => __( 'Message shown when an incorrect password is entered.', 'password-protect-elite' ),
			),
			'password_success_message' => array(
				'label' => __( 'Success Message', 'password-protect-elite' ),
				'default' => __( 'Password accepted! Content unlocked.', 'password-protect-elite' ),
				'description' => __( 'Message shown when password is accepted.', 'password-protect-elite' ),
			),
			'protected_content_header' => array(
				'label' => __( 'Protected Content Header', 'password-protect-elite' ),
				'default' => __( 'Protected Content', 'password-protect-elite' ),
				'description' => __( 'Header text for protected content blocks in the editor.', 'password-protect-elite' ),
			),
			'protected_content_description' => array(
				'label' => __( 'Protected Content Description', 'password-protect-elite' ),
				'default' => __( 'Add content below. It will be hidden until the correct password is entered.', 'password-protect-elite' ),
				'description' => __( 'Description text for protected content blocks in the editor.', 'password-protect-elite' ),
			),
			'password_entry_header' => array(
				'label' => __( 'Password Entry Header', 'password-protect-elite' ),
				'default' => __( 'Password Entry Form', 'password-protect-elite' ),
				'description' => __( 'Header text for password entry blocks in the editor.', 'password-protect-elite' ),
			),
			'password_entry_description' => array(
				'label' => __( 'Password Entry Description', 'password-protect-elite' ),
				'default' => __( 'This block will render a password entry form on the frontend.', 'password-protect-elite' ),
				'description' => __( 'Description text for password entry blocks in the editor.', 'password-protect-elite' ),
			),
			'redirect_url_help' => array(
				'label' => __( 'Redirect URL Help Text', 'password-protect-elite' ),
				'default' => __( 'Optional URL to redirect users after successful password entry.', 'password-protect-elite' ),
				'description' => __( 'Help text for redirect URL field in password entry blocks.', 'password-protect-elite' ),
			),
			'allowed_groups_help' => array(
				'label' => __( 'Allowed Groups Help Text', 'password-protect-elite' ),
				'default' => __( 'Select which password groups can be used with this form. Leave empty to allow all content groups.', 'password-protect-elite' ),
				'description' => __( 'Help text for allowed groups field in password entry blocks.', 'password-protect-elite' ),
			),
			'already_authenticated_message' => array(
				'label' => __( 'Already Authenticated Message', 'password-protect-elite' ),
				'default' => __( 'You have already authenticated for this content.', 'password-protect-elite' ),
				'description' => __( 'Message shown when user has already authenticated for the password group.', 'password-protect-elite' ),
			),
			'auth_required_message' => array(
				'label' => __( 'Authentication Required Message', 'password-protect-elite' ),
				'default' => __( 'You must log in before accessing that page.', 'password-protect-elite' ),
				'description' => __( 'Message shown when a user is redirected to a password form due to authentication requirements.', 'password-protect-elite' ),
			),
			'continue_to_protected_content' => array(
				'label' => __( 'Continue Link Text', 'password-protect-elite' ),
				'default' => __( 'Continue to protected content', 'password-protect-elite' ),
				'description' => __( 'Link text shown when a user is already authenticated and can continue to the protected content.', 'password-protect-elite' ),
			),
		);
	}

	/**
	 * Get a specific string value.
	 *
	 * @param string $key String key.
	 * @return string String value.
	 */
	public function get_string( $key ) {
		$settings = get_option( 'ppe_settings', array() );
		$customizable_strings = $this->get_customizable_strings();

		if ( isset( $settings[ $key ] ) && ! empty( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( isset( $customizable_strings[ $key ]['default'] ) ) {
			return $customizable_strings[ $key ]['default'];
		}

		return '';
	}

	/**
	 * Get all current string values.
	 *
	 * @return array Array of current string values.
	 */
	public function get_all_strings() {
		$strings = array();
		$customizable_strings = $this->get_customizable_strings();

		foreach ( $customizable_strings as $key => $config ) {
			$strings[ $key ] = $this->get_string( $key );
		}

		return $strings;
	}

	/**
	 * Reset all strings to defaults.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function reset_to_defaults() {
		$settings = get_option( 'ppe_settings', array() );
		$customizable_strings = $this->get_customizable_strings();

		foreach ( $customizable_strings as $key => $config ) {
			unset( $settings[ $key ] );
		}

		return update_option( 'ppe_settings', $settings );
	}
}
