<?php
/**
 * Session management class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles session management using WordPress transients and cookies.
 */
class SessionManager {

	/**
	 * Session key for storing validated password groups.
	 *
	 * @var string
	 */
	const SESSION_KEY = 'ppe_validated_groups';

	/**
	 * Cookie name for session identification.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'ppe_session_id';

	/**
	 * Initialize session handling.
	 */
	public function init() {
		// Ensure we have a session ID cookie set.
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) && ! headers_sent() ) {
			$this->set_session_cookie( $this->generate_session_id() );
		}

		// Hook into WordPress logout to also clear password sessions.
		add_action( 'wp_logout', array( $this, 'clear_session_on_wp_logout' ) );
	}

	/**
	 * Clear password session when WordPress user logs out.
	 * This is called via the wp_logout action hook.
	 */
	public function clear_session_on_wp_logout() {
		$this->clear_session();
	}

	/**
	 * Store validated password group in session.
	 *
	 * @param int    $group_id Password group ID.
	 * @param string $password_hash Hashed password.
	 * @return bool True on success, false on failure.
	 */
	public function store_validated_group( $group_id, $password_hash ) {
		$validated_data = $this->get_session_data();

		// Store group ID and password hash for re-validation.
		$validated_data[ $group_id ] = array(
			'group_id'      => $group_id,
			'password_hash' => $password_hash,
			'timestamp'     => time(),
		);

		return $this->save_session_data( $validated_data );
	}

	/**
	 * Check if password group is validated in session.
	 *
	 * @param int $group_id Password group ID.
	 * @return array|null Session data for group or null if not found.
	 */
	public function get_validated_group( $group_id ) {
		$validated_data = $this->get_session_data();

		if ( ! isset( $validated_data[ $group_id ] ) ) {
			return null;
		}

		return $validated_data[ $group_id ];
	}

	/**
	 * Remove a validated group from session.
	 *
	 * @param int $group_id Password group ID.
	 * @return bool True on success.
	 */
	public function remove_validated_group( $group_id ) {
		$validated_data = $this->get_session_data();

		if ( isset( $validated_data[ $group_id ] ) ) {
			unset( $validated_data[ $group_id ] );
			return $this->save_session_data( $validated_data );
		}

		return true;
	}

	/**
	 * Check if session has expired based on timestamp.
	 *
	 * @param int $timestamp Session timestamp.
	 * @return bool True if session has expired, false otherwise.
	 */
	public function is_session_expired( $timestamp ) {
		// Get session duration in hours from settings.
		$session_duration_hours = \PasswordProtectElite\Admin\Settings::get_session_duration_hours();

		// Convert hours to seconds.
		$session_duration_seconds = $session_duration_hours * HOUR_IN_SECONDS;

		// Check if current time exceeds the session duration.
		return ( time() - $timestamp ) > $session_duration_seconds;
	}

	/**
	 * Check if there is any active password session.
	 *
	 * @return bool True if user has active password session, false otherwise.
	 */
	public function has_active_session() {
		$session_data = $this->get_session_data();
		return ! empty( $session_data );
	}

	/**
	 * Clear all session data.
	 *
	 * @return bool True on success.
	 */
	public function clear_session() {
		$session_id = $this->get_session_id();
		if ( $session_id ) {
			delete_transient( 'ppe_session_' . $session_id );
		}

		// Clear the cookie.
		if ( ! headers_sent() ) {
			$this->clear_session_cookie();
		}

		return true;
	}

	/**
	 * Generate a unique session ID.
	 *
	 * @return string
	 */
	private function generate_session_id() {
		return wp_generate_password( 32, false );
	}

	/**
	 * Get the current session ID from cookie.
	 *
	 * @return string|null
	 */
	public function get_session_id() {
		return isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : null;
	}

	/**
	 * Set session cookie.
	 *
	 * @param string $session_id Session ID.
	 */
	private function set_session_cookie( $session_id ) {
		$session_duration = \PasswordProtectElite\Admin\Settings::get_session_duration_hours();
		$expiry           = time() + ( $session_duration * HOUR_IN_SECONDS );

		setcookie(
			self::COOKIE_NAME,
			$session_id,
			array(
				'expires'  => $expiry,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		$_COOKIE[ self::COOKIE_NAME ] = $session_id;
	}

	/**
	 * Clear session cookie.
	 */
	private function clear_session_cookie() {
		setcookie(
			self::COOKIE_NAME,
			'',
			array(
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * Get session data from transient.
	 *
	 * @return array
	 */
	private function get_session_data() {
		$session_id = $this->get_session_id();
		if ( ! $session_id ) {
			return array();
		}

		$data = get_transient( 'ppe_session_' . $session_id );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Save session data to transient.
	 *
	 * @param array $data Session data.
	 * @return bool True on success.
	 */
	private function save_session_data( $data ) {
		$session_id = $this->get_session_id();
		if ( ! $session_id ) {
			$session_id = $this->generate_session_id();
			$this->set_session_cookie( $session_id );
		}

		$session_duration = \PasswordProtectElite\Admin\Settings::get_session_duration_hours();
		return set_transient( 'ppe_session_' . $session_id, $data, $session_duration * HOUR_IN_SECONDS );
	}
}

