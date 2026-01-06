<?php
// Mock WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Mock WordPress functions
function add_action($hook, $callback)
{
}
function add_shortcode($tag, $callback)
{
}
function register_activation_hook($file, $callback)
{
}
$mock_wp_options = array();
function get_option($option, $default = false)
{
    global $mock_wp_options;
    return isset($mock_wp_options[$option]) ? $mock_wp_options[$option] : $default;
}
function add_option($option, $value = '', $deprecated = '', $autoload = 'yes')
{
    global $mock_wp_options;
    $mock_wp_options[$option] = $value;
}
function register_post_type($post_type, $args = array())
{
}
function flush_rewrite_rules($hard = true)
{
}
function add_options_page($page_title, $menu_title, $capability, $menu_slug, $function = '')
{
}
function register_setting($option_group, $option_name, $args = array())
{
}
function add_settings_section($id, $title, $callback, $page)
{
}
function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array())
{
}
function sanitize_text_field($str)
{
    return trim($str);
}
function esc_attr($text)
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function esc_html($text)
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function esc_url($url)
{
    return $url;
}
function current_user_can($capability)
{
    return true;
}
function settings_fields($option_group)
{
}
function do_settings_sections($page)
{
}
function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null)
{
}
function rest_url($path = '')
{
    return 'http://localhost/wp-json' . $path;
}
function register_rest_route($namespace, $route, $args = array(), $override = false)
{
}
function wp_generate_uuid4()
{
    return uniqid();
}
function is_wp_error($thing)
{
    return $thing instanceof WP_Error;
}
function wp_insert_post($postarr, $wp_error = false)
{
    return 123;
}
function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '')
{
    global $mock_post_meta;
    $mock_post_meta[$post_id][$meta_key] = $meta_value;
}
function get_post_meta($post_id, $key = '', $single = false)
{
    global $mock_post_meta;
    if (isset($mock_post_meta[$post_id][$key])) {
        return $mock_post_meta[$post_id][$key];
    }
    return $single ? '' : array();
}
$mock_remote_get_calls = array();
function wp_remote_get($url, $args = array())
{
    global $mock_remote_get_calls;
    $mock_remote_get_calls[] = array('url' => $url, 'args' => $args);
    return array();
}
function wp_remote_retrieve_response_code($response)
{
    return 200;
}
function wp_remote_retrieve_body($response)
{
    return '{}';
}
function shortcode_atts($pairs, $atts, $shortcode = '')
{
    $atts = (array) $atts;
    $out = array();
    foreach ($pairs as $name => $default) {
        if (array_key_exists($name, $atts)) {
            $out[$name] = $atts[$name];
        } else {
            $out[$name] = $default;
        }
    }
    return $out;
}
function plugin_dir_url($file)
{
    return '/wp-content/plugins/my-plugin/';
}
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
}
function wp_trim_words($text, $num_words = 55, $more = null)
{
    return $text;
}
function add_query_arg($args, $url)
{
    if (strpos($url, '?') === false) {
        $url .= '?';
    } else {
        $url .= '&';
    }
    return $url . http_build_query($args);
}

// Mock Classes
class WP_Error
{
    public function __construct($code = '', $message = '', $data = '')
    {
    }
    public function get_error_message()
    {
        return '';
    }
}

class WP_REST_Server
{
    const CREATABLE = 'POST';
}

class WP_REST_Request
{
    public function get_header($header)
    {
        return '';
    }
    public function get_params()
    {
        return array();
    }
}

class WP_REST_Response
{
    public function __construct($data = null, $status = 200, $headers = array())
    {
    }
}

// Global storage for mock data
$mock_post_meta = array();
