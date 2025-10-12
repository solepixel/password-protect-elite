<?php
/**
 * Password management class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles password validation.
 */
class PasswordManager {

	/**
	 * Session key for storing validated password groups (for backward compatibility).
	 *
	 * @var string
	 */
	const SESSION_KEY = 'ppe_validated_groups';

	/**
	 * Cookie name for session identification (for backward compatibility).
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'ppe_session_id';

	/**
	 * Session manager instance.
	 *
	 * @var SessionManager
	 */
	private $session_manager;

	/**
	 * Constructor.
	 *
	 * @param SessionManager|null $session_manager Session manager instance (for dependency injection).
	 */
	public function __construct( $session_manager = null ) {
		$this->session_manager = $session_manager ?? new SessionManager();

		add_action( 'wp_ajax_ppe_validate_password', array( $this, 'ajax_validate_password' ) );
		add_action( 'wp_ajax_nopriv_ppe_validate_password', array( $this, 'ajax_validate_password' ) );
		add_action( 'init', array( $this, 'init_session' ) );
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	private function is_debug_mode_enabled() {
		$settings = get_option( 'ppe_settings', array() );
		return isset( $settings['debug_mode'] ) && $settings['debug_mode'];
	}

	/**
	 * Log debug message if debug mode is enabled.
	 *
	 * @param string $message Debug message.
	 */
	private function debug_log( $message ) {
		if ( $this->is_debug_mode_enabled() ) {
			error_log( 'PPE Debug: ' . $message );
		}
	}

	/**
	 * Initialize session handling.
	 */
	public function init_session() {
		$this->session_manager->init();
	}

	/**
	 * Validate password via AJAX.
	 */
	public function ajax_validate_password() {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ppe_validate_password' ) ) {
			wp_die( esc_html__( 'Security check failed', 'password-protect-elite' ) );
		}

		// Bot honeypot: silently fail and count as attempt if filled.
		$honeypot = isset( $_POST['ppe_hp'] ) ? sanitize_text_field( trim( (string) wp_unslash( $_POST['ppe_hp'] ) ) ) : '';
		$client_fingerprints = $this->get_client_fingerprints();

		// Check lockout before doing any processing.
		$lockout_data = $this->get_active_lockout( $client_fingerprints );
		if ( $lockout_data ) {
			$remaining_seconds = max( 0, (int) $lockout_data['expires_at'] - time() );
			$remaining_minutes = (int) ceil( $remaining_seconds / 60 );
			/* translators: %d is the remaining minutes until the lockout expires. */
			wp_send_json_error( sprintf( __( 'Too many failed attempts. Try again in %d minute(s).', 'password-protect-elite' ), $remaining_minutes ) );
		}

		$password     = sanitize_text_field( wp_unslash( $_POST['password'] ?? '' ) );
		$secure_data  = sanitize_text_field( wp_unslash( $_POST['ppe_secure_data'] ?? '' ) );

		// Debug logging.
		$this->debug_log( 'Password received: ' . $password );
		$this->debug_log( 'Secure data received: ' . $secure_data );

		if ( empty( $password ) || '' !== $honeypot ) {
			// Treat empty password or triggered honeypot as a failed attempt.
			$this->record_failed_attempt( $client_fingerprints );
			$remaining = $this->get_remaining_attempts_message( $client_fingerprints );
			/* translators: %s is a short message about remaining attempts before lockout. */
			wp_send_json_error( $remaining ? sprintf( __( 'Password is required. %s', 'password-protect-elite' ), $remaining ) : __( 'Password is required', 'password-protect-elite' ) );
		}

		// Decrypt and validate secure form data.
		$form_data = SecureData::validate_secure_form_data( $secure_data );
		if ( false === $form_data ) {
			$this->debug_log( 'Secure data validation failed.' );
			$this->record_failed_attempt( $client_fingerprints );
			wp_send_json_error( __( 'Invalid form data', 'password-protect-elite' ) );
		}

		$this->debug_log( 'Form data validated: ' . print_r( $form_data, true ) );

		$type           = $form_data['type'];
		$redirect_url   = $form_data['redirect_url'];
		$allowed_groups = $form_data['allowed_groups'];

		$this->debug_log( 'About to validate password "' . $password . '" with type "' . $type . '".' );

		// Debug: Check what password groups are available.
		$all_groups = Database::get_password_groups( $type );
		$this->debug_log( 'Available password groups for type "' . $type . '": ' . print_r( $all_groups, true ) );

		// Try to validate with the specified type first.
		$password_group = Database::validate_password( $password, $type );

		// If no match found and type is 'content', also try 'general' type.
		if ( ! $password_group && 'content' === $type ) {
			$this->debug_log( 'No match for content type, trying general type.' );
			$general_groups = Database::get_password_groups( 'general' );
			$this->debug_log( 'Available general password groups: ' . print_r( $general_groups, true ) );
			$password_group = Database::validate_password( $password, 'general' );
		}
		$this->debug_log( 'Password validation result: ' . ( $password_group ? 'SUCCESS' : 'FAILED' ) );

		if ( $password_group ) {
			$this->debug_log( 'Password group found: ' . print_r( $password_group, true ) );
			$this->debug_log( 'Allowed groups: ' . print_r( $allowed_groups, true ) );

			// Validate that the password group is in the allowed groups.
			if ( ! empty( $allowed_groups ) && ! in_array( $password_group->id, $allowed_groups, true ) ) {
				$this->debug_log( 'Password group not in allowed groups.' );
				$this->record_failed_attempt( $client_fingerprints );
				$remaining = $this->get_remaining_attempts_message( $client_fingerprints );
				/* translators: %s is a short message about remaining attempts before lockout. */
				wp_send_json_error( $remaining ? sprintf( __( 'Password not authorized for this form. %s', 'password-protect-elite' ), $remaining ) : __( 'Password not authorized for this form', 'password-protect-elite' ) );
			}
		} else {
			$this->debug_log( 'No password group found for password "' . $password . '" and type "' . $type . '".' );
		}

		if ( $password_group ) {
			$this->store_validated_password( $password_group->id, $password );
			$this->reset_failed_attempts( $client_fingerprints );

			// Determine redirect URL.
			$final_redirect = $this->get_redirect_url( $password_group, $redirect_url );

			wp_send_json_success(
				array(
					'message'      => __( 'Password validated successfully', 'password-protect-elite' ),
					'redirect_url' => $final_redirect,
					'group_id'     => $password_group->id,
				)
			);
		} else {
			$this->record_failed_attempt( $client_fingerprints );
			$remaining = $this->get_remaining_attempts_message( $client_fingerprints );
			if ( $this->get_active_lockout( $client_fingerprints ) ) {
				$duration = \PasswordProtectElite\Admin\Settings::get_lockout_duration_minutes();
				/* translators: %d is the number of minutes for the lockout duration. */
				wp_send_json_error( sprintf( __( 'Too many failed attempts. You are locked out for %d minute(s).', 'password-protect-elite' ), $duration ) );
			}
			/* translators: %s is a short message about remaining attempts before lockout. */
			wp_send_json_error( $remaining ? sprintf( __( 'Invalid password. %s', 'password-protect-elite' ), $remaining ) : __( 'Invalid password', 'password-protect-elite' ) );
		}
	}

