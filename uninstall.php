<?php
/**
 * Uninstall file for Password Protect Elite.
 *
 * This file is executed when the plugin is deleted through the WordPress admin.
 * By default, it does NOT delete any data to prevent accidental data loss.
 *
 * To enable data deletion, you must define the constant PPE_DELETE_DATA_ON_UNINSTALL
 * before deleting the plugin. You can do this by:
 * 1. Adding this line to wp-config.php: define( 'PPE_DELETE_DATA_ON_UNINSTALL', true );
 * 2. Or by setting it in your theme's functions.php temporarily
 *
 * @package PasswordProtectElite
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if explicitly requested via constant.
if ( ! defined( 'PPE_DELETE_DATA_ON_UNINSTALL' ) || ! PPE_DELETE_DATA_ON_UNINSTALL ) {
	// Data preservation mode - do not delete anything.
	// This prevents accidental data loss when deleting/reinstalling the plugin.
	return;
}

// Remove plugin options.
delete_option( 'ppe_version' );
delete_option( 'ppe_db_version' );
delete_option( 'ppe_global_protection' );
delete_option( 'ppe_global_password' );
delete_option( 'ppe_global_redirect' );
delete_option( 'ppe_settings' );

// Remove password group posts (Custom Post Type).
$password_groups = get_posts(
	[
		'post_type'      => 'ppe_password_group',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

foreach ( $password_groups as $group_id ) {
	wp_delete_post( $group_id, true ); // Force delete.
}

// Clear any cached data.
wp_cache_flush();
