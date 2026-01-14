<?php
/**
 * Plugin Name: Bold Metrics Integration
 * Plugin URI: https://github.com/davidjes1/jotform-to-boldmetrics
 * Description: Integrates JotForm submissions with the Bold Metrics Virtual Sizer API. Displays comprehensive results with size recommendations, measurements, and predictions.
 * Version: 0.2.0
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

    // Create results page automatically
    bm_create_results_page();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'bm_integration_activate');

// Helper function: Create results page on activation
function bm_create_results_page() {
    // Check if page already exists
    $page = get_page_by_path('results');

    if (!$page) {
        $page_id = wp_insert_post(array(
            'post_title' => 'Your Size Results',
            'post_name' => 'results',
            'post_content' => '[boldmetrics_result]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ));

        // Store page ID in options for reference
        if ($page_id && !is_wp_error($page_id)) {
            update_option('bm_results_page_id', $page_id);
        }
    } else {
        // Page exists, store its ID
        update_option('bm_results_page_id', $page->ID);
    }
}

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

        <h2>Results Display Page</h2>
        <?php
        $results_page_id = get_option('bm_results_page_id');
        if ($results_page_id) {
            $results_url = get_permalink($results_page_id);
            echo '<p>Users will view their results at:</p>';
            echo '<pre>' . esc_url($results_url) . '?id=POST_ID</pre>';
            echo '<p class="description">Configure JotForm to redirect to this URL after form submission, replacing POST_ID with the actual result ID.</p>';
            echo '<p><a href="' . esc_url(get_edit_post_link($results_page_id)) . '" class="button">Edit Results Page</a> ';
            echo '<a href="' . esc_url($results_url) . '" class="button" target="_blank">View Results Page</a></p>';
        } else {
            echo '<p class="description">No results page found. The plugin will automatically create one on next activation.</p>';
        }
        ?>
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
    wp_enqueue_style('bm-integration-style', plugin_dir_url(__FILE__) . 'assets/css/bm-style.css', array(), '0.2.0');
}
add_action('wp_enqueue_scripts', 'bm_enqueue_assets');

// Helper function: Render hero section with brand/garment context
function bm_render_hero_section($input) {
    echo '<div class="bm-section bm-hero">';
    echo '<h2 class="bm-title">Your Size Results</h2>';

    if (!empty($input['desired_brand']) || !empty($input['desired_garment_type'])) {
        echo '<p class="bm-context">';
        if (!empty($input['desired_brand'])) {
            echo 'Brand: <strong>' . esc_html($input['desired_brand']) . '</strong>';
        }
        if (!empty($input['desired_garment_type'])) {
            if (!empty($input['desired_brand'])) echo ' | ';
            echo 'Garment: <strong>' . esc_html($input['desired_garment_type']) . '</strong>';
        }
        echo '</p>';
    }
    echo '</div>';
}

// Helper function: Render size recommendations with fit scores
function bm_render_size_recommendations($response) {
    echo '<div class="bm-section bm-recommendations">';
    echo '<h3 class="bm-section-title">Recommended Sizes</h3>';

    $good_matches = isset($response['good_matches']) ? $response['good_matches'] : array();

    if (!empty($good_matches)) {
        echo '<div class="bm-size-cards">';
        foreach ($good_matches as $match) {
            $brand_size = isset($match['brand_size']) ? $match['brand_size'] : '';
            $size = isset($match['size']) ? $match['size'] : 'Unknown';
            $fit_score = isset($match['fit_score']) ? $match['fit_score'] : '';

            echo '<div class="bm-size-card">';
            echo '<div class="bm-size-label">' . esc_html($brand_size ? $brand_size : $size) . '</div>';
            if (!empty($fit_score)) {
                echo '<div class="bm-fit-score">';
                echo '<span class="bm-score-value">' . esc_html($fit_score) . '%</span>';
                echo '<span class="bm-score-label">Fit Score</span>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="bm-no-results">No size recommendations available at this time.</p>';
    }

    echo '</div>';
}

// Helper function: Render submitted input measurements
function bm_render_input_measurements($input) {
    echo '<div class="bm-section bm-input-data">';
    echo '<h3 class="bm-section-title">Your Submitted Measurements</h3>';
    echo '<div class="bm-measurement-grid">';

    $measurements = array(
        'weight' => array('label' => 'Weight', 'unit' => 'lbs'),
        'height' => array('label' => 'Height', 'unit' => 'inches'),
        'age' => array('label' => 'Age', 'unit' => 'years'),
        'waist_circum_preferred' => array('label' => 'Waist', 'unit' => 'inches'),
        'bra_size' => array('label' => 'Bra Size', 'unit' => ''),
    );

    foreach ($measurements as $key => $config) {
        if (!empty($input[$key])) {
            echo '<div class="bm-measurement-item">';
            echo '<span class="bm-measurement-label">' . esc_html($config['label']) . '</span>';
            echo '<span class="bm-measurement-value">' . esc_html($input[$key]) . ' ' . esc_html($config['unit']) . '</span>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
}

// Helper function: Render predicted body dimensions
function bm_render_predicted_dimensions($response) {
    $predictions = isset($response['predictions']) ? $response['predictions'] : array();

    if (empty($predictions) || !is_array($predictions)) {
        return; // Don't display section if no predictions
    }

    echo '<div class="bm-section bm-predictions">';
    echo '<h3 class="bm-section-title">Predicted Body Measurements</h3>';
    echo '<div class="bm-measurement-grid">';

    foreach ($predictions as $key => $value) {
        // Format field names nicely (e.g., chest_circum becomes Chest Circum)
        $label = ucwords(str_replace('_', ' ', $key));
        echo '<div class="bm-measurement-item">';
        echo '<span class="bm-measurement-label">' . esc_html($label) . '</span>';
        echo '<span class="bm-measurement-value">' . esc_html($value) . '</span>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

// Helper function: Render next steps section
function bm_render_next_steps($input) {
    echo '<div class="bm-section bm-next-steps">';
    echo '<h3 class="bm-section-title">What\'s Next?</h3>';
    echo '<p>Use these size recommendations when shopping for your perfect fit. You can save this page for future reference or take a screenshot of your results.</p>';
    echo '</div>';
}

// Shortcode - Enhanced to read ID from URL and display comprehensive results
function bm_shortcode_result($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $post_id = intval($atts['id']);

    // Read from URL if not provided in shortcode attribute
    if (!$post_id && isset($_GET['id'])) {
        $post_id = intval($_GET['id']);
    }

    // Validate post ID exists
    if (!$post_id) {
        return '<div class="bm-error">
            <h3>No Result Found</h3>
            <p>No result ID specified. Please check your link.</p>
        </div>';
    }

    // Verify post exists and is correct type
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'bm_result') {
        return '<div class="bm-error">
            <h3>Result Not Found</h3>
            <p>This result could not be found. The link may be invalid or expired.</p>
        </div>';
    }

    // Retrieve stored data
    $response = get_post_meta($post_id, 'bm_response', true);
    $input = get_post_meta($post_id, 'bm_input', true);

    // Handle missing or incomplete data
    if (empty($input)) {
        return '<div class="bm-error">
            <h3>Data Unavailable</h3>
            <p>The result data is incomplete. Please contact support for assistance.</p>
        </div>';
    }

    // Start output buffering
    ob_start();

    // Main container with theme-friendly classes
    echo '<div class="bm-result-page entry-content">';

    // Render all sections
    bm_render_hero_section($input);
    bm_render_size_recommendations($response);
    bm_render_input_measurements($input);
    bm_render_predicted_dimensions($response);
    bm_render_next_steps($input);

    echo '</div>';

    return ob_get_clean();
}
add_shortcode('boldmetrics_result', 'bm_shortcode_result');
