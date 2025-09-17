<?php
/**
 * Basic WordPress Integration Diagnostic for ARIA Plugin
 * Run this via: php debug-basic-integration.php
 */

// WordPress Bootstrap
require_once dirname(__FILE__) . '/../../../wp-config.php';
require_once ABSPATH . 'wp-load.php';

echo "=== ARIA BASIC INTEGRATION DIAGNOSTIC ===\n";

// 1. Check if plugin is active
$active_plugins = get_option('active_plugins', array());
$aria_active = false;
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'aria') !== false) {
        $aria_active = true;
        echo "✓ ARIA plugin is active: $plugin\n";
        break;
    }
}
if (!$aria_active) {
    echo "✗ ARIA plugin is NOT active\n";
}

// 2. Check if core classes exist
$core_classes = [
    'Aria_Core',
    'Aria_Admin', 
    'Aria_Ajax_Handler',
    'Aria_Database',
    'Aria_Content_Vectorizer'
];

foreach ($core_classes as $class) {
    if (class_exists($class)) {
        echo "✓ Class $class exists\n";
    } else {
        echo "✗ Class $class NOT found\n";
    }
}

// 3. Check database tables
global $wpdb;
$tables = [
    'aria_conversations',
    'aria_knowledge_entries', 
    'aria_knowledge_base',
    'aria_content_vectors'
];

foreach ($tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo "✓ Table $table exists with $count rows\n";
    } else {
        echo "✗ Table $table does NOT exist\n";
    }
}

// 4. Check if AJAX actions are registered
echo "\n--- Checking AJAX Action Registration ---\n";
global $wp_filter;

$ajax_actions = [
    'wp_ajax_aria_get_dashboard_data',
    'wp_ajax_nopriv_aria_get_dashboard_data'
];

foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo "✓ AJAX action $action is registered\n";
        // Show callback details
        foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback_name = 'Unknown';
                if (is_array($callback['function'])) {
                    $callback_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } elseif (is_string($callback['function'])) {
                    $callback_name = $callback['function'];
                }
                echo "  → Callback: $callback_name (priority: $priority)\n";
            }
        }
    } else {
        echo "✗ AJAX action $action is NOT registered\n";
    }
}

// 5. Test database queries directly
echo "\n--- Testing Database Queries ---\n";

// Test conversations
$site_id = get_current_blog_id();
$conversations = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}aria_conversations WHERE site_id = %d",
    $site_id
));
echo "Conversations for site $site_id: $conversations\n";

// Test knowledge entries
$knowledge = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}aria_knowledge_entries WHERE site_id = %d",
    $site_id
));
echo "Knowledge entries for site $site_id: $knowledge\n";

// Test content vectors
$vectors = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aria_content_vectors");
echo "Content vectors (all sites): $vectors\n";

// 6. Check WordPress posts for content indexing
$posts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type IN ('post', 'page')");
echo "Published WordPress posts/pages: $posts\n";

// 7. Test error logging
echo "\n--- Testing Error Logging ---\n";
$test_message = "ARIA Debug Test - " . date('Y-m-d H:i:s');
error_log($test_message);
echo "Test error logged: $test_message\n";
echo "Check your WordPress error log for this message.\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "If tables have data but dashboard shows 0, the issue is in the AJAX or React layer.\n";
echo "If classes are missing, check plugin activation.\n";
echo "If AJAX actions aren't registered, check the core class initialization.\n";
?>