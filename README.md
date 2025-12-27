# Password Protect Elite

Advanced password protection for WordPress with multiple password groups, custom blocks, and flexible redirect options.

## Features

- **Multiple Password Groups**: Create unlimited password groups with different access levels
- **Flexible Protection Types**: Global site, page/section, or content block protection
- **Gutenberg Blocks**: Password Entry and Protected Content blocks
- **Auto-Protection**: Automatically protect URLs matching patterns
- **URL Exclusions**: Exclude specific URLs from protection using wildcards
- **Multiple Passwords**: Each group can have a master password plus additional passwords
- **Redirect Options**: Redirect users after successful password entry
- **Session Management**: Remember validated passwords across page loads

## Installation

1. Upload the plugin files to `/wp-content/plugins/password-protect-elite/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Password Groups' to create your first password group

## Usage

### Creating Password Groups

1. Navigate to **Password Groups** in your WordPress admin
2. Click **Add New Password Group**
3. Fill in the details:
   - **Name**: Descriptive name for the group
   - **Master Password**: Primary password for access
   - **Additional Passwords**: Other passwords that grant the same access
   - **Protection Type**: Choose from Global Site, General, Section/Page, or Content Block
   - **Redirect URL**: Optional URL to redirect after successful entry

### Using Gutenberg Blocks

#### Password Entry Block
- Add a password entry form to any page or post
- Configure which password groups can use the form
- Customize button text, placeholder, and redirect URL

#### Protected Content Block
- Hide content until the correct password is entered
- Add any content inside the block
- Configure which password groups can unlock the content

### Page Protection

1. Edit any page or post
2. Look for the **Password Protect** meta box in the sidebar
3. Select a password group to protect the entire page

### Global Site Protection

1. Create a password group with **Global Site** protection type
2. The entire website will be protected with that password
3. Use **Exclude URLs** to exclude specific pages from protection

## Advanced Features

### URL Exclusions
Use wildcards to exclude URLs from protection:
- `/admin/*` - Exclude all admin pages
- `/login/*` - Exclude login pages
- `/api/*` - Exclude API endpoints

### Auto-Protection
Automatically protect URLs matching patterns:
- `/private/*` - Protect all URLs starting with /private/
- `/members/*` - Protect all member pages
- `/premium/*` - Protect all premium content

### Multiple Passwords
Each password group supports:
- **Master Password**: Primary password for the group
- **Additional Passwords**: Multiple passwords that grant the same access level

## Development

### Prerequisites
- Node.js 16.0.0 or higher
- npm 8.0.0 or higher
- PHP 8.2 or higher

### Setup
```bash
# Install dependencies
npm install

# Build blocks for production
npm run build

# Start development mode
npm run start

# Run linting
npm run check
```

### Project Structure
```
password-protect-elite/
├── src/
│   ├── classes/           # PHP classes with proper namespaces
│   │   ├── Admin/         # Admin functionality
│   │   ├── Blocks.php     # Block management
│   │   ├── Core.php       # Main plugin class
│   │   ├── Database.php   # Database operations
│   │   ├── Frontend.php   # Frontend functionality
│   │   └── ...
│   └── blocks/            # Gutenberg blocks
│       ├── password-entry/
│       └── protected-content/
├── assets/                # Static assets
└── includes/              # Helper functions
```

### Development
The plugin uses server-side rendering for blocks, so no complex build process is needed:

```bash
# Run linting and formatting checks
npm run build

# Development mode (same as build)
npm run dev
```

## Support

- **Documentation**: Check the Help page in WordPress admin
- **Issues**: Report bugs on GitHub
- **Contact**: brian@briantics.com

## Changelog

### 1.1.4
- Readme file change log corrections
- Safer uninstall process.
- Upgrade bug fix
- Settings page text formatting fix
- Small fix to dynamic change log generation

### 1.1.3
- Fix error in Github Updater script
- Fix to release script for plugin header version

### 1.1.2
- Attempt to fix release script.

### 1.1.1
- Attempt to fix deploy script.

### 1.1.0
- Attempt to fix issues with password form login.
- Added ability to disable failed attempt lockouts.
- Added Protection admin column on Pages list view.
- Added template view loader with filter for override.
- Added dynamic render feature to Protected Content block.
- Small code refactoring, cleanup, and optimizations.

### 1.0.3
- Attempt to fix deploy bug
- Added body classes

### 1.0.2
- Gitignore and README Updates

### 1.0.1
- Fixed Additional Passwords Redirect Bug
- Added Log Out Behavior setting for Password Groups
- Added Session Manager Class
- Added Unit Tests

### 1.0.0
- Initial release
- Multiple password groups
- Gutenberg blocks
- Page protection
- Global site protection
- Auto-protection and URL exclusions

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by [Briantics, Inc.](https://b7s.co/)
