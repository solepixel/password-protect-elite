<?php
/**
 * Tests for Database class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\Database;
use WP_Mock;
use Mockery;

/**
 * Database test case.
 */
class DatabaseTest extends TestCase {

	/**
	 * Test get_password_groups retrieves all groups.
	 */
	public function test_get_password_groups_all() {
		// Mock WP_Query.
		$post1           = new \stdClass();
		$post1->ID       = 1;
		$post1->post_title = 'Group 1';
		$post1->post_content = 'Description 1';

		$post2           = new \stdClass();
		$post2->ID       = 2;
		$post2->post_title = 'Group 2';
		$post2->post_content = 'Description 2';

		$query_mock = Mockery::mock( 'WP_Query' );
		$query_mock->posts = array( $post1, $post2 );

		WP_Mock::userFunction(
			'get_posts',
			array(
				'times'  => 1,
				'return' => array( $post1, $post2 ),
			)
		);

		// Mock get_post_meta for each post.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_additional_passwords' === $key || '_ppe_allowed_roles' === $key ) {
					return array();
				}
				return '';
			}
		);

		$groups = Database::get_password_groups();

		$this->assertIsArray( $groups );
		$this->assertCount( 2, $groups );
	}

	/**
	 * Test get_password_groups with type filter.
	 */
	public function test_get_password_groups_with_type() {
		$post           = new \stdClass();
		$post->ID       = 1;
		$post->post_title = 'Content Group';
		$post->post_content = 'Description';

		WP_Mock::userFunction(
			'get_posts',
			array(
				'times'  => 1,
				'return' => array( $post ),
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_protection_type' === $key ) {
					return 'content';
				}
				if ( '_ppe_additional_passwords' === $key || '_ppe_allowed_roles' === $key ) {
					return array();
				}
				return '';
			}
		);

		$groups = Database::get_password_groups( 'content' );

		$this->assertIsArray( $groups );
	}

	/**
	 * Test get_password_group retrieves single group.
	 */
	public function test_get_password_group() {
		$post           = new \stdClass();
		$post->ID       = 1;
		$post->post_title = 'Test Group';
		$post->post_content = 'Test Description';

		WP_Mock::userFunction(
			'get_post',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $post,
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_master_password' === $key ) {
					return 'test_password';
				}
				if ( '_ppe_additional_passwords' === $key ) {
					return array( 'password1', 'password2' );
				}
				if ( '_ppe_allowed_roles' === $key ) {
					return array( 'administrator' );
				}
				return '';
			}
		);

		$group = Database::get_password_group( 1 );

		$this->assertIsObject( $group );
		$this->assertEquals( 1, $group->id );
		$this->assertEquals( 'Test Group', $group->name );
		$this->assertEquals( 'test_password', $group->master_password );
		$this->assertIsArray( $group->additional_passwords );
		$this->assertCount( 2, $group->additional_passwords );
	}

	/**
	 * Test get_password_group returns null for invalid ID.
	 */
	public function test_get_password_group_returns_null() {
		WP_Mock::userFunction(
			'get_post',
			array(
				'times'  => 1,
				'args'   => array( 999 ),
				'return' => null,
			)
		);

		$group = Database::get_password_group( 999 );

		$this->assertNull( $group );
	}

	/**
	 * Test validate_password with master password.
	 */
	public function test_validate_password_with_master_password() {
		$post           = new \stdClass();
		$post->ID       = 1;
		$post->post_title = 'Test Group';
		$post->post_content = 'Description';

		WP_Mock::userFunction(
			'get_posts',
			array(
				'times'  => 1,
				'return' => array( $post ),
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_master_password' === $key ) {
					return 'correct_password';
				}
				if ( '_ppe_additional_passwords' === $key || '_ppe_allowed_roles' === $key ) {
					return array();
				}
				return '';
			}
		);

		$group = Database::validate_password( 'correct_password' );

		$this->assertIsObject( $group );
		$this->assertEquals( 1, $group->id );
	}

	/**
	 * Test validate_password with additional password.
	 */
	public function test_validate_password_with_additional_password() {
		$post           = new \stdClass();
		$post->ID       = 1;
		$post->post_title = 'Test Group';
		$post->post_content = 'Description';

		WP_Mock::userFunction(
			'get_posts',
			array(
				'times'  => 1,
				'return' => array( $post ),
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_master_password' === $key ) {
					return 'master_pass';
				}
				if ( '_ppe_additional_passwords' === $key ) {
					return array( 'password1', 'password2', 'password3' );
				}
				if ( '_ppe_allowed_roles' === $key ) {
					return array();
				}
				return '';
			}
		);

		$group = Database::validate_password( 'password2' );

		$this->assertIsObject( $group );
		$this->assertEquals( 1, $group->id );
	}

	/**
	 * Test validate_password returns null with invalid password.
	 */
	public function test_validate_password_returns_null_with_invalid_password() {
		$post           = new \stdClass();
		$post->ID       = 1;
		$post->post_title = 'Test Group';
		$post->post_content = 'Description';

		WP_Mock::userFunction(
			'get_posts',
			array(
				'times'  => 1,
				'return' => array( $post ),
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_master_password' === $key ) {
					return 'correct_password';
				}
				if ( '_ppe_additional_passwords' === $key || '_ppe_allowed_roles' === $key ) {
					return array();
				}
				return '';
			}
		);

		$group = Database::validate_password( 'wrong_password' );

		$this->assertNull( $group );
	}

	/**
	 * Test create_password_group.
	 */
	public function test_create_password_group() {
		$data = array(
			'name'                 => 'New Group',
			'description'          => 'New Description',
			'master_password'      => 'new_password',
			'additional_passwords' => array( 'pass1', 'pass2' ),
			'protection_type'      => 'content',
		);

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'sanitize_textarea_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_insert_post',
			array(
				'times'  => 1,
				'return' => 123,
			)
		);

		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'args'   => array( 123 ),
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'update_post_meta',
			array(
				'times' => 6,
			)
		);

		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return_arg' => 0,
			)
		);

		$result = Database::create_password_group( $data );

		$this->assertEquals( 123, $result );
	}

	/**
	 * Test create_password_group returns false on error.
	 */
	public function test_create_password_group_returns_false_on_error() {
		$data = array(
			'name' => 'New Group',
		);

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'sanitize_textarea_field',
			array(
				'return_arg' => 0,
			)
		);

		$error = Mockery::mock( 'WP_Error' );

		WP_Mock::userFunction(
			'wp_insert_post',
			array(
				'times'  => 1,
				'return' => $error,
			)
		);

		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'args'   => array( $error ),
				'return' => true,
			)
		);

		$result = Database::create_password_group( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test update_password_group.
	 */
	public function test_update_password_group() {
		$data = array(
			'name'            => 'Updated Name',
			'master_password' => 'updated_password',
		);

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_update_post',
			array(
				'times'  => 1,
				'return' => 123,
			)
		);

		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'update_post_meta',
			array(
				'times' => 1,
			)
		);

		$result = Database::update_password_group( 123, $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test delete_password_group.
	 */
	public function test_delete_password_group() {
		WP_Mock::userFunction(
			'wp_trash_post',
			array(
				'times'  => 1,
				'args'   => array( 123 ),
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'args'   => array( true ),
				'return' => false,
			)
		);

		$result = Database::delete_password_group( 123 );

		$this->assertTrue( $result );
	}
}

