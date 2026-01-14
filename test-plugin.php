<?php
/**
 * Plugin Name: Bold Metrics Integration Test
 * Description: Test version to diagnose activation issues
 * Version: 0.1.1
 * Author: Jesse David
 */

if (!defined('ABSPATH')) {
    exit;
}

// Simple test - just add an admin notice
add_action('admin_notices', function() {
    echo '<div class="notice notice-success"><p>Bold Metrics plugin is loaded and working!</p></div>';
});

// Add admin menu
add_action('admin_menu', function() {
    add_options_page(
        'BM Test',
        'BM Test',
        'manage_options',
        'bm-test',
        function() {
            echo '<div class="wrap"><h1>BM Test Page</h1><p>If you can see this, the plugin is working.</p></div>';
        }
    );
});

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('bm_test_activated', '1');
});
