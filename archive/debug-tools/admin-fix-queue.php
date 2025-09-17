<?php
/**
 * Admin Fix Queue - Add this to WordPress admin to manually process stuck entries
 * 
 * INSTRUCTIONS:
 * 1. Add this code to your WordPress admin area (via theme functions.php or custom plugin)
 * 2. Access via: wp-admin/admin.php?page=fix-aria-queue
 * 3. Or run the code directly in wp-admin/admin.php
 */

// Add admin menu item
add_action( 'admin_menu', 'aria_add_fix_queue_menu' );

function aria_add_fix_queue_menu() {
	add_management_page(
		'Fix Aria Queue',
		'Fix Aria Queue',
		'manage_options',
		'fix-aria-queue',
		'aria_fix_queue_page'
	);
}

function aria_fix_queue_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}
	
	echo '<div class="wrap">';
	echo '<h1>Fix Aria Processing Queue</h1>';
	
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'fix' ) {
		aria_process_stuck_entries();
	} else {
		aria_show_queue_status();
	}
	
	echo '</div>';
}

function aria_show_queue_status() {
	global $wpdb;
	$table = $wpdb->prefix . 'aria_knowledge_entries';
	
	// Get status counts
	$status_counts = $wpdb->get_results(
		"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status"
	);
	
	echo '<h2>Current Queue Status</h2>';
	echo '<table class="widefat">';
	echo '<tr><th>Status</th><th>Count</th></tr>';
	
	foreach ( $status_counts as $status ) {
		echo '<tr>';
		echo '<td>' . esc_html( $status->status ) . '</td>';
		echo '<td>' . esc_html( $status->count ) . '</td>';
		echo '</tr>';
	}
	echo '</table>';
	
	// Show stuck entries
	$stuck_entries = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, title, status, created_at FROM {$table} 
		WHERE status IN (%s, %s, %s, %s) 
		ORDER BY created_at ASC LIMIT 10",
		'pending_processing',
		'processing_scheduled', 
		'processing',
		'failed'
	) );
	
	if ( ! empty( $stuck_entries ) ) {
		echo '<h2>Stuck Entries (' . count( $stuck_entries ) . ')</h2>';
		echo '<table class="widefat">';
		echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th></tr>';
		
		foreach ( $stuck_entries as $entry ) {
			echo '<tr>';
			echo '<td>' . esc_html( $entry->id ) . '</td>';
			echo '<td>' . esc_html( substr( $entry->title, 0, 50 ) ) . '...</td>';
			echo '<td>' . esc_html( $entry->status ) . '</td>';
			echo '<td>' . esc_html( $entry->created_at ) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		echo '<p><a href="' . admin_url( 'tools.php?page=fix-aria-queue&action=fix' ) . '" class="button button-primary">Fix Processing Queue</a></p>';
	} else {
		echo '<p style="color: green;">✅ No stuck entries found. Processing queue is clean!</p>';
	}
}

function aria_process_stuck_entries() {
	global $wpdb;
	$table = $wpdb->prefix . 'aria_knowledge_entries';
	
	echo '<h2>Processing Stuck Entries...</h2>';
	
	// Get stuck entries
	$stuck_entries = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, title, status FROM {$table} 
		WHERE status IN (%s, %s, %s, %s) 
		ORDER BY created_at ASC",
		'pending_processing',
		'processing_scheduled', 
		'processing',
		'failed'
	) );
	
	if ( empty( $stuck_entries ) ) {
		echo '<p style="color: green;">No stuck entries found!</p>';
		return;
	}
	
	echo '<p>Found ' . count( $stuck_entries ) . ' entries to process...</p>';
	
	// Load background processor
	require_once( ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php' );
	
	try {
		$processor = new Aria_Background_Processor();
		echo '<p style="color: green;">✅ Background processor loaded successfully</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">❌ Failed to load background processor: ' . esc_html( $e->getMessage() ) . '</p>';
		return;
	}
	
	$processed_count = 0;
	$failed_count = 0;
	
	echo '<div style="background: #f1f1f1; padding: 10px; margin: 10px 0; font-family: monospace;">';
	
	foreach ( $stuck_entries as $entry ) {
		echo 'Processing entry ' . esc_html( $entry->id ) . ': ' . esc_html( substr( $entry->title, 0, 50 ) ) . '...<br>';
		
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
				echo '<span style="color: green;">✅ Successfully processed entry ' . esc_html( $entry->id ) . '</span><br>';
				$processed_count++;
			} else {
				echo '<span style="color: red;">❌ Failed to process entry ' . esc_html( $entry->id ) . '</span><br>';
				$failed_count++;
			}
			
		} catch ( Exception $e ) {
			echo '<span style="color: red;">❌ Error processing entry ' . esc_html( $entry->id ) . ': ' . esc_html( $e->getMessage() ) . '</span><br>';
			$failed_count++;
		}
		
		echo '<br>';
	}
	
	echo '</div>';
	
	// Show results
	echo '<h3>Processing Complete</h3>';
	echo '<p>Successfully processed: <strong>' . $processed_count . '</strong> entries</p>';
	echo '<p>Failed to process: <strong>' . $failed_count . '</strong> entries</p>';
	
	// Check final status
	$final_stats = $wpdb->get_results(
		"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status"
	);
	
	echo '<h3>Final Status Counts</h3>';
	echo '<table class="widefat">';
	echo '<tr><th>Status</th><th>Count</th></tr>';
	foreach ( $final_stats as $stat ) {
		echo '<tr>';
		echo '<td>' . esc_html( $stat->status ) . '</td>';
		echo '<td>' . esc_html( $stat->count ) . '</td>';
		echo '</tr>';
	}
	echo '</table>';
	
	// Check chunks
	$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
	$chunk_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$chunks_table}" );
	echo '<p>Total chunks generated: <strong>' . esc_html( $chunk_count ) . '</strong></p>';
	
	echo '<p><a href="' . admin_url( 'tools.php?page=fix-aria-queue' ) . '" class="button">Back to Queue Status</a></p>';
}

// Alternative: Direct execution method
if ( isset( $_GET['aria_fix_queue'] ) && current_user_can( 'manage_options' ) ) {
	aria_process_stuck_entries();
	exit;
}
?>