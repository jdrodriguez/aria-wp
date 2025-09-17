# Aria - Your Website's Voice

Transform your WordPress website's contact forms into intelligent, AI-powered conversational assistants that speak with your brand's unique personality.

## Description

Aria is a premium WordPress plugin that replaces traditional contact forms with an AI-powered chat assistant. Unlike generic chatbots, Aria learns your business, adopts your brand personality, and engages visitors in natural, helpful conversations.

### Key Features

- **🎭 Personality System**: Configure Aria's tone and traits to match your brand
- **🧠 Knowledge Base**: Teach Aria about your products, services, and policies
- **🤖 AI-Powered**: Integrates with OpenAI ChatGPT and Google Gemini
- **🎨 Customizable Design**: Match your website's look and feel perfectly
- **📊 Analytics & Learning**: Aria improves over time based on conversations
- **🔒 GDPR Compliant**: Built-in privacy controls and data management
- **🌍 Multi-language**: Support for multiple languages
- **📱 Mobile Friendly**: Responsive design that works on all devices

## Installation

### Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher (PHP 8.0+ recommended)
- MySQL 5.6 or higher
- SSL certificate (for secure API communications)

### Installation Steps

1. Upload the `aria` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the Aria menu in your WordPress admin
4. Follow the setup wizard to configure your AI provider and personality

### Quick Start

1. **Configure AI Provider**
   - Go to Aria → AI Setup
   - Choose between OpenAI or Google Gemini
   - Enter your API key

2. **Set Aria's Personality**
   - Go to Aria → Personality
   - Select your business type
   - Choose conversation tone
   - Select personality traits

3. **Add Knowledge**
   - Go to Aria → Knowledge Base
   - Add information about your business
   - Import existing FAQs

4. **Customize Appearance**
   - Go to Aria → Design
   - Choose colors and position
   - Preview changes in real-time

## Usage

### Widget Display

The Aria chat widget automatically appears on your website once configured. You can control where it appears:

```php
// Show on all pages (default)
// Or configure in Settings → Display Options
```

### Shortcode

Display Aria in your content:

```
[aria_chat height="500px" class="custom-class"]
```

### Programmatic Control

```javascript
// Open chat programmatically
window.ariaChat.open();

// Close chat
window.ariaChat.close();

// Send a message
window.ariaChat.sendMessage('Hello Aria!');
```

## Configuration

### Personality Configuration

Aria can adopt different personalities based on your business:

- **Professional**: Formal, courteous, business-appropriate
- **Friendly**: Warm, approachable, conversational
- **Casual**: Relaxed, fun, uses emojis
- **Custom**: Define your own personality traits

### Knowledge Base Management

Add knowledge in multiple formats:
- **Q&A Pairs**: Common questions and answers
- **Product Information**: Detailed product descriptions
- **Policies**: Company policies and procedures
- **General Information**: About your business

### Design Customization

- **Position**: Bottom-right, bottom-left, or bottom-center
- **Colors**: Primary, secondary, and text colors
- **Size**: Adjustable chat window dimensions
- **Animations**: Enable/disable smooth transitions
- **Mobile**: Full-screen mode on mobile devices

## API Integration

### OpenAI Configuration

1. Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Choose your preferred model (GPT-3.5 or GPT-4)
3. Set response length and creativity level

### Google Gemini Configuration

1. Get your API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Select Gemini model variant
3. Configure response parameters

## Privacy & GDPR

Aria includes comprehensive privacy features:

- **Consent Management**: GDPR-compliant consent collection
- **Data Retention**: Automatic data cleanup after specified period
- **User Rights**: Export and deletion capabilities
- **IP Anonymization**: Optional IP address anonymization
- **Encryption**: All sensitive data is encrypted

## Troubleshooting

### Common Issues

**Chat widget not appearing:**
- Check if Aria is enabled in settings
- Verify API key is configured
- Ensure license is active
- Check browser console for errors

**Aria not responding:**
- Verify API key is valid
- Check API provider status
- Review rate limits
- Check error logs

**Styling issues:**
- Clear browser cache
- Check for CSS conflicts
- Verify custom CSS syntax

### Debug Mode

Enable debug mode for detailed logging:

```php
define( 'ARIA_DEBUG', true );
```

## Developer Documentation

### Hooks and Filters

**Actions:**
```php
// Before Aria sends a response
do_action( 'aria_before_response', $message, $conversation_id );

// After conversation ends
do_action( 'aria_conversation_ended', $conversation_id, $rating );
```

**Filters:**
```php
// Modify Aria's response
add_filter( 'aria_response_text', 'my_custom_response', 10, 2 );

// Customize widget configuration
add_filter( 'aria_widget_config', 'my_widget_config' );
```

### JavaScript Events

```javascript
document.addEventListener('aria:opened', function(e) {
    console.log('Aria chat opened');
});

document.addEventListener('aria:message_sent', function(e) {
    console.log('Message sent:', e.detail.message);
});
```

### REST API Endpoints

- `GET /wp-json/aria/v1/conversations` - List conversations
- `POST /wp-json/aria/v1/message` - Send message
- `GET /wp-json/aria/v1/knowledge` - Get knowledge entries

## Support

- **Documentation**: [https://ariaplugin.com/docs](https://ariaplugin.com/docs)
- **Support Forum**: [https://support.ariaplugin.com](https://support.ariaplugin.com)
- **Email**: support@ariaplugin.com

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with ❤️ by the Aria team.

### Third-party Libraries

- OpenAI PHP Client
- Google AI PHP Client
- Chart.js for analytics
- WordPress Plugin Boilerplate

## Changelog

### 1.0.0
- Initial release
- Personality system
- Knowledge base management
- OpenAI and Gemini integration
- GDPR compliance features
- Multi-language support
- Advanced analytics
- Mobile responsive design

---

**Note**: This is a premium plugin. A valid license is required for continued use after the 30-day trial period.