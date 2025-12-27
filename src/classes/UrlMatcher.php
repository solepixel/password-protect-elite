<?php
/**
 * URL matching functionality for password protection.
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles URL matching for password protection rules.
 */
class UrlMatcher {

	/**
	 * Check if a URL matches any of the given patterns.
	 *
	 * @param string $url      The URL to check.
	 * @param string $patterns Comma-separated or newline-separated list of URL patterns.
	 * @return bool True if URL matches any pattern.
	 */
	public static function url_matches_patterns( $url, $patterns ) {
		if ( empty( $patterns ) ) {
			return false;
		}

		// Split patterns by newlines and commas.
		$pattern_list = preg_split( '/[\r\n,]+/', $patterns );
		$pattern_list = array_map( 'trim', $pattern_list );
		$pattern_list = array_filter( $pattern_list );

		foreach ( $pattern_list as $pattern ) {
			if ( self::url_matches_pattern( $url, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a URL matches a single pattern.
	 *
	 * @param string $url     The URL to check.
	 * @param string $pattern The pattern to match against.
	 * @return bool True if URL matches pattern.
	 */
	public static function url_matches_pattern( $url, $pattern ) {
		if ( empty( $pattern ) ) {
			return false;
		}

		// Normalize the URL.
		$url     = self::normalize_url( $url );
		$pattern = self::normalize_url( $pattern );

		// If pattern contains wildcard, use fnmatch.
		if ( strpos( $pattern, '*' ) !== false ) {
			return fnmatch( $pattern, $url );
		}

		// Exact match.
		return $url === $pattern;
	}

	/**
	 * Normalize a URL for comparison.
	 *
	 * @param string $url The URL to normalize.
	 * @return string Normalized URL.
	 */
	private static function normalize_url( $url ) {
		// Remove protocol and domain for relative URLs.
		$parsed = wp_parse_url( $url );

		if ( $parsed && isset( $parsed['path'] ) ) {
			$path = $parsed['path'];

			// Ensure path starts with /.
			if ( ! empty( $path ) && '/' !== $path[0] ) {
				$path = '/' . $path;
			}

			return $path;
		}

		// If no path, return the original URL.
		return $url;
	}

	/**
	 * Get the current request URL.
	 *
	 * @return string Current request URL.
	 */
	public static function get_current_url() {
		$request_uri = \wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
		return $request_uri;
	}

	/**
	 * Check if current URL should be excluded from protection.
	 *
	 * @param array $password_groups Array of password group objects.
	 * @return bool True if current URL should be excluded.
	 */
	public static function is_current_url_excluded( $password_groups ) {
		$current_url = self::get_current_url();

		foreach ( $password_groups as $group ) {
			$exclude_urls = get_post_meta( $group->id, '_ppe_exclude_urls', true );
			if ( ! empty( $exclude_urls ) && self::url_matches_patterns( $current_url, $exclude_urls ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get password group that should auto-protect the current URL.
	 *
	 * @param array $password_groups Array of password group objects.
	 * @return object|null Password group that should protect current URL, or null.
	 */
	public static function get_auto_protect_group( $password_groups ) {
		$current_url = self::get_current_url();

		foreach ( $password_groups as $group ) {
			$auto_protect_urls = get_post_meta( $group->id, '_ppe_auto_protect_urls', true );
			if ( ! empty( $auto_protect_urls ) && self::url_matches_patterns( $current_url, $auto_protect_urls ) ) {
				return $group;
			}
		}

		return null;
	}
}
