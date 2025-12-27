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
	 * Menu Integration functionality
	 *
	 * @var ?MenuIntegration
	 */
	public ?MenuIntegration $menu_integration = null;

	/**
	 * Session Manager
	 *
	 * @var ?SessionManager
	 */
	public ?SessionManager $session_manager = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize session manager first (shared dependency).
		$this->session_manager = new SessionManager();

		// Initialize components.
		$this->admin            = new Admin\Admin();
		$this->password_manager = new PasswordManager( $this->session_manager );
		$this->database         = new Database();
		$this->password_groups  = new PasswordGroups();
		$this->blocks           = new Blocks();
		$this->frontend         = new Frontend();
		$this->page_protection  = new PageProtection();
		$this->url_matcher      = new UrlMatcher();
		$this->block_styles     = new BlockStyles();
		$this->menu_integration = new MenuIntegration( $this->session_manager );
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
