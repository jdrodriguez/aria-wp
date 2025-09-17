<?php
/**
 * Direct AJAX Endpoint Test for Aria Plugin
 * 
 * This script tests the AJAX endpoint directly to diagnose
 * why the dashboard is showing zeros.
 */

// Security check and WordPress loading
if ( ! defined( 'ABSPATH' ) ) {
    // Try to load WordPress
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
        die( "WordPress not found. Please run this script from WordPress admin." );
    }
}

// Only allow admin users
if ( ! current_user_can( 'manage_options' ) ) {
    die( "Access denied. Admin privileges required." );
}

echo "<h1>🔧 Aria AJAX Endpoint Direct Test</h1>\n";
echo "<style>
.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.test-pass { color: green; font-weight: bold; }
.test-fail { color: red; font-weight: bold; }
.test-warning { color: orange; font-weight: bold; }
.test-info { color: blue; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 400px; }
</style>\n";

// Test 1: Basic Setup Verification
echo "<div class='test-section'>\n";
echo "<h2>1. Basic Setup Verification</h2>\n";

echo "<span class='test-info'>Current user ID: " . get_current_user_id() . "</span><br>\n";
echo "<span class='test-info'>Can manage options: " . ( current_user_can( 'manage_options' ) ? 'Yes' : 'No' ) . "</span><br>\n";
echo "<span class='test-info'>WordPress version: " . get_bloginfo( 'version' ) . "</span><br>\n";
echo "<span class='test-info'>Aria plugin path: " . ( defined( 'ARIA_PLUGIN_PATH' ) ? ARIA_PLUGIN_PATH : 'Not defined' ) . "</span><br>\n";

if ( class_exists( 'Aria_Ajax_Handler' ) ) {
    echo "<span class='test-pass'>✅ Aria_Ajax_Handler class is available</span><br>\n";
} else {
    echo "<span class='test-fail'>❌ Aria_Ajax_Handler class is NOT available</span><br>\n";
    echo "<span class='test-info'>Attempting to load handler...</span><br>\n";
    
    if ( file_exists( ARIA_PLUGIN_PATH . 'includes/class-aria-ajax-handler.php' ) ) {
        require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ajax-handler.php';
        if ( class_exists( 'Aria_Ajax_Handler' ) ) {
            echo "<span class='test-pass'>✅ Handler loaded successfully</span><br>\n";
        } else {
            echo "<span class='test-fail'>❌ Handler file exists but class not found</span><br>\n";
        }
    } else {
        echo "<span class='test-fail'>❌ Handler file does not exist</span><br>\n";
    }
}

echo "</div>\n";

// Test 2: Database Tables and Data
echo "<div class='test-section'>\n";
echo "<h2>2. Database Tables and Data</h2>\n";

global $wpdb;

// Check conversations table
$conversations_table = $wpdb->prefix . 'aria_conversations';
$conversations_exists = $wpdb->get_var( "SHOW TABLES LIKE '$conversations_table'" ) === $conversations_table;

if ( $conversations_exists ) {
    $total_conversations = $wpdb->get_var( "SELECT COUNT(*) FROM $conversations_table" );
    echo "<span class='test-pass'>✅ Conversations table exists with $total_conversations records</span><br>\n";
    
    // Get today's conversations
    $today_start = date( 'Y-m-d 00:00:00' );
    $conversations_today = $wpdb->get_var( 
        $wpdb->prepare( "SELECT COUNT(*) FROM $conversations_table WHERE created_at >= %s", $today_start )
    );
    echo "<span class='test-info'>   → Conversations today: $conversations_today</span><br>\n";
    
    // Show recent conversations
    $recent = $wpdb->get_results( 
        "SELECT id, guest_name, initial_question, created_at, status FROM $conversations_table ORDER BY created_at DESC LIMIT 5",
        ARRAY_A 
    );
    if ( ! empty( $recent ) ) {
        echo "<span class='test-info'>   → Recent conversations:</span><br>\n";
        foreach ( $recent as $conv ) {
            echo "<span class='test-info'>     #{$conv['id']}: {$conv['guest_name']} - {$conv['initial_question']} ({$conv['created_at']})</span><br>\n";
        }
    }
} else {
    echo "<span class='test-fail'>❌ Conversations table does not exist</span><br>\n";
}

// Check knowledge base table
$knowledge_table = $wpdb->prefix . 'aria_knowledge_base';
$knowledge_exists = $wpdb->get_var( "SHOW TABLES LIKE '$knowledge_table'" ) === $knowledge_table;

if ( $knowledge_exists ) {
    $knowledge_count = $wpdb->get_var( "SELECT COUNT(*) FROM $knowledge_table" );
    echo "<span class='test-pass'>✅ Knowledge base table exists with $knowledge_count entries</span><br>\n";
} else {
    echo "<span class='test-fail'>❌ Knowledge base table does not exist</span><br>\n";
}

