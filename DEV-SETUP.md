# Development Setup

## PHP Location

This project uses **XAMPP PHP** for testing.

### PHP Path
```
C:\xampp\php\php.exe
```

Or in Git Bash:
```
/c/xampp/php/php.exe
```

## Running Tests

### Run All Tests
```bash
cd tests
/c/xampp/php/php.exe run-test.php
/c/xampp/php/php.exe test-credentials.php
```

### Test Files
- **run-test.php** - Tests shortcode rendering and API calls
- **test-credentials.php** - Tests credential handling (options vs constants)
- **mock-wp.php** - Mock WordPress environment (no WP install needed)

## Quick Test Command
```bash
# From project root
cd tests && /c/xampp/php/php.exe run-test.php && /c/xampp/php/php.exe test-credentials.php
```

## Notes
- Tests run without requiring a WordPress installation
- Mock environment simulates WordPress functions
- Test credentials are defined in `tests/run-test.php` (lines 6-7)
