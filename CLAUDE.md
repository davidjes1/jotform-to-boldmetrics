# CLAUDE.md - Bold Metrics WordPress Plugin

## Project Overview

This is a **WordPress plugin** that integrates JotForm form submissions with the **Bold Metrics Virtual Sizer API**. The plugin enables seamless body measurement analysis and size recommendations by:

1. Receiving webhook data from JotForm submissions
2. Processing measurement data and calling the Bold Metrics API
3. Storing results in WordPress as custom post types
4. Displaying size recommendations via shortcodes

**Plugin Name**: Bold Metrics Integration
**Version**: 0.1.0
**Author**: Jesse David
**Text Domain**: bold-metrics-integration

---

## Codebase Structure

```
jotform-to-boldmetrics/
├── bold-metrics-integration.php                 # Main plugin file
├── assets/
│   └── css/
│       └── bm-style.css                         # Styling for results display
└── .git/                                        # Git repository
```

### Key Files

1. **bold-metrics-integration.php** (~400 lines)
   - Main plugin entry point
   - Contains the `BM_Integration` class with all functionality
   - Handles WordPress hooks, REST API, admin UI, and API integration
   - Includes comprehensive PHPDoc documentation

2. **assets/css/bm-style.css** (35 lines)
   - Styles for the result display shortcode
   - Located in proper assets directory structure

---

## Architecture & Components

### Core Class: `BM_Integration`

This is a **singleton-style class** using static methods. All functionality is contained within this class.

#### Key Constants
- `VERSION`: Plugin version (0.1.0)
- `OPTION_KEY`: WordPress option name for storing settings ('bm_integration_options')

#### Main Components

1. **Custom Post Type (CPT)**: `bm_result`
   - Stores Bold Metrics API responses
   - Private post type (not public-facing)
   - Supports title and custom fields
   - Meta fields:
     - `bm_input`: Original webhook data
     - `bm_response`: Bold Metrics API response

2. **REST API Endpoint**
   - Route: `/wp-json/boldmetrics/v1/process`
   - Method: POST
   - Purpose: Receives JotForm webhook data
   - Authentication: Optional webhook secret via `X-BM-Webhook-Secret` header

3. **Admin Settings Page**
   - Location: Settings > Bold Metrics
   - Fields:
     - Client ID (text)
     - User Key (password)
     - Webhook Secret (optional text)
   - Displays webhook endpoint URL for configuration

4. **Shortcode**: `[boldmetrics_result id="123"]`
   - Displays size recommendations from stored results
   - Shows good matches and predicted measurements

5. **Bold Metrics API Integration**
   - Endpoint: `https://api.boldmetrics.io/virtualsizer/get`
   - Method: GET with query parameters
   - Timeout: 15 seconds

---

## WordPress Conventions Used

### Hooks & Actions
- `init`: Register custom post type
- `admin_menu`: Add settings page
- `admin_init`: Register settings fields
- `rest_api_init`: Register REST routes
- `wp_enqueue_scripts`: Enqueue CSS assets
- `register_activation_hook`: Setup on plugin activation

### Security Practices
- `ABSPATH` check to prevent direct file access (line 10-12)
- `current_user_can('manage_options')` for admin page access
- `sanitize_text_field()` for input sanitization
- `esc_attr()`, `esc_html()`, `esc_url()` for output escaping
- `hash_equals()` for timing-safe webhook secret comparison

### WordPress APIs Used
- **Settings API**: `register_setting()`, `add_settings_section()`, `add_settings_field()`
- **Options API**: `get_option()`, `add_option()`, `update_option()`
- **Post API**: `wp_insert_post()`, `get_post_meta()`, `update_post_meta()`
- **HTTP API**: `wp_remote_get()` for external API calls
- **REST API**: `register_rest_route()`, `WP_REST_Request`, `WP_REST_Response`
- **Shortcode API**: `add_shortcode()`

---

## Data Flow

### Webhook Processing Flow

