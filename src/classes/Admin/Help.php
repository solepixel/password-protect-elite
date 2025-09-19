<?php
/**
 * Admin help class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help class.
 */
class Help {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_help_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_help_scripts' ) );
	}

	/**
	 * Add help page.
	 */
	public function add_help_page() {
		add_submenu_page(
			'edit.php?post_type=ppe_password_group',
			__( 'Help & Documentation', 'password-protect-elite' ),
			__( 'Help', 'password-protect-elite' ),
			'manage_options',
			'ppe-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Enqueue help scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_help_scripts( $hook ) {
		if ( 'ppe_password_group_page_ppe-help' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ppe-help',
			PPE_PLUGIN_URL . 'assets/admin/css/help.css',
			array(),
			PPE_VERSION
		);
	}

	/**
	 * Render help page.
	 */
	public function render_help_page() {
		?>
		<div class="wrap ppe-help-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="ppe-help-content">
				<div class="ppe-help-section">
					<h2><?php esc_html_e( 'Getting Started', 'password-protect-elite' ); ?></h2>
					<p><?php esc_html_e( 'Password Protect Elite allows you to create multiple password groups and protect different parts of your website.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( '1. Create Password Groups', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Go to Password Groups and click "Add New Password Group". Fill in the details:', 'password-protect-elite' ); ?></p>
					<ul>
						<li><strong><?php esc_html_e( 'Name', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'A descriptive name for the group', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Master Password', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'The primary password users will need to enter', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Additional Passwords', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Other passwords that grant the same access', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Protection Type', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Choose from Global Site, General, Section/Page, or Content Block', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Redirect URL', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Optional URL to redirect after successful entry', 'password-protect-elite' ); ?></li>
					</ul>

					<h3><?php esc_html_e( '2. Use Gutenberg Blocks', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Add these blocks to your pages and posts:', 'password-protect-elite' ); ?></p>
					<ul>
						<li><strong><?php esc_html_e( 'Password Entry Block', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Creates a password form for users to enter', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Protected Content Block', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Hides content until the correct password is entered', 'password-protect-elite' ); ?></li>
					</ul>

					<h3><?php esc_html_e( '3. Protect Entire Pages', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'When editing a page or post, look for the "Password Protect" meta box in the sidebar. Select a password group to protect the entire page.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( '4. Global Site Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Create a password group with "Global Site" protection type to protect your entire website with a single password.', 'password-protect-elite' ); ?></p>
				</div>

				<div class="ppe-help-section">
					<h2><?php esc_html_e( 'Protection Types', 'password-protect-elite' ); ?></h2>

					<h3><?php esc_html_e( 'Global Site Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Protects your entire website. All visitors must enter the global password to access any part of the site.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( 'General Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Can be used for both page/section protection and content blocks. Most flexible option.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( 'Section/Page Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Protects individual pages or posts. Users must enter the correct password before viewing the page content.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( 'Content Block Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Used with Gutenberg blocks to protect specific content sections within a page.', 'password-protect-elite' ); ?></p>
				</div>

				<div class="ppe-help-section">
					<h2><?php esc_html_e( 'Use Cases', 'password-protect-elite' ); ?></h2>
					<ul>
						<li><strong><?php esc_html_e( 'Client Portals', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Create password-protected areas for different clients', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Premium Content', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Protect premium content with specific passwords', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Event Access', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Provide unique passwords for different events', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Member Areas', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Create different access levels for members', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Beta Testing', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Protect beta features with specific passwords', 'password-protect-elite' ); ?></li>
						<li><strong><?php esc_html_e( 'Site Maintenance', 'password-protect-elite' ); ?>:</strong> <?php esc_html_e( 'Protect the entire site during maintenance', 'password-protect-elite' ); ?></li>
					</ul>
				</div>

				<div class="ppe-help-section">
					<h2><?php esc_html_e( 'Advanced Features', 'password-protect-elite' ); ?></h2>

					<h3><?php esc_html_e( 'URL Exclusions', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Use wildcards to exclude specific URLs from protection. For example: /admin/* or /login/*', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( 'Auto-Protection', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Automatically protect URLs that match patterns. Useful for protecting entire sections of your site.', 'password-protect-elite' ); ?></p>

					<h3><?php esc_html_e( 'Multiple Passwords', 'password-protect-elite' ); ?></h3>
					<p><?php esc_html_e( 'Each password group can have a master password plus additional passwords for the same access level.', 'password-protect-elite' ); ?></p>
				</div>

				<div class="ppe-help-section">
					<h2><?php esc_html_e( 'Support', 'password-protect-elite' ); ?></h2>
					<p><?php esc_html_e( 'If you need help or have questions:', 'password-protect-elite' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'Visit the WordPress.org support forums', 'password-protect-elite' ); ?></li>
						<li><?php esc_html_e( 'Contact the plugin author', 'password-protect-elite' ); ?></li>
						<li><?php esc_html_e( 'Check the plugin documentation', 'password-protect-elite' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
