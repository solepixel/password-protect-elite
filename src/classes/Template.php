<?php
/**
 * Template loader class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template class for loading view files.
 */
class Template {

	/**
	 * Get the path to a view file.
	 *
	 * Allows themes and plugins to override view paths via filter.
	 *
	 * @param string $view_name The view file name (e.g., 'help-page' or 'admin/settings').
	 * @return string The path to the view file.
	 */
	public function get_view( $view_name ) {
		// Ensure .php extension is present.
		if ( '.php' !== substr( $view_name, -4 ) ) {
			$view_name .= '.php';
		}

		// Default view path.
		$view_path = PPE_PLUGIN_PATH . 'views/' . $view_name;

		/**
		 * Filter the view file path.
		 *
		 * Allows themes and plugins to override view file paths.
		 *
		 * @param string $view_path The default view file path.
		 * @param string $view_name The view file name.
		 */
		$view_path = apply_filters( 'ppe_view_path', $view_path, $view_name );

		return $view_path;
	}

	/**
	 * Load a view file with optional variables.
	 *
	 * Variables are extracted using extract() to make them available in the view.
	 * For complex uses, use get_view() to get the path and include it manually.
	 *
	 * @param string $view_name The view file name (e.g., 'help-page' or 'admin/settings').
	 * @param array  $vars      Variables to pass to the view (optional).
	 * @return void
	 */
	public function load_view( $view_name, $vars = [] ) {
		$view_path = $this->get_view( $view_name );

		if ( ! file_exists( $view_path ) ) {
			return;
		}

		// Extract variables to make them available in the view.
		if ( ! empty( $vars ) ) {
			extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		include $view_path;
	}

	/**
	 * Get the rendered output of a view file as a string.
	 *
	 * @param string $view_name The view file name (e.g., 'help-page' or 'admin/settings').
	 * @param array  $vars      Variables to pass to the view (optional).
	 * @return string The rendered view output.
	 */
	public function render_view( $view_name, $vars = [] ) {
		ob_start();
		$this->load_view( $view_name, $vars );
		return ob_get_clean();
	}
}

