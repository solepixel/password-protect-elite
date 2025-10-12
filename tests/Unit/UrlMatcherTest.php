<?php
/**
 * Tests for UrlMatcher class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\UrlMatcher;
use WP_Mock;

/**
 * UrlMatcher test case.
 */
class UrlMatcherTest extends TestCase {

	/**
	 * Test url_matches_pattern with exact match.
	 */
	public function test_url_matches_pattern_exact_match() {
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		$result = UrlMatcher::url_matches_pattern( '/test-page', '/test-page' );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_pattern( '/test-page', '/different-page' );
		$this->assertFalse( $result );
	}

	/**
	 * Test url_matches_pattern with wildcard.
	 */
	public function test_url_matches_pattern_with_wildcard() {
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		// Test trailing wildcard.
		$result = UrlMatcher::url_matches_pattern( '/blog/post-123', '/blog/*' );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_pattern( '/news/article', '/blog/*' );
		$this->assertFalse( $result );

		// Test middle wildcard.
		$result = UrlMatcher::url_matches_pattern( '/category/test/page', '/category/*/page' );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_pattern( '/category/test/article', '/category/*/page' );
		$this->assertFalse( $result );
	}

	/**
	 * Test url_matches_pattern with empty pattern.
	 */
	public function test_url_matches_pattern_with_empty_pattern() {
		$result = UrlMatcher::url_matches_pattern( '/test-page', '' );
		$this->assertFalse( $result );
	}

	/**
	 * Test url_matches_patterns with multiple patterns.
	 */
	public function test_url_matches_patterns_with_multiple_patterns() {
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		// Comma-separated patterns.
		$patterns = '/blog/*, /news/*, /articles/*';
		$result   = UrlMatcher::url_matches_patterns( '/blog/post-1', $patterns );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_patterns( '/news/article-1', $patterns );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_patterns( '/products/item-1', $patterns );
		$this->assertFalse( $result );
	}

	/**
	 * Test url_matches_patterns with newline-separated patterns.
	 */
	public function test_url_matches_patterns_with_newline_patterns() {
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		// Newline-separated patterns.
		$patterns = "/blog/*\n/news/*\n/articles/*";
		$result   = UrlMatcher::url_matches_patterns( '/blog/post-1', $patterns );
		$this->assertTrue( $result );

		$result = UrlMatcher::url_matches_patterns( '/products/item-1', $patterns );
		$this->assertFalse( $result );
	}

	/**
	 * Test url_matches_patterns with empty patterns.
	 */
	public function test_url_matches_patterns_with_empty_patterns() {
		$result = UrlMatcher::url_matches_patterns( '/test-page', '' );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_current_url.
	 */
	public function test_get_current_url() {
		$_SERVER['REQUEST_URI'] = '/test-page?param=value';

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		$result = UrlMatcher::get_current_url();

		$this->assertEquals( '/test-page?param=value', $result );
	}

	/**
	 * Test get_current_url with default value.
	 */
	public function test_get_current_url_with_default() {
		unset( $_SERVER['REQUEST_URI'] );

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		$result = UrlMatcher::get_current_url();

		$this->assertEquals( '/', $result );
	}

	/**
	 * Test is_current_url_excluded.
	 */
	public function test_is_current_url_excluded() {
		$_SERVER['REQUEST_URI'] = '/excluded-page';

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		// Create mock password groups.
		$group1     = new \stdClass();
		$group1->id = 1;

		$group2     = new \stdClass();
		$group2->id = 2;

		// Mock get_post_meta for group 1 - no exclusions.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_exclude_urls', true ),
				'return' => '',
			)
		);

		// Mock get_post_meta for group 2 - has exclusions.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 2, '_ppe_exclude_urls', true ),
				'return' => '/excluded-page, /another-excluded',
			)
		);

		$result = UrlMatcher::is_current_url_excluded( array( $group1, $group2 ) );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_current_url_excluded returns false when not excluded.
	 */
	public function test_is_current_url_excluded_returns_false() {
		$_SERVER['REQUEST_URI'] = '/normal-page';

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		$group     = new \stdClass();
		$group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_exclude_urls', true ),
				'return' => '/excluded-page',
			)
		);

		$result = UrlMatcher::is_current_url_excluded( array( $group ) );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_auto_protect_group.
	 */
	public function test_get_auto_protect_group() {
		$_SERVER['REQUEST_URI'] = '/protected-area/page';

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		// Create mock password groups.
		$group1     = new \stdClass();
		$group1->id = 1;

		$group2     = new \stdClass();
		$group2->id = 2;

		// Mock get_post_meta for group 1 - no auto-protect.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_auto_protect_urls', true ),
				'return' => '',
			)
		);

		// Mock get_post_meta for group 2 - has auto-protect.
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 2, '_ppe_auto_protect_urls', true ),
				'return' => '/protected-area/*',
			)
		);

		$result = UrlMatcher::get_auto_protect_group( array( $group1, $group2 ) );

		$this->assertNotNull( $result );
		$this->assertEquals( 2, $result->id );
	}

	/**
	 * Test get_auto_protect_group returns null when no match.
	 */
	public function test_get_auto_protect_group_returns_null() {
		$_SERVER['REQUEST_URI'] = '/public-page';

		WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
			)
		);

		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		$group     = new \stdClass();
		$group->id = 1;

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'args'   => array( 1, '_ppe_auto_protect_urls', true ),
				'return' => '/protected-area/*',
			)
		);

		$result = UrlMatcher::get_auto_protect_group( array( $group ) );

		$this->assertNull( $result );
	}

	/**
	 * Data provider for URL normalization tests.
	 *
	 * @return array
	 */
	public function urlNormalizationProvider() {
		return array(
			array( 'https://example.com/test-page', '/test-page' ),
			array( 'http://example.com/blog/post', '/blog/post' ),
			array( '/already-normalized', '/already-normalized' ),
			array( 'https://example.com/', '/' ),
		);
	}

	/**
	 * Test URL normalization.
	 *
	 * @dataProvider urlNormalizationProvider
	 *
	 * @param string $input    Input URL.
	 * @param string $expected Expected normalized URL.
	 */
	public function test_url_normalization( $input, $expected ) {
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $url ) {
				return parse_url( $url );
			}
		);

		$result = UrlMatcher::url_matches_pattern( $input, $expected );

		$this->assertTrue( $result );
	}
}

