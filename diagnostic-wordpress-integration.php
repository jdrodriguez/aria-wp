<?php
/**
 * WordPress Integration Diagnostic Script for Aria Plugin
 * 
 * This script tests all the critical WordPress integration points
 * that could be causing the dashboard to show zeros.
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
    // If not in WordPress, try to load WordPress
    $wp_load_paths = [
        '../../../../wp-load.php',
        '../../../wp-load.php', 
        '../../wp-load.php',
        '../wp-load.php',
        'wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if ( ! $wp_loaded ) {
        die( "WordPress not found. Please run this script from WordPress admin or place it in your WordPress root directory." );
    }
}

// Ensure we're in admin context
if ( ! is_admin() ) {
    wp_redirect( admin_url( 'admin.php?page=aria&diagnostic=1' ) );
    exit;
}

echo "<h1>🔧 Aria WordPress Integration Diagnostic</h1>\n";
echo "<style>
.diagnostic-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.diagnostic-pass { color: green; font-weight: bold; }
.diagnostic-fail { color: red; font-weight: bold; }
.diagnostic-warning { color: orange; font-weight: bold; }
.diagnostic-info { color: blue; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>\n";

// Test 1: AJAX Action Registration
echo "<div class='diagnostic-section'>\n";
echo "<h2>1. AJAX Action Registration Test</h2>\n";

global $wp_filter;
$ajax_actions_to_check = [
    'wp_ajax_aria_get_dashboard_data',
    'wp_ajax_aria_send_message',
    'wp_ajax_aria_test_api'
];

foreach ( $ajax_actions_to_check as $action ) {
    if ( isset( $wp_filter[ $action ] ) && ! empty( $wp_filter[ $action ]->callbacks ) ) {
        echo "<span class='diagnostic-pass'>✅ $action is registered</span><br>\n";
        
        // Show callback details
        foreach ( $wp_filter[ $action ]->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( is_array( $callback['function'] ) ) {
                    $class = is_object( $callback['function'][0] ) ? get_class( $callback['function'][0] ) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "<span class='diagnostic-info'>   → Callback: {$class}::{$method} (Priority: $priority)</span><br>\n";
                } else {
                    echo "<span class='diagnostic-info'>   → Callback: {$callback['function']} (Priority: $priority)</span><br>\n";
                }
            }
        }
    } else {
        echo "<span class='diagnostic-fail'>❌ $action is NOT registered</span><br>\n";
    }
}
echo "</div>\n";

// Test 2: Screen ID Detection
echo "<div class='diagnostic-section'>\n";
echo "<h2>2. Admin Screen ID Detection Test</h2>\n";

$current_screen = get_current_screen();
echo "<span class='diagnostic-info'>Current screen ID: <strong>{$current_screen->id}</strong></span><br>\n";
echo "<span class='diagnostic-info'>Current screen base: <strong>{$current_screen->base}</strong></span><br>\n";

// Test the condition used in the plugin
if ( strpos( $current_screen->id, 'aria' ) !== false ) {
    echo "<span class='diagnostic-pass'>✅ Screen ID contains 'aria' - scripts should be enqueued</span><br>\n";
} else {
    echo "<span class='diagnostic-warning'>⚠️ Screen ID does not contain 'aria' - scripts will NOT be enqueued</span><br>\n";
}

// List all available admin pages
$aria_pages = [
    'toplevel_page_aria',
    'aria_page_aria-personality', 
    'aria_page_aria-knowledge',
    'aria_page_aria-ai-config',
    'aria_page_aria-design',
    'aria_page_aria-conversations',
    'aria_page_aria-settings'
];

echo "<span class='diagnostic-info'>Expected Aria screen IDs:</span><br>\n";
foreach ( $aria_pages as $page_id ) {
    echo "<span class='diagnostic-info'>   → $page_id</span><br>\n";
}
echo "</div>\n";

// Test 3: Script Dependencies
echo "<div class='diagnostic-section'>\n";
echo "<h2>3. WordPress Script Dependencies Test</h2>\n";

$required_scripts = [ 'wp-element', 'wp-components', 'wp-i18n', 'jquery' ];
foreach ( $required_scripts as $script ) {
    if ( wp_script_is( $script, 'registered' ) ) {
        echo "<span class='diagnostic-pass'>✅ $script is registered</span>";
        if ( wp_script_is( $script, 'enqueued' ) ) {
            echo " <span class='diagnostic-pass'>(and enqueued)</span>";
        } else {
            echo " <span class='diagnostic-warning'>(but not enqueued)</span>";
        }
        echo "<br>\n";
    } else {
        echo "<span class='diagnostic-fail'>❌ $script is NOT registered</span><br>\n";
    }
}

// Check our custom scripts
$aria_scripts = [ 'aria-admin', 'aria-admin-react' ];
foreach ( $aria_scripts as $script ) {
    if ( wp_script_is( $script, 'registered' ) ) {
        echo "<span class='diagnostic-pass'>✅ $script is registered</span>";
        if ( wp_script_is( $script, 'enqueued' ) ) {
            echo " <span class='diagnostic-pass'>(and enqueued)</span>";
        } else {
            echo " <span class='diagnostic-warning'>(but not enqueued)</span>";
        }
        echo "<br>\n";
    } else {
        echo "<span class='diagnostic-fail'>❌ $script is NOT registered</span><br>\n";
    }
}
echo "</div>\n";

// Test 4: File Existence
echo "<div class='diagnostic-section'>\n";
echo "<h2>4. Required Files Existence Test</h2>\n";

$required_files = [
    ARIA_PLUGIN_PATH . 'dist/admin-react.js',
    ARIA_PLUGIN_PATH . 'dist/admin.js',
    ARIA_PLUGIN_PATH . 'dist/admin-style.css',
    ARIA_PLUGIN_PATH . 'includes/class-aria-ajax-handler.php',
    ARIA_PLUGIN_PATH . 'admin/class-aria-admin.php'
];

foreach ( $required_files as $file ) {
    $relative_path = str_replace( ARIA_PLUGIN_PATH, '', $file );
    if ( file_exists( $file ) ) {
        echo "<span class='diagnostic-pass'>✅ $relative_path exists</span><br>\n";
    } else {
        echo "<span class='diagnostic-fail'>❌ $relative_path is missing</span><br>\n";
    }
}
echo "</div>\n";

// Test 5: Database Connection and Data
echo "<div class='diagnostic-section'>\n";
echo "<h2>5. Database Data Test</h2>\n";

global $wpdb;
$tables_to_check = [
    $wpdb->prefix . 'aria_conversations',
    $wpdb->prefix . 'aria_knowledge_base', 
    $wpdb->prefix . 'aria_personality_settings',
    $wpdb->prefix . 'aria_license'
];

foreach ( $tables_to_check as $table ) {
    $table_name = str_replace( $wpdb->prefix, '', $table );
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
    
    if ( $exists ) {
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        echo "<span class='diagnostic-pass'>✅ $table_name table exists ($count rows)</span><br>\n";
        
        // Show sample data for conversations
        if ( $table_name === 'aria_conversations' && $count > 0 ) {
            $sample = $wpdb->get_results( "SELECT id, guest_name, initial_question, created_at FROM $table ORDER BY created_at DESC LIMIT 3", ARRAY_A );
            echo "<pre>Sample conversations:\n" . print_r( $sample, true ) . "</pre>\n";
        }
    } else {
        echo "<span class='diagnostic-fail'>❌ $table_name table does not exist</span><br>\n";
    }
}
echo "</div>\n";

// Test 6: Direct AJAX Test
echo "<div class='diagnostic-section'>\n";
echo "<h2>6. Direct AJAX Endpoint Test</h2>\n";

// Simulate the AJAX request that the React component makes
if ( class_exists( 'Aria_Ajax_Handler' ) ) {
    echo "<span class='diagnostic-pass'>✅ Aria_Ajax_Handler class exists</span><br>\n";
    
    $ajax_handler = new Aria_Ajax_Handler();
    if ( method_exists( $ajax_handler, 'handle_get_dashboard_data' ) ) {
        echo "<span class='diagnostic-pass'>✅ handle_get_dashboard_data method exists</span><br>\n";
        
        // Simulate the request
        $_POST['nonce'] = wp_create_nonce( 'aria_admin_nonce' );
        $_POST['action'] = 'aria_get_dashboard_data';
        
        echo "<span class='diagnostic-info'>Testing dashboard data retrieval...</span><br>\n";
        
        // Capture output
        ob_start();
        try {
            $ajax_handler->handle_get_dashboard_data();
            $output = ob_get_clean();
            
            // Try to decode JSON response
            $json_data = json_decode( $output, true );
            if ( $json_data !== null ) {
                echo "<span class='diagnostic-pass'>✅ AJAX endpoint returns valid JSON</span><br>\n";
                echo "<pre>Response data:\n" . print_r( $json_data, true ) . "</pre>\n";
            } else {
                echo "<span class='diagnostic-fail'>❌ AJAX endpoint returned invalid JSON</span><br>\n";
                echo "<pre>Raw output: $output</pre>\n";
            }
        } catch ( Exception $e ) {
            ob_end_clean();
            echo "<span class='diagnostic-fail'>❌ AJAX endpoint threw exception: " . $e->getMessage() . "</span><br>\n";
        }
        
        // Clean up
        unset( $_POST['nonce'], $_POST['action'] );
    } else {
        echo "<span class='diagnostic-fail'>❌ handle_get_dashboard_data method does not exist</span><br>\n";
    }
} else {
    echo "<span class='diagnostic-fail'>❌ Aria_Ajax_Handler class does not exist</span><br>\n";
}
echo "</div>\n";

// Test 7: WordPress Constants and Paths
echo "<div class='diagnostic-section'>\n";
echo "<h2>7. WordPress Constants and Paths Test</h2>\n";

$constants_to_check = [
    'ARIA_VERSION',
    'ARIA_PLUGIN_PATH', 
    'ARIA_PLUGIN_URL',
    'ARIA_PLUGIN_BASENAME'
];

foreach ( $constants_to_check as $constant ) {
    if ( defined( $constant ) ) {
        echo "<span class='diagnostic-pass'>✅ $constant is defined: " . constant( $constant ) . "</span><br>\n";
    } else {
        echo "<span class='diagnostic-fail'>❌ $constant is not defined</span><br>\n";
    }
}

echo "<span class='diagnostic-info'>WordPress admin AJAX URL: " . admin_url( 'admin-ajax.php' ) . "</span><br>\n";
echo "<span class='diagnostic-info'>Current user can manage options: " . ( current_user_can( 'manage_options' ) ? 'Yes' : 'No' ) . "</span><br>\n";
echo "</div>\n";

// Test 8: JavaScript Console Test
echo "<div class='diagnostic-section'>\n";
echo "<h2>8. JavaScript Console Test</h2>\n";
echo "<p>Open your browser's developer console and check if you see the following debug messages:</p>\n";
echo "<ul>\n";
echo "<li>🔧 ARIA React script loaded successfully!</li>\n";
echo "<li>🔧 DOM ready, initializing React components...</li>\n";
echo "<li>🔧 Mounting Dashboard component...</li>\n";
echo "<li>ariaAdmin object: [object Object]</li>\n";
echo "</ul>\n";

// Add JavaScript to test admin object
echo "<script>
console.log('🔧 DIAGNOSTIC: Testing WordPress admin object...');
console.log('🔧 ariaAdmin available:', typeof window.ariaAdmin !== 'undefined');
if (typeof window.ariaAdmin !== 'undefined') {
    console.log('🔧 ariaAdmin object:', window.ariaAdmin);
    console.log('🔧 AJAX URL:', window.ariaAdmin.ajaxUrl);
    console.log('🔧 Nonce:', window.ariaAdmin.nonce);
} else {
    console.error('🔧 ariaAdmin object is not available! Script localization failed.');
}

console.log('🔧 Dashboard root element exists:', !!document.getElementById('aria-dashboard-root'));
console.log('🔧 Current page URL:', window.location.href);
console.log('🔧 Current screen contains aria:', window.location.href.indexOf('aria') !== -1);
</script>\n";
echo "</div>\n";

echo "<div class='diagnostic-section'>\n";
echo "<h2>📊 Summary</h2>\n";
echo "<p>This diagnostic has tested the core WordPress integration points. Check the results above to identify any failing components.</p>\n";
echo "<p><strong>Common issues and solutions:</strong></p>\n";
echo "<ul>\n";
echo "<li><strong>AJAX actions not registered:</strong> Check if the Aria_Core class is being instantiated properly</li>\n";
echo "<li><strong>Scripts not enqueued:</strong> Verify you're on an Aria admin page (URL contains 'aria')</li>\n";
echo "<li><strong>Missing dependencies:</strong> Ensure WordPress core scripts are available</li>\n";
echo "<li><strong>Database issues:</strong> Run plugin activation to create missing tables</li>\n";
echo "<li><strong>File missing:</strong> Run 'npm run build' to generate dist files</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Test completion
echo "<p><strong>Diagnostic completed at " . current_time( 'mysql' ) . "</strong></p>\n";