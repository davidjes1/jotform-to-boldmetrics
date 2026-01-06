<?php
// Include the mock environment
require_once __DIR__ . '/mock-wp.php';

// Include the plugin file
require_once __DIR__ . '/../bold_metrics_word_press_plugin_scaffold.php';

// Helper to reset mocks
function reset_mocks()
{
    global $mock_remote_get_calls;
    $mock_remote_get_calls = array();
}

// Mock get_option to return specific values
function mock_set_options($options)
{
    global $mock_options;
    $mock_options = $options;
}

// Override get_option for this test
// Note: We can't redefine the function, but we can rely on the fact that mock-wp.php's get_option
// is simple. However, mock-wp.php's get_option returns $default.
// We need to modify mock-wp.php to support setting options, or use add_option if it worked.
// mock-wp.php: function get_option($option, $default = false) { return $default; }
// This is too simple. It doesn't look at any storage.
// I need to update mock-wp.php to actually store options.

// Let's assume I will update mock-wp.php to use a global $mock_options storage.
// For now, let's write the test assuming I'll fix mock-wp.php next.
// ... actually, I should fix mock-wp.php first.

// Let's just write the test script to manually manipulate the global if I make it one.
// I will update mock-wp.php to use $mock_wp_options global.

echo "Test 1: Credentials from Options (Constants undefined)\n";
global $mock_wp_options;
$mock_wp_options[BM_Integration::OPTION_KEY] = array(
    'client_id' => 'OPT_CLIENT',
    'user_key' => 'OPT_KEY'
);

// Call API
$data = array('height' => 70, 'weight' => 160, 'age' => 30, 'anon_id' => '123');
BM_Integration::call_boldmetrics_api($data);

global $mock_remote_get_calls;
$last_call = end($mock_remote_get_calls);
$url = $last_call['url'];

if (strpos($url, 'client_id=OPT_CLIENT') !== false && strpos($url, 'user_key=OPT_KEY') !== false) {
    echo "PASS: Used options credentials.\n";
} else {
    echo "FAIL: Did not use options credentials. URL: $url\n";
    exit(1);
}

echo "\nTest 2: Credentials from Constants (Override Options)\n";
define('BM_CLIENT_ID', 'CONST_CLIENT');
define('BM_USER_KEY', 'CONST_KEY');

// Call API again
BM_Integration::call_boldmetrics_api($data);

$last_call = end($mock_remote_get_calls);
$url = $last_call['url'];

if (strpos($url, 'client_id=CONST_CLIENT') !== false && strpos($url, 'user_key=CONST_KEY') !== false) {
    echo "PASS: Used constant credentials.\n";
} else {
    echo "FAIL: Did not use constant credentials. URL: $url\n";
    exit(1);
}

echo "\nAll tests passed.\n";
