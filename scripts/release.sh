#!/bin/bash

# Password Protect Elite - Release Script
# This script automates the process of creating a new release

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a git repository. Please run this script from the plugin root directory."
    exit 1
fi

# Check if we have uncommitted changes
if ! git diff-index --quiet HEAD --; then
    print_error "You have uncommitted changes. Please commit or stash them before creating a release."
    exit 1
fi

# Get current version from plugin file
CURRENT_VERSION=$(grep "Version:" password-protect-elite.php | sed 's/.*Version: *//' | sed 's/ .*//')
print_status "Current version: $CURRENT_VERSION"

# Get new version from user
echo -n "Enter new version (current: $CURRENT_VERSION): "
read NEW_VERSION

if [ -z "$NEW_VERSION" ]; then
    print_error "Version cannot be empty."
    exit 1
fi

# Validate version format (basic check)
if ! echo "$NEW_VERSION" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$'; then
    print_error "Version must be in format X.Y.Z (e.g., 1.0.0)"
    exit 1
fi

# Check if version is different
if [ "$CURRENT_VERSION" = "$NEW_VERSION" ]; then
    print_warning "Version is the same as current version. Continue anyway? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        print_status "Release cancelled."
        exit 0
    fi
fi

print_status "Creating release for version $NEW_VERSION..."

# Update version in plugin file
print_status "Updating plugin version..."
sed -i.bak "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" password-protect-elite.php
sed -i.bak "s/const PPE_VERSION = '$CURRENT_VERSION'/const PPE_VERSION = '$NEW_VERSION'/" password-protect-elite.php

# Update version in readme.txt
print_status "Updating readme.txt..."
sed -i.bak "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" readme.txt

# Clean up backup files
rm -f *.bak

# Build assets if package.json exists
if [ -f "package.json" ]; then
    print_status "Building assets..."
    if command -v npm &> /dev/null; then
        npm run build
        print_success "Assets built successfully"
    else
        print_warning "npm not found, skipping asset build"
    fi
fi

# Install PHP dependencies if composer.json exists
if [ -f "composer.json" ]; then
    print_status "Installing PHP dependencies..."
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        print_success "PHP dependencies installed"
    else
        print_warning "Composer not found, skipping dependency installation"
    fi
fi

# Commit version changes
print_status "Committing version changes..."
git add password-protect-elite.php readme.txt
git commit -m "Bump version to $NEW_VERSION"

# Create and push tag
print_status "Creating tag v$NEW_VERSION..."
git tag -a "v$NEW_VERSION" -m "Release version $NEW_VERSION"
git push origin main
git push origin "v$NEW_VERSION"

print_success "Release $NEW_VERSION created successfully!"
print_status "GitHub Actions will now automatically create the release and build the plugin zip file."
print_status "You can monitor the progress at: https://github.com/$(git config --get remote.origin.url | sed 's/.*github.com[:/]\([^/]*\/[^/]*\)\.git.*/\1/')/actions"

echo ""
print_status "Next steps:"
echo "1. Check the GitHub Actions workflow for the release build"
echo "2. Verify the release was created on GitHub"
echo "3. Test the update mechanism in WordPress"
echo ""
print_status "To test updates:"
echo "1. Install an older version of the plugin"
echo "2. Go to WordPress Admin > Dashboard > Updates"
echo "3. The plugin should show an available update"
