<?php
/**
 * Mock WordPress Environment for Testing
 *
 * This file provides mock implementations of WordPress core functions
 * to allow testing the plugin outside of a full WordPress installation.
 */

// Mock globals
global $mock_wp_options;
global $mock_post_meta;
global $mock_remote_get_calls;
global $mock_posts;

$mock_wp_options = array();
$mock_post_meta = array();
$mock_remote_get_calls = array();
$mock_posts = array();

// Define ABSPATH if not defined (plugin checks for this)
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/**
 * Mock get_option function
 */
function get_option($option, $default = false)
{
    global $mock_wp_options;
    if (isset($mock_wp_options[$option])) {
        return $mock_wp_options[$option];
    }
    return $default;
}

/**
 * Mock add_option function
 */
function add_option($option, $value)
{
    global $mock_wp_options;
    if (!isset($mock_wp_options[$option])) {
        $mock_wp_options[$option] = $value;
        return true;
    }
    return false;
}

/**
 * Mock update_option function
 */
function update_option($option, $value)
{
    global $mock_wp_options;
    $mock_wp_options[$option] = $value;
    return true;
}

/**
 * Mock wp_remote_get function
 */
function wp_remote_get($url, $args = array())
{
    global $mock_remote_get_calls;

    // Log the call
    $mock_remote_get_calls[] = array(
        'url' => $url,
        'args' => $args
    );

    // Return a mock successful response
    return array(
        'response' => array(
            'code' => 200,
            'message' => 'OK'
        ),
        'body' => json_encode(array(
            'good_matches' => array(
                array(
                    'brand_size' => 'Test Brand Medium',
                    'size' => 'M',
                    'fit_score' => '92%'
                )
            ),
            'predictions' => array(
                'chest_circum' => 40.0,
                'waist_circum' => 34.0,
                'hip_circum' => 38.0
            )
        ))
    );
}

/**
 * Mock wp_remote_retrieve_response_code function
 */
function wp_remote_retrieve_response_code($response)
{
    if (isset($response['response']['code'])) {
        return $response['response']['code'];
    }
    return 0;
}

/**
 * Mock wp_remote_retrieve_body function
 */
function wp_remote_retrieve_body($response)
{
    if (isset($response['body'])) {
        return $response['body'];
    }
    return '';
}

/**
 * Mock is_wp_error function
 */
function is_wp_error($thing)
{
    return ($thing instanceof WP_Error);
}

/**
 * Mock WP_Error class
 */
class WP_Error
{
    public $errors = array();
    public $error_data = array();

    public function __construct($code = '', $message = '', $data = '')
    {
        if (!empty($code)) {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }
    }

    public function get_error_message($code = '')
    {
        if (empty($code)) {
            $code = $this->get_error_code();
        }
        if (isset($this->errors[$code][0])) {
            return $this->errors[$code][0];
        }
        return '';
    }

    public function get_error_code()
    {
        $codes = array_keys($this->errors);
        if (empty($codes)) {
            return '';
        }
        return $codes[0];
    }
}

/**
 * Mock wp_insert_post function
 */
function wp_insert_post($postarr, $wp_error = false)
{
    global $mock_posts;

    // Generate a mock post ID
    $post_id = count($mock_posts) + 1;

    // Store the post data
    $mock_posts[$post_id] = $postarr;

    return $post_id;
}

/**
 * Mock get_post_meta function
 */
function get_post_meta($post_id, $key = '', $single = false)
{
    global $mock_post_meta;

    if (!isset($mock_post_meta[$post_id])) {
        return $single ? '' : array();
    }

    if ($key === '') {
        return $mock_post_meta[$post_id];
    }

    if (isset($mock_post_meta[$post_id][$key])) {
        return $single ? $mock_post_meta[$post_id][$key] : array($mock_post_meta[$post_id][$key]);
    }

    return $single ? '' : array();
}

/**
 * Mock update_post_meta function
 */
function update_post_meta($post_id, $meta_key, $meta_value)
{
    global $mock_post_meta;

    if (!isset($mock_post_meta[$post_id])) {
        $mock_post_meta[$post_id] = array();
    }

    $mock_post_meta[$post_id][$meta_key] = $meta_value;
    return true;
}

/**
 * Mock register_rest_route function
 */
function register_rest_route($namespace, $route, $args = array())
{
    // No-op for testing
    return true;
}

/**
 * Mock register_post_type function
 */
function register_post_type($post_type, $args = array())
{
    // No-op for testing
    return true;
}

/**
 * Mock add_action function
 */
function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
{
    // No-op for testing
    return true;
}

/**
 * Mock add_shortcode function
 */
function add_shortcode($tag, $callback)
{
    // No-op for testing
    return true;
}

/**
 * Mock register_activation_hook function
 */
function register_activation_hook($file, $callback)
{
    // No-op for testing
    return true;
}

/**
 * Mock register_deactivation_hook function
 */
function register_deactivation_hook($file, $callback)
{
    // No-op for testing
    return true;
}

/**
 * Mock add_settings_section function
 */
function add_settings_section($id, $title, $callback, $page)
{
    // No-op for testing
    return true;
}

/**
 * Mock add_settings_field function
 */
function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array())
{
    // No-op for testing
    return true;
}

/**
 * Mock register_setting function
 */
function register_setting($option_group, $option_name, $args = array())
{
    // No-op for testing
    return true;
}

/**
 * Mock add_menu_page function
 */
function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null)
{
    // No-op for testing
    return true;
}

/**
 * Mock esc_html function
 */
function esc_html($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Mock esc_attr function
 */
function esc_attr($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Mock esc_url function
 */
function esc_url($url)
{
    return $url;
}

/**
 * Mock sanitize_text_field function
 */
function sanitize_text_field($str)
{
    return strip_tags($str);
}

/**
 * Mock __() translation function
 */
function __($text, $domain = 'default')
{
    return $text;
}

/**
 * Mock _e() translation function
 */
function _e($text, $domain = 'default')
{
    echo $text;
}

/**
 * Mock plugin_dir_url function
 */
function plugin_dir_url($file)
{
    return 'http://example.com/wp-content/plugins/bold-metrics-integration/';
}

/**
 * Mock wp_enqueue_style function
 */
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
    // No-op for testing
    return true;
}

/**
 * Mock current_user_can function
 */
function current_user_can($capability)
{
    return true; // Always return true for testing
}

/**
 * Mock settings_fields function
 */
function settings_fields($option_group)
{
    // No-op for testing
}

/**
 * Mock do_settings_sections function
 */
function do_settings_sections($page)
{
    // No-op for testing
}

/**
 * Mock submit_button function
 */
function submit_button($text = '', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = '')
{
    echo '<input type="submit" name="' . esc_attr($name) . '" value="' . esc_attr($text) . '" class="button button-' . esc_attr($type) . '" />';
}

/**
 * Mock admin_url function
 */
function admin_url($path = '', $scheme = 'admin')
{
    return 'http://example.com/wp-admin/' . $path;
}

/**
 * Mock rest_url function
 */
function rest_url($path = '', $scheme = 'rest')
{
    return 'http://example.com/wp-json/' . ltrim($path, '/');
}

/**
 * Mock WP_REST_Request class
 */
class WP_REST_Request
{
    protected $params = array();

    public function __construct($method = '', $route = '', $attributes = array())
    {
        // Constructor
    }

    public function get_param($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    public function get_header($key)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }
}

/**
 * Mock WP_REST_Response class
 */
class WP_REST_Response
{
    public $data;
    public $status;

    public function __construct($data = null, $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }
}

echo "Mock WordPress environment loaded.\n";
