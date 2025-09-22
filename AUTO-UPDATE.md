# WordPress Auto-Update Integration

Password Protect Elite now supports WordPress's built-in auto-update feature, providing seamless automatic updates from GitHub releases.

## How It Works

The plugin integrates with WordPress's auto-update system in multiple ways:

### 1. **Automatic Update Detection**
- Monitors GitHub releases for new versions
- Integrates with WordPress's update system
- Shows updates in WordPress Admin > Dashboard > Updates

### 2. **Auto-Update Eligibility**
- Automatically enables the plugin for WordPress auto-updates
- Checks WordPress and PHP version compatibility
- Ensures safe automatic updates

### 3. **User-Friendly Notifications**
- Shows admin notices when auto-updates are available
- Guides users to enable auto-updates
- Provides clear information about update status

## Enabling Auto-Updates

### Method 1: WordPress Admin Interface

1. **Go to Plugins Page**
   - Navigate to WordPress Admin > Plugins
   - Look for "Password Protect Elite" in the plugin list

2. **Enable Auto-Updates**
   - Click the "Enable auto-updates" link under the plugin name
   - Or use the bulk action "Enable auto-updates" for multiple plugins

3. **Verify Settings**
   - Auto-updates will now be enabled for the plugin
   - WordPress will automatically update when new releases are available

### Method 2: WordPress Code

Add this to your theme's `functions.php` or a custom plugin:

```php
// Enable auto-updates for Password Protect Elite
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( 'password-protect-elite/password-protect-elite.php' === $item->plugin ) {
        return true;
    }
    return $update;
}, 10, 2 );
```

### Method 3: WordPress CLI

```bash
wp plugin auto-updates enable password-protect-elite
```

## Auto-Update Process

### 1. **Release Creation**
When you create a new release on GitHub:
1. Push a tag (e.g., `v1.1.0`) to the repository
2. GitHub Actions automatically builds and releases the plugin
3. The release includes a downloadable zip file

### 2. **Update Detection**
WordPress automatically:
1. Checks for updates every 12 hours
2. Detects new releases from GitHub
3. Downloads update information
4. Shows available updates in admin

### 3. **Automatic Installation**
When auto-updates are enabled:
1. WordPress downloads the new version
2. Backs up the current version
3. Installs the update automatically
4. Notifies administrators of the update

## Compatibility Checks

The plugin performs several compatibility checks before enabling auto-updates:

### WordPress Version
- Checks if the current WordPress version meets requirements
- Ensures compatibility with the plugin's `RequiresAtLeast` setting

### PHP Version
- Verifies PHP version compatibility
- Ensures the server meets the plugin's `RequiresPHP` setting

### Plugin State
- Only enables auto-updates for active plugins
- Ensures the plugin is properly installed and configured

## Debugging Auto-Updates

### Enable Debug Logging

Add to your `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Check Update Logs

Auto-update attempts are logged to `/wp-content/debug.log`:

```
Password Protect Elite: Auto-update triggered for version 1.1.0
```

### Manual Update Check

Force WordPress to check for updates:

```php
// Check for plugin updates
delete_site_transient( 'update_plugins' );
wp_update_plugins();
```

## Troubleshooting

### Auto-Updates Not Working

1. **Check WordPress Version**
   - Ensure WordPress 5.5+ is installed
   - Auto-updates require WordPress 5.5 or higher

2. **Verify Plugin Status**
   - Ensure the plugin is active
   - Check that auto-updates are enabled in WordPress settings

3. **Check File Permissions**
   - Ensure WordPress can write to the plugins directory
   - Verify file permissions are correct

4. **Review Error Logs**
   - Check WordPress debug logs for errors
   - Look for PHP errors or warnings

### Update Failures

1. **Backup Issues**
   - Ensure sufficient disk space
   - Check backup directory permissions

2. **Network Issues**
   - Verify GitHub API connectivity
   - Check firewall settings

3. **Plugin Conflicts**
   - Temporarily disable other plugins
   - Test with a default theme

## Security Considerations

### GitHub Token (Optional)

For private repositories or to avoid API rate limits:

1. **Create GitHub Token**
   - Go to GitHub Settings > Developer settings
   - Generate a Personal Access Token with `repo` scope

2. **Add Token to Plugin**
   ```php
   $github_updater = new PPE_GitHub_Updater(
       __FILE__,
       'your-github-username',
       'your-repository-name',
       'your-github-token'  // Add your token here
   );
   ```

### Update Verification

The plugin verifies updates by:
- Checking GitHub API responses
- Validating download URLs
- Ensuring proper file signatures

## Best Practices

### 1. **Test Updates**
- Always test updates on staging environments
- Verify plugin functionality after updates

### 2. **Monitor Updates**
- Keep an eye on update logs
- Review changelogs before major updates

### 3. **Backup Regularly**
- Maintain regular site backups
- Test backup restoration procedures

### 4. **Stay Informed**
- Subscribe to plugin release notifications
- Follow the GitHub repository for updates

## Support

For issues with auto-updates:

1. **Check Documentation**: Review this guide and the main plugin documentation
2. **Enable Debug Logging**: Check WordPress debug logs for errors
3. **Test Manually**: Try updating the plugin manually first
4. **Contact Support**: Reach out through the GitHub repository issues page

## Conclusion

WordPress auto-update integration provides a seamless way to keep Password Protect Elite updated automatically. The system is designed to be safe, reliable, and user-friendly, ensuring your plugin stays current with the latest features and security updates.
