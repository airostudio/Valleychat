# Valley Virtual Assistant

**An AI-powered virtual assistant WordPress plugin for WooCommerce stores, specifically designed for adult toy and doll retailers.**

## Overview

Valley Virtual Assistant is a sophisticated WordPress plugin that integrates Anthropic's Claude AI to provide intelligent, discreet customer service for adult product retailers. The plugin offers seamless WooCommerce integration, allowing the AI assistant to access product information, order details, and customer data to provide personalized assistance.

## Features

### 🤖 AI-Powered Conversations
- Powered by Anthropic's Claude 3.5 Sonnet for natural, intelligent conversations
- Friendly, professional, and non-judgmental personality
- Context-aware responses based on customer browsing and purchase history

### 🛍️ WooCommerce Integration
- Full access to product catalog with intelligent search
- Order tracking and status updates
- Customer account information (secure)
- Shopping cart awareness
- Product recommendations based on browsing history

### 🔒 Privacy & Discretion
- Encrypted conversation storage
- Configurable data retention policies
- GDPR-compliant data export and deletion
- Discreet language and tone
- Privacy-first design

### 💬 Chat Widget
- Beautiful, responsive chat interface
- Customizable colors and branding
- Typing indicators and smooth animations
- Mobile-optimized design
- Configurable widget position

### 📊 Analytics & Insights
- Conversation volume tracking
- Message analytics
- Customer engagement metrics
- Event tracking
- Performance monitoring

### ⚙️ Easy Configuration
- Intuitive admin interface
- Simple API key setup
- Customizable assistant personality
- Feature toggles
- Comprehensive settings

## Requirements

- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- PHP 7.4 or higher
- Anthropic API key (Claude)
- MySQL 5.6 or higher

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the downloaded zip file
5. Click "Install Now"
6. Activate the plugin

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `valley-virtual-assistant` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### Method 3: FTP Upload

1. Extract the plugin zip file
2. Using FTP, upload the `valley-virtual-assistant` folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress admin

## Quick Start Guide

### Step 1: Get Your API Key

