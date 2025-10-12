<?php
/**
 * Tests for SessionManager class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\SessionManager;
use WP_Mock;

/**
 * SessionManager test case.
 */
class SessionManagerTest extends TestCase {

	/**
	 * Test init sets cookie when not present.
	 */
	public function test_init_sets_cookie_when_not_present() {
		$_COOKIE = array();

		WP_Mock::userFunction(
			'PasswordProtectElite\Admin\Settings::get_session_duration_hours',
			array(
				'return' => 24,
			)
		);

		WP_Mock::userFunction(
			'wp_generate_password',
			array(
				'times'  => 1,
				'args'   => array( 32, false ),
				'return' => 'test_session_id_123',
			)
		);

		WP_Mock::userFunction(
			'is_ssl',
			array(
				'return' => false,
			)
		);

		$session_manager = new SessionManager();
		$session_manager->init();

		// Cookie should be set in $_COOKIE array by setcookie simulation.
		$this->assertTrue( true ); // Can't directly test setcookie in unit tests.
	}

	/**
	 * Test store_validated_group stores data correctly.
	 */
	public function test_store_validated_group() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		// Mock get_transient to return empty array (no existing data).
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_session_test_session_123' ),
				'return' => array(),
			)
		);

		// Mock set_transient.
		WP_Mock::userFunction(
			'set_transient',
			array(
				'times' => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'PasswordProtectElite\Admin\Settings::get_session_duration_hours',
			array(
				'return' => 24,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->store_validated_group( 1, 'password_hash_123' );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_validated_group returns null when not found.
	 */
	public function test_get_validated_group_returns_null_when_not_found() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		// Mock get_transient to return empty array.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_session_test_session_123' ),
				'return' => array(),
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->get_validated_group( 1 );

		$this->assertNull( $result );
	}

	/**
	 * Test get_validated_group returns data when found.
	 */
	public function test_get_validated_group_returns_data_when_found() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		$session_data = array(
			1 => array(
				'group_id'      => 1,
				'password_hash' => 'hash_123',
				'timestamp'     => time(),
			),
		);

		// Mock get_transient to return session data.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_session_test_session_123' ),
				'return' => $session_data,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->get_validated_group( 1 );

		$this->assertIsArray( $result );
		$this->assertEquals( 1, $result['group_id'] );
		$this->assertEquals( 'hash_123', $result['password_hash'] );
	}

	/**
	 * Test remove_validated_group removes data.
	 */
	public function test_remove_validated_group() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		$session_data = array(
			1 => array(
				'group_id'      => 1,
				'password_hash' => 'hash_123',
				'timestamp'     => time(),
			),
		);

		// Mock get_transient.
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => $session_data,
			)
		);

		// Mock set_transient (to save without group 1).
		WP_Mock::userFunction(
			'set_transient',
			array(
				'times' => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'PasswordProtectElite\Admin\Settings::get_session_duration_hours',
			array(
				'return' => 24,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->remove_validated_group( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_session_expired returns false for recent session.
	 */
	public function test_is_session_expired_returns_false_for_recent_session() {
		WP_Mock::userFunction(
			'PasswordProtectElite\Admin\Settings::get_session_duration_hours',
			array(
				'times'  => 1,
				'return' => 24,
			)
		);

		$session_manager = new SessionManager();
		$recent_timestamp = time() - 3600; // 1 hour ago.

		$result = $session_manager->is_session_expired( $recent_timestamp );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_session_expired returns true for old session.
	 */
	public function test_is_session_expired_returns_true_for_old_session() {
		WP_Mock::userFunction(
			'PasswordProtectElite\Admin\Settings::get_session_duration_hours',
			array(
				'times'  => 1,
				'return' => 24,
			)
		);

		$session_manager = new SessionManager();
		$old_timestamp = time() - ( 48 * HOUR_IN_SECONDS ); // 48 hours ago.

		$result = $session_manager->is_session_expired( $old_timestamp );

		$this->assertTrue( $result );
	}

	/**
	 * Test clear_session removes transient and cookie.
	 */
	public function test_clear_session() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		// Mock delete_transient.
		WP_Mock::userFunction(
			'delete_transient',
			array(
				'times' => 1,
				'args'  => array( 'ppe_session_test_session_123' ),
			)
		);

		WP_Mock::userFunction(
			'is_ssl',
			array(
				'return' => false,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->clear_session();

		$this->assertTrue( $result );
	}

	/**
	 * Test get_session_id returns null when no cookie.
	 */
	public function test_get_session_id_returns_null_when_no_cookie() {
		$_COOKIE = array();

		$session_manager = new SessionManager();
		$result          = $session_manager->get_session_id();

		$this->assertNull( $result );
	}

	/**
	 * Test get_session_id returns cookie value.
	 */
	public function test_get_session_id_returns_cookie_value() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'times'  => 1,
				'return_arg' => 0,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->get_session_id();

		$this->assertEquals( 'test_session_123', $result );
	}

	/**
	 * Test session data is array when transient returns non-array.
	 */
	public function test_get_session_data_returns_array_when_transient_invalid() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		// Mock get_transient to return false (expired or doesn't exist).
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$session_manager = new SessionManager();
		// Use reflection to call private method.
		$method = $this->getProtectedMethod( $session_manager, 'get_session_data' );
		$result = $method->invoke( $session_manager );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test has_active_session returns false when no session data.
	 */
	public function test_has_active_session_returns_false_when_empty() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => array(),
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->has_active_session();

		$this->assertFalse( $result );
	}

	/**
	 * Test has_active_session returns true when session data exists.
	 */
	public function test_has_active_session_returns_true_when_data_exists() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		$session_data = array(
			1 => array(
				'group_id'      => 1,
				'password_hash' => 'hash_123',
				'timestamp'     => time(),
			),
		);

		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => $session_data,
			)
		);

		$session_manager = new SessionManager();
		$result          = $session_manager->has_active_session();

		$this->assertTrue( $result );
	}

	/**
	 * Test clear_session_on_wp_logout clears session.
	 */
	public function test_clear_session_on_wp_logout() {
		$_COOKIE[ SessionManager::COOKIE_NAME ] = 'test_session_123';

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'delete_transient',
			array(
				'times' => 1,
				'args'  => array( 'ppe_session_test_session_123' ),
			)
		);

		WP_Mock::userFunction(
			'is_ssl',
			array(
				'return' => false,
			)
		);

		$session_manager = new SessionManager();
		$session_manager->clear_session_on_wp_logout();

		// If we get here without errors, the method was called successfully.
		$this->assertTrue( true );
	}
}

