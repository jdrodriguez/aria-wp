<?php
/**
 * Test Background Processing
 * Run this script to manually trigger background processing
 */

// WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
	require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

// Only allow admin users
if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied' );
}

echo "Testing Aria Background Processing...\n\n";

// Load background processor
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-aria-background-processor.php' );
$processor = Aria_Background_Processor::instance();

// Check database for pending entries
global $wpdb;
$table = $wpdb->prefix . 'aria_knowledge_entries';

$pending_entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT id, title, status, created_at FROM {$table} WHERE status = %s ORDER BY created_at ASC LIMIT 10",
	'pending_processing'
) );

echo "Pending entries found: " . count( $pending_entries ) . "\n";

if ( ! empty( $pending_entries ) ) {
	echo "\nPending entries:\n";
	foreach ( $pending_entries as $entry ) {
		echo "- ID: {$entry->id}, Title: {$entry->title}, Status: {$entry->status}\n";
	}
	
	echo "\nTesting background processing method...\n";
	
	// Test the processing method directly
	try {
		$processor->process_embeddings_async();
		echo "✅ Background processing method executed successfully!\n";
	} catch ( Exception $e ) {
		echo "❌ Error during processing: " . $e->getMessage() . "\n";
	}
	
	// Check if entries were processed
	$processed_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE status = %s",
		'processed'
	) );
	
	echo "\nProcessed entries after test: {$processed_count}\n";
	
	// Check for any errors
	$failed_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE status = %s",
		'failed'
	) );
	
	echo "Failed entries: {$failed_count}\n";
	
} else {
	echo "No pending entries found. Creating a test entry...\n";
	
	// Create a test entry
	$test_data = array(
		'title' => 'Test Entry for Background Processing',
		'content' => 'This is a test knowledge entry to verify background processing is working properly.',
		'context' => 'Test context for background processing',
		'response_instructions' => 'Respond helpfully about this test.',
		'category' => 'Test',
		'tags' => 'test,background,processing',
		'language' => 'en',
		'status' => 'pending_processing',
		'created_at' => current_time( 'mysql' ),
		'site_id' => get_current_blog_id()
	);
	
	$result = $wpdb->insert( $table, $test_data );
	
	if ( false !== $result ) {
		$entry_id = $wpdb->insert_id;
		echo "✅ Test entry created with ID: {$entry_id}\n";
		
		// Schedule processing
		$processor->schedule_embedding_generation( $entry_id );
		echo "✅ Processing scheduled for entry {$entry_id}\n";
		
		// Test direct processing
		echo "\nTesting direct processing...\n";
		try {
			$processor->process_embeddings_async();
			echo "✅ Direct processing completed!\n";
		} catch ( Exception $e ) {
			echo "❌ Processing failed: " . $e->getMessage() . "\n";
		}
	} else {
		echo "❌ Failed to create test entry\n";
	}
}

// Show WordPress cron status
echo "\n=== WordPress Cron Status ===\n";
if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
	echo "❌ WordPress cron is disabled (DISABLE_WP_CRON = true)\n";
} else {
	echo "✅ WordPress cron is enabled\n";
}

// Show scheduled events
$cron_events = _get_cron_array();
$aria_events = 0;
foreach ( $cron_events as $timestamp => $events ) {
	foreach ( $events as $hook => $event_data ) {
		if ( strpos( $hook, 'aria_' ) === 0 ) {
			$aria_events++;
		}
	}
}

echo "Aria cron events scheduled: {$aria_events}\n";

echo "\n=== Test Complete ===\n";
?>