<?php
/**
 * Tests for SecureData class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\SecureData;
use WP_Mock;

/**
 * SecureData test case.
 */
class SecureDataTest extends TestCase {

	/**
	 * Test encrypt method with valid data.
	 */
	public function test_encrypt_with_valid_data() {
		// Mock wp_json_encode.
		$data = array(
			'type'           => 'test',
			'allowed_groups' => array( 1, 2 ),
		);

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'times'  => 1,
				'args'   => array( $data ),
				'return' => json_encode( $data ),
			)
		);

		$result = SecureData::encrypt( $data );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
		// Result should be base64 encoded.
		$this->assertNotFalse( base64_decode( $result, true ) );
	}

	/**
	 * Test encrypt returns false with non-array data.
	 */
	public function test_encrypt_returns_false_with_non_array() {
		$result = SecureData::encrypt( 'not an array' );

		$this->assertFalse( $result );
	}

	/**
	 * Test encrypt returns false when json_encode fails.
	 */
	public function test_encrypt_returns_false_when_json_encode_fails() {
		$data = array( 'key' => 'value' );

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'times'  => 1,
				'args'   => array( $data ),
				'return' => false,
			)
		);

		$result = SecureData::encrypt( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test decrypt with valid encrypted data.
	 */
	public function test_decrypt_with_valid_data() {
		// First encrypt some data.
		$original_data = array(
			'type'           => 'test',
			'allowed_groups' => array( 1, 2 ),
			'redirect_url'   => 'https://example.com',
		);

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'args'   => array( $original_data ),
				'return' => json_encode( $original_data ),
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted = SecureData::encrypt( $original_data );

		// Now decrypt it.
		$decrypted = SecureData::decrypt( $encrypted );

		$this->assertIsArray( $decrypted );
		$this->assertEquals( $original_data, $decrypted );
	}

	/**
	 * Test decrypt returns false with empty string.
	 */
	public function test_decrypt_returns_false_with_empty_string() {
		$result = SecureData::decrypt( '' );

		$this->assertFalse( $result );
	}

	/**
	 * Test decrypt returns false with invalid base64.
	 */
	public function test_decrypt_returns_false_with_invalid_base64() {
		$result = SecureData::decrypt( 'not valid base64!@#$%' );

		$this->assertFalse( $result );
	}

	/**
	 * Test decrypt returns false with tampered data.
	 */
	public function test_decrypt_returns_false_with_tampered_data() {
		// Create valid encrypted data.
		$data = array( 'test' => 'value' );

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return' => json_encode( $data ),
			)
		);

		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted = SecureData::encrypt( $data );

		// Tamper with the encrypted data.
		$tampered = substr( $encrypted, 0, -5 ) . 'XXXXX';

		$result = SecureData::decrypt( $tampered );

		$this->assertFalse( $result );
	}

	/**
	 * Test create_secure_form_data.
	 */
	public function test_create_secure_form_data() {
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => array( 1, 2, 3 ),
			'redirect_url'   => 'https://example.com/redirect',
		);

		// Mock wp_json_encode.
		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $data ) {
				return json_encode( $data );
			}
		);

		// Mock wp_create_nonce.
		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'times'  => 1,
				'args'   => array( 'ppe_secure_form_content' ),
				'return' => 'test_nonce_value',
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$result = SecureData::create_secure_form_data( $form_args );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test validate_secure_form_data with valid data.
	 */
	public function test_validate_secure_form_data_with_valid_data() {
		// Create secure form data.
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => array( 1, 2 ),
			'redirect_url'   => 'https://example.com',
		);

		// Mock wp_json_encode.
		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $data ) {
				return json_encode( $data );
			}
		);

		// Mock wp_create_nonce.
		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		// Mock wp_salt.
		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted_data = SecureData::create_secure_form_data( $form_args );

		// Mock wp_verify_nonce.
		WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$result = SecureData::validate_secure_form_data( $encrypted_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'allowed_groups', $result );
		$this->assertArrayHasKey( 'redirect_url', $result );
		$this->assertEquals( 'content', $result['type'] );
	}

	/**
	 * Test validate_secure_form_data returns false with invalid data.
	 */
	public function test_validate_secure_form_data_returns_false_with_invalid_data() {
		$result = SecureData::validate_secure_form_data( 'invalid_data' );

		$this->assertFalse( $result );
	}

	/**
	 * Test validate_secure_form_data returns false with invalid nonce.
	 */
	public function test_validate_secure_form_data_returns_false_with_invalid_nonce() {
		// Create secure form data.
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => array( 1, 2 ),
			'redirect_url'   => 'https://example.com',
		);

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $data ) {
				return json_encode( $data );
			}
		);

		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted_data = SecureData::create_secure_form_data( $form_args );

		// Mock wp_verify_nonce to return false.
		WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$result = SecureData::validate_secure_form_data( $encrypted_data );

		$this->assertFalse( $result );
	}

	/**
	 * Test validate_secure_form_data returns false with expired timestamp.
	 */
	public function test_validate_secure_form_data_returns_false_with_expired_timestamp() {
		// Manually create data with old timestamp.
		$old_data = array(
			'type'           => 'content',
			'allowed_groups' => array( 1, 2 ),
			'redirect_url'   => 'https://example.com',
			'timestamp'      => time() - 7200, // 2 hours ago.
			'nonce'          => 'test_nonce',
		);

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $data ) {
				return json_encode( $data );
			}
		);

		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted_data = SecureData::encrypt( $old_data );

		// Mock wp_verify_nonce to return true.
		WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'return' => true,
			)
		);

		$result = SecureData::validate_secure_form_data( $encrypted_data );

		// Should return false because data is older than 1 hour.
		$this->assertFalse( $result );
	}

	/**
	 * Test validate_secure_form_data with expected_type parameter.
	 */
	public function test_validate_secure_form_data_with_expected_type() {
		$form_args = array(
			'type'           => 'content',
			'allowed_groups' => array( 1, 2 ),
			'redirect_url'   => 'https://example.com',
		);

		WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'return_arg' => true,
			)
		)->andReturnUsing(
			function ( $data ) {
				return json_encode( $data );
			}
		);

		WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'return' => 'test_nonce',
			)
		);

		WP_Mock::userFunction(
			'wp_salt',
			array(
				'return' => 'test_salt',
			)
		);

		$encrypted_data = SecureData::create_secure_form_data( $form_args );

		WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'return' => true,
			)
		);

		// Validate with matching expected type.
		$result = SecureData::validate_secure_form_data( $encrypted_data, 'content' );
		$this->assertIsArray( $result );

		// Validate with non-matching expected type.
		$result = SecureData::validate_secure_form_data( $encrypted_data, 'general' );
		$this->assertFalse( $result );
	}
}

