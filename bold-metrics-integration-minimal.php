<?php
/**
 * Plugin Name: Bold Metrics Integration
 * Plugin URI: https://github.com/davidjes1/jotform-to-boldmetrics
 * Description: Integrates JotForm submissions with the Bold Metrics Virtual Sizer API.
 * Version: 0.1.1
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Jesse David
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bold-metrics-integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Activation Hook - runs when plugin is activated
function bm_integration_activate() {
    // Create default options
    add_option('bm_integration_options', array(
        'client_id' => '',
        'user_key' => '',
        'webhook_secret' => ''
    ));

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'bm_integration_activate');

// Deactivation Hook
function bm_integration_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'bm_integration_deactivate');

// Register Custom Post Type
function bm_register_post_type() {
    register_post_type('bm_result', array(
        'public' => false,
        'show_ui' => true,
        'labels' => array(
            'name' => 'BM Results',
            'singular_name' => 'BM Result'
        ),
        'supports' => array('title', 'custom-fields')
    ));
}
add_action('init', 'bm_register_post_type');

// Add Admin Menu
function bm_add_admin_menu() {
    add_options_page(
        'Bold Metrics Integration',
        'Bold Metrics',
        'manage_options',
        'bm-integration',
        'bm_settings_page'
    );
}
add_action('admin_menu', 'bm_add_admin_menu');

// Settings Page
function bm_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Bold Metrics Integration</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bm_integration_group');
            do_settings_sections('bm-integration');
            submit_button();
            ?>
        </form>
        <h2>Webhook Endpoint</h2>
        <p>Use this URL as your JotForm webhook endpoint:</p>
        <pre><?php echo esc_url(rest_url('/boldmetrics/v1/process')); ?></pre>
    </div>
    <?php
}

// Register Settings
function bm_register_settings() {
    register_setting('bm_integration_group', 'bm_integration_options', 'bm_sanitize_options');

    add_settings_section('bm_main_section', 'API Settings', null, 'bm-integration');

    add_settings_field('bm_client_id', 'Client ID', 'bm_field_client_id', 'bm-integration', 'bm_main_section');
    add_settings_field('bm_user_key', 'User Key', 'bm_field_user_key', 'bm-integration', 'bm_main_section');
    add_settings_field('bm_webhook_secret', 'Webhook Secret', 'bm_field_webhook_secret', 'bm-integration', 'bm_main_section');
}
add_action('admin_init', 'bm_register_settings');

// Sanitize function
function bm_sanitize_options($input) {
    $output = array();
    $output['client_id'] = isset($input['client_id']) ? sanitize_text_field($input['client_id']) : '';
    $output['user_key'] = isset($input['user_key']) ? sanitize_text_field($input['user_key']) : '';
    $output['webhook_secret'] = isset($input['webhook_secret']) ? sanitize_text_field($input['webhook_secret']) : '';
    return $output;
}

// Field callbacks
function bm_field_client_id() {
    $options = get_option('bm_integration_options');
    $value = isset($options['client_id']) ? $options['client_id'] : '';
    echo '<input type="text" name="bm_integration_options[client_id]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function bm_field_user_key() {
    $options = get_option('bm_integration_options');
    $value = isset($options['user_key']) ? $options['user_key'] : '';
    echo '<input type="password" name="bm_integration_options[user_key]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function bm_field_webhook_secret() {
    $options = get_option('bm_integration_options');
    $value = isset($options['webhook_secret']) ? $options['webhook_secret'] : '';
    echo '<input type="text" name="bm_integration_options[webhook_secret]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Optional shared secret to validate incoming webhooks.</p>';
}

// REST API Endpoint
function bm_register_rest_routes() {
    register_rest_route('boldmetrics/v1', '/process', array(
        'methods' => 'POST',
        'callback' => 'bm_handle_webhook',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'bm_register_rest_routes');

// Webhook Handler (simplified)
function bm_handle_webhook($request) {
    $params = $request->get_params();

    // Basic validation
    if (empty($params['weight']) || empty($params['height']) || empty($params['age'])) {
        return new WP_REST_Response(array('error' => 'Missing required fields'), 400);
    }

    // Store the data
    $post_id = wp_insert_post(array(
        'post_type' => 'bm_result',
        'post_title' => 'BM Result: ' . date('Y-m-d H:i:s'),
        'post_status' => 'publish'
    ));

    if ($post_id) {
        update_post_meta($post_id, 'bm_input', $params);
    }

    return new WP_REST_Response(array('success' => true, 'post_id' => $post_id), 200);
}

// Enqueue CSS
function bm_enqueue_assets() {
    wp_enqueue_style('bm-integration-style', plugin_dir_url(__FILE__) . 'assets/css/bm-style.css', array(), '0.1.1');
}
add_action('wp_enqueue_scripts', 'bm_enqueue_assets');

// Shortcode
function bm_shortcode_result($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $post_id = intval($atts['id']);

    if (!$post_id) {
        return '<p>No result specified.</p>';
    }

    $input = get_post_meta($post_id, 'bm_input', true);

    if (empty($input)) {
        return '<p>Result not found.</p>';
    }

    $output = '<div class="bm-result">';
    $output .= '<h3>Submitted Data</h3>';
    $output .= '<p>Weight: ' . esc_html($input['weight']) . '</p>';
    $output .= '<p>Height: ' . esc_html($input['height']) . '</p>';
    $output .= '<p>Age: ' . esc_html($input['age']) . '</p>';
    $output .= '</div>';

    return $output;
}
add_shortcode('boldmetrics_result', 'bm_shortcode_result');
