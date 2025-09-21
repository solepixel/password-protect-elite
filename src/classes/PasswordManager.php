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
 * Handles password validation and session management.
 */
class PasswordManager {

	/**
	 * Session key for storing validated password groups.
	 *
	 * @var string
	 */
	const SESSION_KEY = 'ppe_validated_groups';

	/**
	 * Constructor.
	 */
	public function __construct() {
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
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

	/**
	 * Validate password via AJAX.
	 */
	public function ajax_validate_password() {
		// Verify nonce.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ?? '' ), 'ppe_validate_password' ) ) {
			wp_die( esc_html__( 'Security check failed', 'password-protect-elite' ) );
		}

		$password     = sanitize_text_field( wp_unslash( $_POST['password'] ?? '' ) );
		$secure_data  = sanitize_text_field( wp_unslash( $_POST['ppe_secure_data'] ?? '' ) );

		// Debug logging
		$this->debug_log( 'Password received: ' . $password );
		$this->debug_log( 'Secure data received: ' . $secure_data );

		if ( empty( $password ) ) {
			wp_send_json_error( __( 'Password is required', 'password-protect-elite' ) );
		}

		// Decrypt and validate secure form data.
		$form_data = SecureData::validate_secure_form_data( $secure_data );
		if ( false === $form_data ) {
			$this->debug_log( 'Secure data validation failed' );
			wp_send_json_error( __( 'Invalid form data', 'password-protect-elite' ) );
		}

		$this->debug_log( 'Form data validated: ' . print_r( $form_data, true ) );

		$type           = $form_data['type'];
		$redirect_url   = $form_data['redirect_url'];
		$allowed_groups = $form_data['allowed_groups'];

		$this->debug_log( 'About to validate password "' . $password . '" with type "' . $type . '"' );

		// Debug: Check what password groups are available
		$all_groups = Database::get_password_groups( $type );
		$this->debug_log( 'Available password groups for type "' . $type . '": ' . print_r( $all_groups, true ) );

		// Try to validate with the specified type first
		$password_group = Database::validate_password( $password, $type );

		// If no match found and type is 'content', also try 'general' type
		if ( ! $password_group && $type === 'content' ) {
			$this->debug_log( 'No match for content type, trying general type' );
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
				$this->debug_log( 'Password group not in allowed groups' );
				wp_send_json_error( __( 'Password not authorized for this form', 'password-protect-elite' ) );
			}
		} else {
			$this->debug_log( 'No password group found for password "' . $password . '" and type "' . $type . '"' );
		}

		if ( $password_group ) {
			$this->store_validated_password( $password_group->id );

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
			wp_send_json_error( __( 'Invalid password', 'password-protect-elite' ) );
		}
	}

	/**
	 * Store validated password group in session.
	 *
	 * @param int $group_id Password group ID.
	 */
	public function store_validated_password( $group_id ) {
		if ( ! session_id() ) {
			session_start();
		}

		$validated_groups              = $_SESSION[ self::SESSION_KEY ] ?? array();
		$validated_groups[]            = $group_id;
		$_SESSION[ self::SESSION_KEY ] = array_unique( $validated_groups );
	}

	/**
	 * Check if password group is validated.
	 *
	 * @param int $group_id Password group ID.
	 * @return bool
	 */
	public function is_password_validated( $group_id ) {
		if ( ! session_id() ) {
			session_start();
		}

		$validated_groups = $_SESSION[ self::SESSION_KEY ] ?? array();
		return in_array( $group_id, $validated_groups, true );
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
			<input type="password" name="password" class="ppe-password-input" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" required>
			<button type="submit" class="ppe-submit-button"><?php echo esc_html( $args['button_text'] ); ?></button>
			<div class="ppe-error-message" style="display: none;"></div>
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

}

