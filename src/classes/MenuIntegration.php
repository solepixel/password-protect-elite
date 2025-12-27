<?php
/**
 * WordPress menu integration class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates password sessions with WordPress menu system.
 */
class MenuIntegration {

	/**
	 * Session manager instance.
	 *
	 * @var SessionManager
	 */
	private $session_manager;

	/**
	 * Constructor.
	 *
	 * @param SessionManager|null $session_manager Session manager instance.
	 */
	public function __construct( $session_manager = null ) {
		$this->session_manager = $session_manager ?? new SessionManager();

		// Hook into WordPress menu system.
		add_filter( 'wp_nav_menu_items', [ $this, 'modify_menu_items' ], 10, 2 );
		add_filter( 'render_block_core/loginout', [ $this, 'modify_loginout_block' ], 20, 2 );
		add_filter( 'loginout', [ $this, 'modify_loginout_link' ], 10, 2 );
		add_action( 'init', [ $this, 'handle_logout_request' ] );
	}

	/**
	 * Check if user has active password session.
	 *
	 * @return bool
	 */
	private function has_active_password_session() {
		return $this->session_manager->has_active_session();
	}

	/**
	 * Modify menu items to change login/logout links.
	 *
	 * @param string $items The HTML list content for the menu items.
	 * @return string Modified menu items.
	 */
	public function modify_menu_items( $items ) {
		// Only modify if user is not logged in as WordPress user but has password session.
		if ( is_user_logged_in() || ! $this->has_active_password_session() ) {
			return $items;
		}

		// Find and replace login links with logout links.
		$login_url  = wp_login_url();
		$logout_url = $this->get_logout_url();

		// Replace login URLs with logout URL.
		$items = str_replace( $login_url, $logout_url, $items );

		// Replace "Log In" text with "Log Out".
		$items = preg_replace(
			'/(<a[^>]*' . preg_quote( $logout_url, '/' ) . '[^>]*>)\s*Log In\s*(<\/a>)/i',
			'$1Log Out$2',
			$items
		);

		// Also handle "Login" text.
		$items = preg_replace(
			'/(<a[^>]*' . preg_quote( $logout_url, '/' ) . '[^>]*>)\s*Login\s*(<\/a>)/i',
			'$1Log Out$2',
			$items
		);

		return $items;
	}

	/**
	 * Get the logout URL for password sessions.
	 *
	 * @return string
	 */
	private function get_logout_url() {
		return add_query_arg(
			[
				'ppe_action' => 'logout',
				'ppe_nonce'  => wp_create_nonce( 'ppe_menu_logout' ),
			],
			home_url()
		);
	}

	/**
	 * Modify the Core Login/Logout block output.
	 *
	 * @param string $block_content Block HTML content.
	 * @return string Modified block content.
	 */
	public function modify_loginout_block( $block_content ) {
		// Only modify if user is not logged in but has password session.
		if ( is_user_logged_in() || ! $this->has_active_password_session() ) {
			return $block_content;
		}

		// Replace login URL with logout URL.
		$login_url  = wp_login_url();
		$logout_url = $this->get_logout_url();

		$block_content = str_replace( $login_url, $logout_url, $block_content );

		// Replace "Log in" text with "Log out" (case variations).
		$block_content = preg_replace( '/>\s*Log\s?in\s*</i', '>Log out<', $block_content );

		return $block_content;
	}

	/**
	 * Modify the loginout link (wp_loginout function).
	 *
	 * @param string $link    The HTML link content.
	 * @param string $redirect Redirect URL after login/logout.
	 * @return string Modified link.
	 */
	public function modify_loginout_link( $link, $redirect = '' ) {
		// Only modify if user is not logged in but has password session.
		if ( is_user_logged_in() || ! $this->has_active_password_session() ) {
			return $link;
		}

		// Build logout URL with optional redirect.
		$logout_url = add_query_arg(
			[
				'ppe_action'  => 'logout',
				'ppe_nonce'   => wp_create_nonce( 'ppe_menu_logout' ),
				'redirect_to' => ! empty( $redirect ) ? rawurlencode( $redirect ) : '',
			],
			home_url()
		);

		// Replace login URL with logout URL.
		$login_url = wp_login_url( $redirect );
		$link      = str_replace( $login_url, $logout_url, $link );

		// Replace "Log in" text with "Log out".
		$link = preg_replace( '/>\s*Log in\s*</i', '>Log out<', $link );
		$link = preg_replace( '/>\s*Login\s*</i', '>Log out<', $link );

		return $link;
	}

	/**
	 * Handle logout request from menu link.
	 */
	public function handle_logout_request() {
		// Check if this is a logout request.
		if ( ! isset( $_GET['ppe_action'] ) || 'logout' !== $_GET['ppe_action'] ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['ppe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ppe_nonce'] ) ), 'ppe_menu_logout' ) ) {
			wp_die( esc_html__( 'Security check failed', 'password-protect-elite' ) );
		}

		// Clear password session.
		$this->session_manager->clear_session();

		// Redirect to home page or referer.
		$redirect_to = ! empty( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url();

		// Remove logout parameters from redirect URL.
		$redirect_to = remove_query_arg( array( 'ppe_action', 'ppe_nonce' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit;
	}
}
