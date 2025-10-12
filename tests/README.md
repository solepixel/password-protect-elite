# Password Protect Elite - Test Suite

This directory contains the comprehensive test suite for the Password Protect Elite WordPress plugin.

## Overview

The test suite uses **PHPUnit 9** with **WP_Mock** and **Mockery** for unit testing WordPress plugin functionality without requiring a full WordPress installation.

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

Dependencies are already installed via Composer. If you need to reinstall:

```bash
composer install
```

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Run Tests with Readable Output

```bash
vendor/bin/phpunit --testdox
```

### Generate Code Coverage Report

```bash
composer test:coverage
```

This generates an HTML coverage report in the `coverage-html/` directory.

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/PasswordManagerTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter test_revalidate_stored_password_with_additional_password
```

## Test Structure

```
tests/
├── bootstrap.php              # Test environment setup
├── TestCase.php               # Base test class with helper methods
└── Unit/                      # Unit tests
    ├── AccessControllerTest.php
    ├── DatabaseTest.php
    ├── FrontendTest.php
    ├── PageProtectionTest.php
    ├── PasswordManagerTest.php
    ├── SecureDataTest.php
    ├── UrlMatcherTest.php
    ├── BlocksTest.php
    └── Admin/
        └── SettingsTest.php
```

## Test Coverage

The test suite currently includes **81 tests** covering:

### Core Authentication (PasswordManagerTest)
- ✅ Password validation and session storage
- ✅ Password revalidation (including bug fix for `json_decode` on arrays)
- ✅ Access control checks
- ✅ Session expiration
- ✅ Role-based access bypass
- ✅ Content accessibility checks
- ✅ Redirect URL handling

### Security & Encryption (SecureDataTest)
- ✅ AES-256-GCM encryption/decryption
- ✅ Secure form data creation
- ✅ Form data validation with nonce verification
- ✅ Timestamp validation (anti-replay protection)
- ✅ Error handling for tampered data

### URL Matching (UrlMatcherTest)
- ✅ Exact URL matching
- ✅ Wildcard pattern matching
- ✅ URL normalization
- ✅ Multiple pattern matching (comma/newline separated)
- ✅ Exclusion logic
- ✅ Auto-protect group detection

### Database Operations (DatabaseTest)
- ✅ Password group CRUD operations
- ✅ Master password validation
- ✅ Additional password validation (array handling)
- ✅ Group retrieval with type filtering

### Access Control (AccessControllerTest)
- ✅ 404 behavior handling
- ✅ Redirect behavior (page and custom URL)
- ✅ Dialog display behavior
- ✅ Fallback logic for misconfigured redirects

### Frontend & Page Protection
- ✅ Global protection checks
- ✅ Auto-protection URL matching
- ✅ Admin/AJAX/REST API skip logic
- ✅ Page-level protection meta boxes

### Admin Settings (SettingsTest)
- ✅ Session duration configuration
- ✅ Password attempt limits
- ✅ Lockout duration settings

## Key Bug Fix

The test suite validates the fix for the critical `json_decode()` bug in `PasswordManager::revalidate_stored_password()`:

**Before (Broken):**
```php
$additional_passwords = json_decode( $password_group->additional_passwords, true );
```

**After (Fixed):**
```php
if ( ! empty( $password_group->additional_passwords ) && is_array( $password_group->additional_passwords ) ) {
    foreach ( $password_group->additional_passwords as $additional_password ) {
        // ...
    }
}
```

This fix prevents the fatal error when `additional_passwords` is already an array from WordPress post meta.

## Writing New Tests

### Basic Test Structure

```php
<?php
namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use WP_Mock;

class MyClassTest extends TestCase {

    public function test_my_method() {
        // Mock WordPress functions
        WP_Mock::userFunction(
            'get_option',
            array(
                'times'  => 1,
                'args'   => array( 'my_option', array() ),
                'return' => array( 'value' => 'test' ),
            )
        );

        // Test your code
        $result = my_function();

        // Assertions
        $this->assertEquals( 'expected', $result );
    }
}
```

### Helper Methods Available

From `TestCase.php`:

- `getProtectedMethod($object, $method_name)` - Access private/protected methods
- `getProtectedProperty($object, $property_name)` - Get private/protected properties
- `setProtectedProperty($object, $property_name, $value)` - Set private/protected properties
- `mockGetTransient($transient, $value)` - Quick transient mocking
- `mockSetTransient($transient, $value, $expiration)` - Quick transient setting
- `mockDeleteTransient($transient)` - Quick transient deletion

## Configuration

Test configuration is in `phpunit.xml.dist`:

- Bootstrap file: `tests/bootstrap.php`
- Test directory: `tests/Unit`
- Coverage reports: `coverage-html/`
- Color output enabled
- Strict about test output

## Known Limitations

Some tests have known issues due to WP_Mock limitations:

1. **Internal PHP Functions**: WP_Mock cannot mock `session_id()`, `exit()`, etc.
2. **Static Method Calls**: Complex static methods from namespaced classes can be challenging
3. **File Inclusion**: Tests using `include` for template files need special handling

These limitations don't prevent the tests from validating the core business logic and will be addressed in future iterations.

## Continuous Integration

To integrate with CI/CD:

```yaml
# Example GitHub Actions
- name: Run Tests
  run: composer test

- name: Generate Coverage
  run: composer test:coverage
```

## Debugging Tests

### Enable Verbose Output

```bash
vendor/bin/phpunit --verbose
```

### Debug Specific Test

```bash
vendor/bin/phpunit --filter test_name --debug
```

### Check Coverage for Specific Class

```bash
vendor/bin/phpunit --coverage-text --filter PasswordManagerTest
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure existing tests pass
3. Aim for 70%+ code coverage on new code
4. Document complex test scenarios

## Support

For issues with the test suite:

1. Ensure all Composer dependencies are installed
2. Check PHP version compatibility (8.2+)
3. Review `tests/bootstrap.php` for mock function definitions
4. Consult WP_Mock documentation: https://github.com/10up/wp_mock

