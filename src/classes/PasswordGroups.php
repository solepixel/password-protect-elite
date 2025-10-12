<?php
/**
 * Password Groups Custom Post Type class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Password Groups Custom Post Type registration and management.
 */
class PasswordGroups {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_ppe_password_group', array( $this, 'save_meta_data' ) );
		add_filter( 'manage_ppe_password_group_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_ppe_password_group_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register the Password Groups Custom Post Type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Password Groups', 'Post type general name', 'password-protect-elite' ),
			'singular_name'         => _x( 'Password Group', 'Post type singular name', 'password-protect-elite' ),
			'menu_name'             => _x( 'Password Groups', 'Admin Menu text', 'password-protect-elite' ),
			'name_admin_bar'        => _x( 'Password Group', 'Add New on Toolbar', 'password-protect-elite' ),
			'add_new'               => __( 'Add New', 'password-protect-elite' ),
			'add_new_item'          => __( 'Add New Password Group', 'password-protect-elite' ),
			'new_item'              => __( 'New Password Group', 'password-protect-elite' ),
			'edit_item'             => __( 'Edit Password Group', 'password-protect-elite' ),
			'view_item'             => __( 'View Password Group', 'password-protect-elite' ),
			'all_items'             => __( 'All Password Groups', 'password-protect-elite' ),
			'search_items'          => __( 'Search Password Groups', 'password-protect-elite' ),
			'parent_item_colon'     => __( 'Parent Password Groups:', 'password-protect-elite' ),
			'not_found'             => __( 'No password groups found.', 'password-protect-elite' ),
			'not_found_in_trash'    => __( 'No password groups found in Trash.', 'password-protect-elite' ),
			'featured_image'        => _x( 'Password Group Cover Image', 'Overrides the "Featured Image" phrase', 'password-protect-elite' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'password-protect-elite' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'password-protect-elite' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'password-protect-elite' ),
			'archives'              => _x( 'Password Group archives', 'The post type archive label', 'password-protect-elite' ),
			'insert_into_item'      => _x( 'Insert into password group', 'Overrides the "Insert into post" phrase', 'password-protect-elite' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this password group', 'Overrides the "Uploaded to this post" phrase', 'password-protect-elite' ),
			'filter_items_list'     => _x( 'Filter password groups list', 'Screen reader text for the filter links', 'password-protect-elite' ),
			'items_list_navigation' => _x( 'Password groups list navigation', 'Screen reader text for the pagination', 'password-protect-elite' ),
			'items_list'            => _x( 'Password groups list', 'Screen reader text for the items list', 'password-protect-elite' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 25, // After Comments.
			'menu_icon'          => 'dashicons-lock',
			'supports'           => array( 'title' ),
			'capabilities'       => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
			),
		);

