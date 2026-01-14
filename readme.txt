=== Bold Metrics Integration ===
Contributors: jessedavid
Tags: jotform, measurements, size-recommendations, api-integration, boldmetrics
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates JotForm form submissions with the Bold Metrics Virtual Sizer API for accurate body measurements and size recommendations.

== Description ==

Bold Metrics Integration seamlessly connects your JotForm submissions with the Bold Metrics Virtual Sizer API to provide accurate body measurement analysis and personalized size recommendations.

**Key Features:**

* **Webhook Integration:** Receive and process JotForm submissions automatically
* **Bold Metrics API:** Leverage AI-powered body measurement predictions
* **Custom Post Type:** Store results securely in WordPress
* **Shortcode Display:** Show size recommendations anywhere with `[boldmetrics_result id="123"]`
* **Admin Settings:** Easy configuration through WordPress admin panel
* **Security:** Optional webhook secret validation for secure data transmission

**How It Works:**

1. User submits measurements through your JotForm form
2. JotForm sends webhook data to your WordPress site
3. Plugin processes data and calls Bold Metrics API
4. Results are stored as custom post type
5. Display recommendations using the shortcode

**Requirements:**

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Bold Metrics API credentials (Client ID and User Key)
* JotForm account for webhook setup

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/bold-metrics-integration/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Bold Metrics to configure your API credentials
4. Copy the webhook endpoint URL from the settings page
5. Configure your JotForm to send webhooks to this endpoint

**Detailed Setup:**

1. **Get Bold Metrics Credentials:**
   - Sign up at [Bold Metrics](https://boldmetrics.com/)
   - Obtain your Client ID and User Key

2. **Configure Plugin:**
   - Navigate to Settings > Bold Metrics in WordPress admin
   - Enter your Client ID and User Key
   - (Optional) Set a webhook secret for added security

3. **Set Up JotForm:**
   - Create or edit your form in JotForm
   - Add webhook integration
   - Use the endpoint URL from WordPress settings
   - Map form fields to expected parameters (weight, height, age, etc.)

4. **Display Results:**
   - Use shortcode `[boldmetrics_result id="POST_ID"]` on any page or post
   - Replace POST_ID with the ID of the stored result

== Frequently Asked Questions ==

= What is Bold Metrics? =

Bold Metrics provides AI-powered body measurement technology that predicts accurate body measurements and size recommendations from basic inputs like height, weight, and age.

= Do I need a Bold Metrics account? =

Yes, you need Bold Metrics API credentials (Client ID and User Key) to use this plugin. Visit boldmetrics.com to sign up.

= What JotForm fields are required? =

Your JotForm must include fields for:
* weight (in pounds)
* height (in inches)
* age (in years)
* Either waist_circum_preferred OR bra_size

Optional fields include: desired_brand, desired_garment_type, product_id

= How do I secure the webhook endpoint? =

Set a webhook secret in the plugin settings, then include it as an `X-BM-Webhook-Secret` header in your JotForm webhook configuration.

= Where are the results stored? =

Results are stored as a custom post type called "BM Results" in your WordPress database. They are private by default and only visible to administrators.

= Can I customize the result display? =

Yes! You can modify the CSS file at `assets/css/bm-style.css` to customize the appearance of the results displayed by the shortcode.

= What happens to my data if I uninstall the plugin? =

When you delete (not deactivate) the plugin, all stored results and settings are permanently removed from your database. This is handled by the uninstall.php script.

== Screenshots ==

1. Admin settings page for configuring API credentials
2. Webhook endpoint URL display
3. Example of shortcode output showing size recommendations
4. BM Results custom post type list view

== Changelog ==

= 0.1.0 =
* Initial release
* REST API endpoint for JotForm webhooks
* Bold Metrics Virtual Sizer API integration
* Custom post type for storing results
* Admin settings page for credentials
* Shortcode for displaying recommendations
* Optional webhook secret validation
* CSS styling for result display

== Upgrade Notice ==

= 0.1.0 =
Initial release of Bold Metrics Integration plugin.

== Privacy & Data Handling ==

This plugin sends user-submitted measurement data (height, weight, age, and optional measurements) to the Bold Metrics API for processing. Please ensure your privacy policy reflects this data sharing.

The plugin stores:
* API credentials in WordPress options table
* Measurement inputs and API responses as custom post types
* Post meta data for input/output values

No data is sent to third parties except Bold Metrics for the explicit purpose of generating size recommendations.

== Support ==

For issues, feature requests, or contributions, visit:
https://github.com/davidjes1/jotform-to-boldmetrics

== Credits ==

Developed by Jesse David
Bold Metrics API by Bold Metrics Inc.
