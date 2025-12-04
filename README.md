# Bold Metrics Integration for WordPress

A WordPress plugin that seamlessly integrates JotForm submissions with the **Bold Metrics Virtual Sizer API** to provide accurate body measurement analysis and personalized size recommendations.

## Overview

This plugin creates a webhook endpoint that receives measurement data from JotForm submissions, processes it through the Bold Metrics API, and stores the results for display on your WordPress site. Perfect for fashion e-commerce sites, clothing retailers, or any business requiring accurate sizing recommendations.

### Key Features

- **REST API Webhook**: Secure endpoint for receiving JotForm submissions
- **Bold Metrics Integration**: Automatic API calls to Virtual Sizer service
- **Result Storage**: Custom post type for storing sizing recommendations
- **Display Shortcode**: Easy-to-use shortcode for showing results to users
- **Admin Dashboard**: Settings page for API credential management
- **Security**: Optional webhook secret validation with timing-safe comparison
- **WordPress Standards**: Built following WordPress coding standards and best practices

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Bold Metrics Account**: API credentials (Client ID and User Key)
- **JotForm Account**: For form submissions (optional, can use direct API)

## Installation

### Option 1: Manual Installation

1. Download or clone this repository:
   ```bash
   git clone https://github.com/yourusername/jotform-to-boldmetrics.git
   ```

2. Copy the plugin folder to your WordPress plugins directory:
   ```bash
   cp -r jotform-to-boldmetrics /path/to/wordpress/wp-content/plugins/
   ```

3. Activate the plugin:
   - Via WordPress Admin: Navigate to **Plugins** → **Installed Plugins** and activate **Bold Metrics Integration**
   - Via WP-CLI: `wp plugin activate bold-metrics-integration`

### Option 2: WordPress Admin Upload

1. Zip the plugin directory
2. Go to **Plugins** → **Add New** → **Upload Plugin**
3. Upload the ZIP file and activate

### Post-Installation Setup

After activation, the plugin will:
- Register the `bm_result` custom post type
- Create default settings options
- Flush rewrite rules for the REST API endpoint

**Important**: If you encounter 404 errors on the webhook endpoint, go to **Settings** → **Permalinks** and click **Save Changes** to flush rewrite rules.

## Configuration

### 1. Configure Bold Metrics API Credentials

1. Navigate to **Settings** → **Bold Metrics** in your WordPress admin
2. Enter your Bold Metrics credentials:
   - **Client ID**: Your Bold Metrics client identifier
   - **User Key**: Your Bold Metrics API key
   - **Webhook Secret** (optional): Shared secret for validating incoming webhooks

### 2. Set Up JotForm Webhook

1. Copy the webhook endpoint URL from the settings page:
   ```
   https://yoursite.com/wp-json/boldmetrics/v1/process
   ```

2. In your JotForm:
   - Go to **Form Settings** → **Integrations** → **Webhooks**
   - Add a new webhook with the URL above
   - Set method to **POST**
   - If you set a webhook secret, add a custom header:
     - Header Name: `X-BM-Webhook-Secret`
     - Header Value: Your secret from WordPress settings

3. Ensure your JotForm includes these fields (matching the parameter names):
   - `weight` (number, in pounds)
   - `height` (number, in inches)
   - `age` (number, in years)
   - `waist_circum_preferred` OR `bra_size` (at least one required)
   - Optional: `desired_brand`, `desired_garment_type`, `product_id`, `anon_id`

### 3. CSS Assets

The plugin expects CSS at `assets/css/bm-style.css`. To set this up:

```bash
cd /path/to/wordpress/wp-content/plugins/jotform-to-boldmetrics
mkdir -p assets/css
mv bm-style.css assets/css/
```

## Usage

### Testing the Webhook

Test your webhook endpoint with curl:

```bash
curl -X POST https://yoursite.com/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -H "X-BM-Webhook-Secret: your-secret-here" \
  -d '{
    "weight": 150,
    "height": 68,
    "age": 30,
    "waist_circum_preferred": 32,
    "desired_brand": "Levi",
    "desired_garment_type": "jeans"
  }'
```

Expected response:
```json
{
  "ok": true,
  "post_id": 123,
  "response": {
    "good_matches": [...],
    "predictions": {...}
  }
}
```

### Displaying Results

Use the shortcode to display size recommendations on any page or post:

```php
[boldmetrics_result id="123"]
```

Where `123` is the post ID returned by the webhook endpoint.

### Viewing Stored Results

1. Navigate to **BM Results** in the WordPress admin
2. View individual results to see stored input data and API responses
3. Custom fields show:
   - `bm_input`: Original webhook data
   - `bm_response`: Bold Metrics API response

## API Integration

