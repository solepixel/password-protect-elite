<?php
/**
 * Gutenberg blocks class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
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
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
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
			[
				'render_callback' => [ $this, 'render_password_entry_block' ],
			]
		);

		// Register password protected content block.
		register_block_type(
			PPE_PLUGIN_PATH . 'src/blocks/protected-content/block.json',
			[
				'render_callback' => [ $this, 'render_protected_content_block' ],
			]
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
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ],
			PPE_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		// Register protected content block script.
		wp_register_script(
			'ppe-protected-content-block',
			PPE_PLUGIN_URL . 'build/protected-content.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'react', 'react-dom' ],
			PPE_VERSION,
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
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
		$groups_data     = [];

		foreach ( $password_groups as $group ) {
			$groups_data[] = [
				'id'   => $group->id,
				'name' => $group->name,
				'type' => $group->protection_type,
			];
		}

		// Get global strings for localization.
		$string_manager = new \PasswordProtectElite\Admin\StringManager();
		$global_strings = $string_manager->get_all_strings();

		// Localize scripts with password groups for the block editor.
		$localization_data = [
			'passwordGroups' => $groups_data,
			'globalStrings'  => $global_strings,
			'strings'        => [
				'passwordEntry'        => __( 'Password Entry', 'password-protect-elite' ),
				'protectedContent'     => __( 'Protected Content', 'password-protect-elite' ),
				'selectPasswordGroups' => __( 'Select Password Groups', 'password-protect-elite' ),
				'buttonText'           => __( 'Button Text', 'password-protect-elite' ),
				'placeholder'          => __( 'Placeholder Text', 'password-protect-elite' ),
				'redirectUrl'          => __( 'Redirect URL', 'password-protect-elite' ),
				'fallbackMessage'      => __( 'Fallback Message', 'password-protect-elite' ),
				'noPasswordGroups'     => __( 'No password groups available. Create some in the plugin settings.', 'password-protect-elite' ),
			],
		];

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
			[ 'jquery' ],
			PPE_VERSION,
			true
		);

		// Get debug mode setting.
		$settings   = get_option( 'ppe_settings', [] );
		$debug_mode = isset( $settings['debug_mode'] ) && $settings['debug_mode'];

		wp_localize_script(
			'ppe-frontend',
			'ppeFrontend',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ppe_validate_password' ),
				'debugMode' => $debug_mode,
				'strings'   => [
					'passwordRequired' => __( 'Password is required', 'password-protect-elite' ),
					'invalidPassword'  => __( 'Invalid password', 'password-protect-elite' ),
					'validating'       => __( 'Validating...', 'password-protect-elite' ),
					'error'            => __( 'An error occurred. Please try again.', 'password-protect-elite' ),
				],
			]
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
		$allowed_groups = $attributes['allowedGroups'] ?? [];

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
			$all_groups     = \array_merge( $content_groups, $general_groups );
			$allowed_groups = wp_list_pluck( $all_groups, 'id' );
		}

		$password_manager = new PasswordManager();

		// Determine if user has access via password validation or role-based bypass.
		$authenticated_group = self::get_authenticated_group_id( $allowed_groups );
		$is_authenticated    = $authenticated_group > 0;

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

			$output  = '<div class="ppe-password-entry-block ppe-authenticated ' . esc_attr( $class_name ) . '">';
			$output .= '<div class="ppe-authenticated-message">';
			$output .= \sprintf(
				'<p>%s</p>',
				esc_html( $authenticated_message )
			);

			// Add redirect link if redirect URL is available.
			if ( ! empty( $final_redirect_url ) ) {
				$link_text = $string_manager->get_string( 'continue_to_protected_content' );
				if ( empty( $link_text ) ) {
					$link_text = __( 'Continue to protected content', 'password-protect-elite' );
				}
				$output .= \sprintf(
					'<p><a href="%s" class="ppe-redirect-link">%s</a></p>',
					esc_url( $final_redirect_url ),
					esc_html( $link_text )
				);
			}

			// Add logout link - use WordPress logout URL which will also clear password sessions.
			$logout_text = $string_manager->get_string( 'logout_link_text' );
			if ( empty( $logout_text ) ) {
				$logout_text = __( 'Log Out', 'password-protect-elite' );
			}

			// Determine redirect URL after logout based on password group settings.
			$redirect_after_logout = $this->get_logout_redirect_url( $authenticated_group );
			$logout_url            = wp_logout_url( $redirect_after_logout );

			$output .= \sprintf(
				'<p><a href="%s" class="ppe-logout-link">%s</a></p>',
				esc_url( $logout_url ),
				esc_html( $logout_text )
			);

			$output .= '</div>';
			$output .= '</div>';

			return $output;
		}

		// User is not authenticated, show the password form.
		$form_args = [
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => $redirect_url,
			'button_text'    => $button_text,
			'placeholder'    => $placeholder,
			'class'          => 'ppe-password-form ' . $class_name,
		];

		$form_html = $password_manager->get_password_form( $form_args );

		// If redirected due to authentication requirement, and this form supports the same group,
		// populate the existing error message element so it inherits the same styling.
		if ( isset( $_GET['ppe_auth_required'] ) && '1' === $_GET['ppe_auth_required'] ) {
			$redirect_group = isset( $_GET['ppe_group'] ) ? absint( $_GET['ppe_group'] ) : 0;
			if ( $redirect_group && ( empty( $allowed_groups ) || in_array( $redirect_group, $allowed_groups, true ) ) ) {
				$auth_required_message = $string_manager->get_string( 'auth_required_message' );
				$replacement           = '<div class="ppe-error-message" style="display: block;">' . esc_html( $auth_required_message ) . '</div>';
				$form_html             = str_replace( '<div class="ppe-error-message" style="display: none;"></div>', $replacement, $form_html );
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
		$allowed_groups       = $attributes['allowedGroups'] ?? [];
		$access_mode          = $attributes['accessMode'] ?? 'groups';
		$allowed_roles        = $attributes['allowedRoles'] ?? [];
		$allowed_capabilities = $attributes['allowedCapabilities'] ?? [];
		$disable_form         = ! empty( $attributes['disableForm'] );
		$render_dynamically   = ! empty( $attributes['renderDynamically'] );

		// Get global strings for defaults.
		$string_manager   = new \PasswordProtectElite\Admin\StringManager();
		$fallback_message = $attributes['fallbackMessage'] ?? $string_manager->get_string( 'default_fallback_message' );
		$class_name       = $attributes['className'] ?? '';
		$align            = $attributes['align'] ?? '';

		// Check dynamic rendering: if enabled and access mode is groups, only render if URL matches.
		if ( $render_dynamically && 'groups' === $access_mode ) {
			if ( ! UrlMatcher::should_render_dynamically( $allowed_groups ) ) {
				// URL doesn't match Auto-Protect URLs or is excluded, don't render content.
				return '';
			}
		}

		// Access Mode: roles -> show content only for matching roles, else empty.
		if ( 'roles' === $access_mode ) {
			if ( is_user_logged_in() ) {
				$user  = wp_get_current_user();
				$roles = \is_array( $allowed_roles ) ? $allowed_roles : [];
				if ( $user && ! empty( $user->roles ) && ! empty( $roles ) ) {
					foreach ( (array) $user->roles as $role_slug ) {
						if ( \in_array( $role_slug, $roles, true ) ) {
							return $content;
						}
					}
				}
			}
			return '';
		}

		// Access Mode: capabilities -> show content only if user has any capability, else empty.
		if ( 'caps' === $access_mode ) {
			$caps = is_array( $allowed_capabilities ) ? $allowed_capabilities : [];
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

		$form_args = [
			'type'           => 'content',
			'allowed_groups' => $allowed_groups,
			'redirect_url'   => '',
			'button_text'    => $string_manager->get_string( 'default_button_text' ),
			'placeholder'    => $string_manager->get_string( 'default_placeholder' ),
			'class'          => 'ppe-password-form ppe-protected-content-form',
		];

		$password_manager = new PasswordManager();
		$form_html        = $password_manager->get_password_form( $form_args );

		// Build CSS classes including alignment.
		$wrapper_classes = [ 'ppe-protected-content-block', 'ppe-locked' ];
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
							if ( \in_array( $role_slug, (array) $group->allowed_roles, true ) ) {
								return $group_id;
							}
						}
					}
				}
			}
		}

		return 0;
	}

	/**
	 * Get logout redirect URL based on password group settings.
	 *
	 * @param int $group_id Password group ID.
	 * @return string Redirect URL after logout.
	 */
	private function get_logout_redirect_url( $group_id ) {
		// Default: stay on current page.
		$protocol    = is_ssl() ? 'https://' : 'http://';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$current_url = $protocol . $host . $request_uri;

		if ( ! $group_id ) {
			return $current_url;
		}

		// Get group logout redirect settings.
		$logout_redirect_type = get_post_meta( $group_id, '_ppe_logout_redirect_type', true );

		if ( 'page' === $logout_redirect_type ) {
			$page_id = absint( get_post_meta( $group_id, '_ppe_logout_redirect_page_id', true ) );
			if ( $page_id ) {
				$page_url = get_permalink( $page_id );
				if ( $page_url ) {
					return $page_url;
				}
			}
		} elseif ( 'custom_url' === $logout_redirect_type ) {
			$custom_url = get_post_meta( $group_id, '_ppe_logout_redirect_custom_url', true );
			if ( ! empty( $custom_url ) ) {
				return esc_url_raw( $custom_url );
			}
		}

		// Default: same page.
		return $current_url;
	}
}