```
JotForm Submission
    ↓
POST /wp-json/boldmetrics/v1/process
    ↓
Validate webhook secret (if configured)
    ↓
Extract & validate required fields:
  - weight (float)
  - height (float)
  - age (int)
  - waist_circum_preferred OR bra_size
  - Optional: desired_brand, desired_garment_type, product_id
    ↓
Call Bold Metrics API
    ↓
Store result as 'bm_result' post type
    ↓
Return JSON response with post_id
```

### Required JotForm Fields

The webhook handler expects these POST parameters:
- `weight` (required)
- `height` (required)
- `age` (required)
- `waist_circum_preferred` OR `bra_size` (at least one required)
- `desired_brand` (optional)
- `desired_garment_type` (optional)
- `product_id` (optional)
- `anon_id` (optional, auto-generated if missing)

---

## Development Workflows

### Initial Setup

1. **WordPress Environment**
   - Requires WordPress 5.0+ (uses modern APIs)
   - PHP 7.4+ recommended (uses null coalescing operator `??`)

2. **Installation**
   ```bash
   # Copy plugin to WordPress plugins directory
   cp -r jotform-to-boldmetrics /path/to/wordpress/wp-content/plugins/

   # Activate via WordPress admin or WP-CLI
   wp plugin activate bold-metrics-integration
   ```

3. **Configuration**
   - Navigate to Settings > Bold Metrics
   - Enter Client ID and User Key from Bold Metrics
   - (Optional) Set webhook secret for security
   - Copy webhook endpoint URL: `/wp-json/boldmetrics/v1/process`

### CSS Asset Location

The plugin expects CSS at `assets/css/bm-style.css` and it is properly located in the assets directory structure.

### Git Workflow

- **Current Branch**: `claude/claude-md-miout9w96fv4wq6u-014955AjCQvH1GPDRPPeLn2T`
- **Commits**:
  - `f04d087`: Added bold metrics word press plugin scaffold file
  - `0039e4d`: Create bm-style.css

### Testing the Webhook

```bash
# Test webhook endpoint
curl -X POST https://yoursite.com/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -H "X-BM-Webhook-Secret: your-secret" \
  -d '{
    "weight": 150,
    "height": 68,
    "age": 30,
    "waist_circum_preferred": 32,
    "desired_brand": "Levi",
    "desired_garment_type": "jeans"
  }'
```

---

## Code Conventions

### Naming Conventions
- **Class**: `BM_Integration` (WordPress standard: uppercase with underscores)
- **Functions**: `snake_case` (WordPress standard)
- **Post Type**: `bm_result`
- **Option Key**: `bm_integration_options`
- **CSS Classes**: `kebab-case` (`.bm-result`, `.bm-error`)

### Code Style
- **Indentation**: Spaces (not tabs) for PHP code
- **Brackets**: K&R style (opening brace on same line)
- **Arrays**: Short array syntax `[]` preferred
- **String Quotes**: Single quotes for strings, double for interpolation
- **WordPress Coding Standards**: Mostly followed

### Error Handling
- Uses `WP_Error` for API errors
- Returns `WP_REST_Response` with appropriate HTTP status codes
- Validates required fields before API calls

---

## API Integration Details

### Bold Metrics Virtual Sizer API

**Endpoint**: `https://api.boldmetrics.io/virtualsizer/get`
**Method**: GET
**Authentication**: Query parameters (`client_id`, `user_key`)

#### Required Parameters
- `client_id`: From settings
- `user_key`: From settings
- `height`: In inches
- `weight`: In pounds
- `age`: In years
- `anon_id`: Anonymous user identifier

#### Optional Parameters
- `waist_circum_preferred`: Waist measurement
- `bra_size`: Bra size (e.g., "34C")
- `desired_brand`: Brand name
- `desired_garment_type`: Type of garment
- `product_id`: Specific product identifier

#### Expected Response Structure
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

---

## Common Modification Tasks

### Adding New Webhook Fields

1. Update `handle_webhook()` method (line 134)
2. Add field to `$data` array with proper sanitization
3. Pass to `call_boldmetrics_api()` if needed
4. Update `call_boldmetrics_api()` to include in API request

### Customizing Result Display

