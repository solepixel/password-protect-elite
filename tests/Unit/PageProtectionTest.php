<?php
/**
 * Tests for PageProtection class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\PageProtection;
use WP_Mock;

/**
 * PageProtection test case.
 */
class PageProtectionTest extends TestCase {

	/**
	 * Test add_meta_boxes adds meta box for public post types.
	 */
	public function test_add_meta_boxes() {
		WP_Mock::userFunction(
			'get_post_types',
			array(
				'times'  => 1,
				'args'   => array( array( 'public' => true ), 'names' ),
				'return' => array( 'post', 'page' ),
			)
		);

		WP_Mock::userFunction(
			'add_meta_box',
			array(
				'times' => 2,
			)
		);

		WP_Mock::userFunction(
			'__',
			array(
				'return_arg' => 0,
			)
		);

		$page_protection = new PageProtection();
		$page_protection->add_meta_boxes();

		$this->assertTrue( true );
	}

	/**
	 * Test check_page_protection returns early for admin.
	 */
	public function test_check_page_protection_returns_for_admin() {
		WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'manage_options' ),
				'return' => true,
			)
		);

		$page_protection = new PageProtection();
		$page_protection->check_page_protection();

		$this->assertTrue( true );
	}

	/**
	 * Test check_page_protection returns early for AJAX.
	 */
	public function test_check_page_protection_returns_for_ajax() {
		WP_Mock::userFunction(
			'current_user_can',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_doing_ajax',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$page_protection = new PageProtection();
		$page_protection->check_page_protection();

		$this->assertTrue( true );
	}

	/**
	 * Test check_page_protection returns when not singular.
	 */
	public function test_check_page_protection_returns_when_not_singular() {
		WP_Mock::userFunction(
			'current_user_can',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'wp_doing_ajax',
			array(
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'is_singular',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$page_protection = new PageProtection();
		$page_protection->check_page_protection();

		$this->assertTrue( true );
	}
}

