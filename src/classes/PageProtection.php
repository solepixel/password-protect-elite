<?php
/**
 * Page protection class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PageProtection class.
 */
class PageProtection {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post_meta' ] );
		add_action( 'template_redirect', [ $this, 'check_page_protection' ] );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ppe-page-protection',
				__( 'Password Protect', 'password-protect-elite' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box
	 *
	 * @param \WP_Post $post Current post object
	 */
	public function render_meta_box( $post ) {
		// Add nonce field.
		wp_nonce_field( 'ppe_page_protection', 'ppe_page_protection_nonce' );

		// Get current protection.
		$current_protection = Database::get_page_protection( $post->ID );
		$current_group_id   = $current_protection ? $current_protection->password_group_id : 0;

		// New: page-level access mode and settings.
		$access_mode  = get_post_meta( $post->ID, '_ppe_access_mode', true );
		$access_roles = get_post_meta( $post->ID, '_ppe_access_roles', true );
		$access_caps  = get_post_meta( $post->ID, '_ppe_access_caps', true );
		if ( empty( $access_mode ) ) {
			$access_mode = 'groups';
		}
		if ( empty( $access_roles ) || ! \is_array( $access_roles ) ) {
			$access_roles = [];
		}
		if ( empty( $access_caps ) ) {
			$access_caps = '';
		}

		// Determine if this page should be considered protected via auto-protect rules for its FRONT-END permalink,
		// respecting each candidate group's own Exclude URLs.
		$auto_protect_group = null;
		if ( ! $current_protection ) {
			$all_groups = Database::get_password_groups();
			$page_url   = get_permalink( $post->ID );
			foreach ( $all_groups as $group ) {
				$auto_patterns = get_post_meta( $group->id, '_ppe_auto_protect_urls', true );
				if ( empty( $auto_patterns ) ) {
					continue;
				}
				if ( UrlMatcher::url_matches_patterns( $page_url, $auto_patterns ) ) {
					$group_exclude_urls = get_post_meta( $group->id, '_ppe_exclude_urls', true );
					if ( ! empty( $group_exclude_urls ) && UrlMatcher::url_matches_patterns( $page_url, $group_exclude_urls ) ) {
						continue; // Excluded for this group.
					}
					$auto_protect_group = $group;
					break;
				}
			}
		}

		// Get available password groups (both section and general types).
		$section_groups  = Database::get_password_groups( 'section' );
		$general_groups  = Database::get_password_groups( 'general' );
		$password_groups = array_merge( $section_groups, $general_groups );

		?>
		<div class="ppe-page-protection-meta">
			<p>
				<label for="ppe-protection-group">
					<strong><?php esc_html_e( 'Protect this page with:', 'password-protect-elite' ); ?></strong>
				</label>
			</p>

			<p>
				<label for="ppe-access-mode"><strong><?php esc_html_e( 'Access Mode', 'password-protect-elite' ); ?></strong></label>
				<select id="ppe-access-mode" name="ppe_access_mode" class="ppe-protection-select">
					<option value="groups" <?php selected( $access_mode, 'groups' ); ?>><?php esc_html_e( 'Password Groups', 'password-protect-elite' ); ?></option>
					<option value="roles" <?php selected( $access_mode, 'roles' ); ?>><?php esc_html_e( 'Role-based Access', 'password-protect-elite' ); ?></option>
					<option value="caps" <?php selected( $access_mode, 'caps' ); ?>><?php esc_html_e( 'Capability-based Access', 'password-protect-elite' ); ?></option>
				</select>
			</p>

			<div class="ppe-access-groups-container" style="<?php echo ( 'groups' === $access_mode ) ? '' : 'display:none;'; ?>">
				<select id="ppe-protection-group" name="ppe_protection_group" class="ppe-protection-select">
					<?php
						$empty_option_label = ( ! $current_protection && $auto_protect_group )
							? __( 'Auto Protection', 'password-protect-elite' )
							: __( 'No protection', 'password-protect-elite' );
					?>
					<option value="" <?php selected( $current_group_id, 0 ); ?>><?php echo esc_html( $empty_option_label ); ?></option>
					<?php foreach ( $password_groups as $group ) : ?>
						<option value="<?php echo esc_attr( $group->id ); ?>" <?php selected( $current_group_id, $group->id ); ?>>
							<?php echo esc_html( $group->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ppe-access-roles-container" style="<?php echo ( 'roles' === $access_mode ) ? '' : 'display:none;'; ?>">
				<p><strong><?php esc_html_e( 'Allowed Roles', 'password-protect-elite' ); ?></strong></p>
				<?php
				$roles = function_exists( 'get_editable_roles' ) ? get_editable_roles() : [];
				if ( ! empty( $roles ) ) :
					?>
					<div class="ppe-roles-checkboxes">
						<?php foreach ( $roles as $role_slug => $role_info ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" name="ppe_access_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $access_roles, true ) ); ?>>
								<?php echo esc_html( translate_user_role( $role_info['name'] ) ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<em><?php esc_html_e( 'No editable roles found.', 'password-protect-elite' ); ?></em>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Users with any selected role can view this page without entering a password.', 'password-protect-elite' ); ?></p>
			</div>

			<div class="ppe-access-caps-container" style="<?php echo ( 'caps' === $access_mode ) ? '' : 'display:none;'; ?>">
				<p><strong><?php esc_html_e( 'Allowed Capabilities', 'password-protect-elite' ); ?></strong></p>
				<input type="text" name="ppe_access_caps" value="<?php echo esc_attr( is_array( $access_caps ) ? implode( ',', $access_caps ) : $access_caps ); ?>" class="widefat" placeholder="read, edit_posts">
				<p class="description"><?php esc_html_e( 'Comma-separated capability slugs. Users must have at least one to view this page.', 'password-protect-elite' ); ?></p>
			</div>

			<?php if ( empty( $password_groups ) ) : ?>
				<p class="ppe-no-groups-notice">
					<em>
						<?php
						\printf(
							'%s <a href="%s" target="_blank">%s</a> %s.',
							esc_html__( 'No password groups available.', 'password-protect-elite' ),
							esc_attr( admin_url( 'edit.php?post_type=ppe_password_group' ) ),
							esc_html__( 'Create some password groups', 'password-protect-elite' ),
							esc_html__( 'to protect pages.', 'password-protect-elite' )
						);
						?>
					</em>
				</p>
			<?php endif; ?>

			<?php if ( $current_protection || $auto_protect_group ) : ?>
				<div class="ppe-current-protection">
					<p>
						<strong><?php esc_html_e( 'Current protection:', 'password-protect-elite' ); ?></strong><br>
						<?php echo esc_html( $current_protection ? $current_protection->group_name : $auto_protect_group->name ); ?>
					</p>
					<?php if ( ! $current_protection && $auto_protect_group ) : ?>
						<p><em><?php esc_html_e( 'This page is auto-protected via URL rules.', 'password-protect-elite' ); ?></em></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<p class="ppe-help-text">
				<?php esc_html_e( 'Select a password group to protect this entire page. Users will need to enter the correct password before viewing the page content.', 'password-protect-elite' ); ?>
			</p>
		</div>

		<style>
		.ppe-page-protection-meta {
			font-size: 13px;
		}

		.ppe-page-protection-meta select {
			margin-bottom: 10px;
			max-width: 100%;
			width: 100%;
		}

		.ppe-protection-select {
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box;
		}

		.ppe-no-groups-notice {
			color: #d63638;
			font-style: italic;
			margin-top: 5px;
		}

		.ppe-no-groups-notice a {
			color: #0073aa;
			text-decoration: none;
		}

		.ppe-no-groups-notice a:hover {
			color: #005177;
			text-decoration: underline;
		}

		.ppe-current-protection {
			background: #f0f6fc;
			border: 1px solid #72aee6;
			border-radius: 4px;
			padding: 10px;
			margin: 10px 0;
		}

		.ppe-current-protection p {
			margin: 0 0 5px 0;
		}

		.ppe-current-protection p:last-child {
			margin-bottom: 0;
		}

		.ppe-help-text {
			color: #666;
			font-style: italic;
			margin-top: 10px;
		}
		</style>
		<script>
		(function(){
			const mode = document.getElementById('ppe-access-mode');
			if(!mode){return;}
			function sync(){
				const v = mode.value || 'groups';
				document.querySelector('.ppe-access-groups-container').style.display = (v==='groups') ? '' : 'none';
				document.querySelector('.ppe-access-roles-container').style.display  = (v==='roles') ? '' : 'none';
				document.querySelector('.ppe-access-caps-container').style.display   = (v==='caps') ? '' : 'none';
			}
			mode.addEventListener('change', sync);
			sync();
		})();
		</script>
		<?php
	}

	/**
	 * Save post meta
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post_meta( $post_id ) {
		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if this is a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_POST['ppe_page_protection_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['ppe_page_protection_nonce'] ), 'ppe_page_protection' ) ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save access mode and related fields.
		$access_mode = isset( $_POST['ppe_access_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['ppe_access_mode'] ) ) : 'groups';
		update_post_meta( $post_id, '_ppe_access_mode', $access_mode );

		if ( 'roles' === $access_mode ) {
			$roles = isset( $_POST['ppe_access_roles'] ) && is_array( $_POST['ppe_access_roles'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['ppe_access_roles'] ) ) : [];
			update_post_meta( $post_id, '_ppe_access_roles', array_values( array_unique( array_filter( $roles ) ) ) );
			// Remove group protection when using roles mode.
			Database::remove_page_protection( $post_id );
			delete_post_meta( $post_id, '_ppe_access_caps' );
		} elseif ( 'caps' === $access_mode ) {
			$caps_raw = isset( $_POST['ppe_access_caps'] ) ? (string) wp_unslash( $_POST['ppe_access_caps'] ) : '';
			$caps     = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $caps_raw ) ) ) );
			update_post_meta( $post_id, '_ppe_access_caps', array_values( array_unique( $caps ) ) );
			// Remove group protection when using caps mode.
			Database::remove_page_protection( $post_id );
			delete_post_meta( $post_id, '_ppe_access_roles' );
		} else {
			// Groups mode.
			delete_post_meta( $post_id, '_ppe_access_roles' );
			delete_post_meta( $post_id, '_ppe_access_caps' );
			// Save selected password group.
			$protection_group = intval( $_POST['ppe_protection_group'] ?? 0 );
			if ( $protection_group > 0 ) {
				Database::set_page_protection( $post_id, $protection_group );
			} else {
				Database::remove_page_protection( $post_id );
			}
		}
	}

	/**
	 * Check page protection
	 */
	public function check_page_protection() {
		// Only check on singular pages.
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		// First evaluate page-level access mode for roles/caps.
		$access_mode = get_post_meta( $post->ID, '_ppe_access_mode', true );
		if ( 'roles' === $access_mode ) {
			$roles   = get_post_meta( $post->ID, '_ppe_access_roles', true );
			$roles   = \is_array( $roles ) ? $roles : [];
			$allowed = false;
			if ( is_user_logged_in() && ! empty( $roles ) ) {
				$user = wp_get_current_user();
				if ( $user && ! empty( $user->roles ) ) {
					foreach ( (array) $user->roles as $role_slug ) {
						if ( in_array( $role_slug, $roles, true ) ) {
							$allowed = true;
							break;
						}
					}
				}
			}
			if ( $allowed ) {
				return;
			}
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}
		if ( 'caps' === $access_mode ) {
			$caps    = get_post_meta( $post->ID, '_ppe_access_caps', true );
			$caps    = \is_array( $caps ) ? $caps : [];
			$allowed = false;
			if ( ! empty( $caps ) ) {
				foreach ( $caps as $cap ) {
					if ( current_user_can( $cap ) ) {
						$allowed = true;
						break;
					}
				}
			}
			if ( $allowed ) {
				return;
			}
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		// Fallback to password group protection.
		$page_protection = Database::get_page_protection( $post->ID );
		if ( ! $page_protection ) {
			return;
		}

		// Check if user already has access via password or role.
		$password_manager = new PasswordManager();
		if ( $password_manager->has_access_to_group( $page_protection->password_group_id ) ) {
			return; // User already has access.
		}

		// Use shared helper for unauthenticated handling.
		$password_group = Database::get_password_group( $page_protection->password_group_id );
		AccessController::handle_unauthenticated_behavior(
			$password_group,
			function () use ( $page_protection ) {
				$this->show_password_form( $page_protection );
			}
		);
		return;
	}

	/**
	 * Show password form for page protection
	 *
	 * @param object $page_protection Page protection data.
	 */
	private function show_password_form( $page_protection ) {
		// Get the password group.
		$password_group = Database::get_password_group( $page_protection->password_group_id );

		if ( ! $password_group ) {
			return;
		}

		// Set page title.
		add_filter(
			'wp_title',
			function () use ( $password_group ) {
				return \sprintf(
					'%s - %s',
					esc_html__( 'Password Required', 'password-protect-elite' ),
					esc_html( $password_group->name )
				);
			}
		);

		// Enqueue necessary scripts.
		wp_enqueue_script( 'ppe-frontend' );

		// Get redirect URL.
		$redirect_url = ! empty( $password_group->redirect_url ) ? $password_group->redirect_url : get_permalink();

		// Create password form.
		$password_manager = new PasswordManager();
		$form_args        = [
			'type'           => 'section',
			'allowed_groups' => [ $password_group->id ],
			'redirect_url'   => $redirect_url,
			'button_text'    => __( 'Access Page', 'password-protect-elite' ),
			'placeholder'    => \sprintf(
				'%s %s',
				__( 'Enter password for', 'password-protect-elite' ),
				$password_group->name
			),
			'class'          => 'ppe-page-protection-form',
		];

		$form_html = $password_manager->get_password_form( $form_args );

		// Output the password form page.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
			printf(
				'<title>%s - %s</title>',
				esc_html__( 'Password Required', 'password-protect-elite' ),
				esc_html( $password_group->name )
			);
			?>
			<?php wp_head(); ?>
		</head>
		<body <?php body_class( 'ppe-password-page' ); ?>>
			<div class="ppe-password-page-wrapper">
				<div class="ppe-password-page-content">
					<div class="ppe-password-page-header">
						<h1><?php echo esc_html( sprintf( __( 'Password Required', 'password-protect-elite' ) ) ); ?></h1>
						<?php
						printf(
							'<p><%s "%s" %s.</p>',
							esc_html__( 'This page is protected. Please enter the password for', 'password-protect-elite' ),
							esc_html( $password_group->name )
						);
						?>
					</div>

					<div class="ppe-password-page-form">
						<?php echo $form_html; ?>
					</div>

					<?php if ( ! empty( $password_group->description ) ) : ?>
						<div class="ppe-password-page-description">
							<p><?php echo esc_html( $password_group->description ); ?></p>
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
}
