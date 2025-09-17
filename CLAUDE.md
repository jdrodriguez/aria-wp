# CLAUDE.md - Aria WordPress Plugin Project Guide

## Project Overview
**Plugin Name:** Aria - Your Website's Voice  
**Version:** 1.0.0  
**Description:** AI-powered conversational assistant WordPress plugin  
**Architecture:** React-based admin interface with WordPress backend  
**Target:** ThemeForest commercial release  

## Critical Project Decisions

### ✅ CONFIRMED: React-Only Admin Interface
**Decision**: All admin pages will use React components with @wordpress/components
**Rationale**: Consistent UI/UX, better maintainability, modern WordPress standards
**Status**: 4/8 pages migrated, 4 remaining

### ✅ CONFIRMED: File Structure Standards
```
├── admin/
│   ├── class-aria-admin.php           # Admin orchestrator
│   └── partials/
│       ├── *-react.php                # KEEP - React page templates
│       ├── *.php                      # REMOVE - Legacy PHP templates
│       └── components/                # WordPress layout components
├── includes/                          # PHP classes (well-organized, keep as-is)
├── src/
│   ├── js/
│   │   ├── admin-react.jsx            # React admin components
│   │   ├── admin.js                   # Legacy admin JS (REMOVE after migration)
│   │   └── admin/                     # React component library
│   └── scss/                          # Source styles
├── dist/                              # Compiled output (WordPress loads from here)
├── public/                            # REMOVE - Legacy static files
└── dev-tools/                         # Debug/test files (move here)
```

## React Admin Architecture

### WordPress Components Integration
```javascript
import { Panel, PanelBody, Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
```

### Page Template Pattern
```php
// admin/partials/aria-[page]-react.php
<div class="wrap">
    <div id="aria-[page]-root"></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const { render } = wp.element;
        const { [Component] } = AriaAdminComponents;
        render(
            wp.element.createElement([Component]),
            document.getElementById('aria-[page]-root')
        );
    });
</script>
```

### React Component Structure
```javascript
// src/js/admin/pages/[Page].jsx
import { Panel, PanelBody } from '@wordpress/components';
import { PageHeader, MetricCard } from '../components';

export const [Page] = () => {
    return (
        <div className="aria-page">
            <PageHeader title="Page Title" />
            <div className="aria-metrics-grid">
                <Panel>
                    <PanelBody title="Section">
                        {/* Content */}
                    </PanelBody>
                </Panel>
            </div>
        </div>
    );
};
```

## Build System Requirements

### Webpack Configuration
- **Primary**: `webpack.config.js` (React + SCSS compilation)
- **Remove**: `webpack.config.admin.js` (legacy)

### NPM Scripts
```json
{
  "dev": "webpack --mode development --watch",
  "build": "webpack --mode production",
  "clean": "rm -rf dist/*"
}
```

### File Loading Priority
1. WordPress loads ONLY from `/dist/` folder
2. Source files in `/src/` compiled to `/dist/`
3. NO direct loading from `/admin/js/` or `/public/js/`

## Development Guidelines

### React Page Migration Checklist
For each remaining PHP page:
- [ ] Create React component in `src/js/admin/pages/[Page].jsx`
- [ ] Create React template in `admin/partials/aria-[page]-react.php`
- [ ] Update admin class method to load React template
- [ ] Test functionality matches original
- [ ] Remove legacy PHP template
- [ ] Update documentation

### CSS/SCSS Standards
```scss
// Page-specific wrapper
.aria-[page-name] {
    .aria-metrics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 30px;
        
        @media (min-width: 1024px) {
            grid-template-columns: repeat(2, 1fr);
        }
    }
}
```

### File Naming Conventions
- **React Components**: PascalCase (`MetricCard.jsx`)
- **PHP Templates**: `aria-[page]-react.php`  
- **SCSS Files**: `_[component].scss`
- **Compiled Files**: `admin-react.js`, `admin-style.css`

## WordPress Integration

### Enqueue Priority
```php
// class-aria-admin.php enqueue_scripts()
wp_enqueue_script(
    'aria-admin-react',
    ARIA_PLUGIN_URL . 'dist/admin-react.js',  // ONLY from dist/
    array( 'wp-element', 'wp-components', 'wp-i18n' ),
    $this->version,
    true
);
```

### AJAX Integration
- Use existing `class-aria-ajax-handler.php`
- All AJAX actions prefixed with `aria_`
- Nonce verification: `aria_admin_nonce`

### Database Integration
- Use existing `class-aria-database.php`
- All tables prefixed with `wp_aria_`
- Prepared statements only

## Cleanup Protocols

### Files to REMOVE Immediately
```
admin/js/admin.js           # Legacy admin JavaScript
public/js/chat.js           # Legacy chat JavaScript  
public/css/chat-style.css   # Duplicate CSS
src/scss/admin-old.scss     # Legacy SCSS
*.backup.*                  # Backup files
debug-*                     # Debug files (move to dev-tools/)
test-*                      # Test files (move to dev-tools/)
dashboard-*.png             # Screenshots (move to docs/)
```

