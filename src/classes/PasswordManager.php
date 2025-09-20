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
		$type         = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		$redirect_url = sanitize_url( wp_unslash( $_POST['redirect_url'] ?? '' ) );

		if ( empty( $password ) ) {
			wp_send_json_error( __( 'Password is required', 'password-protect-elite' ) );
		}

		$password_group = Database::validate_password( $password, $type );

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

		ob_start();
		?>
		<form class="<?php echo esc_attr( $args['class'] ); ?>" data-type="<?php echo esc_attr( $args['type'] ); ?>" data-allowed-groups="<?php echo esc_attr( wp_json_encode( $args['allowed_groups'] ) ); ?>" data-redirect-url="<?php echo esc_attr( $args['redirect_url'] ); ?>">
			<?php wp_nonce_field( 'ppe_validate_password', 'ppe_nonce' ); ?>
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
