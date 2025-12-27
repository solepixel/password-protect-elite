<?php
/**
 * Admin functionality class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the main admin menu and settings page.
 */
class Admin {

	/**
	 * Admin Help functionality
	 *
	 * @var ?Help
	 */
	public ?Help $help = null;

	/**
	 * Admin Settings functionality
	 *
	 * @var ?Settings
	 */
	public ?Settings $settings = null;

	/**
	 * Admin Pages List functionality
	 *
	 * @var ?PagesList
	 */
	public ?PagesList $pages_list = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize admin sub-classes.
		$this->help       = new Help();
		$this->settings   = new Settings();
		$this->pages_list = new PagesList();

		// Enqueue general admin styles on all admin pages.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	/**
	 * Enqueue general admin styles.
	 * Loads on all WordPress admin pages.
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style(
			'ppe-admin-general',
			PPE_PLUGIN_URL . 'assets/admin/css/general.css',
			[],
			PPE_VERSION
		);
	}
}
