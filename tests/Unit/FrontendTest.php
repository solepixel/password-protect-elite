<?php
/**
 * Tests for Frontend class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\Frontend;
use WP_Mock;
use Mockery;

/**
 * Frontend test case.
 */
class FrontendTest extends TestCase {

	/**
	 * Test check_global_protection returns early when no groups.
	 */
	public function test_check_global_protection_returns_early_when_no_groups() {
		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_groups',
			array(
				'times'  => 1,
				'args'   => array( 'global_site' ),
				'return' => array(),
			)
		);

		$frontend = new Frontend();
		$frontend->check_global_protection();

		// If we get here without errors, test passed.
		$this->assertTrue( true );
	}

	/**
	 * Test check_global_protection skips for admin users.
	 */
	public function test_check_global_protection_skips_for_admin() {
		$group     = new \stdClass();
		$group->id = 1;

		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_groups',
			array(
				'times'  => 1,
				'return' => array( $group ),
			)
		);

		WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'manage_options' ),
				'return' => true,
			)
		);

		$frontend = new Frontend();
		$frontend->check_global_protection();

		// If we get here without errors, test passed.
		$this->assertTrue( true );
	}

	/**
	 * Test check_global_protection skips for AJAX requests.
	 */
	public function test_check_global_protection_skips_for_ajax() {
		$group     = new \stdClass();
		$group->id = 1;

		WP_Mock::userFunction(
			'PasswordProtectElite\Database::get_password_groups',
			array(
				'return' => array( $group ),
			)
		);

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

		$frontend = new Frontend();
		$frontend->check_global_protection();

		$this->assertTrue( true );
	}

	/**
	 * Test check_auto_protection returns early for admin.
	 */
	public function test_check_auto_protection_skips_for_admin() {
		WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'manage_options' ),
				'return' => true,
			)
		);

		$frontend = new Frontend();
		$frontend->check_auto_protection();

		$this->assertTrue( true );
	}

	/**
	 * Test check_auto_protection returns early when no auto-protect group.
	 */
	public function test_check_auto_protection_returns_when_no_group() {
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
			'PasswordProtectElite\Database::get_password_groups',
			array(
				'times'  => 1,
				'return' => array(),
			)
		);

		WP_Mock::userFunction(
			'PasswordProtectElite\UrlMatcher::get_auto_protect_group',
			array(
				'times'  => 1,
				'return' => null,
			)
		);

		$frontend = new Frontend();
		$frontend->check_auto_protection();

		$this->assertTrue( true );
	}
}

