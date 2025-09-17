<?php
/**
 * Manual Vector System Migration Script
 * Run this once to trigger the vector system migration
 */

// WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
	require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

// Only allow admin users
if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied' );
}

echo "Starting Aria Vector System Migration...\n";

// Force database update
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-aria-db-updater.php' );

// Force update the database version to trigger migration
delete_option( 'aria_db_version' );
update_option( 'aria_db_version', '1.0.0' );

echo "Triggering database migration...\n";
Aria_DB_Updater::update();

echo "Database migration completed!\n";

// Check results
global $wpdb;

$old_table = $wpdb->prefix . 'aria_knowledge_base';
$new_table = $wpdb->prefix . 'aria_knowledge_entries';

$old_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$old_table}" );
$new_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );

echo "Migration Results:\n";
echo "- Old table entries: {$old_count}\n";
echo "- New table entries: {$new_count}\n";

if ( $new_count > 0 ) {
	$pending_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$new_table} WHERE status = %s",
		'pending_processing'
	) );
	
	echo "- Pending processing: {$pending_count}\n";
	
	if ( $pending_count > 0 ) {
		echo "\nScheduling background processing for migrated entries...\n";
		
		// Get pending entries
		$pending_entries = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$new_table} WHERE status = %s",
			'pending_processing'
		) );
		
		// Schedule processing
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-aria-background-processor.php' );
		$processor = new Aria_Background_Processor();
		
		foreach ( $pending_entries as $entry_id ) {
			$processor->schedule_embedding_generation( $entry_id );
			echo "- Scheduled entry {$entry_id}\n";
		}
		
		echo "\nBackground processing scheduled! Check the admin dashboard for progress.\n";
	}
}

echo "\nMigration complete! Visit the Aria admin dashboard to see the vector system status.\n";
?>