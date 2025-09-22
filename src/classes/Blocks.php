<?php
/**
 * Gutenberg blocks class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks class.
 */
class Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register blocks
	 */
	public function register_blocks() {
		// Register scripts as modules first.
		$this->register_block_scripts();

		// Register password entry block.
		register_block_type(
			PPE_PLUGIN_PATH . 'src/blocks/password-entry/block.json',
			array(
				'render_callback' => array( $this, 'render_password_entry_block' ),
			)
		);

		// Register password protected content block.
		register_block_type(
			PPE_PLUGIN_PATH . 'src/blocks/protected-content/block.json',
			array(
				'render_callback' => array( $this, 'render_protected_content_block' ),
			)
		);
	}

	/**
	 * Register block scripts
	 */
	private function register_block_scripts() {
		// Register password entry block script.
		wp_register_script(
			'ppe-password-entry-block',
			PPE_PLUGIN_URL . 'build/password-entry.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ),
			PPE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Register protected content block script.
		wp_register_script(
			'ppe-protected-content-block',
			PPE_PLUGIN_URL . 'build/protected-content.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ),
			PPE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue password entry block (already registered as module).
		wp_enqueue_script( 'ppe-password-entry-block' );

		// Enqueue protected content block (already registered as module).
		wp_enqueue_script( 'ppe-protected-content-block' );

		// Get password groups for localization.
		$password_groups = Database::get_password_groups();
		$groups_data     = array();

		foreach ( $password_groups as $group ) {
			$groups_data[] = array(
				'id'   => $group->id,
				'name' => $group->name,
				'type' => $group->protection_type,
			);
		}


		// Get global strings for localization.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$global_strings = $string_manager->get_all_strings();

		// Localize scripts with password groups for the block editor.
		$localization_data = array(
			'passwordGroups' => $groups_data,
			'globalStrings' => $global_strings,
			'strings'        => array(
				'passwordEntry'        => __( 'Password Entry', 'password-protect-elite' ),
				'protectedContent'     => __( 'Protected Content', 'password-protect-elite' ),
				'selectPasswordGroups' => __( 'Select Password Groups', 'password-protect-elite' ),
				'buttonText'           => __( 'Button Text', 'password-protect-elite' ),
				'placeholder'          => __( 'Placeholder Text', 'password-protect-elite' ),
				'redirectUrl'          => __( 'Redirect URL', 'password-protect-elite' ),
				'fallbackMessage'      => __( 'Fallback Message', 'password-protect-elite' ),
				'noPasswordGroups'     => __( 'No password groups available. Create some in the plugin settings.', 'password-protect-elite' ),
			),
		);

		wp_localize_script( 'ppe-password-entry-block', 'ppeBlocks', $localization_data );
		wp_localize_script( 'ppe-protected-content-block', 'ppeBlocks', $localization_data );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_script(
			'ppe-frontend',
			PPE_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			PPE_VERSION,
			true
		);

		// Note: Block styles are now handled by the BlockStyles class
		// based on the user's settings preference

		// Get debug mode setting
		$settings = get_option( 'ppe_settings', array() );
		$debug_mode = isset( $settings['debug_mode'] ) && $settings['debug_mode'];

		wp_localize_script(
			'ppe-frontend',
			'ppeFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ppe_validate_password' ),
				'debugMode' => $debug_mode,
				'strings' => array(
					'passwordRequired' => __( 'Password is required', 'password-protect-elite' ),
					'invalidPassword'  => __( 'Invalid password', 'password-protect-elite' ),
					'validating'       => __( 'Validating...', 'password-protect-elite' ),
					'error'            => __( 'An error occurred. Please try again.', 'password-protect-elite' ),
				),
			)
		);
	}

	/**
	 * Render password entry block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content (unused for this block).
	 * @return string
	 */
	public function render_password_entry_block( $attributes, $content ) {
		unset( $content );
		$allowed_groups = $attributes['allowedGroups'] ?? array();

		// Get global strings for defaults.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$button_text    = $attributes['buttonText'] ?? $string_manager->get_string( 'default_button_text' );
		$placeholder    = $attributes['placeholder'] ?? $string_manager->get_string( 'default_placeholder' );
		$redirect_url   = $attributes['redirectUrl'] ?? '';
		$class_name     = $attributes['className'] ?? '';

		// If no groups are selected, allow all content-type and general groups.
		if ( empty( $allowed_groups ) ) {
			$content_groups = Database::get_password_groups( 'content' );
			$general_groups = Database::get_password_groups( 'general' );
			$all_groups     = array_merge( $content_groups, $general_groups );
			$allowed_groups = wp_list_pluck( $all_groups, 'id' );
		}

		$password_manager = new PasswordManager();

		// Determine if user has access via password validation or role-based bypass.
		$authenticated_group = self::get_authenticated_group_id( $allowed_groups );
		$is_authenticated   = ( $authenticated_group > 0 );

		// If user is already authenticated, show message instead of form.
		if ( $is_authenticated ) {
			$authenticated_message = $string_manager->get_string( 'already_authenticated_message' );
			if ( empty( $authenticated_message ) ) {
				$authenticated_message = __( 'You have already authenticated for this content.', 'password-protect-elite' );
			}

			// Get redirect URL from block settings or password group.
			$final_redirect_url = $redirect_url;
			if ( empty( $final_redirect_url ) && $authenticated_group ) {
				$password_group = Database::get_password_group( $authenticated_group );
				if ( $password_group ) {
					$final_redirect_url = $password_manager->get_redirect_url( $password_group );
				}
			}

			$output = '<div class="ppe-password-entry-block ppe-authenticated ' . esc_attr( $class_name ) . '">';
			$output .= '<div class="ppe-authenticated-message">';
			$output .= '<p>' . esc_html( $authenticated_message ) . '</p>';

			// Add redirect link if redirect URL is available.
			if ( ! empty( $final_redirect_url ) ) {
				$link_text = $string_manager->get_string( 'continue_to_protected_content' );
				if ( empty( $link_text ) ) {
					$link_text = __( 'Continue to protected content', 'password-protect-elite' );
				}
				$output .= '<p><a href="' . esc_url( $final_redirect_url ) . '" class="ppe-redirect-link">' . esc_html( $link_text ) . '</a></p>';
			}

			$output .= '</div>';
			$output .= '</div>';

			return $output;
		}

		// User is not authenticated, show the password form.
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => $redirect_url,
			'button_text'    => $button_text,
			'placeholder'    => $placeholder,
			'class'          => 'ppe-password-form ' . $class_name,
		);

		$form_html = $password_manager->get_password_form( $form_args );

		// If redirected due to authentication requirement, and this form supports the same group,
		// populate the existing error message element so it inherits the same styling.
		if ( isset( $_GET['ppe_auth_required'] ) && '1' === $_GET['ppe_auth_required'] ) {
			$redirect_group = isset( $_GET['ppe_group'] ) ? absint( $_GET['ppe_group'] ) : 0;
			if ( $redirect_group && ( empty( $allowed_groups ) || in_array( $redirect_group, $allowed_groups, true ) ) ) {
				$auth_required_message = $string_manager->get_string( 'auth_required_message' );
				$replacement = '<div class="ppe-error-message" style="display: block;">' . esc_html( $auth_required_message ) . '</div>';
				$form_html  = str_replace( '<div class="ppe-error-message" style="display: none;"></div>', $replacement, $form_html );
			}
		}

		return '<div class="ppe-password-entry-block">' . $form_html . '</div>';
	}

	/**
	 * Render protected content block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string
	 */
	public function render_protected_content_block( $attributes, $content ) {
		$allowed_groups       = $attributes['allowedGroups'] ?? array();
		$access_mode          = $attributes['accessMode'] ?? 'groups';
		$allowed_roles        = $attributes['allowedRoles'] ?? array();
		$allowed_capabilities = $attributes['allowedCapabilities'] ?? array();
		$disable_form         = ! empty( $attributes['disableForm'] );

		// Get global strings for defaults.
		$string_manager   = new \PasswordProtectElite\Admin\StringManager();
		$fallback_message = $attributes['fallbackMessage'] ?? $string_manager->get_string( 'default_fallback_message' );
		$class_name       = $attributes['className'] ?? '';
		$align            = $attributes['align'] ?? '';

		$password_manager = new PasswordManager();

		// Access Mode: roles -> show content only for matching roles, else empty.
		if ( 'roles' === $access_mode ) {
			if ( is_user_logged_in() ) {
				$user  = wp_get_current_user();
				$roles = is_array( $allowed_roles ) ? $allowed_roles : array();
				if ( $user && ! empty( $user->roles ) && ! empty( $roles ) ) {
					foreach ( (array) $user->roles as $role_slug ) {
						if ( in_array( $role_slug, $roles, true ) ) {
							return $content;
						}
					}
				}
			}
			return '';
		}

		// Access Mode: capabilities -> show content only if user has any capability, else empty.
		if ( 'caps' === $access_mode ) {
			$caps = is_array( $allowed_capabilities ) ? $allowed_capabilities : array();
			if ( ! empty( $caps ) ) {
				foreach ( $caps as $cap ) {
					if ( current_user_can( sanitize_key( $cap ) ) ) {
						return $content;
					}
				}
			}
			return '';
		}

		// Access Mode: groups (default). Use password validation or role-based bypass per group.
		if ( self::get_authenticated_group_id( $allowed_groups ) > 0 ) {
			return $content;
		}

		// Show password form only if specific groups are selected and form is not disabled; otherwise empty.
		if ( empty( $allowed_groups ) || $disable_form ) {
			return '';
		}

		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => '',
			'button_text'    => $string_manager->get_string( 'default_button_text' ),
			'placeholder'    => $string_manager->get_string( 'default_placeholder' ),
			'class'          => 'ppe-password-form ppe-protected-content-form',
		);

		$form_html = $password_manager->get_password_form( $form_args );

		// Build CSS classes including alignment.
		$wrapper_classes = array( 'ppe-protected-content-block', 'ppe-locked' );
		if ( ! empty( $class_name ) ) {
			$wrapper_classes[] = $class_name;
		}
		if ( ! empty( $align ) ) {
			$wrapper_classes[] = 'align' . $align;
		}
		$wrapper_class_string = implode( ' ', array_filter( $wrapper_classes ) );

		return '<div class="' . esc_attr( $wrapper_class_string ) . '">
			<div class="ppe-protected-message">' . esc_html( $fallback_message ) . '</div>
			' . $form_html . '
		</div>';
	}

	/**
	 * Get the first allowed group ID for which the current user is considered authenticated
	 * either via a validated password or via a role-based bypass. Returns 0 if none.
	 *
	 * @param array $allowed_groups Allowed password group IDs.
	 * @return int Group ID if authenticated; 0 otherwise.
	 */
	public static function get_authenticated_group_id( $allowed_groups ) {
		$manager = new PasswordManager();
		if ( empty( $allowed_groups ) ) {
			return 0;
		}

		// First, check password validation as before.
		foreach ( $allowed_groups as $group_id ) {
			if ( $manager->is_password_validated( $group_id ) ) {
				return $group_id;
			}
		}

		// Next, check role-based bypass if logged in.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && ! empty( $user->roles ) ) {
				foreach ( $allowed_groups as $group_id ) {
					$group = Database::get_password_group( $group_id );
					if ( $group && ! empty( $group->allowed_roles ) ) {
						foreach ( (array) $user->roles as $role_slug ) {
							if ( in_array( $role_slug, (array) $group->allowed_roles, true ) ) {
								return $group_id;
							}
						}
					}
				}
			}
		}

		return 0;
	}
}
