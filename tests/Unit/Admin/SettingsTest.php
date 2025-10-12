<?php
/**
 * Tests for Settings class.
 *
 * @package PasswordProtectElite\Tests\Unit\Admin
 */

namespace PasswordProtectElite\Tests\Unit\Admin;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\Admin\Settings;
use WP_Mock;

/**
 * Settings test case.
 */
class SettingsTest extends TestCase {

	/**
	 * Test get_session_duration_hours returns default value.
	 */
	public function test_get_session_duration_hours_default() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array(),
			)
		);

		$result = Settings::get_session_duration_hours();

		$this->assertEquals( 24, $result );
	}

	/**
	 * Test get_session_duration_hours returns custom value.
	 */
	public function test_get_session_duration_hours_custom() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array( 'session_duration_hours' => 48 ),
			)
		);

		$result = Settings::get_session_duration_hours();

		$this->assertEquals( 48, $result );
	}

	/**
	 * Test get_password_attempts_limit returns default value.
	 */
	public function test_get_password_attempts_limit_default() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array(),
			)
		);

		$result = Settings::get_password_attempts_limit();

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test get_password_attempts_limit returns custom value.
	 */
	public function test_get_password_attempts_limit_custom() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array( 'password_attempts_limit' => 10 ),
			)
		);

		$result = Settings::get_password_attempts_limit();

		$this->assertEquals( 10, $result );
	}

	/**
	 * Test get_lockout_duration_minutes returns default value.
	 */
	public function test_get_lockout_duration_minutes_default() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array(),
			)
		);

		$result = Settings::get_lockout_duration_minutes();

		$this->assertEquals( 15, $result );
	}

	/**
	 * Test get_lockout_duration_minutes returns custom value.
	 */
	public function test_get_lockout_duration_minutes_custom() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_settings', array() ),
				'return' => array( 'lockout_duration_minutes' => 30 ),
			)
		);

		$result = Settings::get_lockout_duration_minutes();

		$this->assertEquals( 30, $result );
	}
}

