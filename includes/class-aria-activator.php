<?php
/**
 * Fired during plugin activation
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Aria_Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Create database tables, set default options, and schedule events.
	 */
	public static function activate() {
		self::check_database_version();

		// Create database tables
		self::create_tables();

		// Set default options
		self::set_default_options();

		// Create upload directories
		self::create_directories();

		// Schedule cron events
		self::schedule_events();

		// Schedule initial content indexing
		wp_schedule_single_event( time() + 60, 'aria_initial_content_indexing' );

		// Set activation flag
		set_transient( 'aria_activation_redirect', true, 30 );

		// Clear permalinks
		flush_rewrite_rules();
	}

	/**
	 * Ensure the database server meets minimum requirements.
	 */
	private static function check_database_version() {
		global $wpdb;

		$db_version_raw = $wpdb->get_var( 'SELECT VERSION()' );
		$db_version     = self::extract_version_number( $db_version_raw );
		$is_mariadb     = ( false !== stripos( (string) $db_version_raw, 'mariadb' ) );

		$minimum = $is_mariadb ? '10.4.0' : '5.7.0';

		if ( empty( $db_version ) || version_compare( $db_version, $minimum, '<' ) ) {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			deactivate_plugins( ARIA_PLUGIN_BASENAME );

			$message = $is_mariadb
				? __( 'Aria requires MariaDB 10.4 or higher. Please upgrade your database server and try again.', 'aria' )
				: __( 'Aria requires MySQL 5.7 or higher. Please upgrade your database server and try again.', 'aria' );

			wp_die( esc_html( $message ), esc_html__( 'Aria Activation Error', 'aria' ) );
		}
	}

	/**
	 * Extract numeric portion of version string.
	 *
	 * @param string $version_raw Raw version string from database.
	 * @return string Parsed version or empty string.
	 */
	private static function extract_version_number( $version_raw ) {
		if ( empty( $version_raw ) ) {
			return '';
		}

		if ( preg_match( '/(\d+\.\d+\.\d+)/', $version_raw, $matches ) ) {
			return $matches[1];
		}

		if ( preg_match( '/(\d+\.\d+)/', $version_raw, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Create plugin database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Knowledge base entries (enhanced for vector system)
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

		// Legacy knowledge base table (for backward compatibility)
		$table_knowledge_legacy = $wpdb->prefix . 'aria_knowledge_base';
		$sql_knowledge_legacy = "CREATE TABLE IF NOT EXISTS $table_knowledge_legacy (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			content longtext NOT NULL,
			context text,
			response_instructions text,
			category varchar(100),
			tags text,
			language varchar(10) DEFAULT 'en',
			site_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY site_id (site_id),
			KEY language (language)
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
			FULLTEXT KEY chunk_text (chunk_text),
			FOREIGN KEY (entry_id) REFERENCES $table_knowledge (id) ON DELETE CASCADE
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

		// Conversations table
		$table_conversations = $wpdb->prefix . 'aria_conversations';
		$sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL,
			guest_name varchar(255),
			guest_email varchar(255),
			guest_phone varchar(50),
			initial_question longtext,
			conversation_log longtext,
			status varchar(20) DEFAULT 'active',
			requires_human_review tinyint(1) DEFAULT 0,
			satisfaction_rating tinyint(1),
			lead_score int(3) DEFAULT 0,
			gdpr_consent tinyint(1) DEFAULT 0,
			conversation_metadata text,
			site_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY site_id (site_id),
			KEY status (status),
			KEY lead_score (lead_score)
		) $charset_collate;";

		// Personality settings table
		$table_personality = $wpdb->prefix . 'aria_personality_settings';
		$sql_personality = "CREATE TABLE IF NOT EXISTS $table_personality (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			site_id bigint(20) NOT NULL,
			business_type varchar(50),
			tone_setting varchar(50) DEFAULT 'professional',
			personality_traits text,
			custom_responses text,
			greeting_message text,
			farewell_message text,
			PRIMARY KEY (id),
			UNIQUE KEY site_id (site_id)
		) $charset_collate;";

		// Learning data table
		$table_learning = $wpdb->prefix . 'aria_learning_data';
		$sql_learning = "CREATE TABLE IF NOT EXISTS $table_learning (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20),
			question text,
			response text,
			response_quality_score int(3),
			user_feedback varchar(20),
			knowledge_gap tinyint(1) DEFAULT 0,
			site_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY site_id (site_id),
			KEY quality_score (response_quality_score)
		) $charset_collate;";

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

		// License table
		$table_license = $wpdb->prefix . 'aria_license';
		$sql_license = "CREATE TABLE IF NOT EXISTS $table_license (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			site_url varchar(255) NOT NULL,
			purchase_code varchar(100),
			license_status varchar(20) DEFAULT 'trial',
			trial_started datetime DEFAULT CURRENT_TIMESTAMP,
			license_activated datetime,
			license_expires datetime,
			last_check datetime,
			PRIMARY KEY (id),
			UNIQUE KEY site_url (site_url)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_knowledge );
		dbDelta( $sql_knowledge_legacy );
		dbDelta( $sql_chunks );
		dbDelta( $sql_search_cache );
		dbDelta( $sql_search_analytics );
		dbDelta( $sql_conversations );
		dbDelta( $sql_personality );
		dbDelta( $sql_learning );
		dbDelta( $sql_content_vectors );
		dbDelta( $sql_license );

		update_option( 'aria_db_version', ARIA_DB_VERSION );
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		$default_options = array(
			'aria_ai_provider'       => 'openai',
			'aria_widget_position'   => 'bottom-right',
			'aria_widget_enabled'    => true,
			'aria_primary_color'     => '#2563EB',
			'aria_chat_title'        => __( 'Hi! I\'m Aria', 'aria' ),
			'aria_placeholder_text'  => __( 'Type your message...', 'aria' ),
			'aria_gdpr_enabled'      => true,
			'aria_analytics_enabled' => true,
			'aria_debug_logging'     => false,
			'aria_trial_started'     => current_time( 'mysql' ),
		);

		foreach ( $default_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $option_value );
			}
		}

		// Initialize trial license
		global $wpdb;
		$table_license = $wpdb->prefix . 'aria_license';
		$wpdb->insert(
			$table_license,
			array(
				'site_url'       => get_site_url(),
				'license_status' => 'trial',
				'trial_started'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Create necessary directories.
	 */
	private static function create_directories() {
		$upload_dir = wp_upload_dir();
		$aria_dir   = $upload_dir['basedir'] . '/aria';

		// Create main directory
		if ( ! file_exists( $aria_dir ) ) {
			wp_mkdir_p( $aria_dir );
		}

		// Create subdirectories
		$subdirs = array( 'logs', 'exports', 'cache' );
		foreach ( $subdirs as $subdir ) {
			$dir_path = $aria_dir . '/' . $subdir;
			if ( ! file_exists( $dir_path ) ) {
				wp_mkdir_p( $dir_path );
			}
		}

		// Add index.php files for security
		$index_content = '<?php // Silence is golden';
		file_put_contents( $aria_dir . '/index.php', $index_content );
		foreach ( $subdirs as $subdir ) {
			file_put_contents( $aria_dir . '/' . $subdir . '/index.php', $index_content );
		}
	}

	/**
	 * Schedule cron events.
	 */
	private static function schedule_events() {
		// Schedule daily license check
		if ( ! wp_next_scheduled( 'aria_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'aria_daily_license_check' );
		}

		// Schedule hourly analytics processing
		if ( ! wp_next_scheduled( 'aria_process_analytics' ) ) {
			wp_schedule_event( time(), 'hourly', 'aria_process_analytics' );
		}
	}
}
