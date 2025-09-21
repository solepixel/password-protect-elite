<?php
/**
 * Frontend functionality class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 */
class Frontend {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'check_global_protection' ) );
		add_action( 'template_redirect', array( $this, 'check_auto_protection' ) );
		add_action( 'wp_footer', array( $this, 'add_footer_scripts' ) );
	}

	/**
	 * Check global protection
	 */
	public function check_global_protection() {
		// Get global site password groups.
		$global_groups = Database::get_password_groups( 'global_site' );

		if ( empty( $global_groups ) ) {
			return;
		}

		// Skip protection for admin users.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip protection for AJAX requests.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Skip protection for REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Check if current URL should be excluded.
		if ( UrlMatcher::is_current_url_excluded( $global_groups ) ) {
			return;
		}

		// Check if user has already validated any global password.
		$password_manager = new PasswordManager();
		foreach ( $global_groups as $group ) {
			if ( $password_manager->is_password_validated( $group->id ) ) {
				return; // User has already validated.
			}
		}

		// Show global password form.
		$this->show_global_password_form( $global_groups );
	}

	/**
	 * Check auto-protection for URLs
	 */
	public function check_auto_protection() {
		// Skip for admin users.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip for AJAX requests.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Skip for REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Get all password groups.
		$all_groups = Database::get_password_groups();

		// Check if current URL should be auto-protected.
		$auto_protect_group = UrlMatcher::get_auto_protect_group( $all_groups );

		if ( ! $auto_protect_group ) {
			return;
		}

		// Check if user has already validated this password group.
		$password_manager = new PasswordManager();
		if ( $password_manager->is_password_validated( $auto_protect_group->id ) ) {
			return; // User has already validated.
		}

		// Handle unauthenticated behavior via shared helper.
		AccessController::handle_unauthenticated_behavior(
			$auto_protect_group,
			function () use ( $auto_protect_group ) {
				$this->show_auto_protection_form( $auto_protect_group );
			}
		);
		return;
	}

	/**
	 * Show global password form
	 *
	 * @param array $global_groups Array of global password groups.
	 */
	private function show_global_password_form( $global_groups ) {
		if ( empty( $global_groups ) ) {
			return;
		}

		// Use the first global group for the form.
		$group = $global_groups[0];

		// Set page title.
		add_filter(
			'wp_title',
			function () {
				return __( 'Site Access Required', 'password-protect-elite' );
			}
		);

		// Enqueue necessary scripts.
		wp_enqueue_script( 'ppe-frontend' );
		// Note: Block styles are now handled by the BlockStyles class

		// Get redirect URL from group settings.
		$redirect_url = $this->get_redirect_url( $group );

		// Create password form.
		$password_manager = new PasswordManager();
		$form_args        = array(
			'type'           => 'global_site',
			'allowed_groups' => array( $group->id ),
			'redirect_url'   => $redirect_url,
			'button_text'    => __( 'Access Site', 'password-protect-elite' ),
			'placeholder'    => __( 'Enter site password', 'password-protect-elite' ),
			'class'          => 'ppe-global-protection-form',
		);

		$form_html = $password_manager->get_password_form( $form_args );

		// Output the global password form page.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( __( 'Site Access Required', 'password-protect-elite' ) ); ?></title>
			<?php wp_head(); ?>
		</head>
		<body <?php body_class( 'ppe-global-password-page' ); ?>>
			<div class="ppe-global-password-wrapper">
				<div class="ppe-global-password-content">
					<div class="ppe-global-password-header">
						<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
						<p><?php esc_html_e( 'This site is password protected. Please enter the site password to continue.', 'password-protect-elite' ); ?></p>
					</div>

					<div class="ppe-global-password-form">
						<?php echo $form_html; ?>
					</div>

					<div class="ppe-global-password-footer">
						<p><?php esc_html_e( 'If you have forgotten the password, please contact the site administrator.', 'password-protect-elite' ); ?></p>
					</div>
				</div>
			</div>

			<style>
			.ppe-global-password-wrapper {
				min-height: 100vh;
				display: flex;
				align-items: center;
				justify-content: center;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				padding: 20px;
			}

			.ppe-global-password-content {
				max-width: 450px;
				width: 100%;
				background: white;
				padding: 50px;
				border-radius: 12px;
				box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
				text-align: center;
			}

			.ppe-global-password-header h1 {
				margin: 0 0 15px 0;
				color: #1e1e1e;
				font-size: 28px;
				font-weight: 600;
			}

			.ppe-global-password-header p {
				margin: 0 0 40px 0;
				color: #666;
				line-height: 1.6;
				font-size: 16px;
			}

			.ppe-global-password-form {
				margin-bottom: 30px;
			}

			.ppe-global-password-footer {
				margin-top: 30px;
				padding-top: 20px;
				border-top: 1px solid #eee;
			}

			.ppe-global-password-footer p {
				margin: 0;
				color: #999;
				font-size: 14px;
				line-height: 1.5;
			}

			.ppe-global-password-form .ppe-password-form {
				background: transparent;
				border: none;
				padding: 0;
			}

			.ppe-global-password-form .ppe-password-input {
				border: 2px solid #e1e5e9;
				border-radius: 8px;
				padding: 15px;
				font-size: 16px;
				transition: border-color 0.3s ease;
			}

			.ppe-global-password-form .ppe-password-input:focus {
				border-color: #667eea;
				box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
			}

			.ppe-global-password-form .ppe-submit-button {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border: none;
				border-radius: 8px;
				padding: 15px 30px;
				font-size: 16px;
				font-weight: 600;
				transition: transform 0.2s ease, box-shadow 0.2s ease;
			}

			.ppe-global-password-form .ppe-submit-button:hover {
				transform: translateY(-2px);
				box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
			}

			@media (max-width: 480px) {
				.ppe-global-password-content {
					padding: 40px 30px;
				}

				.ppe-global-password-header h1 {
					font-size: 24px;
				}
			}
			</style>

			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Show auto-protection password form
	 *
	 * @param object $group Password group object.
	 */
	private function show_auto_protection_form( $group ) {
		// Set page title.
		add_filter(
			'wp_title',
			function () use ( $group ) {
				return sprintf( __( 'Password Required - %s', 'password-protect-elite' ), $group->name );
			}
		);

		// Enqueue necessary scripts.
		wp_enqueue_script( 'ppe-frontend' );
		// Note: Block styles are now handled by the BlockStyles class

		// Get redirect URL from group settings.
		$redirect_url = $this->get_redirect_url( $group );

		// Create password form.
		$password_manager = new PasswordManager();
		$form_args        = array(
			'type'           => 'section',
			'allowed_groups' => array( $group->id ),
			'redirect_url'   => $redirect_url,
			'button_text'    => __( 'Access Page', 'password-protect-elite' ),
			'placeholder'    => sprintf( __( 'Enter password for %s', 'password-protect-elite' ), $group->name ),
			'class'          => 'ppe-auto-protection-form',
		);

		$form_html = $password_manager->get_password_form( $form_args );

		// Output the password form page.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( sprintf( __( 'Password Required - %s', 'password-protect-elite' ), $group->name ) ); ?></title>
			<?php wp_head(); ?>
		</head>
		<body <?php body_class( 'ppe-password-page' ); ?>>
			<div class="ppe-password-page-wrapper">
				<div class="ppe-password-page-content">
					<div class="ppe-password-page-header">
						<h1><?php echo esc_html( __( 'Password Required', 'password-protect-elite' ) ); ?></h1>
						<p><?php echo esc_html( sprintf( __( 'This page is protected. Please enter the password for "%s" to continue.', 'password-protect-elite' ), $group->name ) ); ?></p>
					</div>

					<div class="ppe-password-page-form">
						<?php echo $form_html; ?>
					</div>

					<?php if ( ! empty( $group->description ) ) : ?>
						<div class="ppe-password-page-description">
							<p><?php echo esc_html( $group->description ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<style>
			.ppe-password-page-wrapper {
				min-height: 100vh;
				display: flex;
				align-items: center;
				justify-content: center;
				background: #f9f9f9;
				padding: 20px;
			}

			.ppe-password-page-content {
				max-width: 400px;
				width: 100%;
				background: white;
				padding: 40px;
				border-radius: 8px;
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
				text-align: center;
			}

			.ppe-password-page-header h1 {
				margin: 0 0 10px 0;
				color: #1e1e1e;
				font-size: 24px;
			}

			.ppe-password-page-header p {
				margin: 0 0 30px 0;
				color: #666;
				line-height: 1.5;
			}

			.ppe-password-page-form {
				margin-bottom: 20px;
			}

			.ppe-password-page-description {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #eee;
			}

			.ppe-password-page-description p {
				margin: 0;
				color: #666;
				font-size: 14px;
				font-style: italic;
			}

			@media (max-width: 480px) {
				.ppe-password-page-content {
					padding: 30px 20px;
				}
			}
			</style>

			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Get redirect URL for a password group
	 *
	 * @param object $group Password group object.
	 * @return string Redirect URL.
	 */
	private function get_redirect_url( $group ) {
		if ( 'page' === $group->redirect_type && $group->redirect_page_id ) {
			return get_permalink( $group->redirect_page_id );
		} elseif ( 'custom_url' === $group->redirect_type && $group->redirect_custom_url ) {
			return $group->redirect_custom_url;
		}

		return home_url( $_SERVER['REQUEST_URI'] ?? '/' );
	}

	/**
	 * Add footer scripts
	 */
	public function add_footer_scripts() {
		// Add any additional frontend scripts here.
		?>
		<script>
		// Additional frontend functionality can be added here.
		document.addEventListener('DOMContentLoaded', function() {
			// Handle any additional frontend interactions.
		});
		</script>
		<?php
	}
}
