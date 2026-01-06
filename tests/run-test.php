<?php
// 1. Include the mock environment
require_once __DIR__ . '/mock-wp.php';

// 1.5 Define Constants (Simulate wp-config.php)
define('BM_CLIENT_ID', 'united_shield');
define('BM_USER_KEY', 'bc8c8d307c932d0ffbe2422ed0821711ff8e342917f86f6f');

// 2. Include the plugin file
require_once __DIR__ . '/../bold_metrics_word_press_plugin_scaffold.php';

// 3. Setup Mock Data
// This matches the JSON provided by the user
$json_response = '{
"code": 200,
"customer": {
  "desired_brand": "farah",
  "desired_garment_type": "t_shirt",
  "height": 72.00,
  "waist_circum_preferred": 30.00,
  "weight": 150.00
},
"dimensions": {
  "acromion_height": 58.57,
  "acromion_radial_len": 13.40,
  "acromion_radial_stylion_len": 24.19
},
"message": {
  "overall": "OK"
},
"size_recommendations": {
  "good_matches": [
    {
      "fit_description": {
        "chest": "just right",
        "garment": "just right"
      },
      "fit_score": {
        "chest": 0.01,
        "garment": 0.01
      },
      "garment": {
        "brand": "farah",
        "category": "shirt",
        "fit": "",
        "size": "s",
        "style": "",
        "type": "t_shirt"
      }
    }
  ],
  "poor_matches": []
},
"outlier": false,
"outlier_messages": {
  "overall": "All good",
  "specifics": []
}
}';

$decoded_response = json_decode($json_response, true);

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
