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

		// Front-end Block Styles section.
		add_settings_section(
			'ppe_block_styles_section',
			__( 'Front-end Block Styles', 'password-protect-elite' ),
			array( $this, 'render_block_styles_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'block_styles_mode',
			__( 'Block Styles Mode', 'password-protect-elite' ),
			array( $this, 'render_block_styles_field' ),
			self::PAGE_SLUG,
			'ppe_block_styles_section'
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

		// Global Strings section.
		add_settings_section(
			'ppe_global_strings_section',
			__( 'Global Text Strings', 'password-protect-elite' ),
			array( $this, 'render_global_strings_section_description' ),
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
				'ppe_global_strings_section',
				array( 'string_key' => $key, 'string_config' => $string_config )
			);
		}

		// Additional Settings section.
		add_settings_section(
			'ppe_additional_settings_section',
			__( 'Additional Settings', 'password-protect-elite' ),
			array( $this, 'render_additional_settings_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'password-protect-elite' ),
			array( $this, 'render_debug_mode_field' ),
			self::PAGE_SLUG,
			'ppe_additional_settings_section'
		);

		add_settings_field(
			'auto_clear_cache',
			__( 'Auto Clear Cache', 'password-protect-elite' ),
			array( $this, 'render_auto_clear_cache_field' ),
			self::PAGE_SLUG,
			'ppe_additional_settings_section'
		);

		add_settings_field(
			'password_attempts_limit',
			__( 'Password Attempts Limit', 'password-protect-elite' ),
			array( $this, 'render_password_attempts_limit_field' ),
			self::PAGE_SLUG,
			'ppe_additional_settings_section'
		);

		add_settings_field(
			'lockout_duration',
			__( 'Lockout Duration (minutes)', 'password-protect-elite' ),
			array( $this, 'render_lockout_duration_field' ),
			self::PAGE_SLUG,
			'ppe_additional_settings_section'
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			// Display any settings errors or success messages.
			settings_errors( 'ppe_settings_messages' );
			?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
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
			min="1"
			max="20"
			class="small-text"
		/>
		<p class="description"><?php esc_html_e( 'Maximum number of failed password attempts before temporary lockout (1-20).', 'password-protect-elite' ); ?></p>
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
			min="1"
			max="1440"
			class="small-text"
		/>
		<p class="description"><?php esc_html_e( 'Duration of temporary lockout in minutes (1-1440).', 'password-protect-elite' ); ?></p>
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
		$sanitized['debug_mode'] = isset( $input['debug_mode'] ) ? 1 : 0;
		$sanitized['auto_clear_cache'] = isset( $input['auto_clear_cache'] ) ? 1 : 0;

		if ( isset( $input['password_attempts_limit'] ) ) {
			$sanitized['password_attempts_limit'] = max( 1, min( 20, absint( $input['password_attempts_limit'] ) ) );
		}

		if ( isset( $input['lockout_duration'] ) ) {
			$sanitized['lockout_duration'] = max( 1, min( 1440, absint( $input['lockout_duration'] ) ) );
		}

		return $sanitized;
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
