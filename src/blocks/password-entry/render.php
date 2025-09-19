<?php
/**
 * Server-side rendering for Password Entry block.
 *
 * @package PasswordProtectElite
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PasswordProtectElite\Database;
use PasswordProtectElite\PasswordManager;

/**
 * Render the password entry block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 * @return string
 */
function render_password_entry_block( $attributes, $content ) {
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
