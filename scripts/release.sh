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

# Update version in plugin file (only in header and version constant)
print_status "Updating plugin version..."
# Match "Version:" in WordPress plugin header (in PHP docblock comment) and version constant
if [[ "$OSTYPE" == "darwin"* ]]; then
    # Match " * Version: X.Y.Z" in PHP docblock header (use extended regex, escape * for literal match)
    sed -i.bak -E "s|^ \\\* Version: $CURRENT_VERSION| * Version: $NEW_VERSION|" password-protect-elite.php
    # Match "const PPE_VERSION = 'X.Y.Z';" exactly
    sed -i.bak "s/const PPE_VERSION = '$CURRENT_VERSION'/const PPE_VERSION = '$NEW_VERSION'/" password-protect-elite.php
else
    # Match " * Version: X.Y.Z" in PHP docblock header (use extended regex, escape * for literal match)
    sed -i -E "s|^ \\\* Version: $CURRENT_VERSION| * Version: $NEW_VERSION|" password-protect-elite.php
    # Match "const PPE_VERSION = 'X.Y.Z';" exactly
    sed -i "s/const PPE_VERSION = '$CURRENT_VERSION'/const PPE_VERSION = '$NEW_VERSION'/" password-protect-elite.php
fi

# Update version in readme.txt (only the "Stable tag:" line in header, not changelog entries)
print_status "Updating readme.txt stable tag..."
# Match "Stable tag:" at the start of a line (WordPress readme header, not changelog)
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i.bak "s/^Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" readme.txt
else
    sed -i "s/^Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" readme.txt
fi

# Update version in package.json if it exists (only the root package version, not dependencies)
if [ -f "package.json" ]; then
    print_status "Updating package.json version..."
    # Use Python to safely update only the root package version
    if command -v python3 &> /dev/null; then
        python3 <<PYTHON_SCRIPT
import json
import sys

try:
    with open('package.json', 'r') as f:
        data = json.load(f)

    # Only update the root "version" field, not any dependency versions
    if 'version' in data:
        data['version'] = '$NEW_VERSION'

    with open('package.json', 'w') as f:
        json.dump(data, f, indent='\t', ensure_ascii=False)
        f.write('\n')

    print("Updated package.json version to $NEW_VERSION")
except Exception as e:
    print(f"Error updating package.json: {e}", file=sys.stderr)
    sys.exit(1)
PYTHON_SCRIPT
    else
        print_error "Python 3 is required to safely update package.json. Please install Python 3."
        exit 1
    fi
fi

# Clean up backup files
rm -f *.bak

# Update package-lock.json version (only the root package version, not dependencies)
if [ -f "package-lock.json" ] && [ -f "package.json" ]; then
    print_status "Updating package-lock.json version..."
    # Use Python to safely update only the root package version in package-lock.json
    if command -v python3 &> /dev/null; then
        python3 <<PYTHON_SCRIPT
import json
import sys

try:
    with open('package-lock.json', 'r') as f:
        data = json.load(f)

    # Update root version field
    if 'version' in data:
        data['version'] = '$NEW_VERSION'

    # Update version in packages[""] (root package)
    if 'packages' in data and '' in data['packages']:
        if 'version' in data['packages']['']:
            data['packages']['']['version'] = '$NEW_VERSION'

    with open('package-lock.json', 'w') as f:
        json.dump(data, f, indent='\t', ensure_ascii=False)
        f.write('\n')

    print("Updated package-lock.json version to $NEW_VERSION")
except Exception as e:
    print(f"Error updating package-lock.json: {e}", file=sys.stderr)
    # Don't exit, just warn - npm install will fix it anyway
    print("Warning: package-lock.json update failed, but npm will regenerate it")
PYTHON_SCRIPT
    fi
