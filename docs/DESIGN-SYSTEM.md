# ARIA WordPress Plugin Design System

## Overview

The ARIA WordPress plugin uses a modern, professional design system that emphasizes clarity, usability, and visual consistency across all admin pages.

## Design Principles

1. **Clean & Modern** - Generous whitespace, subtle shadows, and a refined color palette
2. **Focused Functionality** - Clear visual hierarchy guides users to important actions
3. **Responsive Design** - Seamless experience across all devices
4. **Performance First** - Lightweight components with smooth animations
5. **Accessible** - WCAG compliant with proper contrast ratios and keyboard navigation
6. **Consistent** - Unified design language across all admin pages

## Color Palette

### Primary Colors
- **Electric Blue** `#0066FF` - Primary actions and links
- **Deep Navy** `#1A2842` - Main text color
- **Slate Gray** `#64748B` - Secondary text

### Semantic Colors
- **Success Green** `#10B981` - Positive states
- **Alert Orange** `#F59E0B` - Warnings
- **Error Red** `#EF4444` - Errors and critical states

### Neutrals
- **Gray Scale** - From `#F8FAFC` (lightest) to `#0F172A` (darkest)

## Typography

- **Primary Font**: Inter (with system font fallbacks)
- **Monospace Font**: JetBrains Mono
- **Font Sizes**: 12px to 48px scale
- **Line Heights**: 1.25 (tight) to 1.625 (relaxed)

## Components

### Page Structure

```php
<div class="wrap aria-[page-name]">
    <div class="aria-page-container">
        <div class="aria-page-header">
            <?php include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php'; ?>
            <div>
                <div class="aria-page-title-section">
                    <h1 class="aria-page-title">Page Title</h1>
                    <p class="aria-page-description">Page description</p>
                </div>
                <div class="aria-page-actions">
                    <!-- Action buttons -->
                </div>
            </div>
        </div>
        
        <!-- Page content -->
    </div>
</div>
```

### Buttons

```php
// Primary button
<button class="button aria-btn-primary">Primary Action</button>

// Secondary button
<button class="button aria-btn-secondary">Secondary Action</button>

// Ghost button
<button class="button aria-btn-ghost">Ghost Action</button>
```

### Cards

```php
<div class="aria-card">
    <div class="aria-card-header">
        <div class="aria-card-title">
            <span class="dashicons dashicons-[icon]"></span>
            <h3>Card Title</h3>
        </div>
        <div class="aria-card-actions">
            <!-- Card actions -->
        </div>
    </div>
    <div class="aria-card-body">
        <!-- Card content -->
    </div>
    <div class="aria-card-footer">
        <!-- Footer content -->
    </div>
</div>
```

### Metric Cards

```php
<div class="aria-metric-card">
    <div class="metric-icon">
        <span class="dashicons dashicons-[icon]"></span>
    </div>
    <div class="metric-value">1,234</div>
    <div class="metric-label">Metric Label</div>
    <div class="metric-change positive">
        <span class="dashicons dashicons-arrow-up-alt"></span>
        12% increase
    </div>
</div>
```

### Grid System

```php
// 2 columns
<div class="aria-grid aria-grid-2">
    <!-- Grid items -->
</div>

// 3 columns
<div class="aria-grid aria-grid-3">
    <!-- Grid items -->
</div>

// 4 columns
<div class="aria-grid aria-grid-4">
    <!-- Grid items -->
</div>
```

### Form Elements

```php
<div class="aria-form-group">
    <label class="aria-form-label">Field Label</label>
    <input type="text" class="aria-form-input" placeholder="Placeholder text">
    <p class="aria-form-help">Helper text for additional context</p>
</div>
```

### Badges

```php
<span class="aria-badge aria-badge-success">Success</span>
<span class="aria-badge aria-badge-warning">Warning</span>
<span class="aria-badge aria-badge-error">Error</span>
<span class="aria-badge aria-badge-info">Info</span>
```

### Empty States

```php
<div class="aria-empty-state">
    <div class="aria-empty-icon">
        <span class="dashicons dashicons-[icon]"></span>
    </div>
    <h4 class="aria-empty-title">No Data Yet</h4>
    <p class="aria-empty-text">Description of empty state</p>
    <button class="button aria-btn-primary">Primary Action</button>
</div>
```

## Spacing Scale

- `$aria-space-1`: 0.25rem (4px)
- `$aria-space-2`: 0.5rem (8px)
- `$aria-space-3`: 0.75rem (12px)
- `$aria-space-4`: 1rem (16px)
- `$aria-space-6`: 1.5rem (24px)
- `$aria-space-8`: 2rem (32px)
- `$aria-space-10`: 2.5rem (40px)
- `$aria-space-12`: 3rem (48px)
- `$aria-space-16`: 4rem (64px)

## Border Radius

- `$aria-radius-sm`: 6px
- `$aria-radius-base`: 8px
- `$aria-radius-md`: 12px
- `$aria-radius-lg`: 16px
- `$aria-radius-full`: 9999px

## Shadows

- `$aria-shadow-xs`: Subtle elevation
- `$aria-shadow-sm`: Small shadow
- `$aria-shadow-md`: Medium shadow
- `$aria-shadow-lg`: Large shadow
- `$aria-shadow-xl`: Extra large shadow

## Utility Classes

### Text Sizes
- `.aria-text-xs` - 12px
- `.aria-text-sm` - 14px
- `.aria-text-base` - 16px
- `.aria-text-lg` - 18px
- `.aria-text-xl` - 20px
- `.aria-text-2xl` - 24px

### Font Weights
- `.aria-font-normal` - 400
- `.aria-font-medium` - 500
- `.aria-font-semibold` - 600
- `.aria-font-bold` - 700

### Colors
- `.aria-text-primary` - Electric Blue
- `.aria-text-navy` - Deep Navy
- `.aria-text-gray` - Gray 600
- `.aria-text-success` - Success Green
- `.aria-text-error` - Error Red
- `.aria-text-warning` - Warning Orange

### Spacing
- `.aria-mt-[0|2|4|6|8]` - Margin top
- `.aria-mb-[0|2|4|6|8]` - Margin bottom

## SCSS Architecture

The design system is built using SCSS with the following structure:

1. **Design Tokens** - Colors, typography, spacing
2. **Mixins** - Reusable style patterns
3. **CSS Custom Properties** - Runtime theming support
4. **Global Styles** - Base resets and typography
5. **Layout Components** - Page structure
6. **Components** - Reusable UI elements
7. **Utilities** - Helper classes
8. **ARIA Specific** - Page-specific overrides
9. **WordPress Overrides** - WP admin compatibility
10. **Dark Mode** - Future dark theme support

## Best Practices

1. Always use the design system classes instead of inline styles
2. Maintain consistent spacing using the spacing scale
3. Use semantic color names (success, error) instead of color values
4. Ensure all interactive elements have proper hover and focus states
5. Test responsive behavior on all screen sizes
6. Use the grid system for layouts instead of custom CSS
7. Include proper ARIA labels for accessibility

## Browser Support

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)

## Future Enhancements

- Dark mode support
- RTL language support
- Additional component variants
- Animation library
- Advanced theming options