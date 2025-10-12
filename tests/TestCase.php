<?php
/**
 * Base TestCase for Password Protect Elite tests.
 *
 * @package PasswordProtectElite\Tests
 */

namespace PasswordProtectElite\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use WP_Mock;
use Mockery;

/**
 * Base test case class.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Setup method that runs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * Teardown method that runs after each test.
	 */
	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Assert that all WP_Mock conditions have been met.
	 */
	protected function assertConditionsMet(): void {
		$this->assertTrue( true );
		// WP_Mock's tearDown will throw exceptions if expectations aren't met.
	}

	/**
	 * Helper method to get a protected or private method for testing.
	 *
	 * @param object|string $object_or_class Object instance or class name.
	 * @param string        $method_name     Method name.
	 * @return \ReflectionMethod
	 */
	protected function getProtectedMethod( $object_or_class, $method_name ) {
		$reflection = new \ReflectionClass( $object_or_class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Helper method to get a protected or private property for testing.
	 *
	 * @param object $object        Object instance.
	 * @param string $property_name Property name.
	 * @return mixed
	 */
	protected function getProtectedProperty( $object, $property_name ) {
		$reflection = new \ReflectionClass( $object );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		return $property->getValue( $object );
	}

	/**
	 * Helper method to set a protected or private property for testing.
	 *
	 * @param object $object        Object instance.
	 * @param string $property_name Property name.
	 * @param mixed  $value         Value to set.
	 */
	protected function setProtectedProperty( $object, $property_name, $value ) {
		$reflection = new \ReflectionClass( $object );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $value );
	}

	/**
	 * Mock WordPress transient functions.
	 *
	 * @param string $transient Transient name.
	 * @param mixed  $value     Value to return.
	 */
	protected function mockGetTransient( $transient, $value ) {
		WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'args'   => array( $transient ),
				'return' => $value,
			)
		);
	}

	/**
	 * Mock WordPress set_transient function.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Value to set.
	 * @param int    $expiration Expiration time.
	 */
	protected function mockSetTransient( $transient, $value, $expiration ) {
		WP_Mock::userFunction(
			'set_transient',
			array(
				'times' => 1,
				'args'  => array( $transient, $value, $expiration ),
			)
		);
	}

	/**
	 * Mock WordPress delete_transient function.
	 *
	 * @param string $transient Transient name.
	 */
	protected function mockDeleteTransient( $transient ) {
		WP_Mock::userFunction(
			'delete_transient',
			array(
				'times' => 1,
				'args'  => array( $transient ),
			)
		);
	}
}

