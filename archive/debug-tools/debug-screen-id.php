<?php
/**
 * Debug WordPress Admin Screen ID for ARIA Pages
 * Add this to aria dashboard page to see actual screen ID
 */

// Add this as a WordPress admin page action
add_action('admin_footer', function() {
    $screen = get_current_screen();
    echo "<script>
        console.log('=== ARIA SCREEN ID DEBUG ===');
        console.log('Current screen ID: " . $screen->id . "');
        console.log('Screen base: " . $screen->base . "');
        console.log('Screen post_type: " . $screen->post_type . "');
        console.log('Contains aria: " . (strpos($screen->id, 'aria') !== false ? 'YES' : 'NO') . "');
        console.log('=== END SCREEN DEBUG ===');
    </script>";
});

/**
 * Alternative: Check screen ID from WordPress admin
 * Add this to functions.php temporarily or run via plugin
 */
function debug_aria_screen_ids() {
    if (!is_admin()) return;
    
    $screen = get_current_screen();
    error_log("ARIA Screen Debug - ID: {$screen->id}, Base: {$screen->base}, Contains 'aria': " . (strpos($screen->id, 'aria') !== false ? 'YES' : 'NO'));
}
add_action('current_screen', 'debug_aria_screen_ids');
?>