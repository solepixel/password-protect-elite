<?php
/**
 * Block Styles management.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles front-end block styles loading based on settings.
 */
class BlockStyles {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_block_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_styles' ] );
	}

	/**
	 * Enqueue block styles based on settings.
	 */
	public function enqueue_block_styles() {
		$settings = get_option( 'ppe_settings', [] );
		$mode     = $settings['block_styles_mode'] ?? 'all';

		// Load styles based on mode.
		switch ( $mode ) {
			case 'none':
				// No styles loaded - user must define all styles in theme.
				break;
			case 'essential':
				$this->enqueue_essential_styles();
				break;
			case 'all':
			default:
				$this->enqueue_all_styles();
				break;
		}
	}

	/**
	 * Enqueue editor styles.
	 */
	public function enqueue_editor_styles() {
		// Always load editor styles for proper block editing experience.
		wp_enqueue_style(
			'ppe-blocks-editor',
			PPE_PLUGIN_URL . 'src/blocks/password-entry/editor.css',
			[],
			PPE_VERSION
		);

		wp_enqueue_style(
			'ppe-protected-content-editor',
			PPE_PLUGIN_URL . 'src/blocks/protected-content/editor.css',
			[],
			PPE_VERSION
		);
	}

	/**
	 * Enqueue essential styles (layout only).
	 */
	private function enqueue_essential_styles() {
		// Load essential styles that provide layout without visual styling.
		wp_enqueue_style(
			'ppe-blocks-essential',
			PPE_PLUGIN_URL . 'assets/css/blocks-essential.css',
			[],
			PPE_VERSION
		);

		// Add essential-only CSS class to body.
		add_filter( 'body_class', [ $this, 'add_essential_styles_class' ] );
	}

	/**
	 * Enqueue all styles.
	 */
	private function enqueue_all_styles() {
		// Load full styles with all visual styling.
		wp_enqueue_style(
			'ppe-blocks-all',
			PPE_PLUGIN_URL . 'assets/css/blocks-all.css',
			[],
			PPE_VERSION
		);

		// Add custom colors as inline CSS.
		$this->add_custom_colors_css();

		// Add all-styles CSS class to body.
		add_filter( 'body_class', [ $this, 'add_all_styles_class' ] );
	}

	/**
	 * Add essential styles class to body.
	 *
	 * @param array $classes Body classes.
	 * @return array Modified body classes.
	 */
	public function add_essential_styles_class( $classes ) {
		$classes[] = 'ppe-essential-styles';
		return $classes;
	}

	/**
	 * Add all styles class to body.
	 *
	 * @param array $classes Body classes.
	 * @return array Modified body classes.
	 */
	public function add_all_styles_class( $classes ) {
		$classes[] = 'ppe-all-styles';
		return $classes;
	}

	/**
	 * Get current styles mode.
	 *
	 * @return string Current styles mode.
	 */
	public function get_styles_mode() {
		$settings = get_option( 'ppe_settings', [] );
		return $settings['block_styles_mode'] ?? 'all';
	}

	/**
	 * Check if styles are enabled.
	 *
	 * @return bool True if styles are enabled.
	 */
	public function are_styles_enabled() {
		return 'none' !== $this->get_styles_mode();
	}

	/**
	 * Get debug information about current styles mode.
	 *
	 * @return array Debug information.
	 */
	public function get_debug_info() {
		$settings = get_option( 'ppe_settings', [] );
		$mode     = $settings['block_styles_mode'] ?? 'all';

		return [
			'mode'           => $mode,
			'styles_enabled' => $this->are_styles_enabled(),
			'loaded_styles'  => $this->get_loaded_styles(),
		];
	}

	/**
	 * Get information about which styles are currently loaded.
	 *
	 * @return array Loaded styles information.
	 */
	private function get_loaded_styles() {
		global $wp_styles;
		$loaded = [];

		if ( isset( $wp_styles->registered ) ) {
			foreach ( $wp_styles->registered as $handle => $style ) {
				if ( strpos( $handle, 'ppe-blocks' ) === 0 ) {
					$loaded[] = $handle;
				}
			}
		}

		return $loaded;
	}

	/**
	 * Add custom colors as inline CSS.
	 */
	private function add_custom_colors_css() {
		$settings = get_option( 'ppe_settings', array() );

		// Get custom colors with defaults.
		$primary_color       = $settings['primary_color'] ?? '#0073aa';
		$primary_color_hover = $settings['primary_color_hover'] ?? '#005177';
		$border_color        = $settings['border_color'] ?? '#e1e5e9';
		$background_color    = $settings['background_color'] ?? '#fff3cd';
		$success_color       = $settings['success_color'] ?? '#d4edda';
		$error_color         = $settings['error_color'] ?? '#f8d7da';

		// Generate custom CSS.
		$custom_css = "
		.ppe-password-form .ppe-password-input:focus {
			border-color: {$primary_color};
			box-shadow: 0 0 0 3px " . $this->hex_to_rgba( $primary_color, 0.1 ) . ";
		}
		.ppe-password-form .ppe-submit-button {
			background: {$primary_color};
		}
		.ppe-password-form .ppe-submit-button:hover {
			background: {$primary_color_hover};
		}
		.ppe-password-form .ppe-password-input {
			border-color: {$border_color};
		}
		.ppe-protected-content-block.ppe-locked {
			background: {$background_color};
		}
		.ppe-password-form .ppe-message.ppe-success {
			background: {$success_color};
		}
		.ppe-password-form .ppe-message.ppe-error {
			background: {$error_color};
		}
		.ppe-protected-content-block.ppe-locked .ppe-password-form .ppe-password-input:focus {
			border-color: {$primary_color};
			box-shadow: 0 0 0 3px " . $this->hex_to_rgba( $primary_color, 0.1 ) . ";
		}
		.ppe-protected-content-block.ppe-locked .ppe-password-form .ppe-submit-button {
			background: {$primary_color};
		}
		.ppe-protected-content-block.ppe-locked .ppe-password-form .ppe-submit-button:hover {
			background: {$primary_color_hover};
		}
		.ppe-protected-content-block.ppe-locked .ppe-password-form .ppe-message.ppe-success {
			background: {$success_color};
		}
		.ppe-protected-content-block.ppe-locked .ppe-password-form .ppe-message.ppe-error {
			background: {$error_color};
		}
		";

		// Add inline CSS.
		wp_add_inline_style( 'ppe-blocks-all', $custom_css );
	}

	/**
	 * Convert hex color to rgba.
	 *
	 * @param string $hex Hex color.
	 * @param float  $alpha Alpha value.
	 * @return string RGBA color.
	 */
	private function hex_to_rgba( $hex, $alpha ) {
		$hex = ltrim( $hex, '#' );
		$r   = hexdec( substr( $hex, 0, 2 ) );
		$g   = hexdec( substr( $hex, 2, 2 ) );
		$b   = hexdec( substr( $hex, 4, 2 ) );
		return "rgba({$r}, {$g}, {$b}, {$alpha})";
	}
}