	/**
	 * Store validated password group and password in session.
	 *
	 * @param int    $group_id Password group ID.
	 * @param string $password The password that was used for authentication.
	 */
	public function store_validated_password( $group_id, $password ) {
		$password_hash = $this->hash_password_for_session( $password );
		$this->session_manager->store_validated_group( $group_id, $password_hash );
	}

	/**
	 * Check if password group is validated and password is still valid.
	 *
	 * @param int $group_id Password group ID.
	 * @return bool
	 */
	public function is_password_validated( $group_id ) {
		$session_data = $this->session_manager->get_validated_group( $group_id );

		// Check if group is in session data.
		if ( ! $session_data ) {
			return false;
		}

		$stored_password_hash = $session_data['password_hash'] ?? '';
		$timestamp            = $session_data['timestamp'] ?? 0;

		// Check if session has expired based on settings.
		if ( $this->session_manager->is_session_expired( $timestamp ) ) {
			// Session has expired, remove from session.
			$this->session_manager->remove_validated_group( $group_id );
			return false;
		}

		// Re-validate the stored password against current password group data.
		if ( ! $this->revalidate_stored_password( $group_id, $stored_password_hash ) ) {
			// Password is no longer valid, remove from session.
			$this->session_manager->remove_validated_group( $group_id );
			return false;
		}

		return true;
	}

