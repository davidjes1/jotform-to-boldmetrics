<?php
// 1. Include the mock environment
require_once __DIR__ . '/mock-wp.php';

// 1.5 Load credentials from .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: tests/.env file not found. Copy tests/.env.example and add your credentials.\n");
}
$envVars = parse_ini_file($envFile);
if (empty($envVars['BM_CLIENT_ID']) || empty($envVars['BM_USER_KEY'])) {
    die("ERROR: BM_CLIENT_ID and BM_USER_KEY must be set in tests/.env\n");
}
define('BM_CLIENT_ID', $envVars['BM_CLIENT_ID']);
define('BM_USER_KEY', $envVars['BM_USER_KEY']);

// 2. Include the plugin file
require_once __DIR__ . '/../bold-metrics-integration.php';

// 3. Setup Mock Data
// This matches the structure expected by the shortcode (lines 284, 304 of plugin)
$decoded_response = array(
    'good_matches' => array(
        array(
            'brand_size' => 'Farah Small',
            'size' => 'S',
            'fit_score' => '95%'
        ),
        array(
            'brand_size' => 'Farah Medium',
            'size' => 'M',
            'fit_score' => '88%'
        )
    ),
    'predictions' => array(
        'chest_circum' => 38.12,
        'waist_circum' => 32.45,
        'hip_circum' => 39.87,
        'acromion_height' => 58.57,
        'inseam' => 30.5
    )
);

// Simulate saving this to Post Meta (Post ID 123)
global $mock_post_meta;
$mock_post_meta[123]['bm_response'] = $decoded_response;
$mock_post_meta[123]['bm_input'] = array(); // Empty input for now

// 4. Run the Shortcode Logic
echo "Running Shortcode Test...\n";
echo "--------------------------------------------------\n";

$output = BM_Integration::shortcode_show_result(array('id' => 123));

echo $output;

echo "\n--------------------------------------------------\n";
echo "Testing API Credential Usage...\n";

// Test API call with the constants defined above
$test_input = array(
  'height' => 72,
  'weight' => 150,
  'age' => 30,
  'anon_id' => 'local-test'
);

BM_Integration::call_boldmetrics_api($test_input);

global $mock_remote_get_calls;
if (!empty($mock_remote_get_calls)) {
  $last_call = end($mock_remote_get_calls);
  echo "API Call URL: " . $last_call['url'] . "\n";
  if (strpos($last_call['url'], 'client_id=' . BM_CLIENT_ID) !== false) {
    echo "SUCCESS: Client ID found in URL.\n";
  } else {
    echo "FAILURE: Client ID NOT found in URL.\n";
  }
} else {
  echo "No API calls made.\n";
}

echo "Test Complete.\n";