echo "</div>\n";

// Test 3: Database Class Test
echo "<div class='test-section'>\n";
echo "<h2>3. Database Class Method Test</h2>\n";

if ( class_exists( 'Aria_Database' ) ) {
    echo "<span class='test-pass'>✅ Aria_Database class is available</span><br>\n";
    
    try {
        $total_convs = Aria_Database::get_conversations_count();
        echo "<span class='test-info'>Database::get_conversations_count(): $total_convs</span><br>\n";
        
        $today_convs = Aria_Database::get_conversations_count( array(
            'date_from' => date( 'Y-m-d 00:00:00' ),
        ) );
        echo "<span class='test-info'>Database::get_conversations_count(today): $today_convs</span><br>\n";
        
        $knowledge_count = Aria_Database::get_knowledge_count();
        echo "<span class='test-info'>Database::get_knowledge_count(): $knowledge_count</span><br>\n";
        
    } catch ( Exception $e ) {
        echo "<span class='test-fail'>❌ Database methods threw exception: " . $e->getMessage() . "</span><br>\n";
    }
} else {
    echo "<span class='test-fail'>❌ Aria_Database class is NOT available</span><br>\n";
    
    // Try to load it
    if ( file_exists( ARIA_PLUGIN_PATH . 'includes/class-aria-database.php' ) ) {
        require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';
        if ( class_exists( 'Aria_Database' ) ) {
            echo "<span class='test-pass'>✅ Database class loaded successfully</span><br>\n";
        }
    }
}

echo "</div>\n";

// Test 4: Direct AJAX Handler Test
echo "<div class='test-section'>\n";
echo "<h2>4. Direct AJAX Handler Test</h2>\n";

if ( class_exists( 'Aria_Ajax_Handler' ) ) {
    // Set up the request environment
    $_POST['nonce'] = wp_create_nonce( 'aria_admin_nonce' );
    $_POST['action'] = 'aria_get_dashboard_data';
    $_REQUEST['action'] = 'aria_get_dashboard_data';
    
    echo "<span class='test-info'>Simulating AJAX request...</span><br>\n";
    echo "<span class='test-info'>Nonce: {$_POST['nonce']}</span><br>\n";
    echo "<span class='test-info'>Action: {$_POST['action']}</span><br>\n";
    
    $ajax_handler = new Aria_Ajax_Handler();
    
    // Capture the output
    ob_start();
    
    try {
        // Call the handler method directly
        $ajax_handler->handle_get_dashboard_data();
        $output = ob_get_clean();
        
        echo "<span class='test-pass'>✅ AJAX handler executed without fatal errors</span><br>\n";
        echo "<span class='test-info'>Raw output:</span><br>\n";
        echo "<pre>" . htmlspecialchars( $output ) . "</pre>\n";
        
        // Try to parse JSON
        $json_data = json_decode( $output, true );
        if ( $json_data !== null ) {
            echo "<span class='test-pass'>✅ Output is valid JSON</span><br>\n";
            
            if ( isset( $json_data['success'] ) ) {
                if ( $json_data['success'] ) {
                    echo "<span class='test-pass'>✅ AJAX request was successful</span><br>\n";
                    echo "<span class='test-info'>Response data:</span><br>\n";
                    echo "<pre>" . print_r( $json_data['data'], true ) . "</pre>\n";
                } else {
                    echo "<span class='test-fail'>❌ AJAX request failed</span><br>\n";
                    echo "<span class='test-info'>Error message: " . ( $json_data['data']['message'] ?? 'Unknown error' ) . "</span><br>\n";
                }
            } else {
                echo "<span class='test-warning'>⚠️ JSON response missing 'success' field</span><br>\n";
            }
        } else {
            echo "<span class='test-fail'>❌ Output is not valid JSON</span><br>\n";
            echo "<span class='test-info'>JSON error: " . json_last_error_msg() . "</span><br>\n";
        }
        
    } catch ( Exception $e ) {
        ob_end_clean();
        echo "<span class='test-fail'>❌ AJAX handler threw exception: " . $e->getMessage() . "</span><br>\n";
        echo "<span class='test-info'>Stack trace:</span><br>\n";
        echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    }
    
    // Clean up
    unset( $_POST['nonce'], $_POST['action'], $_REQUEST['action'] );
    
} else {
    echo "<span class='test-fail'>❌ Cannot test - Aria_Ajax_Handler class not available</span><br>\n";
}

echo "</div>\n";

// Test 5: WordPress AJAX Action Test
echo "<div class='test-section'>\n";
echo "<h2>5. WordPress AJAX Action Registration Test</h2>\n";

