# Valley Virtual Assistant - Testing Instructions

## Testing Product Images & Links

### Step 1: Enable WordPress Debug Log

Edit `wp-config.php` and add:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Step 2: Clear Browser Cache

- Press `Ctrl+Shift+Delete` (or `Cmd+Shift+Delete` on Mac)
- Select "Cached images and files"
- Click "Clear data"
- Close and reopen browser

### Step 3: Test Product Search

1. Open your site
2. Open browser console (F12 → Console tab)
3. Open the chatbot
4. Ask: "Show me butt plugs" or "Show me vibrators"

### Step 4: Check Console Output

Look for these logs in browser console:
```
VVA: Full AJAX response: {...}
VVA: Products data: [...]
VVA: Number of products: 3
VVA: Creating products container with 3 products
VVA: Creating product card for: {id: 123, name: "...", image: "..."}
VVA: Product image URL: https://yoursite.com/...
```

### Step 5: Check Server Logs

Check `/wp-content/debug.log` for:
```
VVA: Found 10 relevant products for: Show me butt plugs
VVA: Formatting 10 relevant products for AI
VVA: Sample product IDs being sent to AI: 123, 456, 789
VVA: Product 123 image ID: 456
VVA: Final image URL for product 123: https://...
```

## Expected Results

✅ **Success Indicators:**
- Console shows product data with real IDs (not 12345, 67890)
- Product cards appear stacked vertically
- Each card shows: image, name, price, "Add to Cart" button, "View Details" link
- Images load successfully
- Clicking "View Details" opens product page

❌ **Failure Indicators:**
- `VVA: WARNING - No relevant products found!` in debug.log → WooCommerce search not finding products
- `VVA: No products to display` in console → Backend not returning products
- `VVA: Failed to load image` → Image URLs are invalid
- Console shows `[PRODUCT:12345]` in AI response → AI still making up fake IDs

## Testing Add to Cart

1. Click "Add to Cart" button on a product card
2. Check console for:
```
VVA: Attempting to add product to cart: 123
VVA: Add to cart response: {success: true, ...}
```

3. Button should show "✓ Added!" temporarily
4. You should see a success message from the assistant
5. WooCommerce cart count should update

## Troubleshooting

### No Products Found
- Check if you have published products in WooCommerce
- Try a broader search term like "toy" or "product"
- Check debug.log for WooCommerce errors

### Images Not Loading
- Check if product has featured image set in WooCommerce
- Verify image URL in console is accessible (copy/paste in new tab)
- Check file permissions on uploads folder

### Add to Cart Not Working
- Check console for AJAX errors
- Verify WooCommerce is active
- Check if product is in stock and purchasable

## Share With Developer

If still not working, send:
1. Screenshot of browser console output
2. Contents of relevant lines from debug.log
3. Screenshot of product cards (with or without images)
4. What device/browser you're using