fi

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
        # Suppress PHP deprecation warnings from Composer itself (harmless warnings on PHP 8.2+)
        # These are warnings from Composer's dependencies, not actual errors
        COMPOSER_INSTALL_OUTPUT=$(composer install --no-dev --optimize-autoloader 2>&1)
        COMPOSER_EXIT_CODE=$?

        # Filter out deprecation notices but keep other output
        echo "$COMPOSER_INSTALL_OUTPUT" | grep -v "Deprecation Notice:" | \
            grep -v "PHP Deprecated:" | \
            grep -v "should either be compatible" || true

        if [ $COMPOSER_EXIT_CODE -ne 0 ]; then
            print_error "Composer installation failed with exit code $COMPOSER_EXIT_CODE"
            exit $COMPOSER_EXIT_CODE
        fi

        print_success "PHP dependencies installed"
    else
        print_warning "Composer not found, skipping dependency installation"
    fi
fi

# Check if changelog entry already exists for this version
print_status "Checking for existing changelog entries..."
CHANGELOG_EXISTS=false
CHANGELOG_CONFLICT_FILES=()

# Check readme.txt (WordPress format: "= X.Y.Z =")
if grep -q "^= $NEW_VERSION =" readme.txt 2>/dev/null; then
    CHANGELOG_EXISTS=true
    CHANGELOG_CONFLICT_FILES+=("readme.txt")
    print_warning "Found existing changelog entry for version $NEW_VERSION in readme.txt"
fi

# Check README.md (Markdown format: "### X.Y.Z")
if grep -q "^### $NEW_VERSION$" README.md 2>/dev/null || grep -q "^### $NEW_VERSION " README.md 2>/dev/null; then
    CHANGELOG_EXISTS=true
    CHANGELOG_CONFLICT_FILES+=("README.md")
    print_warning "Found existing changelog entry for version $NEW_VERSION in README.md"
fi

if [ "$CHANGELOG_EXISTS" = true ]; then
    print_warning "Changelog entry for version $NEW_VERSION already exists in: ${CHANGELOG_CONFLICT_FILES[*]}"
    echo ""
    echo "Options:"
    echo "  1. Replace existing changelog entry with auto-generated one"
    echo "  2. Skip adding changelog (keep existing entry)"
    echo "  3. Cancel release"
    echo ""
    read -p "Choose an option (1-3): " -r response

    case "$response" in
        1)
            print_status "Will replace existing changelog entries..."
            REPLACE_EXISTING_CHANGELOG=true
            ;;
        2)
            print_status "Skipping changelog generation, keeping existing entries..."
            SKIP_CHANGELOG=true
            ;;
        3)
            print_status "Release cancelled."
            exit 0
            ;;
        *)
            print_error "Invalid option. Release cancelled."
            exit 1
            ;;
    esac
else
    REPLACE_EXISTING_CHANGELOG=false
    SKIP_CHANGELOG=false
fi

# Generate changelog from git commits (unless skipping)
if [ "$SKIP_CHANGELOG" != true ]; then
    print_status "Generating changelog..."
    CHANGELOG_FILE=$(mktemp)
    PREVIOUS_TAG=$(git describe --tags --abbrev=0 HEAD~1 2>/dev/null || echo "")

    if [ -n "$PREVIOUS_TAG" ]; then
        print_status "Previous tag: $PREVIOUS_TAG"
        git log --pretty=format:"* %s" ${PREVIOUS_TAG}..HEAD --no-merges > "$CHANGELOG_FILE" || {
            # If no commits found, create empty changelog
            echo "" > "$CHANGELOG_FILE"
        }
    else
        print_status "No previous tag found, using all commits"
        git log --pretty=format:"* %s" --no-merges -20 > "$CHANGELOG_FILE" || {
            echo "" > "$CHANGELOG_FILE"
        }
    fi

    # If changelog is empty, add a default entry
    if [ ! -s "$CHANGELOG_FILE" ] || [ -z "$(cat "$CHANGELOG_FILE" | tr -d '[:space:]')" ]; then
        echo "* Release version $NEW_VERSION" > "$CHANGELOG_FILE"
    fi
else
    # Skip changelog generation, create empty placeholder
    CHANGELOG_FILE=$(mktemp)
    echo "" > "$CHANGELOG_FILE"
fi

