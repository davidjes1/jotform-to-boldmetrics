# Testing Guide for Bold Metrics WordPress Plugin

## Overview
This document provides comprehensive testing procedures for the Bold Metrics WordPress plugin, including both automated and manual tests.

## Test Infrastructure

### Files
- `tests/mock-wp.php` - Mock WordPress environment for standalone testing
- `tests/run-test.php` - Tests shortcode display and API calls
- `tests/test-credentials.php` - Tests credential management (options vs constants)
- `tests/test-webhook.php` - Tests webhook endpoint functionality (to be created)

---

## Automated Testing

### 1. Running Existing Tests

#### Test Credentials Management
```bash
php tests/test-credentials.php
```

**What it tests:**
- Loading credentials from WordPress options
- Overriding with constants (BM_CLIENT_ID, BM_USER_KEY)
- API URL construction with correct credentials

**Expected output:**
```
Test 1: Credentials from Options (Constants undefined)
PASS: Used options credentials.

Test 2: Credentials from Constants (Override Options)
PASS: Used constant credentials.

All tests passed.
```

#### Test Shortcode Display
```bash
php tests/run-test.php
```

**What it tests:**
- Shortcode rendering with mock Bold Metrics response
- Display of good matches and predicted measurements
- API credential usage in API calls

**Expected output:**
- HTML output showing size recommendations
- Table of predicted measurements
- API URL validation

---

## Component Testing

### 2. Webhook Endpoint Testing

The webhook endpoint is the main integration point with JotForm. Test it thoroughly.

#### Test Cases

**Test 1: Valid Request with All Required Fields**
```bash
curl -X POST http://localhost/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -d '{
    "weight": 150,
    "height": 68,
    "age": 30,
    "waist_circum_preferred": 32,
    "desired_brand": "Levi",
    "desired_garment_type": "jeans"
  }'
```

Expected: 200 OK with `{"ok": true, "post_id": 123, "response": {...}}`

**Test 2: Missing Required Fields**
```bash
curl -X POST http://localhost/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -d '{
    "weight": 150
  }'
```

Expected: 400 Bad Request with `{"error": "Missing required fields"}`

**Test 3: Webhook Secret Validation (if configured)**
```bash
# Without secret header
curl -X POST http://localhost/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -d '{
    "weight": 150,
    "height": 68,
    "age": 30,
    "waist_circum_preferred": 32
  }'
```

Expected (if secret is set): 403 Forbidden with `{"error": "Invalid webhook secret"}`

```bash
# With correct secret header
curl -X POST http://localhost/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -H "X-BM-Webhook-Secret: your-secret-here" \
  -d '{
    "weight": 150,
    "height": 68,
    "age": 30,
    "waist_circum_preferred": 32
  }'
```

Expected: 200 OK with valid response

**Test 4: Alternative Required Field (bra_size instead of waist)**
```bash
curl -X POST http://localhost/wp-json/boldmetrics/v1/process \
  -H "Content-Type: application/json" \
  -d '{
    "weight": 130,
    "height": 65,
    "age": 28,
    "bra_size": "34C",
    "desired_brand": "Nike"
  }'
```

Expected: 200 OK with valid response

---

## Manual Testing in WordPress

### 3. Plugin Installation & Activation

**Checklist:**
- [ ] Copy plugin to `/wp-content/plugins/jotform-to-boldmetrics/`
- [ ] Activate via Plugins page in WordPress admin
- [ ] No PHP errors appear
- [ ] Settings page appears at Settings > Bold Metrics

### 4. Admin Settings Configuration

**Checklist:**
- [ ] Navigate to Settings > Bold Metrics
- [ ] Enter Client ID and User Key
- [ ] Save settings successfully
- [ ] Settings persist after page reload
- [ ] Webhook endpoint URL is displayed correctly
- [ ] URL format: `http://yoursite.com/wp-json/boldmetrics/v1/process`

