# Aria Embed Usage Guide

## Overview
Aria can be embedded directly on any page or post using the `[aria_chat]` shortcode. This provides an alternative to the floating chat widget, perfect for contact pages, support sections, or any dedicated chat area.

## Basic Usage

### Simple Embed
Add this shortcode to any page or post:
```
[aria_chat]
```

### With Custom Options
```
[aria_chat height="700px" title="Chat with Our Support Team" subtitle="We're here to help!" button_text="Send Message"]
```

## Shortcode Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `height` | `600px` | Height of the chat interface after form submission |
| `title` | Site's configured title | Main heading shown above the form |
| `subtitle` | "How can I help you today?" | Subheading text |
| `button_text` | "Start Chat" | Text for the submit button |
| `class` | (empty) | Additional CSS classes for styling |

## How It Works

1. **Initial Form View**
   - Shows a clean form with Name, Email, and Message fields
   - Maintains your configured Aria branding
   - Validates input before proceeding

2. **Chat Interface**
   - After form submission, transforms into a full chat interface
   - Aria greets the user by name
   - Immediately responds to the initial message
   - Allows continued conversation

3. **User Experience**
   - No popups or overlays
   - Seamless transition from form to chat
   - Mobile-responsive design
   - Close button returns to form view

## Examples

### Contact Page
```
<h2>Contact Us</h2>
<p>Have a question? Chat with Aria, our AI assistant!</p>
[aria_chat title="Hi! I'm Aria" subtitle="Ask me anything about our services" height="500px"]
```

### Support Section
```
[aria_chat title="Technical Support" subtitle="Get instant help with your questions" button_text="Get Help Now"]
```

### Custom Styled
```
[aria_chat class="my-custom-chat" height="800px"]
```

Then add custom CSS:
```css
.my-custom-chat {
    max-width: 800px;
    margin: 0 auto;
}
```

## Styling

The embed container uses these main CSS classes:
- `.aria-embed-container` - Main container
- `.aria-embed-form-view` - Initial form view
- `.aria-embed-chat-view` - Chat interface view

You can override styles in your theme's CSS:
```css
.aria-embed-container {
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.aria-embed-submit {
    background: #ff6b6b;
}

.aria-embed-submit:hover {
    background: #ff5252;
}
```

## Best Practices

1. **Placement**
   - Use on dedicated contact or support pages
   - Place after introductory content
   - Consider adding context about what Aria can help with

2. **Height**
   - Default 600px works well for most cases
   - Increase for pages with more vertical space
   - Consider mobile viewports

3. **Messaging**
   - Customize title/subtitle for the page context
   - Clear call-to-action in button text
   - Set expectations about response types

## Notes

- The embed uses the same AI configuration as the floating widget
- User sessions are maintained separately from the widget
- Both embed and widget can be active on the same site
- Knowledge base and personality settings apply to both

## Comparison: Embed vs Widget

| Feature | Embed | Floating Widget |
|---------|-------|-----------------|
| Placement | Fixed in page content | Floating corner button |
| Initial State | Form fields visible | Hidden until clicked |
| Best For | Contact/support pages | Site-wide availability |
| Mobile | Responsive in-page | Full-screen takeover |
| Multiple Instances | Yes, multiple per site | One per site |