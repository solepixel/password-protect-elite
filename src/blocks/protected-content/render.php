<?php
/**
 * Server-side rendering for Protected Content block.
 *
 * @package PasswordProtectElite
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PasswordProtectElite\Database;
use PasswordProtectElite\PasswordManager;
use PasswordProtectElite\Blocks;

/**
 * Render the protected content block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 * @return string
 */
function render_protected_content_block( $attributes, $content ) {
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

    // Check if content is accessible (password or role-based access).
    if ( Blocks::get_authenticated_group_id( $allowed_groups ) > 0 ) {
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
