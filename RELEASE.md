# Release Management for Password Protect Elite

This document explains how to manage releases for the Password Protect Elite WordPress plugin using GitHub releases and automatic updates.

## Overview

The plugin uses a GitHub-based release system that:
- Automatically creates releases when Git tags are pushed
- Builds and packages the plugin for distribution
- Enables automatic updates in WordPress installations
- Provides a seamless update experience for users

## Components

### 1. GitHub Actions Workflow
- **File**: `.github/workflows/release-plugin.yml` (within the plugin directory)
- **Trigger**: Pushes to tags matching `v*` pattern (e.g., `v1.0.0`)
- **Actions**:
  - Builds plugin assets
  - Updates version numbers
  - Creates GitHub release
  - Generates plugin zip file
  - Uploads release assets

### 2. WordPress Update Checker
- **File**: `includes/class-github-updater.php`
- **Purpose**: Enables automatic updates from GitHub releases
- **Features**:
  - Checks GitHub API for new releases
  - Integrates with WordPress update system
  - Supports both public and private repositories
  - Caches API responses for performance

### 3. Release Script
- **File**: `scripts/release.sh`
- **Purpose**: Automates the release process
- **Features**:
  - Updates version numbers
  - Builds assets
  - Creates Git tags
  - Pushes to repository

## Setup Instructions

### 1. Configure GitHub Repository

1. The plugin is configured for the GitHub repository: [https://github.com/solepixel/password-protect-elite](https://github.com/solepixel/password-protect-elite)

2. The GitHub repository URL is already set in the plugin header:
   ```php
   * GitHub Plugin URI: https://github.com/solepixel/password-protect-elite
   ```

3. The GitHub updater is configured in `password-protect-elite.php`:
   ```php
   $github_updater = new PPE_GitHub_Updater(
       __FILE__,
       'solepixel',               // GitHub username
       'password-protect-elite',  // Repository name
       ''                         // GitHub token (optional)
   );
   ```

### 2. GitHub Token (Optional)

For private repositories or to avoid API rate limits:

1. Create a GitHub Personal Access Token:
   - Go to GitHub Settings > Developer settings > Personal access tokens
   - Generate a new token with `repo` scope
   - Add the token as a GitHub Secret named `GITHUB_TOKEN`

2. Update the updater initialization:
   ```php
   $github_updater = new PPE_GitHub_Updater(
       __FILE__,
       'solepixel',
       'password-protect-elite',
       'your-github-token'  // Add your token here
   );
   ```

## Creating a Release

### Method 1: Using the Release Script (Recommended)

1. Navigate to the plugin directory:
   ```bash
   cd wp-content/plugins/password-protect-elite
   ```

2. Run the release script:
   ```bash
   ./scripts/release.sh
   ```

3. Follow the prompts to enter the new version number

4. The script will:
   - Update version numbers in all files
   - Build assets (if package.json exists)
   - Commit changes
   - Create and push a Git tag
   - Trigger the GitHub Actions workflow

### Method 2: Manual Process

1. Update version in `password-protect-elite.php`:
   ```php
   Version: 1.1.0
   const PPE_VERSION = '1.1.0';
   ```

2. Update version in `readme.txt`:
   ```
   Stable tag: 1.1.0
   ```

3. Build assets (if needed):
   ```bash
   npm run build
   ```

4. Commit changes:
   ```bash
   git add .
   git commit -m "Bump version to 1.1.0"
   ```

5. Create and push tag:
   ```bash
   git tag -a v1.1.0 -m "Release version 1.1.0"
   git push origin main
   git push origin v1.1.0
   ```

## Version Numbering

Follow [Semantic Versioning](https://semver.org/) guidelines:

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

Examples:
- `1.0.0` - Initial release
- `1.0.1` - Bug fix
- `1.1.0` - New feature
- `2.0.0` - Breaking changes

## Testing Updates

### 1. Test Release Process

1. Create a test release with a patch version:
   ```bash
   ./scripts/release.sh
   # Enter: 1.0.1
   ```

2. Monitor the GitHub Actions workflow
3. Verify the release was created on GitHub
4. Check that the plugin zip was uploaded

### 2. Test Update Mechanism

1. Install an older version of the plugin on a test WordPress site
2. Go to WordPress Admin > Dashboard > Updates
3. The plugin should show an available update
4. Click "Update" to test the update process

### 3. Test Plugin Functionality

1. Verify the plugin activates correctly after update
2. Test all plugin features
3. Check that settings are preserved
4. Ensure no errors in WordPress debug log

## Troubleshooting

### GitHub Actions Workflow Fails

1. Check the Actions tab in your GitHub repository
2. Review the workflow logs for specific errors
3. Common issues:
   - Missing dependencies
   - Build failures
   - Permission issues

### Updates Not Showing in WordPress

1. Verify the GitHub repository URL is correct
2. Check that releases are being created on GitHub
3. Ensure the plugin zip file is being uploaded
4. Test with a fresh WordPress installation

### API Rate Limits

1. Add a GitHub token to avoid rate limits
2. The updater caches API responses for 12 hours
3. Consider using a GitHub App for higher rate limits

## Best Practices

1. **Always test releases** on a staging environment first
2. **Use semantic versioning** for consistent version numbers
3. **Write clear changelogs** in release descriptions
4. **Keep the main branch stable** - use feature branches for development
5. **Monitor update success rates** in WordPress installations
6. **Backup before updates** - encourage users to backup their sites

## File Structure

```
password-protect-elite/
├── .github/
│   └── workflows/
│       └── release-plugin.yml    # GitHub Actions workflow (plugin-specific)
├── includes/
│   └── class-github-updater.php  # WordPress update checker
├── scripts/
│   └── release.sh                # Release automation script
├── password-protect-elite.php    # Main plugin file
├── readme.txt                    # WordPress plugin readme
└── RELEASE.md                    # This documentation
```

**Note**: All files are contained within the plugin's own GitHub repository, not within the main WordPress site repository.

## Support

For issues with the release system:
1. Check the GitHub Actions logs
2. Review this documentation
3. Test on a clean WordPress installation
4. Check WordPress debug logs for errors

For plugin functionality issues:
1. Test with default WordPress theme
2. Disable other plugins
3. Check WordPress and PHP compatibility
4. Review the plugin's main documentation
