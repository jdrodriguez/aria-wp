# Admin Page Layout Component

The standardized admin page layout component provides consistent width, spacing, and structure across all Aria admin pages.

## Benefits

- **Consistent Width**: All pages now have the same container width and spacing
- **Standardized Structure**: Uniform page header, logo placement, and content organization  
- **Responsive Design**: Built-in responsive behavior using the existing CSS grid system
- **Easy Maintenance**: Changes to layout structure only need to be made in one place

## Usage

### Basic Usage

```php
<?php
// Include the layout component at the top of your admin page
require_once ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-page-layout.php';

// Start the page
aria_admin_page_start(
    'your-page-id',
    __( 'Your Page Title', 'aria' ),
    __( 'Your page description', 'aria' )
);

// Start metrics grid (choose layout)
aria_metrics_grid_start( 'two-column' ); // Options: 'single-column', 'two-column', 'three-column', 'four-column'

// Your content here - use existing aria-metric-card structure
?>
<div class="aria-metric-card">
    <div class="metric-header">
        <span class="metric-icon dashicons dashicons-admin-settings"></span>
        <h3><?php esc_html_e( 'Card Title', 'aria' ); ?></h3>
    </div>
    <div class="metric-content">
        <!-- Your card content -->
    </div>
</div>

<?php
// End metrics grid
aria_metrics_grid_end();

// End the page
aria_admin_page_end();
?>
```

### Helper Function for Cards

```php
<?php
// Use the helper function for consistent card creation
$card_content = '<p>Your card content here</p>';
$actions = '<button class="button">Action</button>';

aria_metric_card(
    __( 'Card Title', 'aria' ),
    'admin-settings', // Dashicon name without 'dashicons-' prefix
    $card_content,
    array(
        'actions' => $actions,
        'custom_class' => 'my-custom-card'
    )
);
?>
```

### Grid Layout Options

- **`single-column`**: Single column layout (full width cards)
- **`two-column`**: Two columns (default - works on all existing pages)
- **`three-column`**: Three columns (responsive: 2 on tablet, 1 on mobile)
- **`four-column`**: Four columns (responsive: 3 on desktop, 2 on tablet, 1 on mobile)

### Advanced Options

```php
<?php
aria_admin_page_start(
    'page-id',
    __( 'Page Title', 'aria' ),
    __( 'Page description', 'aria' ),
    array(
        'show_logo' => true,           // Show/hide logo (default: true)
        'container_class' => 'custom', // Add custom CSS class to container
        'content_class' => 'special'   // Add custom CSS class to content area
    )
);

aria_metrics_grid_start( 'three-column', array(
    'custom_class' => 'my-grid',    // Add custom CSS class
    'margin_bottom' => false        // Remove default bottom margin
));
?>
```

## Migration

### Before (Inconsistent)
```php
<div class="wrap aria-my-page">
    <div class="aria-page-header">
        <!-- Inconsistent header structure -->
    </div>
    <div class="aria-page-content">
        <!-- Content without proper grid -->
    </div>
</div>
```

### After (Standardized)
```php
<?php
require_once ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-page-layout.php';

aria_admin_page_start( 'my-page', __( 'My Page', 'aria' ), __( 'Description', 'aria' ) );
aria_metrics_grid_start( 'two-column' );

// Your cards here

aria_metrics_grid_end();
aria_admin_page_end();
?>
```

## CSS Classes

The component uses the existing CSS classes from `admin.scss`:

- `.aria-page-header` - Page header container
- `.aria-page-info` - Page title and description
- `.aria-page-content` - Main content container  
- `.aria-metrics-grid` - Grid container for cards
- `.aria-metric-card` - Individual card containers

All existing styles and responsive behavior are preserved.