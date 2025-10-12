<?php
/**
 * Tests for Blocks class logout redirect functionality.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\Blocks;
use WP_Mock;

/**
 * Blocks logout redirect test case.
 */
class BlocksLogoutTest extends TestCase {

	/**
	 * Test get_logout_redirect_url returns current page by default.
	 */
	public function test_get_logout_redirect_url_defaults_to_current_page() {
		$_SERVER['HTTP_HOST']   = 'example.com';
		$_SERVER['REQUEST_URI'] = '/test-page';

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
			'is_ssl',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => '',
			)
		);

		$blocks = new Blocks();
		$method = $this->getProtectedMethod( $blocks, 'get_logout_redirect_url' );
		$result = $method->invoke( $blocks, 1 );

		$this->assertStringContainsString( 'example.com/test-page', $result );
	}

	/**
	 * Test get_logout_redirect_url returns page URL when set.
	 */
	public function test_get_logout_redirect_url_returns_page_url() {
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $group_id, $key, $single ) {
				if ( '_ppe_logout_redirect_type' === $key ) {
					return 'page';
				}
				if ( '_ppe_logout_redirect_page_id' === $key ) {
					return 123;
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
			'get_permalink',
			array(
				'times'  => 1,
				'args'   => array( 123 ),
				'return' => 'https://example.com/logout-page',
			)
		);

		$blocks = new Blocks();
		$method = $this->getProtectedMethod( $blocks, 'get_logout_redirect_url' );
		$result = $method->invoke( $blocks, 1 );

		$this->assertEquals( 'https://example.com/logout-page', $result );
	}

	/**
	 * Test get_logout_redirect_url returns custom URL when set.
	 */
	public function test_get_logout_redirect_url_returns_custom_url() {
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $group_id, $key, $single ) {
				if ( '_ppe_logout_redirect_type' === $key ) {
					return 'custom_url';
				}
				if ( '_ppe_logout_redirect_custom_url' === $key ) {
					return 'https://external-site.com/goodbye';
				}
				return '';
			}
		);

		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return_arg' => 0,
			)
		);

		$blocks = new Blocks();
		$method = $this->getProtectedMethod( $blocks, 'get_logout_redirect_url' );
		$result = $method->invoke( $blocks, 1 );

		$this->assertEquals( 'https://external-site.com/goodbye', $result );
	}

	/**
	 * Test get_logout_redirect_url falls back to current page when page not found.
	 */
	public function test_get_logout_redirect_url_falls_back_when_page_invalid() {
		$_SERVER['HTTP_HOST']   = 'example.com';
		$_SERVER['REQUEST_URI'] = '/current-page';

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
			'is_ssl',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $group_id, $key, $single ) {
				if ( '_ppe_logout_redirect_type' === $key ) {
					return 'page';
				}
				if ( '_ppe_logout_redirect_page_id' === $key ) {
					return 999; // Non-existent page.
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
			'get_permalink',
			array(
				'times'  => 1,
				'args'   => array( 999 ),
				'return' => false, // Page doesn't exist.
			)
		);

		$blocks = new Blocks();
		$method = $this->getProtectedMethod( $blocks, 'get_logout_redirect_url' );
		$result = $method->invoke( $blocks, 1 );

		// Should fall back to current page.
		$this->assertStringContainsString( 'example.com/current-page', $result );
	}
}

