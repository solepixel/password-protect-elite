<?php
/**
 * Plugin Name: Password Protect Elite
 * Plugin URI: https://b7s.co/
 * Description: Advanced password protection for WordPress with multiple password groups, custom blocks, and flexible redirect options.
 * Version: 1.1.4
 * Author: Briantics, Inc.
 * Author URI: https://b7s.co/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: password-protect-elite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * Network: false
 * GitHub Plugin URI: https://github.com/solepixel/password-protect-elite
 *
 * @package PasswordProtectElite
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
const PPE_VERSION = '1.1.4';

// Plugin file.
define( 'PPE_PLUGIN_FILE', __FILE__ );

// Plugin directory.
define( 'PPE_PLUGIN_DIR', __DIR__ );

// Plugin path.
define( 'PPE_PLUGIN_PATH', plugin_dir_path( PPE_PLUGIN_FILE ) );

// Plugin URL.
define( 'PPE_PLUGIN_URL', plugin_dir_url( PPE_PLUGIN_FILE ) );

// Plugin basename.
define( 'PPE_PLUGIN_BASENAME', plugin_basename( PPE_PLUGIN_FILE ) );

// Load Composer autoloader.
if ( file_exists( PPE_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once PPE_PLUGIN_DIR . '/vendor/autoload.php';
}

// Helper functions.
require_once 'includes/helpers.php';

// GitHub updater class.
require_once 'includes/class-github-updater.php';

// Initialize the plugin.
ppelite();

// Initialize GitHub updater.
if ( is_admin() ) {
	// GitHub repository details for automatic updates.
	$github_updater = new PPE_GitHub_Updater(
		PPE_PLUGIN_FILE,
		'solepixel', // GitHub username.
		'password-protect-elite', // GitHub repository name.
		'' // GitHub token (leave empty for public repos).
	);
}