### PHP Templates to Remove (after React migration)
```
admin/partials/aria-dashboard.php
admin/partials/aria-design.php  
admin/partials/aria-personality.php
admin/partials/aria-settings.php
admin/partials/aria-ai-config.php
admin/partials/aria-conversations.php
admin/partials/aria-knowledge.php
admin/partials/aria-content-indexing.php
```

### Files to KEEP
```
admin/class-aria-admin.php              # Admin orchestrator
admin/partials/*-react.php              # React templates
admin/partials/components/              # Layout components
includes/class-aria-*.php               # All PHP classes
src/js/admin-react.jsx                  # React entry point
src/js/admin/                           # React components
src/js/chat.js                          # Chat widget (separate system)
src/scss/                               # Source styles
dist/                                   # Compiled output
```

## Testing Requirements

### Before Any Changes
1. **Backup database and files**
2. **Test current functionality works**
3. **Document current admin page behavior**

### After React Migration
1. **Test all admin pages load correctly**
2. **Verify AJAX functionality works**
3. **Check responsive design**
4. **Validate WordPress components render properly**
5. **Test with different user permissions**

### Build Verification
```bash
npm run build
# Verify files exist with recent timestamps:
ls -la dist/admin-react.js
ls -la dist/admin-style.css
```

## Deployment Standards

### Production Requirements
- All files compiled to `/dist/`
- No debug/test files in production
- WordPress loads only production-ready assets
- All React components use WordPress design system
- Consistent error handling and user feedback

### Version Control
- Source files in `/src/` tracked in git
- Compiled files in `/dist/` can be gitignored or tracked
- No legacy files committed
- Clean directory structure

## Migration Priority Order

### Phase 1: Infrastructure (CRITICAL)
1. Remove duplicate JavaScript files
2. Clean up debug/test files  
3. Verify build system works correctly
4. Ensure WordPress loads from `/dist/` only

### Phase 2: React Migration (HIGH)
1. Migrate AI Config page to React
2. Migrate Conversations page to React
3. Migrate Knowledge Base page to React
4. Migrate Content Indexing page to React

### Phase 3: Cleanup (MEDIUM)
1. Remove legacy PHP templates
2. Remove legacy JavaScript files
3. Consolidate documentation
4. Organize assets properly

### Phase 4: Polish (LOW)
1. Optimize build pipeline
2. Add TypeScript support if needed
3. Improve component reusability
4. Performance optimization

## Playwright Visual Testing

### IMPORTANT: Existing Playwright Infrastructure
The project already has a comprehensive Playwright testing setup. **DO NOT create new playwright scripts from scratch.**

### Existing Files
- **Config**: `playwright.config.js` - Configured for localhost:8080
- **Tests Directory**: `tests/visual/` - Contains all visual tests
- **Screenshots**: Saved to `screenshots/` directory
- **Auth**: Username: `admin`, Password: `admin123` (NOT just 'admin'!)

### Running Visual Tests
```bash
# Run all visual tests
npx playwright test

# Run specific test
npx playwright test tests/visual/[test-name].spec.js

# With specific reporter
npx playwright test --reporter=list
```

### Available Test Scripts
- `tests/visual/comprehensive-admin-review.spec.js` - Reviews all admin pages
- `tests/visual/dashboard-professional-review.spec.js` - Dashboard analysis
- `tests/visual/personality-page-review.spec.js` - Personality page analysis
- `tests/visual/ai-config-visual-check.spec.js` - AI Config page comparison
- `capture-dashboard.js` - Simple dashboard capture script

### Creating New Visual Tests
```javascript
// Use existing pattern from tests/visual/
const { test, expect } = require('@playwright/test');

// Login helper (password is admin123!)
async function login(page) {
    await page.goto('/wp-login.php');
    if (page.url().includes('wp-admin')) return;
    
    const loginForm = await page.$('#loginform');
    if (loginForm) {
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin123'); // Critical: NOT 'admin'!
        await page.click('#wp-submit');
        await page.waitForNavigation({ waitUntil: 'networkidle' });
    }
}

test('Your test', async ({ page }) => {
    await login(page);
    // Your test code
});
```

### Visual Comparison Workflow
1. Take screenshots of reference page (e.g., Dashboard)
2. Take screenshots of target page (e.g., AI Config)
3. Compare visual elements and CSS classes
4. Log findings to console for analysis

## Emergency Procedures

### If Build Fails
```bash
# Restore from backup
npm install
rm -rf node_modules/
npm install
npm run build
```

### If WordPress Breaks
- Deactivate plugin
- Restore from file backup
- Check error logs in `/wp-content/debug.log`
- Verify file permissions

### If Admin Pages Don't Load
1. Check browser console for JavaScript errors
2. Verify React components are properly exported
3. Check WordPress enqueue functions
4. Ensure nonce and AJAX setup correct

## Key Contacts & Resources

- **WordPress Components Docs**: https://developer.wordpress.org/block-editor/reference-guides/components/
- **React Integration**: https://developer.wordpress.org/block-editor/how-to-guides/javascript/
- **Build Tools**: Webpack + Babel configuration for WordPress

---

**Last Updated**: July 24, 2025  
**Next Review**: After React migration completion  
**Status**: Active development - React migration in progress