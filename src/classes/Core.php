<?php
/**
 * Core API
 *
 * @package PasswordProtectElite
 */

namespace PasswordProtectElite;

/**
 * Core API
 */
class Core {

	/**
	 * Instance of this class.
	 *
	 * @var ?Core
	 */
	private static ?Core $instance = null;

	/**
	 * Admin functionality
	 *
	 * @var ?Admin\Admin
	 */
	public ?Admin\Admin $admin = null;

	/**
	 * Admin Help functionality
	 *
	 * @var ?Admin\Help
	 */
	public ?Admin\Help $admin_help = null;

	/**
	 * Admin Settings functionality
	 *
	 * @var ?Admin\Settings
	 */
	public ?Admin\Settings $admin_settings = null;

	/**
	 * Password Manager
	 *
	 * @var ?PasswordManager
	 */
	public ?PasswordManager $password_manager = null;

	/**
	 * Database operations
	 *
	 * @var ?Database
	 */
	public ?Database $database = null;

	/**
	 * Password Groups CPT
	 *
	 * @var ?PasswordGroups
	 */
	public ?PasswordGroups $password_groups = null;

	/**
	 * Blocks functionality
	 *
	 * @var ?Blocks
	 */
	public ?Blocks $blocks = null;

	/**
	 * Frontend functionality
	 *
	 * @var ?Frontend
	 */
	public ?Frontend $frontend = null;

	/**
	 * Page Protection functionality
	 *
	 * @var ?PageProtection
	 */
	public ?PageProtection $page_protection = null;

	/**
	 * URL Matcher functionality
	 *
	 * @var ?UrlMatcher
	 */
	public ?UrlMatcher $url_matcher = null;

	/**
	 * Block Styles functionality
	 *
	 * @var ?BlockStyles
	 */
	public ?BlockStyles $block_styles = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize components.
		$this->admin            = new Admin\Admin();
		$this->admin_help       = new Admin\Help();
		$this->admin_settings   = new Admin\Settings();
		$this->password_manager = new PasswordManager();
		$this->database         = new Database();
		$this->password_groups  = new PasswordGroups();
		$this->blocks           = new Blocks();
		$this->frontend         = new Frontend();
		$this->page_protection  = new PageProtection();
		$this->url_matcher      = new UrlMatcher();
		$this->block_styles     = new BlockStyles();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @return Core
	 */
	public static function instance(): Core {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get password groups
	 *
	 * @param string $type Optional protection type filter.
	 * @return array
	 */
	public function get_password_groups( $type = null ) {
		return $this->database->get_password_groups( $type );
	}

	/**
	 * Get password group by ID
	 *
	 * @param int $id Password group ID.
	 * @return object|null
	 */
	public function get_password_group( $id ) {
		return $this->database->get_password_group( $id );
	}

	/**
	 * Validate password
	 *
	 * @param string $password Password to validate.
	 * @param string $type     Protection type (optional).
	 * @return object|null Password group or null if invalid.
	 */
	public function validate_password( $password, $type = null ) {
		return $this->database->validate_password( $password, $type );
	}
}