		register_post_type( 'ppe_password_group', $args );
	}

	/**
	 * Add meta boxes for password group settings.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ppe_password_group_settings',
			__( 'Password Group Settings', 'password-protect-elite' ),
			array( $this, 'render_meta_box' ),
			'ppe_password_group',
			'normal',
			'high'
		);
	}

	/**
	 * Render the password group meta box.
	 *
	 * @param \WP_Post $post The current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'ppe_save_password_group_meta', 'ppe_password_group_meta_nonce' );

		$master_password      = get_post_meta( $post->ID, '_ppe_master_password', true );
		$additional_passwords = get_post_meta( $post->ID, '_ppe_additional_passwords', true );
		$protection_type      = get_post_meta( $post->ID, '_ppe_protection_type', true );
		$redirect_type        = get_post_meta( $post->ID, '_ppe_redirect_type', true );
		$redirect_page_id     = get_post_meta( $post->ID, '_ppe_redirect_page_id', true );
		$redirect_custom_url  = get_post_meta( $post->ID, '_ppe_redirect_custom_url', true );
		$unauthenticated_behavior = get_post_meta( $post->ID, '_ppe_unauthenticated_behavior', true );
		$unauthenticated_redirect_type = get_post_meta( $post->ID, '_ppe_unauthenticated_redirect_type', true );
		$unauthenticated_redirect_page_id = get_post_meta( $post->ID, '_ppe_unauthenticated_redirect_page_id', true );
		$unauthenticated_redirect_custom_url = get_post_meta( $post->ID, '_ppe_unauthenticated_redirect_custom_url', true );
		$logout_redirect_type = get_post_meta( $post->ID, '_ppe_logout_redirect_type', true );
		$logout_redirect_page_id = get_post_meta( $post->ID, '_ppe_logout_redirect_page_id', true );
		$logout_redirect_custom_url = get_post_meta( $post->ID, '_ppe_logout_redirect_custom_url', true );
		$exclude_urls         = get_post_meta( $post->ID, '_ppe_exclude_urls', true );
		$auto_protect_urls    = get_post_meta( $post->ID, '_ppe_auto_protect_urls', true );
		$allowed_roles        = get_post_meta( $post->ID, '_ppe_allowed_roles', true );

		if ( empty( $additional_passwords ) || ! is_array( $additional_passwords ) ) {
			$additional_passwords = array();
		}

		// Set default protection type if not set.
		if ( empty( $protection_type ) ) {
			$protection_type = 'general';
		}

		// Set default redirect type.
		if ( empty( $redirect_type ) ) {
			$redirect_type = 'none';
		}

		// Set default unauthenticated behavior.
		if ( empty( $unauthenticated_behavior ) ) {
			$unauthenticated_behavior = 'show_404';
		}

		// Set default unauthenticated redirect type.
		if ( empty( $unauthenticated_redirect_type ) ) {
			$unauthenticated_redirect_type = 'page';
		}

		// Set default logout redirect type.
		if ( empty( $logout_redirect_type ) ) {
			$logout_redirect_type = 'same_page';
		}
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="ppe_master_password"><?php esc_html_e( 'Master Password', 'password-protect-elite' ); ?></label></th>
				<td>
					<input type="text" name="ppe_master_password" id="ppe_master_password" value="<?php echo esc_attr( $master_password ); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e( 'The primary password for this group.', 'password-protect-elite' ); ?></p>
				</td>
			</tr>
		</table>

		<!-- Additional Access Options Section -->
		<fieldset class="ppe-behavior-section ppe-additional-access-section">
			<legend><h3><?php esc_html_e( 'Additional Access Options', 'password-protect-elite' ); ?></h3></legend>
			<p class="description"><?php esc_html_e( 'Configure additional access methods that grant users access without entering a password.', 'password-protect-elite' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Additional Passwords', 'password-protect-elite' ); ?></th>
					<td>
						<div id="ppe-additional-passwords-wrapper">
							<?php if ( ! empty( $additional_passwords ) ) : ?>
								<?php foreach ( $additional_passwords as $index => $additional_password ) : ?>
									<div class="ppe-additional-password-item">
										<input type="text" name="ppe_additional_passwords[]" value="<?php echo esc_attr( $additional_password ); ?>" class="regular-text">
										<button type="button" class="button button-secondary ppe-remove-additional-password"><?php esc_html_e( 'Remove', 'password-protect-elite' ); ?></button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<button type="button" class="button button-secondary" id="ppe-add-additional-password"><?php esc_html_e( 'Add Additional Password', 'password-protect-elite' ); ?></button>
						<p class="description"><?php esc_html_e( 'Other passwords that grant access to this group.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'User Roles', 'password-protect-elite' ); ?></th>
					<td>
						<?php
						if ( empty( $allowed_roles ) || ! is_array( $allowed_roles ) ) {
							$allowed_roles = array();
						}
						$roles = function_exists( 'get_editable_roles' ) ? get_editable_roles() : array();
						if ( ! empty( $roles ) ) :
						?>
							<div class="ppe-roles-checkboxes">
								<?php foreach ( $roles as $role_slug => $role_info ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="checkbox" name="ppe_allowed_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $allowed_roles, true ) ); ?>>
										<?php echo esc_html( translate_user_role( $role_info['name'] ) ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<em><?php esc_html_e( 'No editable roles found.', 'password-protect-elite' ); ?></em>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Users with any selected role automatically have access (no password required).', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
			</table>
		</fieldset>

		<!-- Protection Type and URL Settings Section -->
		<fieldset class="ppe-behavior-section ppe-protection-type-section">
			<legend><h3><?php esc_html_e( 'Protection Type & URL Settings', 'password-protect-elite' ); ?></h3></legend>
			<p class="description"><?php esc_html_e( 'Configure how this password group protects content and which URLs are affected.', 'password-protect-elite' ); ?></p>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="ppe_protection_type"><?php esc_html_e( 'Protection Type', 'password-protect-elite' ); ?></label></th>
					<td>
						<select name="ppe_protection_type" id="ppe_protection_type">
							<option value="global_site" <?php selected( $protection_type, 'global_site' ); ?>><?php esc_html_e( 'Global Site', 'password-protect-elite' ); ?></option>
							<option value="general" <?php selected( $protection_type, 'general' ); ?>><?php esc_html_e( 'General (Pages/Sections & Blocks)', 'password-protect-elite' ); ?></option>
							<option value="section" <?php selected( $protection_type, 'section' ); ?>><?php esc_html_e( 'Page/Section Specific', 'password-protect-elite' ); ?></option>
							<option value="block" <?php selected( $protection_type, 'block' ); ?>><?php esc_html_e( 'Content Block Specific', 'password-protect-elite' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Defines where this password group can be used.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ppe_redirect_type"><?php esc_html_e( 'Redirect Behavior', 'password-protect-elite' ); ?></label></th>
					<td>
						<select name="ppe_redirect_type" id="ppe_redirect_type">
							<option value="none" <?php selected( $redirect_type, 'none' ); ?>><?php esc_html_e( 'No Redirect (stay on current page)', 'password-protect-elite' ); ?></option>
							<option value="page" <?php selected( $redirect_type, 'page' ); ?>><?php esc_html_e( 'Redirect to an existing Page', 'password-protect-elite' ); ?></option>
							<option value="custom_url" <?php selected( $redirect_type, 'custom_url' ); ?>><?php esc_html_e( 'Redirect to a Custom URL', 'password-protect-elite' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose where to redirect the user after successful password entry.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr class="ppe-redirect-field ppe-redirect-page-field" style="<?php echo ( 'page' === $redirect_type ) ? '' : 'display:none;'; ?>">
					<th scope="row"><label for="ppe_redirect_page_id"><?php esc_html_e( 'Select Page', 'password-protect-elite' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'ppe_redirect_page_id',
								'id'                => 'ppe_redirect_page_id',
								'selected'          => $redirect_page_id,
								'show_option_none'  => __( '— Select —', 'password-protect-elite' ),
								'option_none_value' => 0,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Choose an existing WordPress page to redirect to.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr class="ppe-redirect-field ppe-redirect-custom-url-field" style="<?php echo ( 'custom_url' === $redirect_type ) ? '' : 'display:none;'; ?>">
					<th scope="row"><label for="ppe_redirect_custom_url"><?php esc_html_e( 'Custom Redirect URL', 'password-protect-elite' ); ?></label></th>
					<td>
						<input type="url" name="ppe_redirect_custom_url" id="ppe_redirect_custom_url" value="<?php echo esc_url( $redirect_custom_url ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Enter a full URL (e.g., https://example.com/thank-you).', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr class="ppe-url-protection-field ppe-exclude-urls-field" style="<?php echo ( 'block' === $protection_type ) ? 'display:none;' : ''; ?>">
					<th scope="row"><label for="ppe_exclude_urls"><?php esc_html_e( 'Exclude URLs', 'password-protect-elite' ); ?></label></th>
					<td>
						<textarea name="ppe_exclude_urls" id="ppe_exclude_urls" rows="4" cols="50" class="large-text"><?php echo esc_textarea( $exclude_urls ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'URLs to exclude from protection (one per line). Use * for wildcards, e.g., /some-url/* or /admin/*', 'password-protect-elite' ); ?>
						</p>
					</td>
				</tr>
				<tr class="ppe-url-protection-field ppe-auto-protect-urls-field" style="<?php echo ( 'global_site' === $protection_type || 'block' === $protection_type ) ? 'display:none;' : ''; ?>">
					<th scope="row"><label for="ppe_auto_protect_urls"><?php esc_html_e( 'Auto-Protect URLs', 'password-protect-elite' ); ?></label></th>
					<td>
						<textarea name="ppe_auto_protect_urls" id="ppe_auto_protect_urls" rows="4" cols="50" class="large-text"><?php echo esc_textarea( $auto_protect_urls ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'URLs to automatically protect with this password group (one per line). Use * for wildcards, e.g., /sub-page1/* or /private/*', 'password-protect-elite' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</fieldset>

		<!-- Unauthenticated Access Behavior Section -->
		<fieldset class="ppe-behavior-section ppe-unauthenticated-behavior-section">
			<legend><h3><?php esc_html_e( 'Unauthenticated Access Behavior', 'password-protect-elite' ); ?></h3></legend>
			<p class="description"><?php esc_html_e( 'Configure what happens when users try to access protected content without authentication.', 'password-protect-elite' ); ?></p>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="ppe_unauthenticated_behavior"><?php esc_html_e( 'Behavior', 'password-protect-elite' ); ?></label></th>
				<td>
					<select name="ppe_unauthenticated_behavior" id="ppe_unauthenticated_behavior">
						<option value="show_404" <?php selected( $unauthenticated_behavior, 'show_404' ); ?>><?php esc_html_e( 'Display 404 (Default)', 'password-protect-elite' ); ?></option>
						<option value="redirect" <?php selected( $unauthenticated_behavior, 'redirect' ); ?>><?php esc_html_e( 'Redirect to another page/URL', 'password-protect-elite' ); ?></option>
						<option value="show_dialog" <?php selected( $unauthenticated_behavior, 'show_dialog' ); ?>><?php esc_html_e( 'Render password prompt dialog', 'password-protect-elite' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'What happens when an unauthenticated user tries to access protected content.', 'password-protect-elite' ); ?></p>
				</td>
			</tr>
			<tr class="ppe-unauthenticated-redirect-field ppe-unauthenticated-redirect-type-field" style="<?php echo ( 'redirect' === $unauthenticated_behavior ) ? '' : 'display:none !important;'; ?>">
				<th scope="row"><label for="ppe_unauthenticated_redirect_type"><?php esc_html_e( 'Redirect Type', 'password-protect-elite' ); ?></label></th>
				<td>
					<select name="ppe_unauthenticated_redirect_type" id="ppe_unauthenticated_redirect_type">
						<option value="page" <?php selected( $unauthenticated_redirect_type, 'page' ); ?>><?php esc_html_e( 'Redirect to an existing Page', 'password-protect-elite' ); ?></option>
						<option value="custom_url" <?php selected( $unauthenticated_redirect_type, 'custom_url' ); ?>><?php esc_html_e( 'Redirect to a Custom URL', 'password-protect-elite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="ppe-unauthenticated-redirect-field ppe-unauthenticated-redirect-page-field" style="<?php echo ( 'redirect' === $unauthenticated_behavior && 'page' === $unauthenticated_redirect_type ) ? '' : 'display:none !important;'; ?>">
				<th scope="row"><label for="ppe_unauthenticated_redirect_page_id"><?php esc_html_e( 'Select Page', 'password-protect-elite' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => 'ppe_unauthenticated_redirect_page_id',
							'id'                => 'ppe_unauthenticated_redirect_page_id',
							'selected'          => $unauthenticated_redirect_page_id,
							'show_option_none'  => __( '— Select —', 'password-protect-elite' ),
							'option_none_value' => 0,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Choose an existing WordPress page to redirect unauthenticated users to.', 'password-protect-elite' ); ?></p>
				</td>
			</tr>
			<tr class="ppe-unauthenticated-redirect-field ppe-unauthenticated-redirect-custom-url-field" style="<?php echo ( 'redirect' === $unauthenticated_behavior && 'custom_url' === $unauthenticated_redirect_type ) ? '' : 'display:none !important;'; ?>">
				<th scope="row"><label for="ppe_unauthenticated_redirect_custom_url"><?php esc_html_e( 'Custom Redirect URL', 'password-protect-elite' ); ?></label></th>
				<td>
					<input type="url" name="ppe_unauthenticated_redirect_custom_url" id="ppe_unauthenticated_redirect_custom_url" value="<?php echo esc_url( $unauthenticated_redirect_custom_url ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Enter a full URL for unauthenticated users (e.g., https://example.com/login).', 'password-protect-elite' ); ?></p>
				</td>
			</tr>
			</table>
		</fieldset>

		<!-- Log Out Behavior Section -->
		<fieldset class="ppe-behavior-section ppe-logout-behavior-section">
			<legend><h3><?php esc_html_e( 'Log Out Behavior', 'password-protect-elite' ); ?></h3></legend>
			<p class="description"><?php esc_html_e( 'Configure where users are redirected after logging out of this password group.', 'password-protect-elite' ); ?></p>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="ppe_logout_redirect_type"><?php esc_html_e( 'Log Out Action', 'password-protect-elite' ); ?></label></th>
					<td>
						<select name="ppe_logout_redirect_type" id="ppe_logout_redirect_type">
							<option value="same_page" <?php selected( $logout_redirect_type, 'same_page' ); ?>><?php esc_html_e( 'Stay on the same page (Default)', 'password-protect-elite' ); ?></option>
							<option value="page" <?php selected( $logout_redirect_type, 'page' ); ?>><?php esc_html_e( 'Redirect to an existing Page', 'password-protect-elite' ); ?></option>
							<option value="custom_url" <?php selected( $logout_redirect_type, 'custom_url' ); ?>><?php esc_html_e( 'Redirect to a Custom URL', 'password-protect-elite' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Where users are redirected after clicking the logout link.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr class="ppe-logout-redirect-page-field" style="<?php echo ( 'page' === $logout_redirect_type ) ? '' : 'display:none !important;'; ?>">
					<th scope="row"><label for="ppe_logout_redirect_page_id"><?php esc_html_e( 'Select Page', 'password-protect-elite' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'ppe_logout_redirect_page_id',
								'id'                => 'ppe_logout_redirect_page_id',
								'selected'          => $logout_redirect_page_id,
								'show_option_none'  => __( '— Select —', 'password-protect-elite' ),
								'option_none_value' => 0,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Choose an existing WordPress page to redirect users to after logout.', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
				<tr class="ppe-logout-redirect-custom-url-field" style="<?php echo ( 'custom_url' === $logout_redirect_type ) ? '' : 'display:none !important;'; ?>">
					<th scope="row"><label for="ppe_logout_redirect_custom_url"><?php esc_html_e( 'Custom Redirect URL', 'password-protect-elite' ); ?></label></th>
					<td>
						<input type="url" name="ppe_logout_redirect_custom_url" id="ppe_logout_redirect_custom_url" value="<?php echo esc_url( $logout_redirect_custom_url ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Enter a full URL to redirect users to after logout (e.g., https://example.com).', 'password-protect-elite' ); ?></p>
					</td>
				</tr>
			</table>
		</fieldset>
		<?php
	}

	/**
	 * Save password group meta data.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_meta_data( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['ppe_password_group_meta_nonce'] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( sanitize_key( $_POST['ppe_password_group_meta_nonce'] ), 'ppe_save_password_group_meta' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Sanitize and save master password.
		if ( isset( $_POST['ppe_master_password'] ) ) {
			update_post_meta( $post_id, '_ppe_master_password', sanitize_text_field( wp_unslash( $_POST['ppe_master_password'] ) ) );
		}

		// Sanitize and save additional passwords.
		if ( isset( $_POST['ppe_additional_passwords'] ) && is_array( $_POST['ppe_additional_passwords'] ) ) {
			$additional_passwords = array_map( 'sanitize_text_field', wp_unslash( $_POST['ppe_additional_passwords'] ) );
			update_post_meta( $post_id, '_ppe_additional_passwords', $additional_passwords );
		} else {
			delete_post_meta( $post_id, '_ppe_additional_passwords' );
		}

		// Sanitize and save allowed roles.
		if ( isset( $_POST['ppe_allowed_roles'] ) && is_array( $_POST['ppe_allowed_roles'] ) ) {
			$roles = array_map( 'sanitize_key', wp_unslash( $_POST['ppe_allowed_roles'] ) );
			$roles = array_values( array_unique( array_filter( $roles ) ) );
			if ( ! empty( $roles ) ) {
				update_post_meta( $post_id, '_ppe_allowed_roles', $roles );
			} else {
				delete_post_meta( $post_id, '_ppe_allowed_roles' );
			}
		} else {
			delete_post_meta( $post_id, '_ppe_allowed_roles' );
		}

		// Sanitize and save protection type.
		if ( isset( $_POST['ppe_protection_type'] ) ) {
			update_post_meta( $post_id, '_ppe_protection_type', sanitize_text_field( wp_unslash( $_POST['ppe_protection_type'] ) ) );
		}

		// Sanitize and save redirect type.
		if ( isset( $_POST['ppe_redirect_type'] ) ) {
			$redirect_type = sanitize_text_field( wp_unslash( $_POST['ppe_redirect_type'] ) );
			update_post_meta( $post_id, '_ppe_redirect_type', $redirect_type );

			// Save redirect page ID or custom URL based on type.
			if ( 'page' === $redirect_type && isset( $_POST['ppe_redirect_page_id'] ) ) {
				update_post_meta( $post_id, '_ppe_redirect_page_id', absint( wp_unslash( $_POST['ppe_redirect_page_id'] ) ) );
				delete_post_meta( $post_id, '_ppe_redirect_custom_url' );
			} elseif ( 'custom_url' === $redirect_type && isset( $_POST['ppe_redirect_custom_url'] ) ) {
				update_post_meta( $post_id, '_ppe_redirect_custom_url', esc_url_raw( wp_unslash( $_POST['ppe_redirect_custom_url'] ) ) );
				delete_post_meta( $post_id, '_ppe_redirect_page_id' );
			} else {
				delete_post_meta( $post_id, '_ppe_redirect_page_id' );
				delete_post_meta( $post_id, '_ppe_redirect_custom_url' );
			}
		}

		// Sanitize and save unauthenticated behavior.
		if ( isset( $_POST['ppe_unauthenticated_behavior'] ) ) {
			$unauthenticated_behavior = sanitize_text_field( wp_unslash( $_POST['ppe_unauthenticated_behavior'] ) );
			update_post_meta( $post_id, '_ppe_unauthenticated_behavior', $unauthenticated_behavior );

			// Save unauthenticated redirect settings based on behavior.
			if ( 'redirect' === $unauthenticated_behavior ) {
				// Save the unauthenticated redirect type selection.
				if ( isset( $_POST['ppe_unauthenticated_redirect_type'] ) ) {
					$unauthenticated_redirect_type = sanitize_text_field( wp_unslash( $_POST['ppe_unauthenticated_redirect_type'] ) );
					update_post_meta( $post_id, '_ppe_unauthenticated_redirect_type', $unauthenticated_redirect_type );
				}

				if ( isset( $_POST['ppe_unauthenticated_redirect_page_id'] ) ) {
					update_post_meta( $post_id, '_ppe_unauthenticated_redirect_page_id', absint( wp_unslash( $_POST['ppe_unauthenticated_redirect_page_id'] ) ) );
				}
				if ( isset( $_POST['ppe_unauthenticated_redirect_custom_url'] ) ) {
					update_post_meta( $post_id, '_ppe_unauthenticated_redirect_custom_url', esc_url_raw( wp_unslash( $_POST['ppe_unauthenticated_redirect_custom_url'] ) ) );
				}
			} else {
				delete_post_meta( $post_id, '_ppe_unauthenticated_redirect_page_id' );
				delete_post_meta( $post_id, '_ppe_unauthenticated_redirect_custom_url' );
				delete_post_meta( $post_id, '_ppe_unauthenticated_redirect_type' );
			}
		}

		// Save logout redirect settings.
		if ( isset( $_POST['ppe_logout_redirect_type'] ) ) {
			$logout_redirect_type = sanitize_text_field( wp_unslash( $_POST['ppe_logout_redirect_type'] ) );
			update_post_meta( $post_id, '_ppe_logout_redirect_type', $logout_redirect_type );

			if ( 'page' === $logout_redirect_type && isset( $_POST['ppe_logout_redirect_page_id'] ) ) {
				update_post_meta( $post_id, '_ppe_logout_redirect_page_id', absint( wp_unslash( $_POST['ppe_logout_redirect_page_id'] ) ) );
			} elseif ( 'custom_url' === $logout_redirect_type && isset( $_POST['ppe_logout_redirect_custom_url'] ) ) {
				update_post_meta( $post_id, '_ppe_logout_redirect_custom_url', esc_url_raw( wp_unslash( $_POST['ppe_logout_redirect_custom_url'] ) ) );
			}
		}

		// Sanitize and save exclude URLs.
		if ( isset( $_POST['ppe_exclude_urls'] ) ) {
			update_post_meta( $post_id, '_ppe_exclude_urls', sanitize_textarea_field( wp_unslash( $_POST['ppe_exclude_urls'] ) ) );
		}

		// Sanitize and save auto-protect URLs.
		if ( isset( $_POST['ppe_auto_protect_urls'] ) ) {
			update_post_meta( $post_id, '_ppe_auto_protect_urls', sanitize_textarea_field( wp_unslash( $_POST['ppe_auto_protect_urls'] ) ) );
		}
	}

	/**
	 * Add custom columns to the password group list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = array(
			'cb'              => $columns['cb'],
			'title'           => $columns['title'],
			'master_password' => __( 'Master Password', 'password-protect-elite' ),
			'protection_type' => __( 'Protection Type', 'password-protect-elite' ),
			'redirect_to'     => __( 'Redirect To', 'password-protect-elite' ),
			'date'            => $columns['date'],
		);
		return $new_columns;
	}

	/**
	 * Render custom columns for the password group list table.
	 *
	 * @param string $column_name The name of the column to render.
	 * @param int    $post_id     The current post ID.
	 */
	public function render_columns( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'master_password':
				$master_password = get_post_meta( $post_id, '_ppe_master_password', true );
				echo '<code>' . esc_html( $master_password ) . '</code>';
				break;
			case 'protection_type':
				$protection_type = get_post_meta( $post_id, '_ppe_protection_type', true );
				echo '<span class="ppe-type-badge ppe-type-' . esc_attr( $protection_type ) . '">' . esc_html( ucfirst( str_replace( '_', ' ', $protection_type ) ) ) . '</span>';
				break;
			case 'redirect_to':
				$redirect_type       = get_post_meta( $post_id, '_ppe_redirect_type', true );
				$redirect_page_id    = get_post_meta( $post_id, '_ppe_redirect_page_id', true );
				$redirect_custom_url = get_post_meta( $post_id, '_ppe_redirect_custom_url', true );

				if ( 'page' === $redirect_type && $redirect_page_id ) {
					echo '<a href="' . esc_url( get_permalink( $redirect_page_id ) ) . '" target="_blank">' . esc_html( get_the_title( $redirect_page_id ) ) . '</a>';
				} elseif ( 'custom_url' === $redirect_type && $redirect_custom_url ) {
					echo '<a href="' . esc_url( $redirect_custom_url ) . '" target="_blank">' . esc_html( $redirect_custom_url ) . '</a>';
				} else {
					esc_html_e( 'None', 'password-protect-elite' );
				}
				break;
		}
	}

	/**
	 * Enqueue admin scripts for the password groups CPT.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( 'ppe_password_group' === $post_type && ( 'post.php' === $hook || 'post-new.php' === $hook ) ) {
			// Enqueue WordPress core password strength meter.
			wp_enqueue_script( 'password-strength-meter' );
			wp_enqueue_script( 'ppe-password-groups-js', PPE_PLUGIN_URL . 'assets/admin/js/password-groups.js', array( 'jquery', 'password-strength-meter' ), PPE_VERSION, true );
			wp_enqueue_style( 'ppe-password-groups-css', PPE_PLUGIN_URL . 'assets/admin/css/password-groups.css', array(), PPE_VERSION );

			// Localize script with strings.
			wp_localize_script(
				'ppe-password-groups-js',
				'ppeCptAdmin',
				array(
					'strings' => array(
						'enterPassword'          => __( 'Enter additional password', 'password-protect-elite' ),
						'remove'                 => __( 'Remove', 'password-protect-elite' ),
						'masterPasswordRequired' => __( 'Master password is required.', 'password-protect-elite' ),
						'strength'               => __( 'Strength', 'password-protect-elite' ),
						'veryWeak'               => _x( 'Very weak', 'password strength', 'password-protect-elite' ),
						'weak'                   => _x( 'Weak', 'password strength', 'password-protect-elite' ),
						'medium'                 => _x( 'Medium', 'password strength', 'password-protect-elite' ),
						'strong'                 => _x( 'Strong', 'password strength', 'password-protect-elite' ),
						'veryStrong'             => _x( 'Very strong', 'password strength', 'password-protect-elite' ),
					),
				)
			);
		}
	}
}
