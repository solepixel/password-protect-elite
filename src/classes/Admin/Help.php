<?php
/**
 * Admin help class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help class.
 */
class Help {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_help_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_help_scripts' ] );
	}

	/**
	 * Add help page.
	 */
	public function add_help_page() {
		add_submenu_page(
			'edit.php?post_type=ppe_password_group',
			__( 'Help & Documentation', 'password-protect-elite' ),
			__( 'Help', 'password-protect-elite' ),
			'manage_options',
			'ppe-help',
			[ $this, 'render_help_page' ]
		);
	}

	/**
	 * Enqueue help scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_help_scripts( $hook ) {
		if ( 'ppe_password_group_page_ppe-help' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ppe-help',
			PPE_PLUGIN_URL . 'assets/admin/css/help.css',
			[],
			PPE_VERSION
		);
	}

	/**
	 * Render help page.
	 */
	public function render_help_page() {
		\ppelite()->template->load_view( 'help-page' );
	}
}
