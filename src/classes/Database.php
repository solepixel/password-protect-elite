<?php
/**
 * Database management class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database operations for password groups.
 */
class Database {

	/**
	 * Get all password groups.
	 *
	 * @param string $type Optional protection type filter.
	 * @return array
	 */
	public static function get_password_groups( $type = null ) {
		$args = [
			'post_type'      => 'ppe_password_group',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => [],
		];

		if ( $type ) {
			$args['meta_query'][] = [
				'key'     => '_ppe_protection_type',
				'value'   => $type,
				'compare' => '=',
			];
		}

		$posts  = get_posts( $args );
		$groups = [];

		foreach ( $posts as $post ) {
			$group = self::get_password_group( $post->ID );
			if ( $group ) {
				$groups[] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Get password group by ID.
	 *
	 * @param int $id Password group ID.
	 * @return object|null
	 */
	public static function get_password_group( $id ) {
		$post = get_post( $id );

		if ( ! $post || 'ppe_password_group' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$group = (object) [
			'id'                   => $post->ID,
			'name'                 => $post->post_title,
			'description'          => $post->post_content,
			'master_password'      => get_post_meta( $post->ID, '_ppe_master_password', true ),
			'additional_passwords' => get_post_meta( $post->ID, '_ppe_additional_passwords', true ),
			'protection_type'      => get_post_meta( $post->ID, '_ppe_protection_type', true ),
			'redirect_type'        => get_post_meta( $post->ID, '_ppe_redirect_type', true ),
			'redirect_page_id'     => get_post_meta( $post->ID, '_ppe_redirect_page_id', true ),
			'redirect_custom_url'  => get_post_meta( $post->ID, '_ppe_redirect_custom_url', true ),
			'allowed_roles'        => get_post_meta( $post->ID, '_ppe_allowed_roles', true ),
		];

		if ( empty( $group->additional_passwords ) || ! \is_array( $group->additional_passwords ) ) {
			$group->additional_passwords = [];
		}

		if ( empty( $group->allowed_roles ) || ! \is_array( $group->allowed_roles ) ) {
			$group->allowed_roles = [];
		}

		return $group;
	}

	/**
	 * Create password group.
	 *
	 * @param array $data Password group data.
	 * @return int|false Password group ID or false on failure.
	 */
	public static function create_password_group( $data ) {
		$defaults = [
			'name'                 => '',
			'description'          => '',
			'master_password'      => '',
			'additional_passwords' => [],
			'protection_type'      => 'general',
			'redirect_type'        => 'none',
			'redirect_page_id'     => 0,
			'redirect_custom_url'  => '',
		];

		$data = wp_parse_args( $data, $defaults );

		// Create the post.
		$post_data = [
			'post_title'   => sanitize_text_field( $data['name'] ),
			'post_content' => sanitize_textarea_field( $data['description'] ),
			'post_type'    => 'ppe_password_group',
			'post_status'  => 'publish',
		];

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Save meta fields.
		update_post_meta( $post_id, '_ppe_master_password', sanitize_text_field( $data['master_password'] ) );
		update_post_meta( $post_id, '_ppe_additional_passwords', array_map( 'sanitize_text_field', (array) $data['additional_passwords'] ) );
		update_post_meta( $post_id, '_ppe_protection_type', sanitize_text_field( $data['protection_type'] ) );
		update_post_meta( $post_id, '_ppe_redirect_type', sanitize_text_field( $data['redirect_type'] ) );
		update_post_meta( $post_id, '_ppe_redirect_page_id', absint( $data['redirect_page_id'] ) );
		update_post_meta( $post_id, '_ppe_redirect_custom_url', esc_url_raw( $data['redirect_custom_url'] ) );

		return $post_id;
	}

	/**
	 * Update password group.
	 *
	 * @param int   $id   Password group ID.
	 * @param array $data Password group data.
	 * @return bool
	 */
	public static function update_password_group( $id, $data ) {
		// Update the post.
		$post_data = [];
		if ( isset( $data['name'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['description'] ) ) {
			$post_data['post_content'] = sanitize_textarea_field( $data['description'] );
		}

		if ( ! empty( $post_data ) ) {
			$post_data['ID'] = $id;
			$result          = wp_update_post( $post_data );
			if ( is_wp_error( $result ) ) {
				return false;
			}
		}

		// Update meta fields.
		if ( isset( $data['master_password'] ) ) {
			update_post_meta( $id, '_ppe_master_password', sanitize_text_field( $data['master_password'] ) );
		}
		if ( isset( $data['additional_passwords'] ) ) {
			update_post_meta( $id, '_ppe_additional_passwords', array_map( 'sanitize_text_field', (array) $data['additional_passwords'] ) );
		}
		if ( isset( $data['protection_type'] ) ) {
			update_post_meta( $id, '_ppe_protection_type', sanitize_text_field( $data['protection_type'] ) );
		}
		if ( isset( $data['redirect_type'] ) ) {
			update_post_meta( $id, '_ppe_redirect_type', sanitize_text_field( $data['redirect_type'] ) );
		}
		if ( isset( $data['redirect_page_id'] ) ) {
			update_post_meta( $id, '_ppe_redirect_page_id', absint( $data['redirect_page_id'] ) );
		}
		if ( isset( $data['redirect_custom_url'] ) ) {
			update_post_meta( $id, '_ppe_redirect_custom_url', esc_url_raw( $data['redirect_custom_url'] ) );
		}

		return true;
	}

	/**
	 * Delete password group.
	 *
	 * @param int $id Password group ID.
	 * @return bool
	 */
	public static function delete_password_group( $id ) {
		// Move to trash instead of hard delete.
		$result = wp_trash_post( $id );
		return ! is_wp_error( $result );
	}

	/**
	 * Validate password.
	 *
	 * @param string $password Password to validate.
	 * @param string $type     Protection type (optional).
	 * @return object|null Password group or null if invalid.
	 */
	public static function validate_password( $password, $type = null ) {
		$groups = self::get_password_groups( $type );

		foreach ( $groups as $group ) {
			// Check master password.
			if ( $group->master_password === $password ) {
				return $group;
			}

			// Check additional passwords.
			if ( ! empty( $group->additional_passwords ) && \is_array( $group->additional_passwords ) ) {
				if ( \in_array( $password, $group->additional_passwords, true ) ) {
					return $group;
				}
			}
		}

		return null;
	}

	/**
	 * Get page protection for a specific page.
	 *
	 * @param int $page_id Page ID.
	 * @return object|null Page protection data or null if not protected.
	 */
	public static function get_page_protection( $page_id ) {
		$password_group_id = get_post_meta( $page_id, '_ppe_page_protection_group', true );

		if ( empty( $password_group_id ) ) {
			return null;
		}

		$group = self::get_password_group( $password_group_id );
		if ( ! $group ) {
			return null;
		}

		$redirect_url = '';
		if ( 'page' === $group->redirect_type && $group->redirect_page_id ) {
			$redirect_url = get_permalink( $group->redirect_page_id );
		} elseif ( 'custom_url' === $group->redirect_type && $group->redirect_custom_url ) {
			$redirect_url = $group->redirect_custom_url;
		}

		return (object) [
			'password_group_id' => $group->id,
			'group_name'        => $group->name,
			'redirect_url'      => $redirect_url,
		];
	}

	/**
	 * Set page protection.
	 *
	 * @param int $page_id           Page ID.
	 * @param int $password_group_id Password group ID.
	 * @return bool
	 */
	public static function set_page_protection( $page_id, $password_group_id ) {
		return update_post_meta( $page_id, '_ppe_page_protection_group', absint( $password_group_id ) );
	}

	/**
	 * Remove page protection.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	public static function remove_page_protection( $page_id ) {
		return delete_post_meta( $page_id, '_ppe_page_protection_group' );
	}
}
