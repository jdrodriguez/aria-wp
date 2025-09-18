<?php
/**
 * Debug script to check WordPress cron and background processing
 */

// Try to locate WordPress
$wp_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../wp-load.php',
    '../../wp-load.php',
    '../wp-config.php',
    '../../wp-config.php',
    '../../../wp-config.php',
];

$wp_loaded = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("WordPress could not be loaded. Please run this script from the WordPress directory.\n");
}

// Check if user is admin when running via web
if (php_sapi_name() !== 'cli' && !current_user_can('manage_options')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Aria Background Processing Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        .ok { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Aria Background Processing Debug Report</h1>
    
    <div class="section">
        <h2>WordPress Cron Status</h2>
        <?php
        // Check if WordPress cron is working
        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        echo '<p>WordPress Cron Disabled: <span class="' . ($cron_disabled ? 'warning' : 'ok') . '">' . ($cron_disabled ? 'YES (DISABLE_WP_CRON = true)' : 'NO') . '</span></p>';
        
        // Check scheduled events
        $cron_array = get_option('cron');
        $aria_events = [];
        
        if ($cron_array) {
            foreach ($cron_array as $timestamp => $cron_jobs) {
                foreach ($cron_jobs as $hook => $events) {
                    if (strpos($hook, 'aria_') === 0) {
                        $aria_events[] = [
                            'hook' => $hook,
                            'timestamp' => $timestamp,
                            'time' => date('Y-m-d H:i:s', $timestamp),
                            'events' => count($events)
                        ];
                    }
                }
            }
        }
        
        echo '<p>Scheduled Aria Events: <span class="info">' . count($aria_events) . '</span></p>';
        
        if (!empty($aria_events)) {
            echo '<pre>';
            foreach ($aria_events as $event) {
                echo "Hook: {$event['hook']}\n";
                echo "Time: {$event['time']}\n";
                echo "Events: {$event['events']}\n";
                echo "---\n";
            }
            echo '</pre>';
        }
        
        // Check if cron actions are registered
        global $wp_filter;
        $aria_cron_hooks = ['aria_process_embeddings', 'aria_process_migrated_entry', 'aria_process_entry_batch', 'aria_cleanup_processing'];
        
        echo '<h3>Cron Hook Registration</h3>';
        foreach ($aria_cron_hooks as $hook) {
            $registered = isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks);
            echo '<p>' . $hook . ': <span class="' . ($registered ? 'ok' : 'error') . '">' . ($registered ? 'REGISTERED' : 'NOT REGISTERED') . '</span></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Database Status</h2>
        <?php
        global $wpdb;
        
        // Check table existence
        $entries_table = $wpdb->prefix . 'aria_knowledge_entries';
        $chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
        
        $entries_exists = $wpdb->get_var("SHOW TABLES LIKE '{$entries_table}'") === $entries_table;
        $chunks_exists = $wpdb->get_var("SHOW TABLES LIKE '{$chunks_table}'") === $chunks_table;
        
        echo '<p>Knowledge Entries Table: <span class="' . ($entries_exists ? 'ok' : 'error') . '">' . ($entries_exists ? 'EXISTS' : 'MISSING') . '</span></p>';
        echo '<p>Knowledge Chunks Table: <span class="' . ($chunks_exists ? 'ok' : 'error') . '">' . ($chunks_exists ? 'EXISTS' : 'MISSING') . '</span></p>';
        
        if ($entries_exists) {
            // Get entry status counts
            $statuses = $wpdb->get_results("
                SELECT status, COUNT(*) as count 
                FROM {$entries_table} 
                GROUP BY status
            ", ARRAY_A);
            
            echo '<h3>Entry Status Distribution</h3>';
            $total_entries = 0;
            foreach ($statuses as $status) {
                $total_entries += $status['count'];
                $css_class = 'info';
                if ($status['status'] === 'pending_processing') $css_class = 'warning';
                if ($status['status'] === 'processing_failed') $css_class = 'error';
                if ($status['status'] === 'active') $css_class = 'ok';
                
                echo '<p>' . ucfirst(str_replace('_', ' ', $status['status'])) . ': <span class="' . $css_class . '">' . $status['count'] . '</span></p>';
            }
            
            echo '<p>Total Entries: <span class="info">' . $total_entries . '</span></p>';
            
            // Get sample entries that need processing
            $pending_entries = $wpdb->get_results("
                SELECT id, title, status, total_chunks, created_at, updated_at 
                FROM {$entries_table} 
                WHERE status IN ('pending_processing', 'processing_scheduled', 'processing')
                ORDER BY created_at DESC 
                LIMIT 5
            ", ARRAY_A);
            
            if (!empty($pending_entries)) {
                echo '<h3>Sample Entries Needing Processing</h3>';
                echo '<pre>';
                foreach ($pending_entries as $entry) {
                    echo "ID: {$entry['id']} | Status: {$entry['status']} | Chunks: {$entry['total_chunks']} | Created: {$entry['created_at']}\n";
                    echo "Title: " . substr($entry['title'], 0, 60) . "\n";
                    echo "---\n";
                }
                echo '</pre>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>Background Processor Status</h2>
        <?php
        try {
            require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
            $processor = Aria_Background_Processor::instance();
            
            // Test background processor
            $test_results = $processor->test_background_processor();
            
            echo '<h3>Background Processor Tests</h3>';
            foreach ($test_results as $test => $result) {
                echo '<p>' . ucfirst(str_replace('_', ' ', $test)) . ': <span class="' . ($result ? 'ok' : 'error') . '">' . ($result ? 'PASS' : 'FAIL') . '</span></p>';
            }
            
            // Get processing stats
            $stats = $processor->get_processing_stats();
            
            echo '<h3>Processing Statistics</h3>';
            echo '<p>Total Entries: <span class="info">' . $stats['total_entries'] . '</span></p>';
            echo '<p>Active Entries: <span class="ok">' . $stats['active_entries'] . '</span></p>';
            echo '<p>Pending Entries: <span class="warning">' . $stats['pending_entries'] . '</span></p>';
            echo '<p>Processing Entries: <span class="info">' . $stats['processing_entries'] . '</span></p>';
            echo '<p>Failed Entries: <span class="error">' . $stats['failed_entries'] . '</span></p>';
            echo '<p>Progress: <span class="info">' . $stats['progress_percentage'] . '%</span></p>';
            
            // Check for failed entries
            if ($stats['failed_entries'] > 0) {
                $failed_entries = $processor->get_failed_entries();
                echo '<h3>Failed Entries</h3>';
                echo '<pre>';
                foreach ($failed_entries as $entry) {
                    echo "ID: {$entry['id']} | Title: " . substr($entry['title'], 0, 50) . "\n";
                    echo "Error: {$entry['error_message']}\n";
                    echo "Failed at: {$entry['updated_at']}\n";
                    echo "---\n";
                }
                echo '</pre>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">Error initializing background processor: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>API Configuration</h2>
        <?php
        $provider = get_option('aria_ai_provider', 'not set');
        $api_key_exists = !empty(get_option('aria_ai_api_key'));
        $vector_enabled = get_option('aria_vector_enabled', false);
        
        echo '<p>AI Provider: <span class="info">' . $provider . '</span></p>';
        echo '<p>API Key Set: <span class="' . ($api_key_exists ? 'ok' : 'error') . '">' . ($api_key_exists ? 'YES' : 'NO') . '</span></p>';
        echo '<p>Vector System: <span class="' . ($vector_enabled ? 'ok' : 'warning') . '">' . ($vector_enabled ? 'ENABLED' : 'DISABLED') . '</span></p>';
        
        // Test API connection if configured
        if ($api_key_exists) {
            try {
                require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
                $encrypted_key = get_option('aria_ai_api_key');
                $api_key = Aria_Security::decrypt($encrypted_key);
                
                if ($api_key) {
                    echo '<p>API Key Decrypt: <span class="ok">SUCCESS</span></p>';
                } else {
                    echo '<p>API Key Decrypt: <span class="error">FAILED</span></p>';
                }
            } catch (Exception $e) {
                echo '<p>API Key Decrypt: <span class="error">ERROR - ' . $e->getMessage() . '</span></p>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>System Environment</h2>
        <?php
        echo '<p>PHP Version: <span class="info">' . PHP_VERSION . '</span></p>';
        echo '<p>WordPress Version: <span class="info">' . get_bloginfo('version') . '</span></p>';
        echo '<p>Memory Limit: <span class="info">' . ini_get('memory_limit') . '</span></p>';
        echo '<p>Max Execution Time: <span class="info">' . ini_get('max_execution_time') . 's</span></p>';
        echo '<p>Current Time: <span class="info">' . current_time('Y-m-d H:i:s') . '</span></p>';
        echo '<p>Server Time: <span class="info">' . date('Y-m-d H:i:s') . '</span></p>';
        
        // Check if functions exist
        $functions = ['wp_schedule_single_event', 'wp_next_scheduled', 'wp_cron'];
        echo '<h3>WordPress Functions</h3>';
        foreach ($functions as $func) {
            echo '<p>' . $func . ': <span class="' . (function_exists($func) ? 'ok' : 'error') . '">' . (function_exists($func) ? 'EXISTS' : 'MISSING') . '</span></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Recent Error Log</h2>
        <?php
        // Try to find recent Aria-related errors
        $error_log_paths = [
            '/tmp/error.log',
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log',
            WP_CONTENT_DIR . '/debug.log',
            ini_get('error_log')
        ];
        
        $found_errors = false;
        foreach ($error_log_paths as $log_path) {
            if ($log_path && file_exists($log_path) && is_readable($log_path)) {
                $log_content = file_get_contents($log_path);
                if (stripos($log_content, 'aria') !== false) {
                    // Get recent Aria-related errors
                    $lines = explode("\n", $log_content);
                    $aria_errors = [];
                    
                    foreach (array_reverse($lines) as $line) {
                        if (stripos($line, 'aria') !== false && count($aria_errors) < 10) {
                            $aria_errors[] = $line;
                        }
                    }
                    
                    if (!empty($aria_errors)) {
                        echo '<h3>Recent Aria Errors from ' . $log_path . '</h3>';
                        echo '<pre>';
                        foreach (array_reverse($aria_errors) as $error) {
                            echo htmlspecialchars($error) . "\n";
                        }
                        echo '</pre>';
                        $found_errors = true;
                        break;
                    }
                }
            }
        }
        
        if (!$found_errors) {
            echo '<p class="info">No recent Aria-related errors found in accessible log files.</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Manual Test Actions</h2>
        <p>You can manually test the background processing system:</p>
        <ol>
            <li>Check if WordPress cron is running: <code>wp cron event list</code></li>
            <li>Manually trigger cron: <code>wp cron event run --all</code></li>
            <li>Test specific event: <code>wp cron event run aria_process_embeddings</code></li>
        </ol>
        
        <h3>Quick Fixes</h3>
        <ul>
            <li>If cron is disabled, enable it by removing <code>DISABLE_WP_CRON</code> from wp-config.php</li>
            <li>If entries are stuck in "processing" status, they will be reset to "failed" after 1 hour</li>
            <li>Failed entries can be retried via the admin interface</li>
        </ul>
    </div>

</body>
</html>