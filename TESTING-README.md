# Aria Plugin Testing Guide

## Quick Start

### 1. Start the Testing Environment

```bash
# Make sure Docker is running, then:
./setup-test-env.sh
```

This will:
- Start WordPress on http://localhost:8080
- Start phpMyAdmin on http://localhost:8081
- Mount the plugin in the WordPress plugins directory

### 2. Complete WordPress Setup

1. Visit http://localhost:8080
2. Complete the WordPress installation:
   - Site Title: Aria Test Site
   - Username: admin
   - Password: password (or choose your own)
   - Email: admin@example.com

### 3. Activate and Configure Aria

1. Log in to WordPress admin
2. Go to Plugins → Installed Plugins
3. Activate "Aria - Your Website's Voice"
4. Go to Aria → AI Configuration
5. Add your OpenAI or Gemini API key
6. Test the connection

### 4. Test the Plugin

Follow the [TESTING-CHECKLIST.md](TESTING-CHECKLIST.md) for comprehensive testing.

## Testing Commands

### Docker Commands

```bash
# Start environment
docker-compose up -d

# Stop environment
docker-compose down

# View logs
docker-compose logs -f wordpress

# Restart containers
docker-compose restart

# Remove everything (including data)
docker-compose down -v
```

### WordPress CLI (if needed)

```bash
# Access WordPress container
docker exec -it aria-wordpress bash

# Inside container, you can use WordPress CLI commands:
wp plugin list
wp plugin activate aria
wp option get aria_settings
```

### Database Access

```bash
# Access MySQL
docker exec -it aria-mysql mysql -u wordpress -pwordpress wordpress

# Or use phpMyAdmin at http://localhost:8081
```

## Running PHPUnit Tests

### Setup PHPUnit (One-time setup)

```bash
# Install PHPUnit if not already installed
composer install

# Run tests
vendor/bin/phpunit
```

### Run Specific Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/test-aria-core.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

## JavaScript Testing

```bash
# Run Jest tests
npm test

# Run tests in watch mode
npm run test:watch

# Run with coverage
npm run test:coverage
```

## Manual Testing Tips

### 1. Test Different Scenarios

- **New User**: Clear browser data and test as a first-time visitor
- **Returning User**: Test session persistence
- **Mobile**: Use browser dev tools to test responsive design
- **Slow Network**: Use Chrome DevTools Network throttling

### 2. Test with Different Themes

The Docker environment allows you to test with any WordPress theme:
1. Go to Appearance → Themes
2. Install and activate different themes
3. Test the chat widget appearance and functionality

### 3. Test with Popular Plugins

Install these plugins to test compatibility:
- WooCommerce
- Elementor
- Contact Form 7
- Yoast SEO

### 4. Debug Mode

Enable WordPress debug mode for detailed error reporting:

```php
// In wp-config.php (inside container)
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
```

## Common Issues

### Port Already in Use

If port 8080 or 8081 is already in use:

```yaml
# Edit docker-compose.yml and change ports:
ports:
  - "8082:80"  # Change 8080 to 8082
```

### Plugin Not Showing

If the plugin doesn't appear in WordPress:
1. Check that Docker mounted the volume correctly
2. Verify the plugin files are in the correct location
3. Check file permissions

### Database Connection Issues

If you see database connection errors:
1. Wait a few more seconds for MySQL to start
2. Check Docker logs: `docker-compose logs db`
3. Ensure the database container is running

## Debugging

### View PHP Errors

```bash
# Watch WordPress debug log
docker exec -it aria-wordpress tail -f /var/www/html/wp-content/debug.log
```

### View JavaScript Console

1. Open browser DevTools (F12)
2. Check Console tab for errors
3. Check Network tab for failed requests

### Check AJAX Requests

1. Open Network tab in DevTools
2. Filter by XHR
3. Look for admin-ajax.php requests
4. Check request/response data

## Performance Testing

### Use Query Monitor Plugin

1. Install Query Monitor plugin
2. Activate it
3. Check the admin bar for performance data

### Browser Performance

1. Open Chrome DevTools
2. Go to Performance tab
3. Record while using the chat widget
4. Analyze the results

## Security Testing

### Basic Security Checks

1. Try XSS in chat input: `<script>alert('XSS')</script>`
2. Try SQL injection in search fields
3. Check if API keys are exposed in frontend
4. Verify nonces in AJAX requests

## Accessibility Testing

### Screen Reader Testing

1. Enable screen reader (NVDA, JAWS, or VoiceOver)
2. Navigate the chat widget with keyboard only
3. Verify all interactive elements are announced

### Keyboard Navigation

1. Tab through all elements
2. Ensure focus indicators are visible
3. Test Enter/Space key activation
4. Test Escape key to close widget

---

For any issues or questions, refer to the main documentation or create an issue in the repository.