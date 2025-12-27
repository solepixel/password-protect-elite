<?php
/**
 * Admin Settings functionality.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin settings page and functionality.
 */
class Settings {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'ppe-settings';

	/**
	 * Settings group name.
	 */
	const SETTINGS_GROUP = 'ppe_settings';

	/**
	 * Minimum value for password attempts limit.
	 */
	const PASSWORD_ATTEMPTS_LIMIT_MIN = 0;

	/**
	 * Maximum value for password attempts limit.
	 */
	const PASSWORD_ATTEMPTS_LIMIT_MAX = 20;

	/**
	 * Minimum value for lockout duration (minutes).
	 */
	const LOCKOUT_DURATION_MIN = 1;

	/**
	 * Maximum value for lockout duration (minutes).
	 */
	const LOCKOUT_DURATION_MAX = 1440;

	/**
	 * Minimum value for session duration.
	 */
	const SESSION_DURATION_MIN = 1;

	/**
	 * Maximum value for session duration.
	 */
	const SESSION_DURATION_MAX = 8760;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}


	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=ppe_password_group',
			__( 'Settings', 'password-protect-elite' ),
			__( 'Settings', 'password-protect-elite' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		// Register the main settings group.
		register_setting(
			self::SETTINGS_GROUP,
			self::SETTINGS_GROUP,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// General Tab - Session and Security Settings
		add_settings_section(
			'ppe_general_section',
			__( 'General Settings', 'password-protect-elite' ),
			array( $this, 'render_general_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'session_duration',
			__( 'Session Duration', 'password-protect-elite' ),
			array( $this, 'render_session_duration_field' ),
			self::PAGE_SLUG,
			'ppe_general_section'
		);

		add_settings_field(
			'password_attempts_limit',
			__( 'Password Attempts Limit', 'password-protect-elite' ),
			array( $this, 'render_password_attempts_limit_field' ),
			self::PAGE_SLUG,
			'ppe_general_section'
		);

		add_settings_field(
			'lockout_duration',
			__( 'Lockout Duration (minutes)', 'password-protect-elite' ),
			array( $this, 'render_lockout_duration_field' ),
			self::PAGE_SLUG,
			'ppe_general_section'
		);

		// Appearance Tab - Block Styles and Colors
		add_settings_section(
			'ppe_appearance_section',
			__( 'Appearance Settings', 'password-protect-elite' ),
			array( $this, 'render_appearance_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'block_styles_mode',
			__( 'Block Styles Mode', 'password-protect-elite' ),
			array( $this, 'render_block_styles_field' ),
			self::PAGE_SLUG,
			'ppe_appearance_section'
		);

		// Color Customization section (only for All mode).
		add_settings_section(
			'ppe_color_customization_section',
			__( 'Color Customization', 'password-protect-elite' ),
			array( $this, 'render_color_customization_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'primary_color',
			__( 'Primary Color', 'password-protect-elite' ),
			array( $this, 'render_primary_color_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		add_settings_field(
			'primary_color_hover',
			__( 'Primary Color (Hover)', 'password-protect-elite' ),
			array( $this, 'render_primary_color_hover_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		add_settings_field(
			'border_color',
			__( 'Border Color', 'password-protect-elite' ),
			array( $this, 'render_border_color_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		add_settings_field(
			'background_color',
			__( 'Background Color', 'password-protect-elite' ),
			array( $this, 'render_background_color_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		add_settings_field(
			'success_color',
			__( 'Success Message Color', 'password-protect-elite' ),
			array( $this, 'render_success_color_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		add_settings_field(
			'error_color',
			__( 'Error Message Color', 'password-protect-elite' ),
			array( $this, 'render_error_color_field' ),
			self::PAGE_SLUG,
			'ppe_color_customization_section'
		);

		// Messages Tab - Global Text Strings
		add_settings_section(
			'ppe_messages_section',
			__( 'Message Settings', 'password-protect-elite' ),
			array( $this, 'render_messages_section_description' ),
			self::PAGE_SLUG
		);

		// Add fields for each customizable string.
		$string_manager = new StringManager();
		$customizable_strings = $string_manager->get_customizable_strings();

		foreach ( $customizable_strings as $key => $string_config ) {
			add_settings_field(
				$key,
				$string_config['label'],
				array( $this, 'render_string_field' ),
				self::PAGE_SLUG,
				'ppe_messages_section',
				array(
					'string_key'    => $key,
					'string_config' => $string_config,
				)
			);
		}

		// Advanced Tab - Debug and Cache Settings
		add_settings_section(
			'ppe_advanced_section',
			__( 'Advanced Settings', 'password-protect-elite' ),
			array( $this, 'render_advanced_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'password-protect-elite' ),
			array( $this, 'render_debug_mode_field' ),
			self::PAGE_SLUG,
			'ppe_advanced_section'
		);

		add_settings_field(
			'auto_clear_cache',
			__( 'Auto Clear Cache', 'password-protect-elite' ),
			array( $this, 'render_auto_clear_cache_field' ),
			self::PAGE_SLUG,
			'ppe_advanced_section'
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		// Get current tab from URL parameter.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

		// Define available tabs.
		$tabs = array(
			'general'    => __( 'General', 'password-protect-elite' ),
			'appearance' => __( 'Appearance', 'password-protect-elite' ),
			'messages'   => __( 'Messages', 'password-protect-elite' ),
			'advanced'   => __( 'Advanced', 'password-protect-elite' ),
		);

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			// Display any settings errors or success messages.
			settings_errors( 'ppe_settings_messages' );
			?>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="?post_type=ppe_password_group&page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=<?php echo esc_attr( $tab_key ); ?>"
					   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );

				// Render tab content.
				switch ( $current_tab ) {
					case 'general':
						$this->render_general_tab();
						break;
					case 'appearance':
						$this->render_appearance_tab();
						break;
					case 'messages':
						$this->render_messages_tab();
						break;
					case 'advanced':
						$this->render_advanced_tab();
						break;
					default:
						$this->render_general_tab();
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section_description() {
		echo '<p>' . esc_html__( 'Configure general security and session settings for password protection.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render appearance section description.
	 */
	public function render_appearance_section_description() {
		echo '<p>' . esc_html__( 'Configure the visual appearance and styling of password protection blocks.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render messages section description.
	 */
	public function render_messages_section_description() {
		echo '<p>' . esc_html__( 'Customize the default text strings used in the plugin blocks. These will be used as defaults when creating new blocks.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render advanced section description.
	 */
	public function render_advanced_section_description() {
		echo '<p>' . esc_html__( 'Advanced configuration options for debugging and performance.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render session duration field.
	 */
	public function render_session_duration_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$duration_value = isset( $settings['session_duration'] ) ? $settings['session_duration'] : 24;
		$duration_unit  = isset( $settings['session_duration_unit'] ) ? $settings['session_duration_unit'] : 'hours';
		?>
		<div class="session-duration-container">
			<input type="number"
				name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[session_duration]"
				id="session_duration"
				value="<?php echo esc_attr( $duration_value ); ?>"
				min="<?php echo esc_attr( self::SESSION_DURATION_MIN ); ?>"
				max="<?php echo esc_attr( self::SESSION_DURATION_MAX ); ?>"
				class="small-text"
				style="margin-right: 8px;"
			/>
			<select name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[session_duration_unit]" id="session_duration_unit" style="margin-right: 8px;">
				<option value="minutes" <?php selected( $duration_unit, 'minutes' ); ?>><?php esc_html_e( 'minutes', 'password-protect-elite' ); ?></option>
				<option value="hours" <?php selected( $duration_unit, 'hours' ); ?>><?php esc_html_e( 'hours', 'password-protect-elite' ); ?></option>
				<option value="days" <?php selected( $duration_unit, 'days' ); ?>><?php esc_html_e( 'days', 'password-protect-elite' ); ?></option>
			</select>
		</div>
		<p class="description"><?php esc_html_e( 'How long a user session remains valid before requiring re-authentication.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render block styles section description.
	 */
	public function render_block_styles_section_description() {
		echo '<p>' . esc_html__( 'Configure which front-end styles are loaded for the plugin blocks.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render block styles field.
	 */
	public function render_block_styles_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['block_styles_mode'] ) ? $settings['block_styles_mode'] : 'all';
		?>
		<select name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[block_styles_mode]" id="block_styles_mode">
			<option value="all" <?php selected( $value, 'all' ); ?>><?php esc_html_e( 'All - Load all built-in styles', 'password-protect-elite' ); ?></option>
			<option value="essential" <?php selected( $value, 'essential' ); ?>><?php esc_html_e( 'Essential - Load only basic layout styles', 'password-protect-elite' ); ?></option>
			<option value="none" <?php selected( $value, 'none' ); ?>><?php esc_html_e( 'None - Disable all front-end styles', 'password-protect-elite' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose which styles to load on the front-end. "Essential" includes only non-opinionated layout styles.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render global strings section description.
	 */
	public function render_global_strings_section_description() {
		echo '<p>' . esc_html__( 'Customize the default text strings used in the plugin blocks. These will be used as defaults when creating new blocks.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render string field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_string_field( $args ) {
		$string_key = $args['string_key'];
		$string_config = $args['string_config'];
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value = isset( $settings[ $string_key ] ) ? $settings[ $string_key ] : $string_config['default'];
		?>
		<input type="text"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[<?php echo esc_attr( $string_key ); ?>]"
			id="<?php echo esc_attr( $string_key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $string_config['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $string_config['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render color customization section description.
	 */
	public function render_color_customization_section_description() {
		echo '<p>' . esc_html__( 'Customize the colors used in the "All" style mode. These settings only apply when Block Styles Mode is set to "All".', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render primary color field.
	 */
	public function render_primary_color_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['primary_color'] ) ? $settings['primary_color'] : '#0073aa';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[primary_color]"
			id="primary_color"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Main color for buttons and focus states.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render primary color hover field.
	 */
	public function render_primary_color_hover_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['primary_color_hover'] ) ? $settings['primary_color_hover'] : '#005177';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[primary_color_hover]"
			id="primary_color_hover"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Color for button hover states.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render border color field.
	 */
	public function render_border_color_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['border_color'] ) ? $settings['border_color'] : '#e1e5e9';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[border_color]"
			id="border_color"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Color for input borders.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render background color field.
	 */
	public function render_background_color_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['background_color'] ) ? $settings['background_color'] : '#fff3cd';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[background_color]"
			id="background_color"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Background color for protected content areas.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render success color field.
	 */
	public function render_success_color_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['success_color'] ) ? $settings['success_color'] : '#d4edda';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[success_color]"
			id="success_color"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Background color for success messages.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render error color field.
	 */
	public function render_error_color_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['error_color'] ) ? $settings['error_color'] : '#f8d7da';
		?>
		<input type="color"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[error_color]"
			id="error_color"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Background color for error messages.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render additional settings section description.
	 */
	public function render_additional_settings_section_description() {
		echo '<p>' . esc_html__( 'Additional configuration options for the plugin.', 'password-protect-elite' ) . '</p>';
	}

	/**
	 * Render debug mode field.
	 */
	public function render_debug_mode_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value    = isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : false;
		?>
		<label>
			<input type="checkbox"
				name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[debug_mode]"
				value="1"
				<?php checked( $value, 1 ); ?>
			/>
			<?php esc_html_e( 'Enable debug mode for troubleshooting', 'password-protect-elite' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, additional debug information will be logged and displayed.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render auto clear cache field.
	 */
	public function render_auto_clear_cache_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value = isset( $settings['auto_clear_cache'] ) ? $settings['auto_clear_cache'] : true;
		?>
		<label>
			<input type="checkbox"
				name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[auto_clear_cache]"
				value="1"
				<?php checked( $value, 1 ); ?>
			/>
			<?php esc_html_e( 'Automatically clear cache when settings change', 'password-protect-elite' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Automatically clear relevant caches when plugin settings are updated.', 'password-protect-elite' ); ?></p>
		<?php
	}

	/**
	 * Render password attempts limit field.
	 */
	public function render_password_attempts_limit_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value = isset( $settings['password_attempts_limit'] ) ? $settings['password_attempts_limit'] : 5;
		?>
		<input type="number"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[password_attempts_limit]"
			id="password_attempts_limit"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( self::PASSWORD_ATTEMPTS_LIMIT_MIN ); ?>"
			max="<?php echo esc_attr( self::PASSWORD_ATTEMPTS_LIMIT_MAX ); ?>"
			class="small-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %1$d: minimum value, %2$d: maximum value */
				esc_html__( 'Maximum number of failed password attempts before temporary lockout (%1$d-%2$d). Set to 0 to disable lockout feature and allow unlimited attempts.', 'password-protect-elite' ),
				self::PASSWORD_ATTEMPTS_LIMIT_MIN,
				self::PASSWORD_ATTEMPTS_LIMIT_MAX
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render lockout duration field.
	 */
	public function render_lockout_duration_field() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$value = isset( $settings['lockout_duration'] ) ? $settings['lockout_duration'] : 15;
		?>
		<input type="number"
			name="<?php echo esc_attr( self::SETTINGS_GROUP ); ?>[lockout_duration]"
			id="lockout_duration"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( self::LOCKOUT_DURATION_MIN ); ?>"
			max="<?php echo esc_attr( self::LOCKOUT_DURATION_MAX ); ?>"
			class="small-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %1$d: minimum value, %2$d: maximum value */
				esc_html__( 'Duration of temporary lockout in minutes (%1$d-%2$d).', 'password-protect-elite' ),
				self::LOCKOUT_DURATION_MIN,
				self::LOCKOUT_DURATION_MAX
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render general tab content.
	 */
	public function render_general_tab() {
		?>
		<div class="ppe-tab-content">
			<?php $this->render_general_section_description(); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="session_duration"><?php esc_html_e( 'Session Duration', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_session_duration_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="password_attempts_limit"><?php esc_html_e( 'Password Attempts Limit', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_password_attempts_limit_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="lockout_duration"><?php esc_html_e( 'Lockout Duration (minutes)', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_lockout_duration_field(); ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render appearance tab content.
	 */
	public function render_appearance_tab() {
		?>
		<div class="ppe-tab-content">
			<?php $this->render_appearance_section_description(); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="block_styles_mode"><?php esc_html_e( 'Block Styles Mode', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_block_styles_field(); ?>
					</td>
				</tr>
			</table>

			<div id="ppe-color-desc" style="<?php $settings = get_option( self::SETTINGS_GROUP, array() ); echo ( isset( $settings['block_styles_mode'] ) && 'all' !== $settings['block_styles_mode'] ) ? 'display:none;' : ''; ?>">
				<?php $this->render_color_customization_section_description(); ?>
			</div>

			<table class="form-table" role="presentation">
				<tbody id="ppe-color-fields" style="<?php $settings = get_option( self::SETTINGS_GROUP, array() ); echo ( isset( $settings['block_styles_mode'] ) && 'all' !== $settings['block_styles_mode'] ) ? 'display:none;' : ''; ?>">
				<tr>
					<th scope="row">
						<label for="primary_color"><?php esc_html_e( 'Primary Color', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_primary_color_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="primary_color_hover"><?php esc_html_e( 'Primary Color (Hover)', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_primary_color_hover_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="border_color"><?php esc_html_e( 'Border Color', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_border_color_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="background_color"><?php esc_html_e( 'Background Color', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_background_color_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="success_color"><?php esc_html_e( 'Success Message Color', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_success_color_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="error_color"><?php esc_html_e( 'Error Message Color', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_error_color_field(); ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render messages tab content.
	 */
	public function render_messages_tab() {
		?>
		<div class="ppe-tab-content ppe-messages-tab">
			<?php $this->render_messages_section_description(); ?>

			<?php
			// Render string fields.
			$string_manager = new StringManager();
			$customizable_strings = $string_manager->get_customizable_strings();
			?>

			<table class="form-table ppe-messages-table" role="presentation">
				<?php foreach ( $customizable_strings as $key => $string_config ) : ?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $string_config['label'] ); ?></label>
						</th>
						<td>
							<?php $this->render_string_field( array( 'string_key' => $key, 'string_config' => $string_config ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render advanced tab content.
	 */
	public function render_advanced_tab() {
		?>
		<div class="ppe-tab-content">
			<?php $this->render_advanced_section_description(); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="debug_mode"><?php esc_html_e( 'Debug Mode', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_debug_mode_field(); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="auto_clear_cache"><?php esc_html_e( 'Auto Clear Cache', 'password-protect-elite' ); ?></label>
					</th>
					<td>
						<?php $this->render_auto_clear_cache_field(); ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Sanitize settings data.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		// Add success message when settings are saved.
		add_settings_error(
			'ppe_settings_messages',
			'ppe_settings_saved',
			__( 'Settings saved successfully!', 'password-protect-elite' ),
			'updated'
		);
		$sanitized = array();

		// Sanitize block styles mode.
		if ( isset( $input['block_styles_mode'] ) ) {
			$allowed_modes = array( 'all', 'essential', 'none' );
			$sanitized['block_styles_mode'] = in_array( $input['block_styles_mode'], $allowed_modes, true )
				? $input['block_styles_mode']
				: 'all';
		}

		// Sanitize string fields.
		$string_manager = new StringManager();
		$customizable_strings = $string_manager->get_customizable_strings();

		foreach ( $customizable_strings as $key => $string_config ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		// Sanitize color settings.
		$color_fields = array(
			'primary_color',
			'primary_color_hover',
			'border_color',
			'background_color',
			'success_color',
			'error_color',
		);

		foreach ( $color_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
			}
		}

		// Sanitize additional settings.
		$sanitized['debug_mode']        = isset( $input['debug_mode'] ) ? 1 : 0;
		$sanitized['auto_clear_cache']  = isset( $input['auto_clear_cache'] ) ? 1 : 0;

		if ( isset( $input['password_attempts_limit'] ) ) {
			$sanitized['password_attempts_limit'] = max( self::PASSWORD_ATTEMPTS_LIMIT_MIN, min( self::PASSWORD_ATTEMPTS_LIMIT_MAX, absint( $input['password_attempts_limit'] ) ) );
		}

		if ( isset( $input['lockout_duration'] ) ) {
			$sanitized['lockout_duration'] = max( self::LOCKOUT_DURATION_MIN, min( self::LOCKOUT_DURATION_MAX, absint( $input['lockout_duration'] ) ) );
		}

		if ( isset( $input['session_duration'] ) ) {
			$sanitized['session_duration'] = max( self::SESSION_DURATION_MIN, min( self::SESSION_DURATION_MAX, absint( $input['session_duration'] ) ) );
		}

		if ( isset( $input['session_duration_unit'] ) ) {
			$allowed_units = array( 'minutes', 'hours', 'days' );
			$sanitized['session_duration_unit'] = in_array( $input['session_duration_unit'], $allowed_units, true )
				? $input['session_duration_unit']
				: 'hours';
		}

		return $sanitized;
	}

	/**
	 * Convert session duration to hours for internal use.
	 *
	 * @param int    $duration The duration value.
	 * @param string $unit The duration unit (minutes, hours, days).
	 * @return int Duration in hours.
	 */
	public static function convert_session_duration_to_hours( $duration, $unit ) {
		switch ( $unit ) {
			case 'minutes':
				return max( 1, round( $duration / 60 ) );
			case 'hours':
				return max( 1, $duration );
			case 'days':
				return max( 1, $duration * 24 );
			default:
				return max( 1, $duration );
		}
	}

	/**
	 * Get session duration in hours from settings.
	 *
	 * @return int Session duration in hours.
	 */
	public static function get_session_duration_hours() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$duration = isset( $settings['session_duration'] ) ? $settings['session_duration'] : 24;
		$unit     = isset( $settings['session_duration_unit'] ) ? $settings['session_duration_unit'] : 'hours';
		return self::convert_session_duration_to_hours( $duration, $unit );
	}

	/**
	 * Get max failed password attempts before lockout.
	 *
	 * @return int Returns 0 if disabled, otherwise 1-20.
	 */
	public static function get_password_attempts_limit() {
		$settings = get_option( self::SETTINGS_GROUP, array() );
		$limit    = isset( $settings['password_attempts_limit'] ) ? absint( $settings['password_attempts_limit'] ) : 5;
		return max( self::PASSWORD_ATTEMPTS_LIMIT_MIN, min( self::PASSWORD_ATTEMPTS_LIMIT_MAX, $limit ) );
	}

	/**
	 * Get lockout duration in minutes.
	 *
	 * @return int
	 */
	public static function get_lockout_duration_minutes() {
		$settings  = get_option( self::SETTINGS_GROUP, array() );
		$duration  = isset( $settings['lockout_duration'] ) ? absint( $settings['lockout_duration'] ) : 15;
		return max( self::LOCKOUT_DURATION_MIN, min( self::LOCKOUT_DURATION_MAX, $duration ) );
	}

	/**
	 * Enqueue admin scripts for settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'ppe_password_group_page_' . self::PAGE_SLUG === $hook ) {
			wp_enqueue_style( 'ppe-settings-css', PPE_PLUGIN_URL . 'assets/admin/css/settings.css', array(), PPE_VERSION );
			wp_enqueue_script( 'ppe-settings-js', PPE_PLUGIN_URL . 'assets/admin/js/settings.js', array( 'jquery' ), PPE_VERSION, true );
		}
	}
}