# Display generated changelog and allow user to edit (unless skipping)
if [ "$SKIP_CHANGELOG" != true ]; then
    print_status "Generated changelog:"
    echo ""
    cat "$CHANGELOG_FILE"
    echo ""
    print_warning "You can edit the changelog now. The changelog will be added to readme.txt and README.md"
    read -p "Press Enter to edit the changelog, or 's' to skip editing: " -r response

    if [[ ! "$response" =~ ^[Ss]$ ]]; then
        # Open changelog in editor (use $EDITOR if set, otherwise try common editors)
        if [ -n "$EDITOR" ]; then
            $EDITOR "$CHANGELOG_FILE"
        elif command -v nano &> /dev/null; then
            nano "$CHANGELOG_FILE"
        elif command -v vim &> /dev/null; then
            vim "$CHANGELOG_FILE"
        elif command -v vi &> /dev/null; then
            vi "$CHANGELOG_FILE"
        else
            print_warning "No editor found, using generated changelog as-is"
        fi
    fi

    # Read the changelog content
    CHANGELOG_CONTENT=$(cat "$CHANGELOG_FILE")
else
    # Empty changelog content when skipping
    CHANGELOG_CONTENT=""
fi

# Update changelog in readme.txt (WordPress format) - skip if SKIP_CHANGELOG is true
if [ "$SKIP_CHANGELOG" != true ]; then
    print_status "Updating changelog in readme.txt..."
    README_TXT_NEW_ENTRY=$(mktemp)
    {
        echo "= $NEW_VERSION ="
        echo "$CHANGELOG_CONTENT" | sed 's/^\* /*/'
        echo ""
    } > "$README_TXT_NEW_ENTRY"

    # Insert changelog entry after the == Changelog == header in readme.txt
    if grep -q "== Changelog ==" readme.txt; then
        # Check if Python is available
        if ! command -v python3 &> /dev/null; then
            print_error "Python 3 is required to update changelog. Please install Python 3 or update the changelog manually."
            exit 1
        fi

        # Use Python to insert/replace changelog entry
        python3 <<PYTHON_SCRIPT
import sys

new_entry_file = "$README_TXT_NEW_ENTRY"
readme_file = "readme.txt"
replace_existing = $([ "$REPLACE_EXISTING_CHANGELOG" = true ] && echo "True" || echo "False")
version = "$NEW_VERSION"

with open(new_entry_file, 'r') as f:
    new_entry = f.read()

with open(readme_file, 'r') as f:
    lines = f.readlines()

output = []
inserted = False
i = 0
in_changelog = False
skipping_version = False
found_existing = False

while i < len(lines):
    line = lines[i]

    # Track if we're in the changelog section
    if line.strip() == "== Changelog ==":
        in_changelog = True
        output.append(line)
        i += 1
        continue

    if not in_changelog:
        output.append(line)
        i += 1
        continue

    # If replacing and we find the version entry, skip it and insert new one
    if replace_existing and not inserted and line.strip() == f"= {version} =":
        found_existing = True
        skipping_version = True
        # Insert new entry where old one was
        output.append(new_entry)
        # Skip the old version header line
        i += 1
        continue

    # Skip all lines until next version entry (when replacing existing)
    if skipping_version:
        # Stop when we hit the next version entry
        if line.strip().startswith("= ") and line.strip().endswith(" ="):
            skipping_version = False
            # Append this line (next version entry)
            output.append(line)
            inserted = True
        # Also stop at empty line if followed by version entry
        elif line.strip() == "" and i + 1 < len(lines) and lines[i + 1].strip().startswith("= ") and lines[i + 1].strip().endswith(" ="):
            skipping_version = False
            inserted = True
            # Don't append this empty line yet, let it be handled naturally
            continue
        else:
            # Skip this line (part of old changelog entry)
            i += 1
            continue

    # Insert new entry after changelog header (if not replacing existing)
    if not inserted and not replace_existing and line.strip() == "":
        # Check if next line is a version entry (to insert before it) or empty
        if i + 1 < len(lines):
            next_line = lines[i + 1].strip()
            if next_line.startswith("= ") and next_line.endswith(" ="):
                output.append(line)
                output.append(new_entry)
                inserted = True
                i += 1
                continue

    output.append(line)
    i += 1

