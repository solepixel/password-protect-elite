=== Password Protect Elite ===
Contributors: briandichiara
Tags: password, protection, security, gutenberg, blocks
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced password protection for WordPress with multiple password groups, custom blocks, and flexible redirect options.

== Description ==

Password Protect Elite is a comprehensive WordPress plugin that provides advanced password protection capabilities for your website. Unlike simple password protection plugins, this plugin offers multiple password groups, custom Gutenberg blocks, and flexible redirect options.

= Key Features =

* **Multiple Password Groups**: Create unlimited password groups for different types of protection
* **Global Site Protection**: Protect your entire website with a single password
* **Page-Level Protection**: Protect individual pages or posts with specific passwords
* **Content Block Protection**: Use Gutenberg blocks to protect specific content sections
* **Custom Gutenberg Blocks**: Two powerful blocks for password entry and content protection
* **Flexible Redirects**: Set custom redirect URLs for each password group
* **Session Management**: Remember validated passwords during user sessions
* **Admin Interface**: Easy-to-use admin interface for managing password groups
* **Responsive Design**: Works perfectly on all devices
* **WordPress Standards**: Follows WordPress coding standards and best practices

= Protection Types =

1. **Global Site Protection**: Protects the entire website with a single password
2. **Section/Page Protection**: Protects individual pages or posts
3. **Content Block Protection**: Protects specific content sections using blocks

= Gutenberg Blocks =

1. **Password Entry Block**: A form that allows users to enter a password and get redirected
2. **Protected Content Block**: Content that is hidden until the correct password is entered

= Use Cases =

* **Client Portals**: Create password-protected areas for different clients
* **Premium Content**: Protect premium content with specific passwords
* **Event Access**: Provide unique passwords for different events
* **Member Areas**: Create different access levels for members
* **Beta Testing**: Protect beta features with specific passwords
* **Site Maintenance**: Protect the entire site during maintenance

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/password-protect-elite` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Password Protect Elite' in the admin menu to configure your password groups
4. Add the password protection blocks to your pages or posts
5. Configure page-level protection using the meta box in the post editor

== Frequently Asked Questions ==

= How do I create password groups? =

Go to 'Password Protect Elite' > 'Password Groups' in your WordPress admin. Click 'Add New Password Group' and fill in the details.

= What's the difference between the protection types? =

* **Content Block**: Used with the Gutenberg blocks for protecting specific content
* **Section/Page**: Used for protecting entire pages or posts
* **Global Site**: Used for protecting the entire website

= How do I use the Gutenberg blocks? =

1. Add the 'Password Entry' block to create a password form
2. Add the 'Protected Content' block and add your content inside it
3. Configure the blocks in the sidebar settings

= Can I set different redirect URLs for different passwords? =

Yes! Each password group can have its own redirect URL. Users will be redirected to the specified URL after successful password entry.

= How long do validated passwords stay active? =

Validated passwords remain active for the duration of the user's session (until they close their browser or the session expires).

= Can I protect the entire site? =

Yes! Go to 'Password Protect Elite' > 'Global Settings' and enable global protection. Set a global password and optionally a redirect URL.

== Screenshots ==

1. Admin interface showing password groups
2. Gutenberg block editor with password protection blocks
3. Frontend password entry form
4. Protected content display
5. Global settings page

== Changelog ==

= 1.1.3 =
* Fix error in Github Updater script
* Fix to release script for plugin header version

= 1.1.2 =
* Attempt to fix release script.

= 1.1.1 =
* Attempt to fix deploy script.

= 1.1.0 =
* Attempt to fix issues with password form login.
* Added ability to disable failed attempt lockouts.
* Added Protection admin column on Pages list view.
* Added template view loader with filter for override.
* Added dynamic render feature to Protected Content block.
* Small code refactoring, cleanup, and optimizations.

= 1.0.3 =
* Attempt to fix deploy bug
* Added body classes

= 1.0.2 =
* Gitignore and README Updates

= 1.0.1 =
* Fixed Additional Passwords Redirect Bug
* Added Log Out Behavior setting for Password Groups
* Added Session Manager Class
* Added Unit Tests

= 1.0.0 =
* Initial release
* Multiple password groups support
* Global site protection
* Page-level protection
* Gutenberg blocks for password entry and content protection
* Flexible redirect options
* Session management
* Admin interface
* Responsive design
* WordPress coding standards compliance

== Upgrade Notice ==

= 1.0.0 =
Initial release of Password Protect Elite.

== Support ==

For support, please visit the [WordPress.org support forums](https://wordpress.org/support/plugin/password-protect-elite/) or contact the plugin author.

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. All password validation is handled locally on your server and no data is sent to external services.