1. Modify `shortcode_show_result()` method (line 239)
2. Update HTML structure and classes
3. Adjust CSS in `bm-style.css`

### Adding Settings Fields

1. Add field definition in `register_settings()` (line 70)
2. Create field callback method (e.g., `field_new_setting()`)
3. Update `sanitize_options()` to handle new field (line 80)

### Modifying API Request

1. Edit `call_boldmetrics_api()` method (line 188)
2. Update `$query` array with new parameters
3. Ensure proper validation and error handling

### Webhook Security Enhancement

The plugin supports optional webhook secret validation:
- Set webhook secret in admin settings
- Include `X-BM-Webhook-Secret` header in JotForm webhook
- Uses timing-safe comparison with `hash_equals()`

---

## Debugging & Troubleshooting

### Enable WordPress Debug Mode
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Common Issues

1. **404 on webhook endpoint**
   - Flush rewrite rules: Settings > Permalinks > Save
   - Or: `wp rewrite flush` via WP-CLI

2. **CSS not loading**
   - Check file path: Should be at `assets/css/bm-style.css`
   - Verify `plugin_dir_url(__FILE__)` resolves correctly

3. **API call failures**
   - Verify credentials in Settings > Bold Metrics
   - Check API endpoint accessibility
   - Review error logs for `WP_Error` messages

4. **Shortcode not displaying**
   - Verify post ID exists: `wp post get <id> --post_type=bm_result`
   - Check post meta exists: `bm_response` and `bm_input`

---

## File Organization Best Practices

### Current Structure
```
bold-metrics-integration/
├── bold-metrics-integration.php      # Main plugin file
├── assets/
│   └── css/
│       └── bm-style.css              # Plugin styles
├── tests/
│   ├── run-test.php                   # Test runner
│   ├── test-credentials.php           # Credential tests
│   ├── mock-wp.php                    # Mock WordPress environment
│   └── .env.example                   # Example credentials
├── CLAUDE.md                          # This documentation
├── README.md                          # User-facing readme
├── TESTING.md                         # Testing guide
└── DEV-SETUP.md                       # Development setup guide
```

### Future Expansion Structure
```
bold-metrics-integration/
├── bold-metrics-integration.php      # Main plugin file
├── readme.txt                         # WordPress plugin readme
├── assets/
│   ├── css/
│   │   └── bm-style.css
│   └── js/
│       └── admin.js                   # Future: Admin JS
├── includes/
│   ├── class-bm-integration.php      # Move class here
│   ├── class-bm-api.php              # Future: Separate API class
│   └── functions.php                  # Helper functions
└── templates/
    └── result-display.php             # Future: Template for shortcode
```

---

## Security Considerations

### Current Security Measures
1. Direct access prevention via `ABSPATH` check
2. Capability checks for admin pages
3. Input sanitization with `sanitize_text_field()`
4. Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
5. Nonce verification (handled by Settings API)
6. Timing-safe webhook secret comparison

### Recommendations for Enhancement
1. **Add nonce to shortcode AJAX**: If adding AJAX functionality
2. **Rate limiting**: Consider adding rate limiting to webhook endpoint
3. **Input validation**: More strict validation for measurement ranges
4. **SQL injection**: Already protected by WordPress APIs
5. **XSS**: Already escaped, maintain this practice

---

## Testing Strategy

### Manual Testing Checklist

- [ ] Plugin activation successful
- [ ] Settings page accessible and saves correctly
- [ ] Webhook endpoint returns 200 on valid request
- [ ] Webhook endpoint returns 400 on missing required fields
- [ ] Webhook endpoint returns 403 on invalid secret
- [ ] Bold Metrics API called with correct parameters
- [ ] Results stored as custom post type
- [ ] Shortcode displays results correctly
- [ ] CSS loads on frontend
- [ ] Admin UI displays webhook URL correctly

### Test Webhook Locally

Use a tool like ngrok to expose local WordPress:
```bash
ngrok http 80
# Use ngrok URL in JotForm webhook settings
```

---

## WordPress Plugin Standards

