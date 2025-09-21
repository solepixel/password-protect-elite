<?php
/**
 * Access control utilities for unauthenticated behavior handling.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * AccessController class.
 */
class AccessController {

	/**
	 * Handle unauthenticated behavior for a password group.
	 *
	 * @param object   $password_group        Password group object.
	 * @param callable $show_dialog_callback  Callback to render/show the dialog when behavior is 'show_dialog' or misconfigured.
	 * @return void
	 */
	public static function handle_unauthenticated_behavior( $password_group, $show_dialog_callback ) {
		if ( ! $password_group ) {
			// If group missing, default to 404.
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		$behavior = get_post_meta( $password_group->id, '_ppe_unauthenticated_behavior', true );
		if ( empty( $behavior ) ) {
			$behavior = 'show_404';
		}

		if ( 'redirect' === $behavior ) {
			$page_id = absint( get_post_meta( $password_group->id, '_ppe_unauthenticated_redirect_page_id', true ) );
			$custom  = esc_url_raw( get_post_meta( $password_group->id, '_ppe_unauthenticated_redirect_custom_url', true ) );
			if ( $page_id ) {
				wp_safe_redirect( get_permalink( $page_id ) );
				exit;
			}
			if ( ! empty( $custom ) ) {
				wp_safe_redirect( $custom );
				exit;
			}
			// If redirect configured but invalid, fall through to dialog.
		}

		if ( 'show_dialog' === $behavior ) {
			call_user_func( $show_dialog_callback );
			return;
		}

		// Default: 404 for unauthenticated access.
		status_header( 404 );
		nocache_headers();
		include get_query_template( '404' );
		exit;
	}
}
