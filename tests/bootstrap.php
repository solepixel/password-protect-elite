<?php
/**
 * PHPUnit bootstrap file for Password Protect Elite tests.
 *
 * @package PasswordProtectElite
 */

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WP_Mock.
WP_Mock::bootstrap();

// Define WordPress constants that might be used in the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'OPENSSL_RAW_DATA' ) ) {
	define( 'OPENSSL_RAW_DATA', 1 );
}

// Define plugin constants.
define( 'PPE_VERSION', '1.0.0' );
define( 'PPE_PLUGIN_FILE', dirname( __DIR__ ) . '/password-protect-elite.php' );
define( 'PPE_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'PPE_PLUGIN_URL', 'https://example.com/wp-content/plugins/password-protect-elite/' );

// Mock WordPress functions that are commonly used.
if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock esc_html__
	 *
	 * @param string $text Text to escape.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock __
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Mock esc_url_raw
	 *
	 * @param string $url URL to escape.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return strip_tags( $str );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Mock sanitize_textarea_field
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_textarea_field( $str ) {
		return strip_tags( $str );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Mock absint
	 *
	 * @param mixed $value Value to convert to absolute integer.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Mock wp_parse_args
	 *
	 * @param array $args     Arguments to parse.
	 * @param array $defaults Default arguments.
	 * @return array
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args =& $args;
		} else {
			parse_str( $args, $parsed_args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	/**
	 * Mock wp_salt
	 *
	 * @param string $scheme Salt scheme.
	 * @return string
	 */
	function wp_salt( $scheme = 'auth' ) {
		return 'mock_salt_value_' . $scheme;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	/**
	 * Mock is_singular
	 *
	 * @return bool
	 */
	function is_singular() {
		return true;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Mock get_post
	 *
	 * @param int $post_id Post ID.
	 * @return object|null
	 */
	function get_post( $post_id ) {
		return null;
	}
}

