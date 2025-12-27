<?php
/**
 * Helper functions
 *
 * @package PasswordProtectElite
 */

use PasswordProtectElite\Core;

if ( ! function_exists( 'ppelite' ) ) {
	/**
	 * Password Protect Elite Core API instance.
	 *
	 * @return Core
	 */
	function ppelite(): Core {
		return Core::instance();
	}
}

if ( ! function_exists( 'ppelite_get_password_groups' ) ) {
	/**
	 * Get password groups
	 *
	 * @param string $type Optional protection type filter.
	 * @return array
	 */
	function ppelite_get_password_groups( $type = null ) {
		return ppelite()->get_password_groups( $type );
	}
}

if ( ! function_exists( 'ppelite_get_password_group' ) ) {
	/**
	 * Get password group by ID
	 *
	 * @param int $id Password group ID.
	 * @return object|null
	 */
	function ppelite_get_password_group( $id ) {
		return ppelite()->get_password_group( $id );
	}
}

if ( ! function_exists( 'ppelite_validate_password' ) ) {
	/**
	 * Validate password
	 *
	 * @param string $password Password to validate.
	 * @param string $type     Protection type (optional).
	 * @return object|null Password group or null if invalid.
	 */
	function ppelite_validate_password( $password, $type = null ) {
		return ppelite()->validate_password( $password, $type );
	}
}

if ( ! function_exists( 'ppelite_get_password_form' ) ) {
	/**
	 * Get password form HTML
	 *
	 * @param array $args Form arguments.
	 * @return string
	 */
	function ppelite_get_password_form( $args = [] ) {
		return ppelite()->password_manager->get_password_form( $args );
	}
}

if ( ! function_exists( 'ppelite_is_content_accessible' ) ) {
	/**
	 * Check if content is accessible based on password groups
	 *
	 * @param array $allowed_groups Allowed password group IDs.
	 * @return bool
	 */
	function ppelite_is_content_accessible( $allowed_groups ) {
		return ppelite()->password_manager->is_content_accessible( $allowed_groups );
	}
}
