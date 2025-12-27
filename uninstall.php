<?php
/**
 * Uninstall file for Password Protect Elite.
 *
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package PasswordProtectElite
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'ppe_version' );
delete_option( 'ppe_db_version' );
delete_option( 'ppe_global_protection' );
delete_option( 'ppe_global_password' );
delete_option( 'ppe_global_redirect' );

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
