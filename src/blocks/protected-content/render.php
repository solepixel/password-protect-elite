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
	$access_mode      = $attributes['accessMode'] ?? 'groups';
	$allowed_roles    = $attributes['allowedRoles'] ?? array();
	$allowed_caps     = $attributes['allowedCapabilities'] ?? array();
	$disable_form     = ! empty( $attributes['disableForm'] );
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

	// Role-based/capability-based access modes bypass password UI entirely.
	if ( 'roles' === $access_mode ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$roles = is_array( $allowed_roles ) ? $allowed_roles : array();
			if ( $user && ! empty( $user->roles ) && ! empty( $roles ) ) {
				foreach ( (array) $user->roles as $role_slug ) {
					if ( in_array( $role_slug, $roles, true ) ) {
						return '<div class="ppe-protected-content-block ' . esc_attr( $class_name ) . '">' . $content . '</div>';
					}
				}
			}
		}
		// Not authorized: render nothing.
		return '';
	}

	if ( 'caps' === $access_mode ) {
		$caps = is_array( $allowed_caps ) ? $allowed_caps : array();
		if ( ! empty( $caps ) ) {
			foreach ( $caps as $cap ) {
				if ( current_user_can( sanitize_key( $cap ) ) ) {
					return '<div class="ppe-protected-content-block ' . esc_attr( $class_name ) . '">' . $content . '</div>';
				}
			}
		}
		return '';
	}

	// Default (groups): show content if authenticated via password or role-based bypass for any selected group.
	if ( Blocks::get_authenticated_group_id( $allowed_groups ) > 0 ) {
		return '<div class="ppe-protected-content-block ' . esc_attr( $class_name ) . '">' . $content . '</div>';
	} else {
		// Show password form only if there are password groups selected and form is not disabled.
		if ( empty( $allowed_groups ) || $disable_form ) {
			return '';
		}

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
