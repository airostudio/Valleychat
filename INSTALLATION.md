# Installation Guide - Valley Virtual Assistant

## Pre-Installation Checklist

Before installing Valley Virtual Assistant, ensure you have:

- [ ] WordPress 6.0 or higher installed
- [ ] WooCommerce 7.0 or higher activated
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.6 or higher
- [ ] An Anthropic API key (get one at https://console.anthropic.com/)
- [ ] Administrator access to WordPress
- [ ] FTP access (for manual installation)

## Installation Methods

### Method 1: WordPress Admin (Recommended)

This is the easiest method for most users.

1. **Download the Plugin**
   - Download `valley-virtual-assistant.zip`
   - Save it to your computer

2. **Upload to WordPress**
   - Log in to WordPress Admin
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin** button
   - Click **Choose File** and select the zip file
   - Click **Install Now**

3. **Activate the Plugin**
   - After installation, click **Activate Plugin**
   - You'll be redirected to the WordPress plugins page

4. **Verify Installation**
   - Look for "Virtual Assistant" in the WordPress admin menu
   - You should see a success message

### Method 2: FTP Upload

For users comfortable with FTP.

1. **Extract the Plugin**
   - Unzip `valley-virtual-assistant.zip` on your computer
   - You should have a folder named `valley-virtual-assistant`

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `valley-virtual-assistant` folder
   - Wait for upload to complete

3. **Activate in WordPress**
   - Log in to WordPress Admin
   - Go to **Plugins → Installed Plugins**
   - Find "Valley Virtual Assistant"
   - Click **Activate**

### Method 3: WP-CLI

For advanced users and developers.

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Install the plugin
wp plugin install valley-virtual-assistant.zip

# Activate the plugin
wp plugin activate valley-virtual-assistant

# Verify installation
wp plugin list | grep valley-virtual-assistant
```

## Post-Installation Setup

### Step 1: Database Tables

The plugin automatically creates the following database tables on activation:

- `wp_vva_conversations`
- `wp_vva_messages`
- `wp_vva_recommendations`
- `wp_vva_analytics`

**Verify Tables Were Created:**

```sql
SHOW TABLES LIKE 'wp_vva_%';
```

If tables are missing:
1. Deactivate the plugin
2. Re-activate the plugin
3. Check database user permissions

### Step 2: Configure API Key

1. **Get Your Anthropic API Key**
   - Visit https://console.anthropic.com/
   - Sign up or log in
   - Go to API Keys section
   - Click "Create Key"
   - Copy the key (starts with "sk-ant-")

2. **Add API Key to Plugin**
   - In WordPress Admin, go to **Virtual Assistant → Settings**
   - Click the **AI Configuration** tab
   - Paste your API key in the "Anthropic API Key" field
   - Click **Save Changes**

3. **Test Connection**
   - Click the **Test Connection** button
   - You should see "✓ API connection successful!"
   - If you see an error, verify your API key

### Step 3: Basic Configuration

1. **General Settings**
   - Go to **Virtual Assistant → Settings → General**
   - Set Assistant Name: e.g., "Valley"
   - Choose Personality: "Friendly & Professional"
   - Customize Welcome Message
   - Click **Save Changes**

2. **Widget Settings**
   - Go to the **Widget** tab
   - Check "Enable Widget"
   - Choose Position: "Bottom Right"
   - Set Primary Color: #e91e63 (or your brand color)
   - Click **Save Changes**

3. **Privacy Settings**
   - Go to the **Privacy** tab
   - Set Data Retention: 90 days (recommended)
   - Check "Data Encryption"
   - Check "Conversation Logging"
   - Click **Save Changes**

### Step 4: Test the Installation

1. **Frontend Test**
   - Open your store in a new browser tab
   - Look for the chat widget in the bottom right
   - Click to open the chat
   - Send a test message: "Hello"
   - Verify you get a response

2. **Admin Test**
   - Go to **Virtual Assistant → Dashboard**
   - You should see your test conversation
   - Check that statistics are updating

## Troubleshooting Installation

### Plugin Won't Activate

**Possible Causes:**
- WooCommerce is not installed or activated
- PHP version is too old
- WordPress version is too old

**Solutions:**
1. Install and activate WooCommerce first
2. Check PHP version: `php -v` (should be 7.4+)
3. Update WordPress to latest version
4. Check error logs: `/wp-content/debug.log`

### Database Tables Not Created

**Solutions:**
1. Check database user permissions:
   ```sql
   SHOW GRANTS FOR 'your_db_user'@'localhost';
   ```
   User needs CREATE, INSERT, UPDATE, DELETE privileges

2. Manually create tables:
   ```bash
   wp db query < sql/create-tables.sql
   ```

3. Check for errors in debug.log

### Widget Not Appearing

**Solutions:**
1. Clear all caches (browser, WordPress, CDN)
2. Check if widget is enabled in settings
3. Verify no JavaScript errors in browser console
4. Try a different theme temporarily
5. Disable other plugins to check for conflicts

### API Connection Failed

**Solutions:**
1. Verify API key is correct (no spaces, complete)
2. Check your Anthropic account status
3. Verify you have API credits
4. Check server can make outbound HTTPS requests:
   ```bash
   curl https://api.anthropic.com/v1/messages
   ```
5. Check firewall settings

## Advanced Installation

### Custom Database Prefix

If you use a custom WordPress database prefix:

The plugin automatically uses `$wpdb->prefix`, so it will work with any prefix. No changes needed.

### Multisite Installation

To install on WordPress Multisite:

1. **Network Activate** (for all sites):
   - Go to **Network Admin → Plugins**
   - Click **Network Activate** under the plugin

2. **Individual Site Activation** (for specific sites):
   - Activate on each site individually
   - Configure settings per site

**Note:** Each site needs its own API configuration.

### Custom Installation Path

If WordPress is in a custom directory:

1. Upload plugin to: `{WordPress Directory}/wp-content/plugins/`
2. Activate as normal
3. No configuration changes needed

### Server Requirements

**Minimum Requirements:**
- PHP: 7.4+
- MySQL: 5.6+
- WordPress: 6.0+
- WooCommerce: 7.0+
- PHP Extensions: json, curl, openssl

**Recommended:**
- PHP: 8.0+
- MySQL: 8.0+
- Memory Limit: 256MB+
- Max Execution Time: 60s

**Check PHP Extensions:**
```bash
php -m | grep -E "(json|curl|openssl)"
```

## Security Considerations

### File Permissions

Set correct permissions after installation:

```bash
# Plugin directory
chmod 755 valley-virtual-assistant/

# PHP files
find valley-virtual-assistant/ -type f -name "*.php" -exec chmod 644 {} \;

# Directories
find valley-virtual-assistant/ -type d -exec chmod 755 {} \;
```

### API Key Security

**Important:** Never commit your API key to version control!

1. Store API key in database (plugin handles this)
2. Use environment variables for development:
   ```php
   define('VVA_ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY'));
   ```
3. Rotate keys regularly
4. Monitor API usage for anomalies

### HTTPS Requirement

The plugin requires HTTPS for:
- API communication (Anthropic requires HTTPS)
- Secure customer data transmission
- Cookie security

**Verify HTTPS is enabled:**
```php
if (!is_ssl()) {
    // Show warning
}
```

## Migration from Other Systems

### From Another WordPress Site

1. Export plugin settings (use export feature)
2. Install plugin on new site
3. Import settings
4. Verify API key works
5. Test conversations

### From Custom Chat Solution

1. Install Valley Virtual Assistant
2. Export conversation history (if needed)
3. Import via CSV (custom import script needed)
4. Configure to match previous setup
5. Test thoroughly before switching

## Uninstallation

### Clean Uninstall

To completely remove the plugin:

1. **Deactivate Plugin**
   - Go to **Plugins → Installed Plugins**
   - Click **Deactivate** under Valley Virtual Assistant

2. **Delete Plugin**
   - Click **Delete**
   - Confirm deletion

3. **Remove Database Tables** (optional)
   ```sql
   DROP TABLE IF EXISTS wp_vva_conversations;
   DROP TABLE IF EXISTS wp_vva_messages;
   DROP TABLE IF EXISTS wp_vva_recommendations;
   DROP TABLE IF EXISTS wp_vva_analytics;
   ```

4. **Remove Options** (optional)
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'vva_%';
   ```

**Note:** Deleting tables and options is permanent and cannot be undone!

### Keep Data for Reinstall

If you want to keep conversation data:

1. Deactivate plugin (don't delete)
2. Database tables remain intact
3. Reactivate later to restore functionality

## Next Steps

After successful installation:

1. **Customize Settings**: Tailor the assistant to your brand
2. **Test Thoroughly**: Try different scenarios
3. **Train Staff**: Ensure team understands capabilities
4. **Monitor Analytics**: Track performance
5. **Gather Feedback**: Get customer input
6. **Iterate**: Continuously improve based on usage

## Support

If you need help with installation:

- **Documentation**: See README.md
- **Email**: support@valleyofthedolls.com.au
- **GitHub**: Open an issue
- **WooCommerce Forums**: Community support

---

**Installation Questions?** Contact our support team!