**Test with Constants:**
- [ ] Add to `wp-config.php`:
  ```php
  define('BM_CLIENT_ID', 'your_client_id');
  define('BM_USER_KEY', 'your_user_key');
  ```
- [ ] Reload settings page
- [ ] Fields show "Defined in wp-config.php"
- [ ] Fields are disabled (greyed out)
- [ ] User key is masked (shows only first 4 chars + asterisks)

### 5. Custom Post Type

**Checklist:**
- [ ] Navigate to BM Results in admin sidebar (should appear after first webhook)
- [ ] Create a test result via webhook
- [ ] BM Results post type appears
- [ ] Can view individual result posts
- [ ] Post meta shows `bm_input` and `bm_response` data

### 6. Shortcode Display

**Checklist:**
- [ ] Create or edit a page/post
- [ ] Add shortcode: `[boldmetrics_result id="123"]` (use actual post ID)
- [ ] Preview/publish page
- [ ] Shortcode renders size recommendations
- [ ] CSS styling is applied (list styling, table formatting)
- [ ] View page source to confirm CSS file loads

**Test Edge Cases:**
- [ ] Shortcode with invalid ID: `[boldmetrics_result id="999999"]`
  - Should show: "Result not found."
- [ ] Shortcode with no ID: `[boldmetrics_result]`
  - Should show: "No result specified."

### 7. CSS Asset Loading

**Checklist:**
- [ ] CSS file exists at `assets/css/bm-style.css`
- [ ] File is enqueued on frontend pages
- [ ] Check browser DevTools > Network tab
- [ ] Confirm `bm-style.css` loads successfully (200 OK)
- [ ] Styles apply to `.bm-result` elements

---

## Integration Testing with JotForm

### 8. JotForm Webhook Configuration

**Setup:**
1. Create a JotForm with fields matching the required parameters:
   - `weight` (Number)
   - `height` (Number)
   - `age` (Number)
   - `waist_circum_preferred` OR `bra_size`
   - Optional: `desired_brand`, `desired_garment_type`, `product_id`

2. Configure webhook in JotForm:
   - Settings > Integrations > Webhooks
   - Add webhook URL: `https://yoursite.com/wp-json/boldmetrics/v1/process`
   - Method: POST
   - Add custom header (if using webhook secret):
     - Name: `X-BM-Webhook-Secret`
     - Value: `your-secret-here`

**Test:**
- [ ] Submit JotForm with valid data
- [ ] Check WordPress admin for new BM Result post
- [ ] Verify post meta contains submission data
- [ ] Verify Bold Metrics API response is stored
- [ ] Create page with shortcode using new post ID
- [ ] Verify size recommendations display correctly

---

## API Testing

### 9. Bold Metrics API Integration

**Test API Call Directly:**
```bash
php -r "
include 'bold_metrics_word_press_plugin_scaffold.php';
\$data = array(
  'height' => 72,
  'weight' => 150,
  'age' => 30,
  'anon_id' => 'test-123'
);
\$result = BM_Integration::call_boldmetrics_api(\$data);
var_dump(\$result);
"
```

**What to check:**
- [ ] No PHP errors
- [ ] Returns array (not WP_Error)
- [ ] Response contains expected fields:
  - `size_recommendations`
  - `good_matches`
  - `dimensions`
  - `customer`

**Test Error Handling:**
- [ ] Test with invalid credentials (should return WP_Error)
- [ ] Test with missing credentials (should return error)
- [ ] Test with network timeout (if possible)

---

## Security Testing

### 10. Security Validation

**Input Sanitization:**
- [ ] Test webhook with malicious input:
  ```json
  {
    "weight": "<script>alert('xss')</script>",
    "desired_brand": "'; DROP TABLE wp_posts; --"
  }
  ```
- [ ] Verify input is sanitized (no code execution)
- [ ] Check database for proper escaping

**Webhook Secret:**
- [ ] Set webhook secret in settings
- [ ] Test without header (should fail with 403)
- [ ] Test with wrong secret (should fail with 403)
- [ ] Test with correct secret (should succeed)
- [ ] Verify timing-safe comparison (no timing attacks)