global $wp_filter;

// Check if the action is registered in WordPress
$action_name = 'wp_ajax_aria_get_dashboard_data';
if ( isset( $wp_filter[ $action_name ] ) && ! empty( $wp_filter[ $action_name ]->callbacks ) ) {
    echo "<span class='test-pass'>✅ WordPress AJAX action '$action_name' is registered</span><br>\n";
    
    // Show callback details
    foreach ( $wp_filter[ $action_name ]->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback ) {
            if ( is_array( $callback['function'] ) ) {
                $class = is_object( $callback['function'][0] ) ? get_class( $callback['function'][0] ) : $callback['function'][0];
                $method = $callback['function'][1];
                echo "<span class='test-info'>   → Callback: {$class}::{$method} (Priority: $priority)</span><br>\n";
            } else {
                echo "<span class='test-info'>   → Callback: {$callback['function']} (Priority: $priority)</span><br>\n";
            }
        }
    }
    
    // Test calling the action through WordPress
    echo "<span class='test-info'>Testing through WordPress do_action...</span><br>\n";
    
    $_POST['nonce'] = wp_create_nonce( 'aria_admin_nonce' );
    $_POST['action'] = 'aria_get_dashboard_data';
    $_REQUEST['action'] = 'aria_get_dashboard_data';
    
    ob_start();
    do_action( 'wp_ajax_aria_get_dashboard_data' );
    $wp_output = ob_get_clean();
    
    if ( ! empty( $wp_output ) ) {
        echo "<span class='test-pass'>✅ WordPress action produced output</span><br>\n";
        echo "<span class='test-info'>WordPress action output:</span><br>\n";
        echo "<pre>" . htmlspecialchars( $wp_output ) . "</pre>\n";
    } else {
        echo "<span class='test-warning'>⚠️ WordPress action produced no output</span><br>\n";
    }
    
    unset( $_POST['nonce'], $_POST['action'], $_REQUEST['action'] );
    
} else {
    echo "<span class='test-fail'>❌ WordPress AJAX action '$action_name' is NOT registered</span><br>\n";
    echo "<span class='test-info'>This suggests the plugin's AJAX actions were not properly hooked</span><br>\n";
}

echo "</div>\n";

// Test 6: Error Log Analysis
echo "<div class='test-section'>\n";
echo "<h2>6. Error Log Analysis</h2>\n";

$error_log_locations = [
    ini_get( 'error_log' ),
    ABSPATH . 'wp-content/debug.log',
    ABSPATH . 'error_log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log'
];

echo "<span class='test-info'>Checking error log locations...</span><br>\n";

$found_logs = false;
foreach ( $error_log_locations as $log_path ) {
    if ( $log_path && file_exists( $log_path ) && is_readable( $log_path ) ) {
        echo "<span class='test-pass'>✅ Found readable log: $log_path</span><br>\n";
        $found_logs = true;
        
        // Get recent Aria-related errors
        $log_content = file_get_contents( $log_path );
        $lines = explode( "\n", $log_content );
        $aria_errors = array_filter( $lines, function( $line ) {
            return stripos( $line, 'aria' ) !== false && 
                   ( stripos( $line, 'error' ) !== false || 
                     stripos( $line, 'warning' ) !== false ||
                     stripos( $line, 'fatal' ) !== false );
        } );
        
        if ( ! empty( $aria_errors ) ) {
            $recent_errors = array_slice( $aria_errors, -10 ); // Last 10 Aria errors
            echo "<span class='test-warning'>⚠️ Found " . count( $aria_errors ) . " Aria-related errors (showing last 10):</span><br>\n";
            echo "<pre>" . implode( "\n", $recent_errors ) . "</pre>\n";
        } else {
            echo "<span class='test-info'>   → No Aria-related errors found in this log</span><br>\n";
        }
    }
}

if ( ! $found_logs ) {
    echo "<span class='test-warning'>⚠️ No accessible error logs found</span><br>\n";
    echo "<span class='test-info'>You may need to enable WordPress debug logging:</span><br>\n";
    echo "<pre>
// Add to wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
</pre>\n";
}

echo "</div>\n";

echo "<div class='test-section'>\n";
echo "<h2>📊 Test Summary</h2>\n";
echo "<p>This test has examined the AJAX endpoint functionality in detail.</p>\n";
echo "<p><strong>Key findings to check:</strong></p>\n";
echo "<ul>\n";
echo "<li>Are database tables populated with actual data?</li>\n";
echo "<li>Is the AJAX handler executing without errors?</li>\n";
echo "<li>Is the WordPress AJAX action properly registered?</li>\n";
echo "<li>Are there any errors in the logs?</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><strong>Test completed at " . current_time( 'mysql' ) . "</strong></p>\n";
?>