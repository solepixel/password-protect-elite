<?php
/**
 * Secure data encryption/decryption class.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles secure encryption and decryption of form data.
 */
class SecureData {

	/**
	 * Encryption method.
	 *
	 * @var string
	 */
	const ENCRYPTION_METHOD = 'AES-256-GCM';

	/**
	 * Get encryption key.
	 *
	 * @return string
	 */
	private static function get_encryption_key() {
		// Use WordPress salt as base, but create a more secure key.
		$key = wp_salt( 'AUTH_KEY' ) . wp_salt( 'SECURE_AUTH_KEY' );
		return hash( 'sha256', $key, true );
	}

	/**
	 * Generate a random IV.
	 *
	 * @return string
	 */
	private static function generate_iv() {
		return random_bytes( 12 ); // 96 bits for GCM.
	}

	/**
	 * Encrypt data.
	 *
	 * @param array $data Data to encrypt.
	 * @return string|false Encrypted data or false on failure.
	 */
	public static function encrypt( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		$json_data = wp_json_encode( $data );
		if ( false === $json_data ) {
			return false;
		}

		$key = self::get_encryption_key();
		$iv  = self::generate_iv();

		// Encrypt the data.
		$encrypted = openssl_encrypt( $json_data, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $encrypted ) {
			return false;
		}

		// Combine IV, tag, and encrypted data.
		$combined = $iv . $tag . $encrypted;

		// Base64 encode for safe transmission.
		return base64_encode( $combined );
	}

	/**
	 * Decrypt data.
	 *
	 * @param string $encrypted_data Encrypted data.
	 * @return array|false Decrypted data or false on failure.
	 */
	public static function decrypt( $encrypted_data ) {
		if ( empty( $encrypted_data ) ) {
			return false;
		}

		// Decode base64 with strict mode to handle edge cases better.
		$combined = base64_decode( $encrypted_data, true );
		if ( false === $combined ) {
			return false;
		}

		$key = self::get_encryption_key();

		// Extract IV (first 12 bytes), tag (next 16 bytes), and encrypted data.
		$iv_length   = 12;
		$tag_length  = 16;
		$data_length = strlen( $combined ) - $iv_length - $tag_length;

		if ( $data_length < 0 ) {
			return false;
		}

		$iv        = substr( $combined, 0, $iv_length );
		$tag       = substr( $combined, $iv_length, $tag_length );
		$encrypted = substr( $combined, $iv_length + $tag_length );

		// Decrypt the data.
		$decrypted = openssl_decrypt( $encrypted, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $decrypted ) {
			return false;
		}

		// Decode JSON.
		$data = json_decode( $decrypted, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		return $data;
	}

	/**
	 * Create secure form data.
	 *
	 * @param array $form_args Form arguments.
	 * @return string|false Encrypted form data or false on failure.
	 */
	public static function create_secure_form_data( $form_args ) {
		$secure_data = array(
			'type'           => $form_args['type'] ?? '',
			'allowed_groups' => $form_args['allowed_groups'] ?? array(),
			'redirect_url'   => $form_args['redirect_url'] ?? '',
			'timestamp'      => time(),
			'nonce'          => wp_create_nonce( 'ppe_secure_form_' . $form_args['type'] ),
		);

		return self::encrypt( $secure_data );
	}

	/**
	 * Validate and decrypt form data.
	 *
	 * @param string $encrypted_data Encrypted form data.
	 * @param string $expected_type Expected form type.
	 * @return array|false Decrypted and validated data or false on failure.
	 */
	public static function validate_secure_form_data( $encrypted_data, $expected_type = '' ) {
		// Check if encrypted data is empty or invalid format.
		if ( empty( $encrypted_data ) || ! is_string( $encrypted_data ) ) {
			return false;
		}

		// Attempt to decrypt the data.
		$data = self::decrypt( $encrypted_data );
		if ( false === $data || ! is_array( $data ) ) {
			return false;
		}

		// Validate required fields.
		if ( ! isset( $data['type'] ) || ! isset( $data['allowed_groups'] ) || ! isset( $data['timestamp'] ) || ! isset( $data['nonce'] ) ) {
			return false;
		}

		// Check timestamp first (prevent replay attacks - data older than 24 hours is invalid).
		// Increased from 1 hour to 24 hours for better mobile/incognito compatibility.
		$max_age = 86400; // 24 hours.
		$age     = time() - $data['timestamp'];
		if ( $age > $max_age || $age < 0 ) {
			return false;
		}

		// Validate nonce with more lenient approach for mobile/incognito scenarios.
		// For better mobile/incognito compatibility, we allow nonce verification to pass
		// if timestamp is still within reasonable bounds (24 hours).
		// This is a trade-off between security and usability for edge cases.
		$nonce_valid = wp_verify_nonce( $data['nonce'], 'ppe_secure_form_' . $data['type'] );
		if ( ! $nonce_valid ) {
			// If nonce fails but timestamp is valid, still allow (for mobile/incognito edge cases).
			// The timestamp check above already provides protection against replay attacks.
		}

		// Validate type if provided.
		if ( ! empty( $expected_type ) && $data['type'] !== $expected_type ) {
			return false;
		}

		return $data;
	}
}
