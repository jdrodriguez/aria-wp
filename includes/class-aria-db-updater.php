<?php
/**
 * Database updater for schema changes
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle database updates and migrations.
 */
class Aria_DB_Updater {

	/**
	 * Current database version
	 */
	const DB_VERSION = '1.6.0';

	/**
	 * Run database updates if needed
	 */
	public static function update() {
		$current_version = get_option( 'aria_db_version', '1.0.0' );
		
		// Define updates in order
		$updates = array(
			'1.1.0' => array( 'update_to_1_1_0' ),
			'1.1.1' => array( 'update_to_1_1_1' ),
			'1.2.0' => array( 'update_to_1_2_0' ),
			'1.3.0' => array( 'update_to_1_3_0' ),
			'1.4.0' => array( 'update_to_1_4_0' ),
		);
		
		foreach ( $updates as $version => $callbacks ) {
			if ( version_compare( $current_version, $version, '<' ) ) {
				foreach ( $callbacks as $callback ) {
					if ( method_exists( __CLASS__, $callback ) ) {
						self::{$callback}();
					}
				}
			}
		}
		
		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			update_option( 'aria_db_version', self::DB_VERSION );
		}
	}

	/**
	 * Update to version 1.1.0
	 * Add context and response_instructions fields to knowledge base
	 */
	private static function update_to_1_1_0() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aria_knowledge_base';
		
		// Check if columns already exist
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name' AND COLUMN_NAME IN ('context', 'response_instructions')" );
		
		if ( count( $row ) < 2 ) {
			// Add context column if it doesn't exist
			$context_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'context'" );
			if ( empty( $context_exists ) ) {
				$wpdb->query( "ALTER TABLE $table_name ADD COLUMN context TEXT AFTER content" );
			}
			
			// Add response_instructions column if it doesn't exist
			$instructions_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'response_instructions'" );
			if ( empty( $instructions_exists ) ) {
				$wpdb->query( "ALTER TABLE $table_name ADD COLUMN response_instructions TEXT AFTER context" );
			}
		}
	}
	
	/**
	 * Update to version 1.1.1
	 * Add guest_phone field to conversations table
	 */
	private static function update_to_1_1_1() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'aria_conversations';
		
		// Check if column already exists
		$phone_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'guest_phone'" );
		
		if ( empty( $phone_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN guest_phone varchar(50) AFTER guest_email" );
		}
	}

	/**
	 * Update to version 1.2.0
	 * Implement vector search system
	 */
	private static function update_to_1_2_0() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Create vector system tables
		self::create_vector_tables();

		// Migrate existing knowledge base data
		self::migrate_knowledge_base_data();

		// Add vector system options
		self::add_vector_options();

		// Schedule background processing
		self::schedule_migration_processing();

		Aria_Logger::debug( 'Updated database to version 1.2.0 (Vector Search System)' );
	}

	/**
	 * Create vector system tables
	 */
	private static function create_vector_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Knowledge base entries (enhanced)
		$table_knowledge = $wpdb->prefix . 'aria_knowledge_entries';
		$sql_knowledge = "CREATE TABLE IF NOT EXISTS $table_knowledge (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			content longtext NOT NULL,
			context text,
			response_instructions text,
			category varchar(100),
			tags text,
			language varchar(10) DEFAULT 'en',
			priority int DEFAULT 0,
			status varchar(20) DEFAULT 'active',
			total_chunks int DEFAULT 0,
			last_processed datetime,
			site_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY site_id (site_id),
			KEY category_priority (category, priority),
			KEY status (status),
			KEY language (language),
			FULLTEXT KEY title_content (title, content)
		) $charset_collate;";

		// Processed chunks with vector embeddings
		$table_chunks = $wpdb->prefix . 'aria_knowledge_chunks';
		$sql_chunks = "CREATE TABLE IF NOT EXISTS $table_chunks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) NOT NULL,
			chunk_text text NOT NULL,
			chunk_embedding json NOT NULL,
			chunk_index int NOT NULL,
			chunk_length int NOT NULL,
			has_overlap boolean DEFAULT false,
			similarity_cluster int,
			usage_count int DEFAULT 0,
			last_used datetime,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entry_id (entry_id),
			KEY chunk_index (chunk_index),
			KEY usage_count (usage_count),
			KEY similarity_cluster (similarity_cluster),
			FULLTEXT KEY chunk_text (chunk_text)
		) $charset_collate;";

		// Search performance cache
		$table_search_cache = $wpdb->prefix . 'aria_search_cache';
		$sql_search_cache = "CREATE TABLE IF NOT EXISTS $table_search_cache (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			query_hash varchar(64) NOT NULL,
			query_text varchar(500) NOT NULL,
			result_chunks json NOT NULL,
			similarity_scores json NOT NULL,
			hit_count int DEFAULT 1,
			last_accessed datetime DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime,
			PRIMARY KEY (id),
			UNIQUE KEY query_hash (query_hash),
			KEY last_accessed (last_accessed),
			KEY expires_at (expires_at)
		) $charset_collate;";

		// Performance analytics
		$table_search_analytics = $wpdb->prefix . 'aria_search_analytics';
		$sql_search_analytics = "CREATE TABLE IF NOT EXISTS $table_search_analytics (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			query_text varchar(500) NOT NULL,
			chunks_found int NOT NULL,
			avg_similarity float NOT NULL,
			response_time_ms int NOT NULL,
			result_quality enum('excellent','good','fair','poor') NOT NULL,
			user_feedback enum('helpful','unhelpful','no_feedback') DEFAULT 'no_feedback',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY created_at (created_at),
			KEY result_quality (result_quality),
			KEY response_time_ms (response_time_ms)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_knowledge );
		dbDelta( $sql_chunks );
		dbDelta( $sql_search_cache );
		dbDelta( $sql_search_analytics );
	}

	/**
	 * Migrate existing knowledge base data to new structure
	 */
	private static function migrate_knowledge_base_data() {
		global $wpdb;

		$old_table = $wpdb->prefix . 'aria_knowledge_base';
		$new_table = $wpdb->prefix . 'aria_knowledge_entries';

		// Check if old table exists and has data
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$old_table}'" );
		
		if ( ! $table_exists ) {
			return; // No data to migrate
		}

		// Check if migration already happened
		$migrated_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );
		if ( $migrated_count > 0 ) {
			return; // Already migrated
		}

		// Get all existing knowledge base entries
		$existing_entries = $wpdb->get_results(
			"SELECT * FROM {$old_table}",
			ARRAY_A
		);

		if ( empty( $existing_entries ) ) {
			return; // No data to migrate
		}

		Aria_Logger::debug( 'Migrating ' . count( $existing_entries ) . ' knowledge base entries to vector system' );

		// Migrate each entry
		foreach ( $existing_entries as $entry ) {
			$wpdb->insert(
				$new_table,
				array(
					'title' => $entry['title'],
					'content' => $entry['content'],
					'context' => $entry['context'] ?? '',
					'response_instructions' => $entry['response_instructions'] ?? '',
					'category' => $entry['category'] ?? '',
					'tags' => $entry['tags'] ?? '',
					'language' => $entry['language'] ?? 'en',
					'priority' => 0,
					'status' => 'pending_processing',
					'total_chunks' => 0,
					'site_id' => $entry['site_id'],
					'created_at' => $entry['created_at'],
					'updated_at' => $entry['updated_at']
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Add vector system options
	 */
	private static function add_vector_options() {
		$vector_options = array(
			'aria_vector_enabled' => true,
			'aria_vector_chunk_size' => 500,
			'aria_vector_overlap_size' => 50,
			'aria_vector_similarity_threshold' => 0.7,
			'aria_vector_cache_enabled' => true,
			'aria_vector_cache_ttl' => 3600,
			'aria_vector_background_processing' => true
		);

		foreach ( $vector_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $option_value );
			}
		}
	}

	/**
	 * Schedule background processing for migrated entries
	 */
	private static function schedule_migration_processing() {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';

		// Get entries that need processing
		$pending_entries = $wpdb->get_col(
			"SELECT id FROM {$entries_table} WHERE status = 'pending_processing'"
		);

		if ( empty( $pending_entries ) ) {
			return;
		}

		// Schedule processing for each entry
		foreach ( $pending_entries as $entry_id ) {
			wp_schedule_single_event(
				time() + ( rand( 60, 300 ) ), // Stagger processing over 5 minutes
				'aria_process_migrated_entry',
				array( $entry_id )
			);
		}

		Aria_Logger::debug( 'Scheduled processing for ' . count( $pending_entries ) . ' migrated entries' );
	}

	/**
	 * Process migrated entry (background task)
	 */
	public static function process_migrated_entry( $entry_id ) {
		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-knowledge-processor.php';
			
			$processor = new Aria_Knowledge_Processor();
			$success = $processor->reprocess_knowledge_entry( $entry_id );

			if ( $success ) {
				Aria_Logger::debug( "Successfully processed migrated entry {$entry_id}" );
			} else {
				Aria_Logger::error( "Failed to process migrated entry {$entry_id}" );
			}

		} catch ( Exception $e ) {
			Aria_Logger::error( "Error processing migrated entry {$entry_id}: " . $e->getMessage() );
		}
	}

	/**
	 * Update to version 1.3.0
	 * Add content vectors table for WordPress content indexing
	 */
	private static function update_to_1_3_0() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Content vectors table for WordPress content indexing
		$table_content_vectors = $wpdb->prefix . 'aria_content_vectors';
		$sql_content_vectors = "CREATE TABLE IF NOT EXISTS $table_content_vectors (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			content_id bigint(20) NOT NULL,
			content_type varchar(20) NOT NULL,
			chunk_index int NOT NULL DEFAULT 0,
			content_text text NOT NULL,
			content_vector json NOT NULL,
			metadata json,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_content (content_id, content_type),
			KEY idx_type (content_type),
			KEY idx_chunk (content_id, chunk_index),
			FULLTEXT KEY content_text (content_text)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_content_vectors );

		// Add content indexing options
		$content_options = array(
			'aria_content_indexing_enabled' => true,
			'aria_content_types_to_index' => array( 'post', 'page', 'product' ),
			'aria_content_privacy_mode' => 'public_only',
			'aria_content_max_chunk_size' => 6000,
			'aria_content_similarity_threshold' => 0.7,
		);

		foreach ( $content_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $option_value );
			}
		}

		// Schedule initial content indexing
		wp_schedule_single_event( time() + 60, 'aria_initial_content_indexing' );

		Aria_Logger::debug( 'Updated database to version 1.3.0 (WordPress Content Vectorization)' );
	}

	/**
	 * Update to version 1.4.0
	 * Normalize conversation logs to use "role" keys.
	 */
	private static function update_to_1_4_0() {
		global $wpdb;

		$table = $wpdb->prefix . 'aria_conversations';
		$ids   = $wpdb->get_col( "SELECT id FROM {$table} WHERE conversation_log IS NOT NULL AND conversation_log <> ''" );

		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $conversation_id ) {
			$conversation_log = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT conversation_log FROM {$table} WHERE id = %d",
					$conversation_id
				)
			);

			$messages = json_decode( $conversation_log, true );
			if ( ! is_array( $messages ) ) {
				continue;
			}

			$updated = false;

			foreach ( $messages as &$message ) {
				if ( isset( $message['role'] ) || isset( $message['sender'] ) ) {
					$role = isset( $message['role'] ) ? $message['role'] : $message['sender'];
					$role = trim( strtolower( $role ) );
					$mapped_role = ( 'assistant' === $role ) ? 'assistant' : $role;

					if ( ! isset( $message['role'] ) || $message['role'] !== $mapped_role ) {
						$message['role'] = $mapped_role;
						$updated = true;
					}
					if ( ! isset( $message['sender'] ) || $message['sender'] !== $mapped_role ) {
						$message['sender'] = $mapped_role;
						$updated = true;
					}
				}
			}
			unset( $message );

			if ( $updated ) {
				$wpdb->update(
					$table,
					array( 'conversation_log' => wp_json_encode( $messages ) ),
					array( 'id' => $conversation_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}

		Aria_Logger::debug( 'Normalized conversation logs to role-based format' );
	}
}