### Plugin Header Requirements (Already Implemented)
- Plugin Name ✓
- Description ✓
- Version ✓
- Author ✓
- Text Domain ✓

### Missing (Optional Additions)
- Author URI
- Plugin URI
- License (recommend GPLv2+)
- Requires at least (WordPress version)
- Requires PHP

### Recommended Plugin Header
```php
/**
 * Plugin Name: Bold Metrics Integration
 * Plugin URI: https://github.com/yourusername/jotform-to-boldmetrics
 * Description: Integrates JotForm submissions with the Bold Metrics Virtual Sizer API.
 * Version: 0.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Jesse David
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bold-metrics-integration
 * Domain Path: /languages
 */
```

---

## AI Assistant Guidelines

### When Working with This Codebase

1. **Always Read Before Modifying**
   - Read the main plugin file before making changes
   - Understand the context and existing patterns

2. **Maintain WordPress Standards**
   - Use WordPress APIs over raw PHP
   - Follow WordPress naming conventions
   - Escape output, sanitize input, validate data

3. **Security First**
   - Never remove security checks
   - Add proper escaping for any new output
   - Sanitize any new inputs
   - Validate webhook data thoroughly

4. **Backward Compatibility**
   - Don't break existing shortcodes
   - Don't change post type slugs
   - Don't change option keys without migration

5. **Code Organization**
   - Keep related code together
   - Add comments for complex logic
   - Use descriptive variable and function names

6. **Testing Requirements**
   - Test webhook endpoint after changes
   - Verify admin settings save correctly
   - Check shortcode output renders properly
   - Confirm API integration still works

### Common AI Assistant Tasks

1. **Add new measurement fields**: Update webhook handler and API call
2. **Customize result display**: Modify shortcode function and CSS
3. **Add admin features**: Use WordPress Settings API patterns
4. **Improve error handling**: Add more `WP_Error` checks
5. **Enhance security**: Add rate limiting or additional validation
6. **Refactor code**: Split into multiple files/classes while maintaining structure

### Quick Reference: Key Functions

- **Webhook Handler**: `handle_webhook()` at line 134
- **API Call**: `call_boldmetrics_api()` at line 188
- **Shortcode**: `shortcode_show_result()` at line 239
- **Settings Page**: `settings_page()` at line 104
- **CSS Enqueue**: `enqueue_assets()` at line 280

---

## Version History

- **0.1.0** (Current): Initial scaffold with core functionality
  - REST API endpoint for webhooks
  - Admin settings for credentials
  - Custom post type for results
  - Shortcode for displaying recommendations

---

## Future Enhancements (Not Yet Implemented)

1. **Internationalization**: Add translation support
2. **Admin Results Table**: Custom columns for CPT list view
3. **AJAX Form**: Direct form on frontend (bypass JotForm)
4. **Result Email**: Send recommendations via email
5. **Product Integration**: Link to WooCommerce products
6. **Analytics**: Track API usage and success rates
7. **Caching**: Cache API responses to reduce calls
8. **Logging**: Detailed logging for debugging
9. **Unit Tests**: PHPUnit tests for core functionality
10. **Block Editor**: Gutenberg block for results display

---

## Repository Information

- **Git Status**: Clean working directory
- **Current Branch**: `claude/claude-md-miout9w96fv4wq6u-014955AjCQvH1GPDRPPeLn2T`
- **Remote**: Uses git push with `-u origin <branch-name>`
- **Branches**: Feature branch pattern with `claude/` prefix

### Git Workflow for AI Assistants

1. **Always commit** changes with descriptive messages
2. **Always push** to the feature branch (not main/master)
3. **Create branches** locally if they don't exist
4. **Use retry logic** for network failures (exponential backoff)
5. **Never force push** to main/master without permission

---

## Contact & Support

**Author**: Jesse David
**Project**: JotForm to Bold Metrics Integration
**Type**: WordPress Plugin
**Status**: Active Development (v0.1.0)

For questions about Bold Metrics API, consult their official documentation at https://boldmetrics.com/

---

**Last Updated**: 2025-12-02
**Document Version**: 1.0
**Generated for**: Claude AI Assistant
