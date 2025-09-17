<?php
/**
 * Manually trigger background processing for queued entries
 */

// WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
	require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

// Only allow admin users
if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied' );
}

echo "Triggering Aria Background Processing...\n\n";

// Get pending entries
global $wpdb;
$table = $wpdb->prefix . 'aria_knowledge_entries';

$pending_entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT id, title, status FROM {$table} WHERE status IN (%s, %s) ORDER BY created_at ASC LIMIT 10",
	'pending_processing',
	'processing_scheduled'
) );

echo "Found " . count( $pending_entries ) . " entries to process:\n";

if ( ! empty( $pending_entries ) ) {
	// Load background processor
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-aria-background-processor.php' );
	$processor = new Aria_Background_Processor();
	
	foreach ( $pending_entries as $entry ) {
		echo "Processing entry {$entry->id}: {$entry->title}\n";
		
		try {
			// Process directly
			$result = $processor->process_embeddings_async( $entry->id );
			if ( false !== $result ) {
				echo "✅ Entry {$entry->id} processed successfully\n";
			} else {
				echo "❌ Entry {$entry->id} processing failed\n";
			}
		} catch ( Exception $e ) {
			echo "❌ Error processing entry {$entry->id}: " . $e->getMessage() . "\n";
		}
		
		echo "\n";
	}
	
	// Check results
	$processed_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE status = %s",
		'active'
	) );
	
	$failed_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE status = %s",
		'failed'
	) );
	
	echo "=== Results ===\n";
	echo "Processed entries: {$processed_count}\n";
	echo "Failed entries: {$failed_count}\n";
	
} else {
	echo "No pending entries found.\n";
}

// Show current cron jobs
echo "\n=== Current Cron Jobs ===\n";
$cron_jobs = _get_cron_array();
$aria_jobs = 0;

foreach ( $cron_jobs as $timestamp => $jobs ) {
	foreach ( $jobs as $hook => $job_data ) {
		if ( strpos( $hook, 'aria_' ) === 0 ) {
			$aria_jobs++;
			$time_str = date( 'Y-m-d H:i:s', $timestamp );
			echo "- {$hook} scheduled for {$time_str}\n";
		}
	}
}

if ( $aria_jobs === 0 ) {
	echo "No Aria cron jobs scheduled.\n";
} else {
	echo "Total Aria cron jobs: {$aria_jobs}\n";
}

echo "\nDone!\n";
?>