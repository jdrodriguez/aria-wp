<?php
/**
 * Fix Processing Queue - Manually process stuck entries
 * Run this script to clear the processing queue and fix any stuck entries
 */

// WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
	require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

// Only allow admin users
if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied' );
}

echo "Fixing Aria Processing Queue...\n\n";

// Get all entries that are stuck in processing states
global $wpdb;
$table = $wpdb->prefix . 'aria_knowledge_entries';

$stuck_entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT id, title, status, created_at, updated_at FROM {$table} 
	WHERE status IN (%s, %s, %s, %s) 
	ORDER BY created_at ASC",
	'pending_processing',
	'processing_scheduled', 
	'processing',
	'failed'
) );

echo "Found " . count( $stuck_entries ) . " entries to process:\n\n";

if ( empty( $stuck_entries ) ) {
	echo "No stuck entries found. Processing queue is clean!\n";
	exit;
}

// Load the background processor
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-aria-background-processor.php' );

try {
	$processor = Aria_Background_Processor::instance();
	echo "✅ Background processor loaded successfully\n\n";
} catch ( Exception $e ) {
	echo "❌ Failed to load background processor: " . $e->getMessage() . "\n";
	exit;
}

// Process each stuck entry
$processed_count = 0;
$failed_count = 0;

foreach ( $stuck_entries as $entry ) {
	echo "Processing entry {$entry->id}: " . substr( $entry->title, 0, 50 ) . "...\n";
	
	try {
		// Reset status to pending if it was stuck
		if ( in_array( $entry->status, array( 'processing', 'processing_scheduled' ) ) ) {
			$wpdb->update(
				$table,
				array( 'status' => 'pending_processing' ),
				array( 'id' => $entry->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
		
		// Process the entry directly
		$result = $processor->process_embeddings_async( $entry->id );
		
		if ( false !== $result ) {
			echo "✅ Successfully processed entry {$entry->id}\n";
			$processed_count++;
		} else {
			echo "❌ Failed to process entry {$entry->id}\n";
			$failed_count++;
		}
		
	} catch ( Exception $e ) {
		echo "❌ Error processing entry {$entry->id}: " . $e->getMessage() . "\n";
		$failed_count++;
	}
	
	echo "\n";
}

// Show final results
echo "=== Processing Complete ===\n";
echo "Successfully processed: {$processed_count} entries\n";
echo "Failed to process: {$failed_count} entries\n";

// Check final status
$final_stats = $wpdb->get_results(
	"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status"
);

echo "\n=== Final Status Counts ===\n";
foreach ( $final_stats as $stat ) {
	echo "- {$stat->status}: {$stat->count} entries\n";
}

// Check if any entries have chunks now
$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
$chunk_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$chunks_table}" );
echo "\nTotal chunks generated: {$chunk_count}\n";

// Show background processor stats
echo "\n=== Background Processor Status ===\n";
try {
	$stats = $processor->get_processing_stats();
	echo "Active entries: " . ( $stats['active_entries'] ?? 0 ) . "\n";
	echo "Pending entries: " . ( $stats['pending_entries'] ?? 0 ) . "\n";
	echo "Failed entries: " . ( $stats['failed_entries'] ?? 0 ) . "\n";
	echo "Processing entries: " . ( $stats['processing_entries'] ?? 0 ) . "\n";
} catch ( Exception $e ) {
	echo "Could not get processing stats: " . $e->getMessage() . "\n";
}

echo "\n✅ Processing queue fix complete!\n";
?>