with open(readme_file, 'w') as f:
    f.writelines(output)
PYTHON_SCRIPT
    else
        # Append changelog section if it doesn't exist
        {
            echo ""
            echo "== Changelog =="
            echo ""
            cat "$README_TXT_NEW_ENTRY"
        } >> readme.txt
    fi

    rm -f "$README_TXT_NEW_ENTRY"
else
    print_status "Skipping changelog update in readme.txt (keeping existing entry)"
fi

# Update changelog in README.md (Markdown format) - skip if SKIP_CHANGELOG is true
if [ "$SKIP_CHANGELOG" != true ]; then
    print_status "Updating changelog in README.md..."
    README_MD_NEW_ENTRY=$(mktemp)
    {
        echo "### $NEW_VERSION"
        echo "$CHANGELOG_CONTENT" | sed 's/^\* /- /'
        echo ""
    } > "$README_MD_NEW_ENTRY"

    # Insert changelog entry after the ## Changelog header in README.md
    if grep -q "## Changelog" README.md; then
        # Use Python to insert/replace changelog entry
        python3 <<PYTHON_SCRIPT
import sys

new_entry_file = "$README_MD_NEW_ENTRY"
readme_file = "README.md"
replace_existing = $([ "$REPLACE_EXISTING_CHANGELOG" = true ] && echo "True" || echo "False")
version = "$NEW_VERSION"

with open(new_entry_file, 'r') as f:
    new_entry = f.read()

with open(readme_file, 'r') as f:
    lines = f.readlines()

output = []
inserted = False
in_changelog = False
skipping_version = False
i = 0

while i < len(lines):
    line = lines[i]

    # Track if we're in the changelog section
    if line.strip() == "## Changelog":
        in_changelog = True
        output.append(line)
        i += 1
        continue

    if not in_changelog:
        output.append(line)
        i += 1
        continue

    # If replacing and we find the version entry, skip it and insert new one
    if replace_existing and not inserted and line.strip() == f"### {version}":
        skipping_version = True
        # Insert new entry where old one was
        output.append(new_entry)
        # Skip the old version header line
        i += 1
        continue

    # Skip all lines until next version entry (when replacing existing)
    if skipping_version:
        # Stop when we hit the next version entry
        if line.strip().startswith("### "):
            skipping_version = False
            # Append this line (next version entry)
            output.append(line)
            inserted = True
        # Also stop at empty line if followed by version entry
        elif line.strip() == "" and i + 1 < len(lines) and lines[i + 1].strip().startswith("### "):
            skipping_version = False
            inserted = True
            # Don't append this empty line yet, let it be handled naturally
            continue
        else:
            # Skip this line (part of old changelog entry)
            i += 1
            continue

    # Insert new entry after changelog header (if not replacing existing)
    if not inserted and not replace_existing and line.strip() == "":
        # Check if next line is a version entry (to insert before it) or empty
        if i + 1 < len(lines):
            next_line = lines[i + 1].strip()
            if next_line.startswith("### "):
                output.append(line)
                output.append(new_entry)
                inserted = True
                i += 1
                continue

    output.append(line)
    i += 1

with open(readme_file, 'w') as f:
    f.writelines(output)
PYTHON_SCRIPT
    else
        # Append changelog section if it doesn't exist
        {
            echo ""
            echo "## Changelog"
            echo ""
            cat "$README_MD_NEW_ENTRY"
        } >> README.md
    fi

    rm -f "$README_MD_NEW_ENTRY"
else
    print_status "Skipping changelog update in README.md (keeping existing entry)"
fi

# Clean up temporary files
rm -f "$CHANGELOG_FILE"

# Commit version changes
print_status "Committing version changes..."
git add password-protect-elite.php readme.txt README.md
if [ -f "package.json" ]; then
    git add package.json package-lock.json 2>/dev/null || true
fi
git commit -m "v$NEW_VERSION: Release"

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