### Bold Metrics Virtual Sizer API

**Endpoint**: `https://api.boldmetrics.io/virtualsizer/get`

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `client_id` | string | Yes | From plugin settings |
| `user_key` | string | Yes | From plugin settings |
| `height` | float | Yes | Height in inches |
| `weight` | float | Yes | Weight in pounds |
| `age` | integer | Yes | Age in years |
| `anon_id` | string | Yes | Anonymous user ID (auto-generated) |
| `waist_circum_preferred` | float | No* | Waist measurement |
| `bra_size` | string | No* | Bra size (e.g., "34C") |
| `desired_brand` | string | No | Target brand name |
| `desired_garment_type` | string | No | Garment type (e.g., "jeans") |
| `product_id` | string | No | Specific product identifier |

*At least one of `waist_circum_preferred` or `bra_size` is required.

#### Response Structure

```json
{
  "good_matches": [
    {
      "brand_size": "M",
      "size": "Medium",
      "fit_score": 95
    }
  ],
  "predictions": {
    "waist": 32.5,
    "hip": 38.2,
    "inseam": 30
  }
}
```

## Development

### File Structure

```
jotform-to-boldmetrics/
├── bold_metrics_word_press_plugin_scaffold.php  # Main plugin file
├── bm-style.css                                 # Styles (move to assets/css/)
├── CLAUDE.md                                    # Comprehensive documentation
├── README.md                                    # This file
└── .git/                                        # Git repository
```

### Key Components

- **Main Class**: `BM_Integration` (singleton pattern)
- **Custom Post Type**: `bm_result` (private, for storing results)
- **REST Endpoint**: `/wp-json/boldmetrics/v1/process`
- **Shortcode**: `[boldmetrics_result id="123"]`
- **Admin Page**: Settings → Bold Metrics

### WordPress Hooks Used

- `init`: Register custom post type
- `admin_menu`: Add settings page
- `admin_init`: Register settings
- `rest_api_init`: Register REST routes
- `wp_enqueue_scripts`: Enqueue CSS
- `register_activation_hook`: Plugin activation setup

### Security Measures

- Direct access prevention via `ABSPATH` check
- Capability checks for admin pages (`manage_options`)
- Input sanitization with `sanitize_text_field()`
- Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- Timing-safe webhook secret comparison with `hash_equals()`
- WordPress Settings API nonce verification

### Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes following WordPress coding standards
4. Test thoroughly
5. Commit with descriptive messages
6. Push and create a pull request

## Troubleshooting

### 404 Error on Webhook Endpoint

**Solution**: Flush rewrite rules
- Go to **Settings** → **Permalinks** and click **Save Changes**
- Or via WP-CLI: `wp rewrite flush`

### CSS Not Loading

**Solution**: Ensure CSS file is in the correct location
```bash
mkdir -p assets/css
mv bm-style.css assets/css/bm-style.css
```

### API Call Failures

**Checklist**:
- Verify credentials in Settings → Bold Metrics
- Check PHP error logs: `wp-content/debug.log` (if `WP_DEBUG_LOG` is enabled)
- Test API endpoint accessibility manually
- Ensure required fields are present in webhook data

### Shortcode Not Displaying

**Checklist**:
- Verify post ID exists: Check **BM Results** in admin
- Confirm post has `bm_response` meta data
- Check for JavaScript console errors
- Verify user has permission to view content

### Webhook Secret Validation Failing

**Solution**: Ensure header is exactly `X-BM-Webhook-Secret` (case-insensitive in code) and matches the secret in WordPress settings.

## Debug Mode

Enable WordPress debug logging:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will be written to `wp-content/debug.log`.

## Roadmap

Future enhancements planned:

- [ ] Internationalization (i18n) support
- [ ] Admin results table with custom columns
- [ ] Direct frontend form (no JotForm required)
- [ ] Email notifications with recommendations
- [ ] WooCommerce product integration
- [ ] API usage analytics dashboard
- [ ] Response caching to reduce API calls
- [ ] Detailed logging system
- [ ] PHPUnit test coverage
- [ ] Gutenberg block for result display

## License

This project is licensed under the GPL v2 or later.

## Author

**Jesse David**

## Support

For issues or questions:
- Review the [CLAUDE.md](CLAUDE.md) documentation for detailed information
- Check the [Troubleshooting](#troubleshooting) section
- Open an issue on GitHub
- Consult [Bold Metrics API documentation](https://boldmetrics.com/)

## Version History

### 0.1.0 (Current)
- Initial release
- REST API webhook endpoint
- Bold Metrics API integration
- Custom post type for results
- Admin settings page
- Result display shortcode
- Optional webhook secret validation

---

**Made with ❤️ for the WordPress community**
