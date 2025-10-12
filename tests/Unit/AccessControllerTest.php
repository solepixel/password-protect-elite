<?php
/**
 * Tests for AccessController class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\AccessController;
use WP_Mock;

/**
 * AccessController test case.
 */
class AccessControllerTest extends TestCase {

	/**
	 * Test handle_unauthenticated_behavior with show_404 behavior.
	 */
	public function test_handle_unauthenticated_behavior_show_404() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_unauthenticated_behavior', true ),
				'return' => 'show_404',
			)
		);

		WP_Mock::userFunction(
			'status_header',
			array(
				'times' => 1,
				'args'  => array( 404 ),
			)
		);

		WP_Mock::userFunction(
			'nocache_headers',
			array(
				'times' => 1,
			)
		);

		WP_Mock::userFunction(
			'get_query_template',
			array(
				'times'  => 1,
				'args'   => array( '404' ),
				'return' => '/path/to/404.php',
			)
		);

		$callback_called = false;
		$callback        = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		// Mock exit as an exception for testing.
		WP_Mock::userFunction(
			'exit',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'exit' ) );

		try {
			AccessController::handle_unauthenticated_behavior( $password_group, $callback );
		} catch ( \Exception $e ) {
			// Verify callback was not called.
			$this->assertFalse( $callback_called );
			throw $e;
		}
	}

	/**
	 * Test handle_unauthenticated_behavior with show_dialog behavior.
	 */
	public function test_handle_unauthenticated_behavior_show_dialog() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_unauthenticated_behavior', true ),
				'return' => 'show_dialog',
			)
		);

		$callback_called = false;
		$callback        = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		AccessController::handle_unauthenticated_behavior( $password_group, $callback );

		// Verify callback was called.
		$this->assertTrue( $callback_called );
	}

	/**
	 * Test handle_unauthenticated_behavior with redirect to page.
	 */
	public function test_handle_unauthenticated_behavior_redirect_to_page() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_unauthenticated_behavior' === $key ) {
					return 'redirect';
				}
				if ( '_ppe_unauthenticated_redirect_page_id' === $key ) {
					return 123;
				}
				if ( '_ppe_unauthenticated_redirect_custom_url' === $key ) {
					return '';
				}
				return '';
			}
		);

		WP_Mock::userFunction(
			'absint',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'get_permalink',
			array(
				'times'  => 1,
				'args'   => array( 123 ),
				'return' => 'https://example.com/login-page',
			)
		);

		WP_Mock::userFunction(
			'add_query_arg',
			array(
				'times'  => 1,
				'return' => 'https://example.com/login-page?ppe_auth_required=1&ppe_group=1',
			)
		);

		WP_Mock::userFunction(
			'wp_safe_redirect',
			array(
				'times' => 1,
				'args'  => array( 'https://example.com/login-page?ppe_auth_required=1&ppe_group=1' ),
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		WP_Mock::userFunction(
			'exit',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'exit' ) );

		$callback = function () {
			// Should not be called.
		};

		AccessController::handle_unauthenticated_behavior( $password_group, $callback );
	}

	/**
	 * Test handle_unauthenticated_behavior with redirect to custom URL.
	 */
	public function test_handle_unauthenticated_behavior_redirect_to_custom_url() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_unauthenticated_behavior' === $key ) {
					return 'redirect';
				}
				if ( '_ppe_unauthenticated_redirect_page_id' === $key ) {
					return 0;
				}
				if ( '_ppe_unauthenticated_redirect_custom_url' === $key ) {
					return 'https://external-site.com/login';
				}
				return '';
			}
		);

		WP_Mock::userFunction(
			'absint',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'add_query_arg',
			array(
				'times'  => 1,
				'return' => 'https://external-site.com/login?ppe_auth_required=1&ppe_group=1',
			)
		);

		WP_Mock::userFunction(
			'wp_safe_redirect',
			array(
				'times' => 1,
				'args'  => array( 'https://external-site.com/login?ppe_auth_required=1&ppe_group=1' ),
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		WP_Mock::userFunction(
			'exit',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'exit' ) );

		$callback = function () {
			// Should not be called.
		};

		AccessController::handle_unauthenticated_behavior( $password_group, $callback );
	}

	/**
	 * Test handle_unauthenticated_behavior defaults to 404 when behavior empty.
	 */
	public function test_handle_unauthenticated_behavior_defaults_to_404() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_unauthenticated_behavior', true ),
				'return' => '',
			)
		);

		WP_Mock::userFunction(
			'status_header',
			array(
				'times' => 1,
				'args'  => array( 404 ),
			)
		);

		WP_Mock::userFunction(
			'nocache_headers',
			array(
				'times' => 1,
			)
		);

		WP_Mock::userFunction(
			'get_query_template',
			array(
				'times'  => 1,
				'args'   => array( '404' ),
				'return' => '/path/to/404.php',
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		WP_Mock::userFunction(
			'exit',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'exit' ) );

		$callback = function () {
			// Should not be called.
		};

		AccessController::handle_unauthenticated_behavior( $password_group, $callback );
	}

	/**
	 * Test handle_unauthenticated_behavior with null group.
	 */
	public function test_handle_unauthenticated_behavior_with_null_group() {
		WP_Mock::userFunction(
			'status_header',
			array(
				'times' => 1,
				'args'  => array( 404 ),
			)
		);

		WP_Mock::userFunction(
			'nocache_headers',
			array(
				'times' => 1,
			)
		);

		WP_Mock::userFunction(
			'get_query_template',
			array(
				'times'  => 1,
				'args'   => array( '404' ),
				'return' => '/path/to/404.php',
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'exit' );

		WP_Mock::userFunction(
			'exit',
			array(
				'times' => 1,
			)
		)->andThrow( new \Exception( 'exit' ) );

		$callback = function () {
			// Should not be called.
		};

		AccessController::handle_unauthenticated_behavior( null, $callback );
	}

	/**
	 * Test handle_unauthenticated_behavior falls back to dialog when redirect misconfigured.
	 */
	public function test_handle_unauthenticated_behavior_redirect_fallback_to_dialog() {
		$password_group     = new \stdClass();
		$password_group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $post_id, $key, $single ) {
				if ( '_ppe_unauthenticated_behavior' === $key ) {
					return 'redirect';
				}
				// No page ID and no custom URL - misconfigured.
				return '';
			}
		);

		WP_Mock::userFunction(
			'absint',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return_arg' => 0,
			)
		);

		$callback_called = false;
		$callback        = function () use ( &$callback_called ) {
			$callback_called = true;
		};

		AccessController::handle_unauthenticated_behavior( $password_group, $callback );

		// Should fall back to showing the dialog.
		$this->assertTrue( $callback_called );
	}
}