**Output Escaping:**
- [ ] Check shortcode output in page source
- [ ] Verify all user data is escaped with `esc_html()`, `esc_attr()`, etc.
- [ ] Test with special characters in input

**Direct Access Prevention:**
- [ ] Try accessing plugin file directly in browser:
  `http://yoursite.com/wp-content/plugins/jotform-to-boldmetrics/bold_metrics_word_press_plugin_scaffold.php`
- [ ] Should show blank page or WordPress error (not plugin code)

---

## Performance Testing

### 11. Load & Performance

**API Response Time:**
- [ ] Monitor Bold Metrics API response time (should be < 15 seconds)
- [ ] Test with slow network conditions
- [ ] Verify timeout handling works correctly

**Database Performance:**
- [ ] Create 100+ BM Result posts
- [ ] Check admin page load time
- [ ] Verify queries are efficient (use Query Monitor plugin)

---

## Debugging & Troubleshooting

### Common Issues

**1. Webhook Returns 404**
- Solution: Flush permalinks (Settings > Permalinks > Save)
- Or run: `wp rewrite flush` via WP-CLI

**2. CSS Not Loading**
- Check file path: `assets/css/bm-style.css` exists
- Check browser console for 404 errors
- Verify `plugin_dir_url(__FILE__)` resolves correctly

**3. API Calls Failing**
- Enable debug mode in `wp-config.php`:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  ```
- Check `/wp-content/debug.log` for errors
- Verify credentials are correct
- Test API endpoint directly with curl

**4. Shortcode Not Displaying**
- Verify post ID exists and is type `bm_result`
- Check post meta contains `bm_response` data
- Enable debug mode and check for errors

---

## Test Data Examples

### Valid Webhook Payload (All Fields)
```json
{
  "weight": 150,
  "height": 68,
  "age": 30,
  "waist_circum_preferred": 32,
  "bra_size": "",
  "desired_brand": "Levi",
  "desired_garment_type": "jeans",
  "product_id": "PROD-123",
  "anon_id": "user-456"
}
```

### Minimal Valid Payload (Waist)
```json
{
  "weight": 150,
  "height": 68,
  "age": 30,
  "waist_circum_preferred": 32
}
```

### Minimal Valid Payload (Bra Size)
```json
{
  "weight": 130,
  "height": 65,
  "age": 28,
  "bra_size": "34C"
}
```

### Expected Bold Metrics Response
```json
{
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
    "chest_circum": 38.12
  },
  "size_recommendations": {
    "good_matches": [
      {
        "garment": {
          "brand": "farah",
          "size": "m"
        },
        "fit_score": {
          "chest": 0.01,
          "garment": 0.01
        }
      }
    ]
  }
}
```

---

## Testing Checklist Summary

### Pre-Deployment
- [ ] All automated tests pass
- [ ] Webhook endpoint responds correctly
- [ ] Settings page works and saves data
- [ ] Shortcode displays results properly
- [ ] CSS loads and applies correctly
- [ ] Security validations pass
- [ ] Error handling works for edge cases

### Post-Deployment
- [ ] Test with real JotForm submission
- [ ] Verify Bold Metrics API integration
- [ ] Monitor error logs for issues
- [ ] Test on production server (not just local)
- [ ] Verify HTTPS works (if applicable)
- [ ] Check permalink structure on live site

---

## Continuous Testing

**Recommended practices:**
1. Test webhook after any code changes
2. Keep test credentials separate from production
3. Monitor WordPress debug log regularly
4. Test with different WordPress versions if possible
5. Test with different PHP versions (7.4+)
6. Use a staging environment before production deployment

---

## Reporting Issues

When reporting issues, include:
1. WordPress version
2. PHP version
3. Plugin version
4. Error messages from debug log
5. Steps to reproduce
6. Expected vs actual behavior

---

**Last Updated:** 2025-12-18
**Plugin Version:** 0.1.0
