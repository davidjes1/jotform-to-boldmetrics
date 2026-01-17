<?php
/**
 * Plugin Name: Bold Metrics Integration
 * Plugin URI: https://github.com/davidjes1/jotform-to-boldmetrics
 * Description: Integrates JotForm submissions with the Bold Metrics Virtual Sizer API. Provides a REST endpoint for webhooks, admin settings for API keys, and a shortcode to display results.
 * Version: 0.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Jesse David
 * Author URI: https://github.com/davidjes1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bold-metrics-integration
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main plugin class for Bold Metrics Integration
 *
 * Handles webhook processing, API integration, admin settings,
 * and result display functionality.
 */
final class BM_Integration
{
    const VERSION = '0.1.0';
    const OPTION_KEY = 'bm_integration_options';

    /**
     * Initialize the plugin by registering hooks
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
        add_shortcode('boldmetrics_form', array(__CLASS__, 'shortcode_measurement_form'));
        add_shortcode('boldmetrics_result', array(__CLASS__, 'shortcode_show_result'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Plugin activation hook
     * Sets up default options and flushes rewrite rules
     */
    public static function on_activate()
    {
        // Ensure CPT is registered on activation so rewrite rules are flushed cleanly.
        self::register_post_type();
        flush_rewrite_rules();

        $defaults = array(
            'client_id' => '',
            'user_key' => '',
            'webhook_secret' => '', // optional shared secret to validate incoming webhooks
        );
        if (false === get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, $defaults);
        }
    }

    /**
     * Plugin deactivation hook
     * Flushes rewrite rules on plugin deactivation
     */
    public static function on_deactivate()
    {
        // Flush rewrite rules to clean up custom post type permalinks
        flush_rewrite_rules();

        // Note: We don't delete data on deactivation
        // Data is only removed when plugin is deleted (see uninstall.php)
    }

    /**
     * Register the custom post type for storing Bold Metrics results
     */
    public static function register_post_type()
    {
        $labels = array(
            'name' => 'BM Results',
            'singular_name' => 'BM Result',
            'menu_name' => 'BM Results'
        );

        $args = array(
            'public' => false,
            'show_ui' => true,
            'labels' => $labels,
            'supports' => array('title', 'custom-fields'),
        );

        register_post_type('bm_result', $args);
    }

    /**
     * Add admin menu page
     */
    public static function add_admin_menu()
    {
        add_options_page(
            'Bold Metrics Integration',
            'Bold Metrics',
            'manage_options',
            'bm-integration',
            array(__CLASS__, 'settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public static function register_settings()
    {
        register_setting('bm_integration_group', self::OPTION_KEY, array(__CLASS__, 'sanitize_options'));

        add_settings_section('bm_main_section', 'API Settings', null, 'bm-integration');

        add_settings_field('bm_client_id', 'Client ID', array(__CLASS__, 'field_client_id'), 'bm-integration', 'bm_main_section');
        add_settings_field('bm_user_key', 'User Key', array(__CLASS__, 'field_user_key'), 'bm-integration', 'bm_main_section');
        add_settings_field('bm_webhook_secret', 'Webhook Secret (optional)', array(__CLASS__, 'field_webhook_secret'), 'bm-integration', 'bm_main_section');
    }

    /**
     * Sanitize options before saving
     *
     * @param array $input Raw input from settings form
     * @return array Sanitized options
     */
    public static function sanitize_options($input)
    {
        $out = array();
        $out['client_id'] = sanitize_text_field($input['client_id'] ?? '');
        $out['user_key'] = sanitize_text_field($input['user_key'] ?? '');
        $out['webhook_secret'] = sanitize_text_field($input['webhook_secret'] ?? '');
        return $out;
    }

    /**
     * Render Client ID field
     */
    public static function field_client_id()
    {
        if (defined('BM_CLIENT_ID')) {
            echo '<input type="text" value="' . esc_attr(BM_CLIENT_ID) . '" class="regular-text" disabled /> ';
            echo '<p class="description">Defined in wp-config.php</p>';
        } else {
            $opts = get_option(self::OPTION_KEY);
            printf('<input type="text" name="%s[client_id]" value="%s" class="regular-text"/>', esc_attr(self::OPTION_KEY), esc_attr($opts['client_id'] ?? ''));
        }
    }

    /**
     * Render User Key field
     */
    public static function field_user_key()
    {
        if (defined('BM_USER_KEY')) {
            // Mask the key for display
            $display = substr(BM_USER_KEY, 0, 4) . str_repeat('*', strlen(BM_USER_KEY) - 4);
            echo '<input type="text" value="' . esc_attr($display) . '" class="regular-text" disabled /> ';
            echo '<p class="description">Defined in wp-config.php</p>';
        } else {
            $opts = get_option(self::OPTION_KEY);
            printf('<input type="password" name="%s[user_key]" value="%s" class="regular-text"/>', esc_attr(self::OPTION_KEY), esc_attr($opts['user_key'] ?? ''));
        }
    }

    /**
     * Render Webhook Secret field
     */
    public static function field_webhook_secret()
    {
        $opts = get_option(self::OPTION_KEY);
        printf('<input type="text" name="%s[webhook_secret]" value="%s" class="regular-text"/>', esc_attr(self::OPTION_KEY), esc_attr($opts['webhook_secret'] ?? ''));
        echo '<p class="description">Optional shared secret to validate incoming webhooks from JotForm.</p>';
    }

    /**
     * Render the settings page
     */
    public static function settings_page()
    {
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
            <h2>Usage Instructions</h2>
            <p>To add the measurement form to any page or post, use this shortcode:</p>
            <pre>[boldmetrics_form]</pre>
            <p>The form will collect user measurements and display size recommendations immediately.</p>

            <h3>Legacy Webhook Endpoint (Optional)</h3>
            <p>If you need webhook integration from external sources:</p>
            <pre><?php echo esc_url(rest_url('/boldmetrics/v1/process')); ?></pre>
            <p>If you set a webhook secret, include it in an <code>X-BM-Webhook-Secret</code> header on the POST.</p>
        </div>
        <?php
    }

    /**
     * Register REST API routes
     */
    public static function register_rest_routes()
    {
        register_rest_route('boldmetrics/v1', '/process', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('boldmetrics/v1', '/submit', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'handle_form_submission'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle webhook requests from JotForm
     *
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response Response object
     */
    public static function handle_webhook(WP_REST_Request $request)
    {
        $opts = get_option(self::OPTION_KEY);
        $secret = $opts['webhook_secret'] ?? '';

        // If webhook_secret is set, validate header
        if (!empty($secret)) {
            $header = $request->get_header('x-bm-webhook-secret');
            if (empty($header) || !hash_equals($secret, $header)) {
                return new WP_REST_Response(array('error' => 'Invalid webhook secret'), 403);
            }
        }

        $params = $request->get_params();

        // Map/validate expected inputs. Adjust keys to match your JotForm field names.
        $data = array(
            'weight' => isset($params['weight']) ? floatval($params['weight']) : null,
            'height' => isset($params['height']) ? floatval($params['height']) : null,
            'age' => isset($params['age']) ? intval($params['age']) : null,
            'waist_circum_preferred' => isset($params['waist_circum_preferred']) ? floatval($params['waist_circum_preferred']) : null,
            'bra_size' => isset($params['bra_size']) ? sanitize_text_field($params['bra_size']) : null,
            'desired_brand' => isset($params['desired_brand']) ? sanitize_text_field($params['desired_brand']) : null,
            'desired_garment_type' => isset($params['desired_garment_type']) ? sanitize_text_field($params['desired_garment_type']) : null,
            'product_id' => isset($params['product_id']) ? sanitize_text_field($params['product_id']) : null,
            'anon_id' => isset($params['anon_id']) ? sanitize_text_field($params['anon_id']) : wp_generate_uuid4(),
        );

        // Ensure required fields exist (example: client requires height, weight, age, and either waist or bra_size)
        if (empty($data['height']) || empty($data['weight']) || empty($data['age']) || (empty($data['waist_circum_preferred']) && empty($data['bra_size']))) {
            return new WP_REST_Response(array('error' => 'Missing required fields'), 400);
        }

        // Call Bold Metrics API
        $result = self::call_boldmetrics_api($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array('error' => $result->get_error_message()), 500);
        }

        // Store result as private CPT for future retrieval
        $post_id = wp_insert_post(array(
            'post_type' => 'bm_result',
            'post_title' => sprintf('BM Result: %s', $data['anon_id']),
            'post_status' => 'publish',
        ));

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'bm_input', $data);
            update_post_meta($post_id, 'bm_response', $result);
        }

        return new WP_REST_Response(array('ok' => true, 'post_id' => $post_id, 'response' => $result), 200);
    }

    /**
     * Handle form submission from frontend
     *
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response Response object
     */
    public static function handle_form_submission(WP_REST_Request $request)
    {
        $params = $request->get_params();

        // Validate and sanitize inputs
        $weight = isset($params['weight']) ? floatval($params['weight']) : null;
        $height = isset($params['height']) ? floatval($params['height']) : null;
        $age = isset($params['age']) ? intval($params['age']) : null;
        $sex = isset($params['sex']) ? sanitize_text_field($params['sex']) : null;

        // Validate required fields
        if (empty($weight) || empty($height) || empty($age) || empty($sex)) {
            return new WP_REST_Response(array('error' => 'Please fill in all required fields'), 400);
        }

        // Build data array
        $data = array(
            'weight' => $weight,
            'height' => $height,
            'age' => $age,
            'anon_id' => wp_generate_uuid4(),
        );

        // Handle sex-specific measurements
        if (strtolower($sex) === 'male') {
            $waist = isset($params['waist']) ? floatval($params['waist']) : null;
            if (empty($waist)) {
                return new WP_REST_Response(array('error' => 'Waist size is required for males'), 400);
            }
            $data['waist_circum_preferred'] = $waist;
        } elseif (strtolower($sex) === 'female') {
            $strap = isset($params['strap_size']) ? sanitize_text_field($params['strap_size']) : null;
            $cup = isset($params['cup_size']) ? sanitize_text_field($params['cup_size']) : null;
            if (empty($strap) || empty($cup)) {
                return new WP_REST_Response(array('error' => 'Strap and cup size are required for females'), 400);
            }
            $data['bra_size'] = $strap . $cup;
        } else {
            return new WP_REST_Response(array('error' => 'Invalid sex value'), 400);
        }

        // Call Bold Metrics API
        $result = self::call_boldmetrics_api($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array('error' => $result->get_error_message()), 500);
        }

        // Store result as private CPT for future retrieval
        $post_id = wp_insert_post(array(
            'post_type' => 'bm_result',
            'post_title' => sprintf('BM Result: %s', $data['anon_id']),
            'post_status' => 'publish',
        ));

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'bm_input', $data);
            update_post_meta($post_id, 'bm_response', $result);
        }

        return new WP_REST_Response(array('success' => true, 'post_id' => $post_id, 'result' => $result), 200);
    }

    /**
     * Call the Bold Metrics Virtual Sizer API
     *
     * @param array $data Measurement data to send to API
     * @return array|WP_Error API response or error object
     */
    public static function call_boldmetrics_api($data)
    {
        $opts = get_option(self::OPTION_KEY);
        $client_id = defined('BM_CLIENT_ID') ? BM_CLIENT_ID : ($opts['client_id'] ?? '');
        $user_key = defined('BM_USER_KEY') ? BM_USER_KEY : ($opts['user_key'] ?? '');

        if (empty($client_id) || empty($user_key)) {
            return new WP_Error('missing_credentials', 'Bold Metrics credentials are not configured.');
        }

        $endpoint = 'https://api.boldmetrics.io/virtualsizer/get';

        $query = array(
            'client_id' => $client_id,
            'user_key' => $user_key,
            'height' => $data['height'],
            'weight' => $data['weight'],
            'age' => $data['age'],
            'anon_id' => $data['anon_id'],
        );

        // Add optional fields if provided
        $optional_keys = array('waist_circum_preferred', 'bra_size', 'desired_brand', 'desired_garment_type', 'product_id');
        foreach ($optional_keys as $k) {
            if (!empty($data[$k])) {
                $query[$k] = $data[$k];
            }
        }

        $url = add_query_arg($query, $endpoint);

        $response = wp_remote_get($url, array('timeout' => 15));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (200 !== (int) $code) {
            return new WP_Error('bm_api_error', sprintf('Bold Metrics returned HTTP %d: %s', $code, wp_trim_words($body, 30)));
        }

        $json = json_decode($body, true);
        if (null === $json) {
            return new WP_Error('bm_parse_error', 'Unable to parse Bold Metrics response as JSON.');
        }

        return $json;
    }

    /**
     * Shortcode to display Bold Metrics results
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function shortcode_show_result($atts)
    {
        $atts = shortcode_atts(array('id' => 0), $atts, 'boldmetrics_result');
        $post_id = intval($atts['id']);
        if (!$post_id) {
            return '<p>No result specified.</p>';
        }

        $response = get_post_meta($post_id, 'bm_response', true);
        $input = get_post_meta($post_id, 'bm_input', true);

        if (empty($response)) {
            return '<p>Result not found.</p>';
        }

        ob_start();
        echo '<div class="bm-result">';
        echo '<h3>Size Recommendations</h3>';

        $good_matches = $response['good_matches'] ?? array();

        if (!empty($good_matches)) {
            echo '<ul class="bm-good-matches">';
            foreach ($good_matches as $match) {
                $brand_size = $match['brand_size'] ?? '';
                $size = $match['size'] ?? 'Unknown Size';
                $fit_score = $match['fit_score'] ?? '';

                $display_title = !empty($brand_size) ? $brand_size : $size;
                $score_str = !empty($fit_score) ? ' (Fit Score: ' . esc_html($fit_score) . ')' : '';

                echo '<li>' . esc_html($display_title . $score_str) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No recommended sizes returned.</p>';
        }

        // Display predicted measurements
        $predictions = $response['predictions'] ?? array();
        if (!empty($predictions) && is_array($predictions)) {
            echo '<h4>Predicted Measurements</h4>';
            echo '<table class="bm-measurements"><tbody>';
            foreach ($predictions as $key => $val) {
                echo '<tr><td>' . esc_html($key) . '</td><td>' . esc_html($val) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Shortcode to display measurement input form
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function shortcode_measurement_form($atts)
    {
        ob_start();
        ?>
        <div class="bm-form-container">
            <form id="bm-measurement-form" class="bm-form">
                <div class="bm-form-group">
                    <label for="bm-weight">Weight (lbs) <span class="required">*</span></label>
                    <input type="number" id="bm-weight" name="weight" required min="1" step="0.1">
                </div>

                <div class="bm-form-group">
                    <label for="bm-height">Height (inches) <span class="required">*</span></label>
                    <input type="number" id="bm-height" name="height" required min="1" step="0.1">
                </div>

                <div class="bm-form-group">
                    <label for="bm-age">Age <span class="required">*</span></label>
                    <input type="number" id="bm-age" name="age" required min="1" max="120">
                </div>

                <div class="bm-form-group">
                    <label>Sex <span class="required">*</span></label>
                    <div class="bm-radio-group">
                        <label>
                            <input type="radio" name="sex" value="male" required> Male
                        </label>
                        <label>
                            <input type="radio" name="sex" value="female" required> Female
                        </label>
                    </div>
                </div>

                <div id="bm-male-fields" class="bm-conditional-fields" style="display:none;">
                    <div class="bm-form-group">
                        <label for="bm-waist">Waist Size (inches) <span class="required">*</span></label>
                        <input type="number" id="bm-waist" name="waist" min="1" step="0.1">
                    </div>
                </div>

                <div id="bm-female-fields" class="bm-conditional-fields" style="display:none;">
                    <div class="bm-form-group">
                        <label for="bm-strap">Strap Size <span class="required">*</span></label>
                        <input type="text" id="bm-strap" name="strap_size" placeholder="e.g., 34">
                    </div>
                    <div class="bm-form-group">
                        <label for="bm-cup">Cup Size <span class="required">*</span></label>
                        <input type="text" id="bm-cup" name="cup_size" placeholder="e.g., C">
                    </div>
                </div>

                <div class="bm-form-group">
                    <button type="submit" class="bm-submit-btn">Get My Size Recommendations</button>
                </div>

                <div id="bm-form-message" class="bm-message" style="display:none;"></div>
            </form>

            <div id="bm-results-container" class="bm-results" style="display:none;">
                <h3>Your Size Recommendations</h3>
                <div id="bm-results-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue plugin CSS and JavaScript assets
     */
    public static function enqueue_assets()
    {
        $plugin_url = plugin_dir_url(__FILE__);
        $css_url = $plugin_url . 'assets/css/bm-style.css';
        $js_url = $plugin_url . 'assets/js/bm-form.js';

        // Debug: Log the URLs being used (remove after testing)
        error_log('BM Plugin URL: ' . $plugin_url);
        error_log('BM CSS URL: ' . $css_url);
        error_log('BM JS URL: ' . $js_url);

        wp_enqueue_style('bm-integration-style', $css_url, array(), self::VERSION);
        wp_enqueue_script('bm-integration-script', $js_url, array('jquery'), self::VERSION, true);

        // Localize script with REST API URL
        wp_localize_script('bm-integration-script', 'bmData', array(
            'restUrl' => rest_url('boldmetrics/v1/submit'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('BM_Integration', 'on_activate'));
register_deactivation_hook(__FILE__, array('BM_Integration', 'on_deactivate'));

// Initialize the plugin
BM_Integration::init();
