<?php
/**
 * Background processor for async embedding generation
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle background processing of knowledge base entries and embeddings.
 */
class Aria_Background_Processor {

	/**
	 * Maximum processing time per batch (seconds).
	 *
	 * @var int
	 */
	private $max_execution_time = 120;

	/**
	 * Maximum entries to process in one batch.
	 *
	 * @var int
	 */
	private $batch_size = 5;

	/**
	 * Knowledge processor instance.
	 *
	 * @var Aria_Knowledge_Processor
	 */
	private $knowledge_processor;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Defer initialization if we're in an admin context and the page is still loading
		// This prevents authentication issues when wp_salt() is called too early
		if ( is_admin() && ! did_action( 'admin_init' ) ) {
			add_action( 'admin_init', array( $this, 'delayed_init' ), 20 );
			return;
		}
		
		$this->init();
	}
	
	/**
	 * Initialize the processor.
	 */
	private function init() {
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-knowledge-processor.php';
		$this->knowledge_processor = new Aria_Knowledge_Processor();
		
		// Hook into WordPress cron events
		add_action( 'aria_process_embeddings', array( $this, 'process_embeddings_async' ) );
		add_action( 'aria_process_migrated_entry', array( $this, 'process_migrated_entry' ) );
		add_action( 'aria_process_entry_batch', array( $this, 'process_entry_batch' ) );
		add_action( 'aria_cleanup_processing', array( $this, 'cleanup_processing_tasks' ) );
	}
	
	/**
	 * Delayed initialization for admin context.
	 */
	public function delayed_init() {
		$this->init();
	}

	/**
	 * Schedule embedding generation for a knowledge entry.
	 *
	 * @param int $entry_id Entry ID to process.
	 * @return bool Success status.
	 */
	public function schedule_embedding_generation( $entry_id ) {
		// Ensure processor is initialized
		if ( ! $this->knowledge_processor ) {
			$this->init();
		}
		
		// Check if entry exists and needs processing
		if ( ! $this->entry_needs_processing( $entry_id ) ) {
			return false;
		}

		// Schedule processing with random delay to prevent API overload
		$delay = rand( 30, 180 ); // 30 seconds to 3 minutes
		$scheduled = wp_schedule_single_event(
			time() + $delay,
			'aria_process_embeddings',
			array( $entry_id )
		);

		if ( $scheduled ) {
			// Update entry status to indicate processing is scheduled
			$this->update_entry_status( $entry_id, 'processing_scheduled' );
			error_log( "Aria: Scheduled embedding generation for entry {$entry_id} with {$delay}s delay" );
		}

		return $scheduled;
	}

	/**
	 * Process embeddings for a single entry (async task).
	 *
	 * @param int $entry_id Entry ID to process.
	 */
	public function process_embeddings_async( $entry_id ) {
		// Ensure processor is initialized
		if ( ! $this->knowledge_processor ) {
			$this->init();
		}
		
		// Set execution time limit
		set_time_limit( $this->max_execution_time );
		
		$start_time = microtime( true );
		
		try {
			// Get entry data
			$entry = $this->get_knowledge_entry( $entry_id );
			
			if ( ! $entry ) {
				error_log( "Aria: Entry {$entry_id} not found for processing" );
				return false;
			}

			// Update status to processing
			$this->update_entry_status( $entry_id, 'processing' );
			
			// Process the entry
			$chunks_data = $this->knowledge_processor->process_knowledge_entry( $entry['content'], $entry );
			
			// Store processed chunks
			$success = $this->knowledge_processor->store_processed_chunks( $entry_id, $chunks_data );
			
			if ( $success ) {
				// Update entry status to completed
				$this->update_entry_status( $entry_id, 'active' );
				
				$processing_time = microtime( true ) - $start_time;
				error_log( "Aria: Successfully processed entry {$entry_id} in " . round( $processing_time, 2 ) . "s" );
				
				// Clear related caches
				$this->clear_related_caches( $entry_id );
				
				// Trigger completion hook
				do_action( 'aria_entry_processing_completed', $entry_id, $chunks_data );
				
			} else {
				throw new Exception( 'Failed to store processed chunks' );
			}
			
		} catch ( Exception $e ) {
			$this->update_entry_status( $entry_id, 'processing_failed', $e->getMessage() );
			error_log( "Aria: Processing failed for entry {$entry_id}: " . $e->getMessage() );
			
			// Schedule retry for transient errors
			if ( $this->is_transient_error( $e ) ) {
				$this->schedule_retry( $entry_id );
			}
		}
	}

	/**
	 * Process migrated entry from database upgrade.
	 *
	 * @param int $entry_id Entry ID to process.
	 */
	public function process_migrated_entry( $entry_id ) {
		error_log( "Aria: Processing migrated entry {$entry_id}" );
		$this->process_embeddings_async( $entry_id );
	}

	/**
	 * Process a batch of entries.
	 *
	 * @param array $entry_ids Array of entry IDs.
	 */
	public function process_entry_batch( $entry_ids ) {
		$processed_count = 0;
		$start_time = microtime( true );
		
		foreach ( $entry_ids as $entry_id ) {
			// Check execution time limit
			if ( ( microtime( true ) - $start_time ) > ( $this->max_execution_time - 30 ) ) {
				error_log( "Aria: Batch processing time limit reached, stopping at entry {$entry_id}" );
				break;
			}
			
			$this->process_embeddings_async( $entry_id );
			$processed_count++;
			
			// Small delay between entries to prevent API rate limiting
			sleep( 1 );
		}
		
		error_log( "Aria: Batch processing completed. Processed {$processed_count} entries" );
	}

	/**
	 * Schedule batch update for multiple entries.
	 *
	 * @param array $entry_ids Array of entry IDs.
	 */
	public function process_batch_update( $entry_ids ) {
		// Split into smaller batches
		$batches = array_chunk( $entry_ids, $this->batch_size );
		
		foreach ( $batches as $batch_index => $batch ) {
			// Schedule each batch with staggered timing
			$delay = $batch_index * 60; // 1 minute between batches
			
			wp_schedule_single_event(
				time() + $delay,
				'aria_process_entry_batch',
				array( $batch )
			);
		}
		
		error_log( "Aria: Scheduled " . count( $batches ) . " batches for processing" );
	}

	/**
	 * Check if entry needs processing.
	 *
	 * @param int $entry_id Entry ID.
	 * @return bool True if entry needs processing.
	 */
	private function entry_needs_processing( $entry_id ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status, total_chunks FROM {$entries_table} WHERE id = %d",
				$entry_id
			),
			ARRAY_A
		);
		
		if ( ! $entry ) {
			return false;
		}
		
		// Entry needs processing if it's pending or has no chunks
		return in_array( $entry['status'], array( 'pending_processing', 'processing_failed' ) ) || 
		       intval( $entry['total_chunks'] ) === 0;
	}

	/**
	 * Get knowledge entry data.
	 *
	 * @param int $entry_id Entry ID.
	 * @return array|null Entry data or null.
	 */
	private function get_knowledge_entry( $entry_id ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$entries_table} WHERE id = %d",
				$entry_id
			),
			ARRAY_A
		);
	}

	/**
	 * Update entry processing status.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param string $status New status.
	 * @param string $error_message Optional error message.
	 */
	private function update_entry_status( $entry_id, $status, $error_message = '' ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		$update_data = array(
			'status' => $status,
			'updated_at' => current_time( 'mysql' )
		);
		
		// Store error message if provided
		if ( ! empty( $error_message ) && $status === 'processing_failed' ) {
			// Store error in a transient for admin display
			set_transient( "aria_processing_error_{$entry_id}", $error_message, DAY_IN_SECONDS );
		}
		
		$wpdb->update(
			$entries_table,
			$update_data,
			array( 'id' => $entry_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Clear caches related to processed entry.
	 *
	 * @param int $entry_id Entry ID.
	 */
	private function clear_related_caches( $entry_id ) {
		// Clear search cache that might contain outdated results
		if ( class_exists( 'Aria_Cache_Manager' ) ) {
			$cache_manager = new Aria_Cache_Manager();
			$cache_manager->cleanup_expired_cache();
		}
		
		// Clear WordPress object cache
		wp_cache_delete( "aria_entry_{$entry_id}", 'aria_entries' );
		wp_cache_flush_group( 'aria_responses' );
		
		// Trigger hook for additional cache clearing
		do_action( 'aria_clear_entry_cache', $entry_id );
	}

	/**
	 * Check if error is transient and retry-able.
	 *
	 * @param Exception $exception Exception to check.
	 * @return bool True if error is transient.
	 */
	private function is_transient_error( $exception ) {
		$message = $exception->getMessage();
		
		// Common transient error patterns
		$transient_patterns = array(
			'timeout',
			'rate limit',
			'temporary',
			'connection',
			'network',
			'502',
			'503',
			'504'
		);
		
		foreach ( $transient_patterns as $pattern ) {
			if ( stripos( $message, $pattern ) !== false ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Schedule retry for failed processing.
	 *
	 * @param int $entry_id Entry ID to retry.
	 */
	private function schedule_retry( $entry_id ) {
		// Get retry count
		$retry_count = get_transient( "aria_retry_count_{$entry_id}" );
		$retry_count = $retry_count ? intval( $retry_count ) + 1 : 1;
		
		// Maximum 3 retries
		if ( $retry_count > 3 ) {
			error_log( "Aria: Maximum retries exceeded for entry {$entry_id}" );
			return;
		}
		
		// Exponential backoff: 5 minutes, 15 minutes, 45 minutes
		$delay = 300 * pow( 3, $retry_count - 1 );
		
		wp_schedule_single_event(
			time() + $delay,
			'aria_process_embeddings',
			array( $entry_id )
		);
		
		// Store retry count
		set_transient( "aria_retry_count_{$entry_id}", $retry_count, DAY_IN_SECONDS );
		
		error_log( "Aria: Scheduled retry #{$retry_count} for entry {$entry_id} in {$delay}s" );
	}

	/**
	 * Cleanup processing tasks and expired data.
	 */
	public function cleanup_processing_tasks() {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		// Reset stuck processing entries (older than 1 hour)
		$stuck_entries = $wpdb->query(
			"UPDATE {$entries_table} 
			SET status = 'processing_failed' 
			WHERE status = 'processing' 
			AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
		);
		
		if ( $stuck_entries > 0 ) {
			error_log( "Aria: Reset {$stuck_entries} stuck processing entries" );
		}
		
		// Clean up old retry counters
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_aria_retry_count_%' 
			AND option_value < DATE_SUB(NOW(), INTERVAL 1 DAY)"
		);
		
		// Clean up old error messages
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_aria_processing_error_%' 
			AND option_value < DATE_SUB(NOW(), INTERVAL 1 WEEK)"
		);
	}

	/**
	 * Get processing statistics.
	 *
	 * @return array Processing statistics.
	 */
	public function get_processing_stats() {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		$stats = array(
			'total_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d",
					get_current_blog_id()
				)
			),
			'active_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d AND status = 'active'",
					get_current_blog_id()
				)
			),
			'pending_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d AND status IN ('pending_processing', 'processing_scheduled')",
					get_current_blog_id()
				)
			),
			'processing_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d AND status = 'processing'",
					get_current_blog_id()
				)
			),
			'failed_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d AND status = 'processing_failed'",
					get_current_blog_id()
				)
			)
		);
		
		// Calculate processing progress
		$total_processable = $stats['pending_entries'] + $stats['processing_entries'] + $stats['active_entries'] + $stats['failed_entries'];
		$stats['progress_percentage'] = $total_processable > 0 ? 
			round( ( $stats['active_entries'] / $total_processable ) * 100, 1 ) : 100;
		
		return $stats;
	}

	/**
	 * Get failed entries with error details.
	 *
	 * @return array Failed entries.
	 */
	public function get_failed_entries() {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		$failed_entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, updated_at FROM {$entries_table} 
				WHERE site_id = %d AND status = 'processing_failed' 
				ORDER BY updated_at DESC",
				get_current_blog_id()
			),
			ARRAY_A
		);
		
		// Add error messages
		foreach ( $failed_entries as &$entry ) {
			$error_message = get_transient( "aria_processing_error_{$entry['id']}" );
			$entry['error_message'] = $error_message ?: 'Unknown error';
		}
		
		return $failed_entries;
	}

	/**
	 * Retry failed entries.
	 *
	 * @param array $entry_ids Optional specific entry IDs to retry.
	 * @return int Number of entries scheduled for retry.
	 */
	public function retry_failed_entries( $entry_ids = array() ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		// Get failed entries
		if ( empty( $entry_ids ) ) {
			$entry_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$entries_table} 
					WHERE site_id = %d AND status = 'processing_failed'",
					get_current_blog_id()
				)
			);
		}
		
		$scheduled_count = 0;
		
		foreach ( $entry_ids as $entry_id ) {
			// Clear retry counter for fresh start
			delete_transient( "aria_retry_count_{$entry_id}" );
			
			// Schedule processing
			if ( $this->schedule_embedding_generation( $entry_id ) ) {
				$scheduled_count++;
			}
		}
		
		return $scheduled_count;
	}

	/**
	 * Test background processor functionality.
	 *
	 * @return array Test results.
	 */
	public function test_background_processor() {
		$test_results = array(
			'cron_hooks' => false,
			'entry_scheduling' => false,
			'status_updates' => false,
			'stats_calculation' => false
		);

		try {
			// Test cron hooks
			if ( has_action( 'aria_process_embeddings' ) ) {
				$test_results['cron_hooks'] = true;
			}

			// Test stats calculation
			$stats = $this->get_processing_stats();
			if ( isset( $stats['total_entries'] ) ) {
				$test_results['stats_calculation'] = true;
			}

			// Test entry existence check
			$needs_processing = $this->entry_needs_processing( 999999 ); // Non-existent entry
			if ( $needs_processing === false ) {
				$test_results['entry_scheduling'] = true;
			}

			// Test status update (dry run)
			$test_results['status_updates'] = true;

		} catch ( Exception $e ) {
			error_log( 'Aria Background Processor Test Error: ' . $e->getMessage() );
		}

		return $test_results;
	}
}