	/**
	 * Get redirect URL for password group.
	 *
	 * @param object $password_group Password group object.
	 * @param string $fallback_url   Fallback URL.
	 * @return string
	 */
	public function get_redirect_url( $password_group, $fallback_url = '' ) {
		if ( 'page' === $password_group->redirect_type && $password_group->redirect_page_id ) {
			return get_permalink( $password_group->redirect_page_id );
		} elseif ( 'custom_url' === $password_group->redirect_type && $password_group->redirect_custom_url ) {
			return $password_group->redirect_custom_url;
		}

		return ! empty( $fallback_url ) ? $fallback_url : home_url();
	}

	/**
	 * Get password form HTML.
	 *
	 * @param array $args Form arguments.
	 * @return string
	 */
	public function get_password_form( $args = array() ) {
		$defaults = array(
			'type'           => '',
			'allowed_groups' => array(),
			'redirect_url'   => '',
			'button_text'    => __( 'Submit', 'password-protect-elite' ),
			'placeholder'    => __( 'Enter password', 'password-protect-elite' ),
			'class'          => 'ppe-password-form',
		);

		$args = wp_parse_args( $args, $defaults );

		// Create secure encrypted form data.
		$secure_data = SecureData::create_secure_form_data( $args );
		if ( false === $secure_data ) {
			wp_die( 'Failed to create secure form data' );
		}

		ob_start();
		?>
		<form class="<?php echo esc_attr( $args['class'] ); ?>">
			<?php wp_nonce_field( 'ppe_validate_password', 'ppe_nonce' ); ?>
			<input type="hidden" name="ppe_secure_data" value="<?php echo esc_attr( $secure_data ); ?>">
			<input type="text" name="ppe_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
			<div class="ppe-error-message" style="display: none;"></div>
			<input type="password" name="password" class="ppe-password-input" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" required>
			<button type="submit" class="ppe-submit-button"><?php echo esc_html( $args['button_text'] ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if content is accessible based on password groups.
	 *
	 * @param array $allowed_groups Allowed password group IDs.
	 * @return bool
	 */
	public function is_content_accessible( $allowed_groups ) {
		if ( empty( $allowed_groups ) ) {
			return true;
		}

		foreach ( $allowed_groups as $group_id ) {
			if ( $this->is_password_validated( $group_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if current user has access to a specific password group via either
	 * a validated password session or role-based bypass configured on the group.
	 *
	 * @param int $group_id Password group ID.
	 * @return bool True if user may access, false otherwise.
	 */
	public function has_access_to_group( $group_id ) {
		// Password validated session grants access.
		if ( $this->is_password_validated( $group_id ) ) {
			return true;
		}

		// Role-based bypass if user is logged in and has a permitted role.
		$group = Database::get_password_group( $group_id );
		if ( $group && ! empty( $group->allowed_roles ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && ! empty( $user->roles ) ) {
				foreach ( (array) $user->roles as $role_slug ) {
					if ( in_array( $role_slug, (array) $group->allowed_roles, true ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Hash password for session storage.
	 *
	 * @param string $password Plain text password.
	 * @return string Hashed password.
	 */
	private function hash_password_for_session( $password ) {
		// Use a combination of password and a salt for session storage.
		// This is different from database storage to add an extra layer of security.
		return hash( 'sha256', $password . wp_salt( 'auth' ) );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) );
				// Handle comma-separated list from proxies.
				if ( false !== strpos( $ip, ',' ) ) {
					$parts = array_map( 'trim', explode( ',', $ip ) );
					$ip    = $parts[0];
				}
				return $ip;
			}
		}
		return '0.0.0.0';
	}

	/**
	 * Build a set of client fingerprint hashes for tracking attempts.
	 * Uses IP and IP+UA to resist simple cookie/incognito evasion.
	 *
	 * @return array
	 */
	private function get_client_fingerprints() {
		$ip         = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return array(
			md5( 'ip:' . $ip ),
			md5( 'ipua:' . $ip . '|' . $user_agent ),
		);
	}

	/**
	 * Check if there is an active lockout for any fingerprint.
	 *
	 * @param array $fingerprints Array of fingerprint hashes.
	 * @return array|null Lockout data or null if none.
	 */
	private function get_active_lockout( array $fingerprints ) {
		foreach ( $fingerprints as $fp ) {
			$lock = get_transient( 'ppe_lockout_' . $fp );
			if ( is_array( $lock ) && isset( $lock['expires_at'] ) && $lock['expires_at'] > time() ) {
				return $lock;
			}
		}
		return null;
	}

	/**
	 * Record a failed attempt and initiate lockout if limit reached.
	 *
	 * @param array $fingerprints Fingerprints to update.
	 */
	private function record_failed_attempt( array $fingerprints ) {
		$limit   = \PasswordProtectElite\Admin\Settings::get_password_attempts_limit();
		$minutes = \PasswordProtectElite\Admin\Settings::get_lockout_duration_minutes();
		$expires = time() + ( $minutes * 60 );
		$locked  = false;

		foreach ( $fingerprints as $fp ) {
			$key      = 'ppe_attempts_' . $fp;
			$attempts = get_transient( $key );
			if ( ! is_array( $attempts ) ) {
				$attempts = array(
					'count'    => 0,
					'first_at' => time(),
				);
			}
			++$attempts['count'];
			// Keep attempts window within a day to avoid unbounded growth.
			set_transient( $key, $attempts, DAY_IN_SECONDS );

			if ( $attempts['count'] >= $limit ) {
				set_transient( 'ppe_lockout_' . $fp, array( 'expires_at' => $expires ), ( $minutes * 60 ) + 60 );
				delete_transient( $key );
				$locked = true;
			}
		}

		return $locked;
	}

	/**
	 * Reset failed attempts counters after successful validation.
	 *
	 * @param array $fingerprints Fingerprints to clear.
	 */
	private function reset_failed_attempts( array $fingerprints ) {
		foreach ( $fingerprints as $fp ) {
			delete_transient( 'ppe_attempts_' . $fp );
		}
	}

	/**
	 * Get a human-friendly remaining attempts message.
	 *
	 * @param array $fingerprints Fingerprints to check.
	 * @return string Empty string if not applicable.
	 */
	private function get_remaining_attempts_message( array $fingerprints ) {
		$limit = \PasswordProtectElite\Admin\Settings::get_password_attempts_limit();
		$min_remaining = $limit;
		foreach ( $fingerprints as $fp ) {
			$attempts = get_transient( 'ppe_attempts_' . $fp );
			$count    = is_array( $attempts ) && isset( $attempts['count'] ) ? (int) $attempts['count'] : 0;
			$remaining = max( 0, $limit - $count );
			$min_remaining = min( $min_remaining, $remaining );
		}
		if ( $min_remaining <= 2 && $min_remaining > 0 ) {
			/* translators: %d is the number of remaining attempts before lockout. */
			return sprintf( __( '%d attempt(s) remaining before temporary lockout.', 'password-protect-elite' ), $min_remaining );
		}
		if ( 0 === $min_remaining ) {
			$minutes = \PasswordProtectElite\Admin\Settings::get_lockout_duration_minutes();
			/* translators: %d is the number of minutes for the lockout duration. */
			return sprintf( __( 'Too many failed attempts. You will be locked out for %d minute(s).', 'password-protect-elite' ), $minutes );
		}
		return '';
	}

	/**
	 * Re-validate stored password against current password group data.
	 *
	 * @param int    $group_id Password group ID.
	 * @param string $stored_password_hash Stored password hash from session.
	 * @return bool True if password is still valid, false otherwise.
	 */
	private function revalidate_stored_password( $group_id, $stored_password_hash ) {
		// Get current password group data.
		$password_group = Database::get_password_group( $group_id );
		if ( ! $password_group ) {
			// Group no longer exists.
			return false;
		}

		// Check master password.
		if ( ! empty( $password_group->master_password ) ) {
			$master_hash = $this->hash_password_for_session( $password_group->master_password );
			if ( hash_equals( $stored_password_hash, $master_hash ) ) {
				return true;
			}
		}

		// Check additional passwords.
		if ( ! empty( $password_group->additional_passwords ) && is_array( $password_group->additional_passwords ) ) {
			foreach ( $password_group->additional_passwords as $additional_password ) {
				$additional_hash = $this->hash_password_for_session( $additional_password );
				if ( hash_equals( $stored_password_hash, $additional_hash ) ) {
					return true;
				}
			}
		}

		// Password not found in current group data.
		return false;
	}
}
