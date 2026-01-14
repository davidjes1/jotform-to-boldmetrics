<?php
/**
 * Uninstall script for Bold Metrics Integration
 *
 * This file is called when the plugin is deleted (not just deactivated).
 * It handles cleanup of all plugin data from the database.
 *
 * @package Bold_Metrics_Integration
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('bm_integration_options');

// Delete all bm_result posts and their meta
$posts = get_posts(array(
    'post_type' => 'bm_result',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($posts as $post) {
    // Delete post meta
    delete_post_meta($post->ID, 'bm_input');
    delete_post_meta($post->ID, 'bm_response');

    // Delete post permanently (bypass trash)
    wp_delete_post($post->ID, true);
}

// Clear any cached data
wp_cache_flush();

// Optional: Remove custom capabilities if you added any
// (Not used in current version, but here for future reference)
// delete_role('bm_manager');
