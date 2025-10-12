<?php
/**
 * Tests for PasswordManager class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\PasswordManager;
use PasswordProtectElite\SessionManager;
use PasswordProtectElite\Database;
use WP_Mock;
use Mockery;

/**
 * PasswordManager test case.
 */
class PasswordManagerTest extends TestCase {

	/**
	 * Test store_validated_password method.
	 */
	public function test_store_validated_password() {
		// Mock SessionManager.
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'store_validated_group' )
			->once()
			->andReturn( true );

		// Mock wp_salt for password hashing.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$manager = new PasswordManager( $session_manager );

		$group_id = 1;
		$password = 'test_password';

		$manager->store_validated_password( $group_id, $password );

		// Test passes if store_validated_group was called.
		$this->assertTrue( true );
	}

	/**
	 * Test is_password_validated returns false when group not in session.
	 */
	public function test_is_password_validated_returns_false_when_not_in_session() {
		// Mock SessionManager.
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'get_validated_group' )
			->once()
			->with( 1 )
			->andReturn( null );

		$manager = new PasswordManager( $session_manager );

		$result = $manager->is_password_validated( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_password_validated returns false when session expired.
	 */
	public function test_is_password_validated_returns_false_when_expired() {
		// Mock SessionManager.
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'get_validated_group' )
			->once()
			->with( 1 )
			->andReturn(
				array(
					'group_id'      => 1,
					'password_hash' => 'test_hash',
					'timestamp'     => time() - 7200,
				)
			);

		$session_manager->shouldReceive( 'is_session_expired' )
			->once()
			->andReturn( true );

		$session_manager->shouldReceive( 'remove_validated_group' )
			->once()
			->with( 1 )
			->andReturn( true );

		$manager = new PasswordManager( $session_manager );

		$result = $manager->is_password_validated( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test revalidate_stored_password with master password.
	 */
	public function test_revalidate_stored_password_with_master_password() {
		$manager = new PasswordManager();

		// Create a mock password group.
		$password_group              = new \stdClass();
		$password_group->id          = 1;
		$password_group->master_password = 'master123';
		$password_group->additional_passwords = array();

		// Mock Database::get_password_group.
		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $password_group,
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'salt_value',
			)
		);

		$method = $this->getProtectedMethod( $manager, 'revalidate_stored_password' );

		// Calculate the expected hash.
		$expected_hash = hash( 'sha256', 'master123' . 'salt_value' );

		$result = $method->invoke( $manager, 1, $expected_hash );

		$this->assertTrue( $result );
	}

	/**
	 * Test revalidate_stored_password with additional password (the bug fix).
	 */
	public function test_revalidate_stored_password_with_additional_password() {
		$manager = new PasswordManager();

		// Create a mock password group with additional passwords as an ARRAY.
		$password_group              = new \stdClass();
		$password_group->id          = 1;
		$password_group->master_password = 'master123';
		$password_group->additional_passwords = array( 'password1', 'password2', 'password3' );

		// Mock Database::get_password_group.
		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $password_group,
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'salt_value',
			)
		);

		$method = $this->getProtectedMethod( $manager, 'revalidate_stored_password' );

		// Calculate the expected hash for password2.
		$expected_hash = hash( 'sha256', 'password2' . 'salt_value' );

		$result = $method->invoke( $manager, 1, $expected_hash );

		$this->assertTrue( $result );
	}

	/**
	 * Test revalidate_stored_password returns false when password not found.
	 */
	public function test_revalidate_stored_password_returns_false_when_password_not_found() {
		$manager = new PasswordManager();

		// Create a mock password group.
		$password_group              = new \stdClass();
		$password_group->id          = 1;
		$password_group->master_password = 'master123';
		$password_group->additional_passwords = array( 'password1' );

		// Mock Database::get_password_group.
		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $password_group,
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'salt_value',
			)
		);

		$method = $this->getProtectedMethod( $manager, 'revalidate_stored_password' );

		// Use a hash for a different password.
		$wrong_hash = hash( 'sha256', 'wrong_password' . 'salt_value' );

		$result = $method->invoke( $manager, 1, $wrong_hash );

		$this->assertFalse( $result );
	}

	/**
	 * Test revalidate_stored_password returns false when group not found.
	 */
	public function test_revalidate_stored_password_returns_false_when_group_not_found() {
		$manager = new PasswordManager();

		// Mock Database::get_password_group to return null.
		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => null,
			)
		);

		$method = $this->getProtectedMethod( $manager, 'revalidate_stored_password' );

		$result = $method->invoke( $manager, 1, 'some_hash' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_redirect_url with page redirect.
	 */
	public function test_get_redirect_url_with_page() {
		$manager = new PasswordManager();

		$password_group                    = new \stdClass();
		$password_group->redirect_type     = 'page';
		$password_group->redirect_page_id  = 123;
		$password_group->redirect_custom_url = '';

		WP_Mock::userFunction(
			'get_permalink',
			array(
				'times'  => 1,
				'args'   => array( 123 ),
				'return' => 'https://example.com/redirect-page',
			)
		);

		$result = $manager->get_redirect_url( $password_group, 'https://example.com/fallback' );

		$this->assertEquals( 'https://example.com/redirect-page', $result );
	}

	/**
	 * Test get_redirect_url with custom URL.
	 */
	public function test_get_redirect_url_with_custom_url() {
		$manager = new PasswordManager();

		$password_group                    = new \stdClass();
		$password_group->redirect_type     = 'custom_url';
		$password_group->redirect_page_id  = 0;
		$password_group->redirect_custom_url = 'https://example.com/custom';

		$result = $manager->get_redirect_url( $password_group, 'https://example.com/fallback' );

		$this->assertEquals( 'https://example.com/custom', $result );
	}

	/**
	 * Test get_redirect_url with fallback.
	 */
	public function test_get_redirect_url_with_fallback() {
		$manager = new PasswordManager();

		$password_group                    = new \stdClass();
		$password_group->redirect_type     = 'none';
		$password_group->redirect_page_id  = 0;
		$password_group->redirect_custom_url = '';

		$result = $manager->get_redirect_url( $password_group, 'https://example.com/fallback' );

		$this->assertEquals( 'https://example.com/fallback', $result );
	}

	/**
	 * Test get_redirect_url with no fallback returns home_url.
	 */
	public function test_get_redirect_url_defaults_to_home_url() {
		$manager = new PasswordManager();

		$password_group                    = new \stdClass();
		$password_group->redirect_type     = 'none';
		$password_group->redirect_page_id  = 0;
		$password_group->redirect_custom_url = '';

		WP_Mock::userFunction(
			'home_url',
			array(
				'times'  => 1,
				'return' => 'https://example.com',
			)
		);

		$result = $manager->get_redirect_url( $password_group, '' );

		$this->assertEquals( 'https://example.com', $result );
	}

	/**
	 * Test has_access_to_group with validated password.
	 */
	public function test_has_access_to_group_with_validated_password() {
		$manager = Mockery::mock( PasswordManager::class )->makePartial();

		// Mock is_password_validated to return true.
		$manager->shouldReceive( 'is_password_validated' )
			->once()
			->with( 1 )
			->andReturn( true );

		$result = $manager->has_access_to_group( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test has_access_to_group with role-based bypass.
	 */
	public function test_has_access_to_group_with_role_bypass() {
		$manager = Mockery::mock( PasswordManager::class )->makePartial();

		// Mock is_password_validated to return false.
		$manager->shouldReceive( 'is_password_validated' )
			->once()
			->with( 1 )
			->andReturn( false );

		// Mock password group with allowed roles.
		$password_group               = new \stdClass();
		$password_group->id           = 1;
		$password_group->allowed_roles = array( 'administrator', 'editor' );

		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $password_group,
			)
		);

		// Mock is_user_logged_in.
		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		// Mock wp_get_current_user.
		$user        = new \stdClass();
		$user->roles = array( 'administrator' );

		WP_Mock::userFunction(
			'wp_get_current_user',
			array(
				'times'  => 1,
				'return' => $user,
			)
		);

		$result = $manager->has_access_to_group( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test has_access_to_group returns false when no access.
	 */
	public function test_has_access_to_group_returns_false_when_no_access() {
		$manager = Mockery::mock( PasswordManager::class )->makePartial();

		// Mock is_password_validated to return false.
		$manager->shouldReceive( 'is_password_validated' )
			->once()
			->with( 1 )
			->andReturn( false );

		// Mock password group with no allowed roles.
		$password_group               = new \stdClass();
		$password_group->id           = 1;
		$password_group->allowed_roles = array();

		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_group',
			array(
				'times'  => 1,
				'args'   => array( 1 ),
				'return' => $password_group,
			)
		);

		// Mock is_user_logged_in.
		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$result = $manager->has_access_to_group( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_content_accessible with empty allowed groups.
	 */
	public function test_is_content_accessible_with_empty_groups() {
		$manager = new PasswordManager();

		$result = $manager->is_content_accessible( array() );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_content_accessible returns true when one group is validated.
	 */
	public function test_is_content_accessible_returns_true_when_one_group_validated() {
		$manager = Mockery::mock( PasswordManager::class )->makePartial();

		$manager->shouldReceive( 'is_password_validated' )
			->once()
			->with( 1 )
			->andReturn( false );

		$manager->shouldReceive( 'is_password_validated' )
			->once()
			->with( 2 )
			->andReturn( true );

		$result = $manager->is_content_accessible( array( 1, 2, 3 ) );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_content_accessible returns false when no groups validated.
	 */
	public function test_is_content_accessible_returns_false_when_no_groups_validated() {
		$manager = Mockery::mock( PasswordManager::class )->makePartial();

		$manager->shouldReceive( 'is_password_validated' )
			->times( 3 )
			->andReturn( false );

		$result = $manager->is_content_accessible( array( 1, 2, 3 ) );

		$this->assertFalse( $result );
	}
}

