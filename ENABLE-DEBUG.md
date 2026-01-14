# Enable WordPress Debug Mode

Add these lines to your `wp-config.php` file (before the "That's all" line):

```php
// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to wp-content/debug.log
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
```

Then try activating the plugin again and check:
`wp-content/debug.log`

This will show us the exact error that's causing the activation to fail.
