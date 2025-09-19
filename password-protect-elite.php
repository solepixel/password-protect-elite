<?php
/**
 * Plugin Name: Password Protect Elite
 * Plugin URI: https://b7s.co/
 * Description: Advanced password protection for WordPress with multiple password groups, custom blocks, and flexible redirect options.
 * Version: 1.0.0
 * Author: Briantics, Inc.
 * Author URI: https://b7s.co/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: password-protect-elite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package PasswordProtectElite
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
const PPE_VERSION = '1.0.0';

// Plugin file.
define( 'PPE_PLUGIN_FILE', __FILE__ );

// Plugin path.
define( 'PPE_PLUGIN_PATH', plugin_dir_path( PPE_PLUGIN_FILE ) );

// Plugin URL.
define( 'PPE_PLUGIN_URL', plugin_dir_url( PPE_PLUGIN_FILE ) );

// Plugin basename.
define( 'PPE_PLUGIN_BASENAME', plugin_basename( PPE_PLUGIN_FILE ) );

// Load Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Helper functions.
require_once 'includes/helpers.php';

// Initialize the plugin.
ppelite();
