<?php
/**
 * Tests for MenuIntegration class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\MenuIntegration;
use PasswordProtectElite\SessionManager;
use WP_Mock;
use Mockery;

/**
 * MenuIntegration test case.
 */
class MenuIntegrationTest extends TestCase {

	/**
	 * Test modify_menu_items does nothing when user is logged in.
	 */
	public function test_modify_menu_items_returns_unchanged_when_user_logged_in() {
		$session_manager = Mockery::mock( SessionManager::class );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_items = '<li><a href="https://example.com/wp-login.php">Log In</a></li>';
		$result         = $menu_integration->modify_menu_items( $original_items, new \stdClass() );

		$this->assertEquals( $original_items, $result );
	}

	/**
	 * Test modify_menu_items does nothing when no password session.
	 */
	public function test_modify_menu_items_returns_unchanged_when_no_session() {
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'has_active_session' )
			->once()
			->andReturn( false );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_items = '<li><a href="https://example.com/wp-login.php">Log In</a></li>';
		$result         = $menu_integration->modify_menu_items( $original_items, new \stdClass() );

		$this->assertEquals( $original_items, $result );
	}

	/**
	 * Test modify_menu_items changes login to logout when password session exists.
	 */
	public function test_modify_menu_items_changes_login_to_logout_with_session() {
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'has_active_session' )
			->andReturn( true );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_login_url',
			array(
				'times'  => 1,
				'return' => 'https://example.com/wp-login.php',
			)
		);

		WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'https://example.com',
			)
		);

		WP_Mock::userFunction(
			'add_query_arg',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $args, $url ) {
				$query = http_build_query( $args );
				return $url . '?' . $query;
			}
		);

		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_items = '<li><a href="https://example.com/wp-login.php">Log In</a></li>';
		$result         = $menu_integration->modify_menu_items( $original_items, new \stdClass() );

		// Should replace login URL with logout URL and "Log In" with "Log Out".
		$this->assertStringContainsString( 'Log Out', $result );
		$this->assertStringContainsString( 'ppe_action=logout', $result );
		$this->assertStringNotContainsString( 'Log In', $result );
	}

	/**
	 * Test handle_logout_request does nothing when no action parameter.
	 */
	public function test_handle_logout_request_does_nothing_without_action() {
		$_GET = array();

		$session_manager  = Mockery::mock( SessionManager::class );
		$menu_integration = new MenuIntegration( $session_manager );

		// Should not call clear_session.
		$menu_integration->handle_logout_request();

		$this->assertTrue( true );
	}

	/**
	 * Test handle_logout_request fails with invalid nonce.
	 */
	public function test_handle_logout_request_fails_with_invalid_nonce() {
		$_GET = array(
			'ppe_action' => 'logout',
			'ppe_nonce'  => 'invalid_nonce',
		);

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
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_die',
			array(
				'times' => 1,
			)
		);

		WP_Mock::userFunction(
			'esc_html__',
			array(
				'return_arg' => 0,
			)
		);

		$session_manager  = Mockery::mock( SessionManager::class );
		$menu_integration = new MenuIntegration( $session_manager );

		$this->expectException( \Exception::class );

		// Mock wp_die as exception for testing.
		WP_Mock::userFunction(
			'wp_die',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'wp_die' ) );

		$menu_integration->handle_logout_request();
	}

	/**
	 * Test handle_logout_request clears session and redirects.
	 */
	public function test_handle_logout_request_clears_session_and_redirects() {
		$_GET = array(
			'ppe_action' => 'logout',
			'ppe_nonce'  => 'valid_nonce',
		);

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
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'https://example.com',
			)
		);

		WP_Mock::userFunction(
			'remove_query_arg',
			array(
				'return' => 'https://example.com',
			)
		);

		WP_Mock::userFunction(
			'wp_safe_redirect',
			array(
				'times' => 1,
			)
		);

		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'clear_session' )
			->once()
			->andReturn( true );

		$menu_integration = new MenuIntegration( $session_manager );

		// Mock exit for testing.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		// We need to mock exit which will be called after redirect.
		// For testing purposes, we'll just verify the clear_session was called.
		try {
			$menu_integration->handle_logout_request();
		} catch ( \Exception $e ) {
			// Exit was called (mocked).
		}

		// If we get here, clear_session should have been called (verified by Mockery).
		$this->assertTrue( true );
	}

	/**
	 * Test constructor adds hooks.
	 */
	public function test_constructor_adds_hooks() {
		WP_Mock::expectFilterAdded( 'wp_nav_menu_items', Mockery::type( 'callable' ), 10, 2 );
		WP_Mock::expectFilterAdded( 'render_block_core/loginout', Mockery::type( 'callable' ), 20, 2 );
		WP_Mock::expectFilterAdded( 'loginout', Mockery::type( 'callable' ), 10, 2 );
		WP_Mock::expectActionAdded( 'init', Mockery::type( 'callable' ) );

		$session_manager  = Mockery::mock( SessionManager::class );
		$menu_integration = new MenuIntegration( $session_manager );

		$this->assertConditionsMet();
	}

	/**
	 * Test modify_loginout_block does nothing when user logged in.
	 */
	public function test_modify_loginout_block_returns_unchanged_when_user_logged_in() {
		$session_manager = Mockery::mock( SessionManager::class );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_content = '<div class="wp-block-loginout"><a href="https://example.com/wp-login.php">Log in</a></div>';
		$result           = $menu_integration->modify_loginout_block( $original_content, array() );

		$this->assertEquals( $original_content, $result );
	}

	/**
	 * Test modify_loginout_block does nothing when no password session.
	 */
	public function test_modify_loginout_block_returns_unchanged_when_no_session() {
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'has_active_session' )
			->once()
			->andReturn( false );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_content = '<div class="wp-block-loginout"><a href="https://example.com/wp-login.php">Log in</a></div>';
		$result           = $menu_integration->modify_loginout_block( $original_content, array() );

		$this->assertEquals( $original_content, $result );
	}

	/**
	 * Test modify_loginout_block changes to logout when password session exists.
	 */
	public function test_modify_loginout_block_changes_to_logout_with_session() {
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'has_active_session' )
			->andReturn( true );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_login_url',
			array(
				'return' => 'https://example.com/wp-login.php',
			)
		);

		WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'https://example.com',
			)
		);

		WP_Mock::userFunction(
			'add_query_arg',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $args, $url ) {
				$query = http_build_query( $args );
				return $url . '?' . $query;
			}
		);

		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_content = '<div class="wp-block-loginout"><a href="https://example.com/wp-login.php">Log in</a></div>';
		$result           = $menu_integration->modify_loginout_block( $original_content, array() );

		$this->assertStringContainsString( 'Log out', $result );
		$this->assertStringContainsString( 'ppe_action=logout', $result );
		$this->assertStringNotContainsString( 'Log in', $result );
	}

	/**
	 * Test modify_loginout_link changes to logout when password session exists.
	 */
	public function test_modify_loginout_link_changes_to_logout_with_session() {
		$session_manager = Mockery::mock( SessionManager::class );
		$session_manager->shouldReceive( 'has_active_session' )
			->andReturn( true );

		WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_login_url',
			array(
				'return' => 'https://example.com/wp-login.php',
			)
		);

		WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'https://example.com',
			)
		);

		WP_Mock::userFunction(
			'add_query_arg',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $args, $url ) {
				$query = http_build_query( $args );
				return $url . '?' . $query;
			}
		);

		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		$menu_integration = new MenuIntegration( $session_manager );

		$original_link = '<a href="https://example.com/wp-login.php">Log in</a>';
		$result        = $menu_integration->modify_loginout_link( $original_link, '' );

		$this->assertStringContainsString( 'Log out', $result );
		$this->assertStringContainsString( 'ppe_action=logout', $result );
		$this->assertStringNotContainsString( 'Log in', $result );
	}
}

