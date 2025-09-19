<?php
/**
 * Admin functionality class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the main admin menu and settings page.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Admin functionality is now handled by the PasswordGroups CPT.
		// No additional admin menu needed.
	}
}
