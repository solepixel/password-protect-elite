<?php
/**
 * GitHub Updater for WordPress Plugins
 *
 * This class handles automatic updates from GitHub releases for WordPress plugins.
 * It integrates with the WordPress update system to check for new releases.
 *
 * @package PasswordProtectElite
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PPE_GitHub_Updater
 *
 * Handles automatic updates from GitHub releases for WordPress plugins.
 *
 * @since 1.0.0
 */
class PPE_GitHub_Updater {

	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * GitHub username
	 *
	 * @var string
	 */
	private $github_user;

	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	private $github_repo;

	/**
	 * GitHub token
	 *
	 * @var string
	 */
	private $github_token;

	/**
	 * Plugin data
	 *
	 * @var array
	 */
	private $plugin_data = array();

	/**
	 * GitHub repository data
	 *
	 * @var array
	 */
	private $github_data = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file  Path to the plugin file.
	 * @param string $github_user  GitHub username.
	 * @param string $github_repo  GitHub repository name.
	 * @param string $github_token GitHub token (optional).
	 */
	public function __construct( $plugin_file, $github_user, $github_repo, $github_token = '' ) {
		$this->plugin_file  = sanitize_text_field( $plugin_file );
		$this->github_user  = sanitize_text_field( $github_user );
		$this->github_repo  = sanitize_text_field( $github_repo );
		$this->github_token = sanitize_text_field( $github_token );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_api_call' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ), 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient WordPress transient object.
	 * @return object Modified transient object.
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get plugin data.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->plugin_data = get_plugin_data( $this->plugin_file );
		$current_version   = $this->plugin_data['Version'];

		// Get latest release from GitHub.
		$latest_release = $this->get_latest_release();
		if ( ! $latest_release ) {
			return $transient;
		}

		$latest_version = ltrim( $latest_release['tag_name'], 'v' );

		// Check if update is needed.
		if ( version_compare( $current_version, $latest_version, '<' ) ) {
			$transient->response[ plugin_basename( $this->plugin_file ) ] = (object) array(
				'slug'          => dirname( plugin_basename( $this->plugin_file ) ),
				'plugin'        => plugin_basename( $this->plugin_file ),
				'new_version'   => $latest_version,
				'url'           => $this->plugin_data['PluginURI'],
				'package'       => $this->get_download_url( $latest_release ),
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => $this->plugin_data['TestedUpTo'],
				'requires_php'  => $this->plugin_data['RequiresPHP'],
				'compatibility' => new stdClass(),
			);
		}

		return $transient;
	}

	/**
	 * Get latest release from GitHub.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false Release data or false on failure.
	 */
	private function get_latest_release() {
		$cache_key = 'ppe_github_latest_release';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			rawurlencode( $this->github_user ),
			rawurlencode( $this->github_repo )
		);

		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress-Plugin-Update-Checker',
		);

		if ( ! empty( $this->github_token ) ) {
			$headers['Authorization'] = 'token ' . $this->github_token;
		}

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => $headers,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body    = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( ! $release || ! isset( $release['tag_name'] ) ) {
			return false;
		}

		// Cache for 12 hours.
		set_transient( $cache_key, $release, 12 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Get download URL for the release.
	 *
	 * @since 1.0.0
	 *
	 * @param array $release Release data.
	 * @return string Download URL.
	 */
	private function get_download_url( $release ) {
		// Look for the plugin zip file in assets.
		if ( isset( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && strpos( $asset['name'], 'password-protect-elite-' ) === 0 && strpos( $asset['name'], '.zip' ) !== false ) {
					return $asset['browser_download_url'];
				}
			}
		}

		// Fallback to source zip.
		return $release['zipball_url'];
	}

	/**
	 * Plugin API call for update information.
	 *
	 * @since 1.0.0
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested.
	 * @param object             $args Plugin API arguments.
	 * @return false|object|array Modified result.
	 */
	public function plugin_api_call( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || dirname( plugin_basename( $this->plugin_file ) ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$latest_version = ltrim( $release['tag_name'], 'v' );

		$result                 = new stdClass();
		$result->name           = $this->plugin_data['Name'];
		$result->slug           = $args->slug;
		$result->version        = $latest_version;
		$result->author         = $this->plugin_data['Author'];
		$result->author_profile = $this->plugin_data['AuthorURI'];
		$result->homepage       = $this->plugin_data['PluginURI'];
		$result->requires       = $this->plugin_data['RequiresAtLeast'];
		$result->tested         = $this->plugin_data['TestedUpTo'];
		$result->requires_php   = $this->plugin_data['RequiresPHP'];
		$result->last_updated   = $release['published_at'];
		$result->sections       = array(
			'description' => $this->plugin_data['Description'],
			'changelog'   => $this->format_changelog( $release ),
		);

		$result->download_link = $this->get_download_url( $release );

		return $result;
	}

	/**
	 * Format changelog from release data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $release Release data.
	 * @return string Formatted changelog.
	 */
	private function format_changelog( $release ) {
		$changelog = '';

		if ( isset( $release['body'] ) && ! empty( $release['body'] ) ) {
			$changelog = $release['body'];
		} else {
			$changelog = 'No changelog available for this release.';
		}

		return $changelog;
	}

	/**
	 * Handle download authentication.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $reply Whether to bail without returning the package.
	 * @param string $package The package file name.
	 * @return bool|WP_Error
	 */
	public function upgrader_pre_download( $reply, $package ) {
		if ( strpos( $package, 'api.github.com' ) === false ) {
			return $reply;
		}

		if ( empty( $this->github_token ) ) {
			return $reply;
		}

		// Add authentication header for private repositories.
		add_filter( 'http_request_args', array( $this, 'add_auth_header' ), 10, 2 );

		return $reply;
	}

	/**
	 * Add authentication header for GitHub API requests.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $args HTTP request arguments.
	 * @param string $url Request URL.
	 * @return array Modified arguments.
	 */
	public function add_auth_header( $args, $url ) {
		if ( strpos( $url, 'api.github.com' ) !== false && ! empty( $this->github_token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->github_token;
		}

		return $args;
	}
}
