<?php
/**
 * Debug Vector System - Simple diagnostic script
 * 
 * Instructions:
 * 1. Upload this file to your WordPress root directory
 * 2. Access via: yoursite.com/debug-vector-system.php
 * 3. Or add the code to a WordPress admin page
 */

// Basic WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('./wp-blog-header.php');

// Only allow admin users
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied - please log in as admin' );
}

echo '<h1>Aria Vector System Debug</h1>';

// Check if plugin is active
if ( ! is_plugin_active( 'aria/aria.php' ) ) {
	echo '<p style="color: red;">❌ Aria plugin is not active</p>';
	exit;
}

echo '<p style="color: green;">✅ Aria plugin is active</p>';

// Check database tables
global $wpdb;
$tables = array(
	'aria_knowledge_entries',
	'aria_knowledge_chunks',
	'aria_search_cache',
	'aria_search_analytics'
);

echo '<h2>Database Tables</h2>';
foreach ( $tables as $table ) {
	$full_table = $wpdb->prefix . $table;
	$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table}'" );
	
	if ( $exists ) {
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$full_table}" );
		echo '<p style="color: green;">✅ ' . $table . ' exists (' . $count . ' rows)</p>';
	} else {
		echo '<p style="color: red;">❌ ' . $table . ' does not exist</p>';
	}
}

// Check entry statuses
$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
$status_counts = $wpdb->get_results(
	"SELECT status, COUNT(*) as count FROM {$entries_table} GROUP BY status"
);

echo '<h2>Entry Status Counts</h2>';
if ( ! empty( $status_counts ) ) {
	echo '<table border="1" style="border-collapse: collapse; margin: 10px 0;">';
	echo '<tr><th style="padding: 5px;">Status</th><th style="padding: 5px;">Count</th></tr>';
	
	foreach ( $status_counts as $status ) {
		$color = '';
		if ( $status->status === 'active' ) $color = 'background: #d4edda;';
		if ( $status->status === 'failed' ) $color = 'background: #f8d7da;';
		if ( strpos( $status->status, 'processing' ) !== false ) $color = 'background: #fff3cd;';
		
		echo '<tr style="' . $color . '">';
		echo '<td style="padding: 5px;">' . esc_html( $status->status ) . '</td>';
		echo '<td style="padding: 5px;">' . esc_html( $status->count ) . '</td>';
		echo '</tr>';
	}
	echo '</table>';
} else {
	echo '<p>No entries found</p>';
}

// Check WordPress cron
echo '<h2>WordPress Cron Status</h2>';
if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
	echo '<p style="color: red;">❌ WordPress cron is disabled (DISABLE_WP_CRON = true)</p>';
} else {
	echo '<p style="color: green;">✅ WordPress cron is enabled</p>';
}

// Check scheduled events
$cron_events = _get_cron_array();
$aria_events = 0;
$next_aria_event = null;

foreach ( $cron_events as $timestamp => $events ) {
	foreach ( $events as $hook => $event_data ) {
		if ( strpos( $hook, 'aria_' ) === 0 ) {
			$aria_events++;
			if ( ! $next_aria_event || $timestamp < $next_aria_event ) {
				$next_aria_event = $timestamp;
			}
		}
	}
}

echo '<p>Aria cron events scheduled: <strong>' . $aria_events . '</strong></p>';
if ( $next_aria_event ) {
	echo '<p>Next Aria event: <strong>' . date( 'Y-m-d H:i:s', $next_aria_event ) . '</strong></p>';
}

// Check AI configuration
echo '<h2>AI Configuration</h2>';
$ai_provider = get_option( 'aria_ai_provider', 'openai' );
$api_key = get_option( 'aria_ai_api_key', '' );

echo '<p>AI Provider: <strong>' . esc_html( $ai_provider ) . '</strong></p>';
echo '<p>API Key: <strong>' . ( $api_key ? 'Set (' . substr( $api_key, 0, 8 ) . '...)' : 'Not set' ) . '</strong></p>';

// Test background processor loading
echo '<h2>Background Processor Test</h2>';
try {
	require_once( ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php' );
	$processor = Aria_Background_Processor::instance();
	echo '<p style="color: green;">✅ Background processor loaded successfully</p>';
	
	// Get processing stats
	$stats = $processor->get_processing_stats();
	echo '<p>Processing stats:</p>';
	echo '<ul>';
	echo '<li>Active entries: ' . ( $stats['active_entries'] ?? 0 ) . '</li>';
	echo '<li>Pending entries: ' . ( $stats['pending_entries'] ?? 0 ) . '</li>';
	echo '<li>Failed entries: ' . ( $stats['failed_entries'] ?? 0 ) . '</li>';
	echo '<li>Processing entries: ' . ( $stats['processing_entries'] ?? 0 ) . '</li>';
	echo '</ul>';
	
} catch ( Exception $e ) {
	echo '<p style="color: red;">❌ Failed to load background processor: ' . esc_html( $e->getMessage() ) . '</p>';
}

// Manual process button
if ( isset( $_GET['process'] ) && $_GET['process'] === 'now' ) {
	echo '<h2>Manual Processing Results</h2>';
	
	// Get one pending entry
	$pending_entry = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, title FROM {$entries_table} WHERE status = %s LIMIT 1",
		'pending_processing'
	) );
	
	if ( $pending_entry ) {
		echo '<p>Processing entry: ' . esc_html( $pending_entry->title ) . '</p>';
		
		try {
			$result = $processor->process_embeddings_async( $pending_entry->id );
			
			if ( false !== $result ) {
				echo '<p style="color: green;">✅ Successfully processed entry!</p>';
			} else {
				echo '<p style="color: red;">❌ Processing failed</p>';
			}
		} catch ( Exception $e ) {
			echo '<p style="color: red;">❌ Error: ' . esc_html( $e->getMessage() ) . '</p>';
		}
	} else {
		echo '<p>No pending entries to process</p>';
	}
}

// Add process button
$pending_count = $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$entries_table} WHERE status = %s",
	'pending_processing'
) );

if ( $pending_count > 0 ) {
	echo '<h2>Manual Processing</h2>';
	echo '<p>There are ' . $pending_count . ' pending entries.</p>';
	echo '<p><a href="?process=now" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Process One Entry Now</a></p>';
}

echo '<p><a href="' . admin_url( 'admin.php?page=aria-dashboard' ) . '">← Back to Aria Dashboard</a></p>';
?>