1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Sign up or log in to your account
3. Navigate to API Keys section
4. Generate a new API key
5. Copy the key (you'll need it in the next step)

### Step 2: Configure the Plugin

1. Go to WordPress Admin → Virtual Assistant → Settings
2. Navigate to the "AI Configuration" tab
3. Paste your Anthropic API key
4. Select your preferred Claude model (Claude 3.5 Sonnet recommended)
5. Click "Test Connection" to verify
6. Save settings

### Step 3: Customize Your Assistant

1. Go to the "General" settings tab
2. Set your assistant's name (e.g., "Valley", "Sophie", "Emma")
3. Customize the welcome message
4. Upload an avatar image (optional)
5. Choose a personality style
6. Save settings

### Step 4: Configure the Widget

1. Navigate to the "Widget" tab
2. Enable the chat widget
3. Choose widget position (bottom-right or bottom-left)
4. Customize colors to match your brand
5. Save settings

### Step 5: Test Your Assistant

1. Visit your store's frontend
2. Look for the chat widget in the bottom corner
3. Click to open the chat
4. Send a test message
5. Verify the assistant responds correctly

## Configuration Guide

### General Settings

**Assistant Name**
- The name displayed to customers
- Recommended: Use a friendly, approachable name
- Example: "Valley", "Sophie", "Emma"

**Personality**
- Friendly & Professional: Balanced approach (recommended)
- Warm & Supportive: More empathetic tone
- Efficient & Helpful: Direct and to-the-point

**Welcome Message**
- First message customers see
- Should be welcoming and set expectations
- Example: "Hi! I'm here to help you find the perfect products in complete discretion. How can I assist you today?"

**Avatar**
- Image representing your assistant
- Recommended size: 200x200 pixels
- Formats: JPG, PNG
- Keep it professional and on-brand

### AI Configuration

**API Provider**
- Currently supports Anthropic (Claude)
- Future versions may support additional providers

**API Key**
- Required for the plugin to function
- Keep this key secure and never share it
- Regenerate if compromised

**Model Selection**
- **Claude 3.5 Sonnet** (Recommended): Best balance of speed and intelligence
- **Claude 3 Opus**: Maximum intelligence, slower responses
- **Claude 3 Haiku**: Fastest responses, lower cost

**Max Tokens**
- Controls response length
- Range: 256-8192
- Recommended: 4096
- Higher = longer responses, higher cost

**Temperature**
- Controls creativity vs. consistency
- Range: 0.0-1.0
- Recommended: 0.7
- Lower = more focused, Higher = more creative

### Widget Settings

**Enable Widget**
- Toggle to show/hide the chat widget
- Can be useful for testing or temporary disabling

**Widget Position**
- Bottom Right: Most common (recommended)
- Bottom Left: Alternative placement

**Colors**
- Primary Color: Main widget color, buttons
- Secondary Color: Accents and highlights
- Choose colors that match your brand

### Feature Settings

**Order Tracking**
- Allows assistant to check order status
- Requires customer authentication
- Includes shipping tracking information

**Product Recommendations**
- AI-powered product suggestions
- Based on browsing history and preferences
- Uses collaborative filtering

**Customer Service**
- FAQ access and general support
- Shipping and return policy information
- Privacy and security questions

**Proactive Assistance**
- Assistant can offer help proactively
- Appears based on customer behavior
- Can be disabled for less intrusive approach

### Privacy Settings

**Data Retention**
- How long to keep conversation logs
- Options: 30, 60, 90, 180, 365 days, or forever
- Recommended: 90 days
- Set to 0 to keep forever (not recommended for GDPR)

**Data Encryption**
- Encrypts sensitive conversation data
- Recommended: Always enabled
- Uses WordPress salts for encryption keys

**Conversation Logging**
- Save conversations for analysis and improvement
- Required for conversation history feature
- Can be disabled for maximum privacy

**Anonymous Mode**
- Allow guests to use the assistant
- When disabled, requires login
- Recommended: Enabled for better customer service

## Usage Guide

### Customer Usage

Customers can interact with the virtual assistant by:

1. **Opening the Chat**: Click the chat icon in the corner
2. **Asking Questions**: Type naturally, as if talking to a person
3. **Getting Product Help**: Ask about products, categories, features
4. **Tracking Orders**: Request order status (requires login)
5. **Getting Support**: Ask about shipping, returns, privacy

### Example Customer Interactions

**Product Discovery**
```
Customer: "I'm looking for beginner-friendly products"
Assistant: "I'd be happy to help you find beginner-friendly options!
           Could you tell me a bit more about what you're interested in?"
```

**Order Tracking**
```
Customer: "Where is my order?"
Assistant: "I can help you track your order! I can see you have order #12345
           which is currently being prepared for shipment. Would you like
           more details?"
```

**Product Recommendations**
```
Customer: "What's popular right now?"
Assistant: "Based on current trends, here are some of our most popular items..."
```

### Admin Usage

**Dashboard**
- View conversation statistics
- Monitor active conversations
- Check system status
- Quick access to all features

**Conversations**
- View all past conversations
- Read full conversation transcripts
- Filter by date, customer, status
- Export conversation data

**Analytics**
- Track conversation volume
- Monitor engagement metrics
- View top events
- Identify trends

**Settings**
- Configure all plugin options
- Test API connection
- Manage privacy settings
- Customize appearance

## WooCommerce Integration

### Product Information

The assistant has access to:
- Product names and descriptions
- Prices and sale information
- Stock status
- Categories and tags
- Product attributes
- Images and galleries
- Reviews and ratings

### Order Management

For logged-in customers, the assistant can:
- View order history
- Check order status
- Provide tracking information
- Explain shipping details
- Handle return inquiries

### Customer Data

The assistant securely accesses:
- Customer name and email
- Order history
- Browsing history (session-based)
- Shopping cart contents
- Saved addresses (for context only)

**Note**: All customer data access is logged and secured. The assistant never stores sensitive payment information.

## Privacy & Security

### Data Protection

1. **Encryption**: All sensitive data is encrypted at rest
2. **Access Control**: User authentication required for personal data
3. **Secure Storage**: Conversations stored in WordPress database
4. **API Security**: API keys stored securely, never exposed to frontend

### GDPR Compliance

The plugin is designed with GDPR in mind:

1. **Data Export**: Customers can request conversation data export
2. **Right to Deletion**: Conversations can be deleted on request
3. **Consent**: Clear privacy notices in the chat widget
4. **Data Minimization**: Only necessary data is collected
5. **Retention Limits**: Configurable automatic data deletion

### Customer Privacy

1. **Discreet Language**: Assistant uses appropriate, non-judgmental language
2. **Secure Conversations**: All chats are encrypted
3. **No Data Sharing**: Customer data never shared with third parties
4. **Anonymous Options**: Customers can chat anonymously
5. **Privacy Notices**: Clear information about data usage

## Troubleshooting

### Common Issues

**Widget Not Appearing**
- Check if widget is enabled in settings
- Verify JavaScript is not blocked
- Check for theme conflicts
- Clear cache

**Assistant Not Responding**
- Verify API key is correct
- Test API connection in settings
- Check API usage limits
- Review error logs

**Slow Responses**
- Check internet connection
- Verify API is not rate-limited
- Consider using Claude 3 Haiku for speed
- Check server resources

**Database Errors**
- Verify database permissions
- Check table creation on activation
- Re-activate the plugin
- Contact support

### Debug Mode

Enable debug mode for detailed logging:

1. Go to Settings → Advanced
2. Enable "Debug Mode"
3. Check WordPress debug.log for errors
4. Disable after troubleshooting

### Getting Help

If you encounter issues:

1. Check this documentation
2. Review error messages
3. Test with default theme
4. Disable other plugins to check for conflicts
5. Contact support with error details

## API Usage & Costs

### Anthropic Pricing

The plugin uses the Anthropic API, which has usage-based pricing:

- **Claude 3.5 Sonnet**: ~$3 per million input tokens, ~$15 per million output tokens
- **Claude 3 Opus**: ~$15 per million input tokens, ~$75 per million output tokens
- **Claude 3 Haiku**: ~$0.25 per million input tokens, ~$1.25 per million output tokens

### Estimated Costs

For a typical adult toy store:
- Average conversation: 10-20 messages
- Estimated cost per conversation: $0.01-0.05
- 100 conversations/month: ~$1-5/month
- 1000 conversations/month: ~$10-50/month

**Note**: Actual costs depend on conversation length and model choice.

### Cost Optimization

1. Use Claude 3 Haiku for price-sensitive applications
2. Set appropriate max_tokens limits
3. Monitor usage in Anthropic Console
4. Implement conversation limits if needed

## Performance Optimization

### Caching

The plugin implements caching for:
- Product information
- Category data
- FAQ content
- System settings

### Database Optimization

- Indexed conversation and message tables
- Automatic cleanup of old data
- Efficient query structure
- Minimal database calls

### Frontend Performance

- Lazy-load chat widget
- Minified CSS and JavaScript
- Async API calls
- Optimized animations

## Roadmap

### Planned Features

- [ ] Multi-language support
- [ ] Voice input/output
- [ ] Proactive chat triggers
- [ ] Advanced analytics dashboard
- [ ] Integration with more AI providers
- [ ] WhatsApp/SMS integration
- [ ] Advanced product filtering
- [ ] Sentiment analysis
- [ ] Admin chat takeover
- [ ] Chatbot training interface

### Future Integrations

- Email marketing platforms
- CRM systems
- Help desk software
- Analytics platforms
- Social media

## Support

### Documentation

- Full documentation: [Link to docs]
- Video tutorials: [Link to videos]
- FAQs: [Link to FAQs]

### Contact

- Email: support@valleyofthedolls.com.au
- Website: https://valleyofthedolls.com.au
- GitHub: [Repository link]

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

**Developed by**: Valley of the Dolls
**AI Provider**: Anthropic (Claude)
**Built for**: WordPress & WooCommerce

## Changelog

### Version 1.0.0
- Initial release
- Claude 3.5 Sonnet integration
- WooCommerce integration
- Chat widget interface
- Admin dashboard
- Analytics
- Privacy features
- GDPR compliance

---

**Made with ❤️ for Valley of the Dolls**
