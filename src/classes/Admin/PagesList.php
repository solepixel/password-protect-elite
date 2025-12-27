<?php
/**
 * Pages List Screen enhancements.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PasswordProtectElite\Database;
use PasswordProtectElite\UrlMatcher;

/**
 * Handles Pages list screen modifications.
 */
class PagesList {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add Protection column after Title.
		add_filter( 'manage_pages_columns', array( $this, 'add_protection_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_protection_column' ), 10, 2 );

		// Add lock icon after post title using display_post_states filter (allows HTML).
		add_filter( 'display_post_states', array( $this, 'add_lock_icon_state' ), 10, 2 );
	}

	/**
	 * Add Protection column after Title column.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_protection_column( $columns ) {
		// Insert Protection column after Title.
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['ppe_protection'] = __( 'Protection', 'password-protect-elite' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render Protection column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function render_protection_column( $column_name, $post_id ) {
		if ( 'ppe_protection' !== $column_name ) {
			return;
		}

		$protection_info = $this->get_page_protection_info( $post_id );

		if ( ! $protection_info['is_protected'] ) {
			echo '<span aria-label="' . esc_attr__( 'Not protected', 'password-protect-elite' ) . '">—</span>';
			return;
		}

		// Display protection status matching the meta box "Current Protection" text.
		$display_text = $protection_info['display_text'];
		echo '<span class="ppe-protection-status">' . esc_html( $display_text ) . '</span>';

		if ( $protection_info['is_auto_protected'] ) {
			echo '<br><em style="font-size: 11px; color: #666;">' . esc_html__( 'Auto-protected via URL rules', 'password-protect-elite' ) . '</em>';
		}
	}

	/**
	 * Add lock icon state after post title in list table.
	 * Uses display_post_states filter which allows HTML output.
	 *
	 * @param array   $post_states Array of post display states.
	 * @param WP_Post $post        Current post object.
	 * @return array Modified post states.
	 */
	public function add_lock_icon_state( $post_states, $post ) {
		// Only modify for pages.
		if ( 'page' !== $post->post_type ) {
			return $post_states;
		}

		// Check if Protection column has a value (page is protected).
		$protection_info = $this->get_page_protection_info( $post->ID );

		if ( $protection_info['is_protected'] ) {
			// Add lock icon using dashicon (HTML is allowed in post states).
			$post_states['ppe_protected'] = '<span class="dashicons dashicons-lock ppe-lock-icon" title="' . esc_attr__( 'This page is password protected', 'password-protect-elite' ) . '"></span>';
		}

		return $post_states;
	}

	/**
	 * Get protection information for a page.
	 *
	 * @param int $page_id Page ID.
	 * @return array Protection information.
	 */
	private function get_page_protection_info( $page_id ) {
		$info = array(
			'is_protected'      => false,
			'is_auto_protected' => false,
			'display_text'      => '',
		);

		// Check direct protection via password group.
		$page_protection = Database::get_page_protection( $page_id );
		if ( $page_protection ) {
			$info['is_protected'] = true;
			$info['display_text']  = $page_protection->group_name;
			return $info;
		}

		// Check access mode (roles/caps).
		$access_mode = get_post_meta( $page_id, '_ppe_access_mode', true );
		if ( 'roles' === $access_mode ) {
			$roles = get_post_meta( $page_id, '_ppe_access_roles', true );
			$roles = is_array( $roles ) ? $roles : array();
			if ( ! empty( $roles ) ) {
				$info['is_protected'] = true;
				$role_names           = array();
				$editable_roles       = function_exists( 'get_editable_roles' ) ? get_editable_roles() : array();
				foreach ( $roles as $role_slug ) {
					if ( isset( $editable_roles[ $role_slug ] ) ) {
						$role_names[] = translate_user_role( $editable_roles[ $role_slug ]['name'] );
					}
				}
				$info['display_text'] = ! empty( $role_names )
					? sprintf( __( 'Role-based: %s', 'password-protect-elite' ), implode( ', ', $role_names ) )
					: __( 'Role-based Access', 'password-protect-elite' );
				return $info;
			}
		}

		if ( 'caps' === $access_mode ) {
			$caps = get_post_meta( $page_id, '_ppe_access_caps', true );
			$caps = is_array( $caps ) ? $caps : array();
			if ( ! empty( $caps ) ) {
				$info['is_protected'] = true;
				$info['display_text'] = sprintf( __( 'Capability-based: %s', 'password-protect-elite' ), implode( ', ', $caps ) );
				return $info;
			}
		}

		// Check indirect protection via auto-protect URL patterns (child URL).
		$page_url = get_permalink( $page_id );
		if ( $page_url ) {
			$all_groups = Database::get_password_groups();
			foreach ( $all_groups as $group ) {
				$auto_patterns = get_post_meta( $group->id, '_ppe_auto_protect_urls', true );
				if ( empty( $auto_patterns ) ) {
					continue;
				}
				if ( UrlMatcher::url_matches_patterns( $page_url, $auto_patterns ) ) {
					$group_exclude_urls = get_post_meta( $group->id, '_ppe_exclude_urls', true );
					if ( ! empty( $group_exclude_urls ) && UrlMatcher::url_matches_patterns( $page_url, $group_exclude_urls ) ) {
						continue; // Excluded for this group.
					}
					$info['is_protected']      = true;
					$info['is_auto_protected'] = true;
					$info['display_text']      = $group->name;
					return $info;
				}
			}
		}

		return $info;
	}
}

