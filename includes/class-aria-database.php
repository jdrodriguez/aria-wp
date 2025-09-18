<?php
/**
 * Database operations handler
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle database operations for the plugin.
 */
class Aria_Database {

	/**
	 * Get conversation by ID.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array|null Conversation data or null.
	 */
	public static function get_conversation( $conversation_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$conversation_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get conversations list.
	 *
	 * @param array $args Query arguments.
	 * @return array Conversations list.
	 */
	public static function get_conversations( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$defaults = array(
			'site_id'  => get_current_blog_id(),
			'status'   => '',
			'search'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'limit'    => 20,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array();
		$where[] = $wpdb->prepare( 'site_id = %d', $args['site_id'] );

		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare(
				'(guest_name LIKE %s OR guest_email LIKE %s OR initial_question LIKE %s)',
				$search,
				$search,
				$search
			);
		}

		$where_clause = implode( ' AND ', $where );
		$order_clause = sprintf( '%s %s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) );

		$sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_clause LIMIT %d OFFSET %d";

		return $wpdb->get_results(
			$wpdb->prepare( $sql, $args['limit'], $args['offset'] ),
			ARRAY_A
		);
	}

	/**
	 * Get conversation messages.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array Messages array.
	 */
	public static function get_conversation_messages( $conversation_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$conversation_log = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT conversation_log FROM $table WHERE id = %d",
				$conversation_id
			)
		);

		if ( ! $conversation_log ) {
			return array();
		}

		$messages = json_decode( $conversation_log, true );
		if ( ! is_array( $messages ) ) {
			return array();
		}

		foreach ( $messages as &$message ) {
			$role = isset( $message['role'] ) ? $message['role'] : ( isset( $message['sender'] ) ? $message['sender'] : 'aria' );
			$role = trim( strtolower( $role ) );
			$message['role'] = $role ?: 'aria';
			$message['sender'] = $message['role'];
		}
		unset( $message );

		return $messages;
	}

	/**
	 * Get conversation count.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function get_conversations_count( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$defaults = array(
			'site_id' => get_current_blog_id(),
			'status'  => '',
			'search'  => '',
			'date_from' => '',
			'date_to' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array();
		$where[] = $wpdb->prepare( 'site_id = %d', $args['site_id'] );

		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare(
				'(guest_name LIKE %s OR guest_email LIKE %s OR initial_question LIKE %s)',
				$search,
				$search,
				$search
			);
		}

		// Add date filtering support
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = $wpdb->prepare( 'created_at >= %s', $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = $wpdb->prepare( 'created_at < %s', $args['date_to'] );
		}

		$where_clause = implode( ' AND ', $where );

		$sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";
		
		// Debug logging for date-filtered queries
		if ( ! empty( $args['date_from'] ) || ! empty( $args['date_to'] ) ) {
			Aria_Logger::debug( "Aria Database Query: $sql" );
			Aria_Logger::debug( 'Aria Date Filter Args: ' . wp_json_encode( array(
				'date_from' => $args['date_from'],
				'date_to' => $args['date_to'],
				'status' => $args['status']
			) ) );
		}

		$result = (int) $wpdb->get_var( $sql );
		
		// Log the result for debugging
		if ( ! empty( $args['date_from'] ) || ! empty( $args['date_to'] ) ) {
			Aria_Logger::debug( "Aria Query Result: $result conversations found" );
		}

		return $result;
	}

	/**
	 * Update conversation.
	 *
	 * @param int   $conversation_id Conversation ID.
	 * @param array $data Data to update.
	 * @return bool Success status.
	 */
	public static function update_conversation( $conversation_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$data['updated_at'] = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			$data,
			array( 'id' => $conversation_id ),
			null,
			array( '%d' )
		);
	}

	/**
	 * Delete conversation.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return bool Success status.
	 */
	public static function delete_conversation( $conversation_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		// Also delete related learning data
		$learning_table = $wpdb->prefix . 'aria_learning_data';
		$wpdb->delete(
			$learning_table,
			array( 'conversation_id' => $conversation_id ),
			array( '%d' )
		);

		return false !== $wpdb->delete(
			$table,
			array( 'id' => $conversation_id ),
			array( '%d' )
		);
	}

	/**
	 * Get knowledge base entries.
	 *
	 * @param array $args Query arguments.
	 * @return array Knowledge entries.
	 */
	public static function get_knowledge_entries( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_entries';

		$defaults = array(
			'site_id'  => get_current_blog_id(),
			'category' => '',
			'language' => '',
			'search'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'limit'    => 20,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array();
		$where[] = $wpdb->prepare( 'site_id = %d', $args['site_id'] );

		if ( ! empty( $args['category'] ) ) {
			$where[] = $wpdb->prepare( 'category = %s', $args['category'] );
		}

		if ( ! empty( $args['language'] ) ) {
			$where[] = $wpdb->prepare( 'language = %s', $args['language'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare(
				'(title LIKE %s OR content LIKE %s OR context LIKE %s OR response_instructions LIKE %s OR tags LIKE %s)',
				$search,
				$search,
				$search,
				$search,
				$search
			);
		}

		$where_clause = implode( ' AND ', $where );
		$order_clause = sprintf( '%s %s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) );

		$sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_clause LIMIT %d OFFSET %d";

		return $wpdb->get_results(
			$wpdb->prepare( $sql, $args['limit'], $args['offset'] ),
			ARRAY_A
		);
	}

	/**
	 * Get knowledge entry by ID.
	 *
	 * @param int $entry_id Entry ID.
	 * @return array|null Knowledge entry or null.
	 */
	public static function get_knowledge_entry( $entry_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_entries';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$entry_id
			),
			ARRAY_A
		);
	}

	/**
	 * Save knowledge entry.
	 *
	 * @param array $data Entry data.
	 * @param int   $entry_id Entry ID for update.
	 * @return int|bool Entry ID or false.
	 */
	public static function save_knowledge_entry( $data, $entry_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_entries';

		$data['site_id'] = get_current_blog_id();

		if ( $entry_id > 0 ) {
			$data['updated_at'] = current_time( 'mysql' );
			$data['status'] = 'pending_processing'; // Trigger reprocessing
			$result = $wpdb->update(
				$table,
				$data,
				array( 'id' => $entry_id ),
				null,
				array( '%d' )
			);
			
			// Process immediately or schedule background processing
			if ( false !== $result ) {
				try {
					require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
					$processor = Aria_Background_Processor::instance();
					
					// Try immediate processing first
					$processed_immediately = false;
					
					if ( ! wp_doing_cron() ) {
						try {
							$process_result = $processor->process_embeddings_async( $entry_id );
							$processed_immediately = ( false !== $process_result );
							
							if ( $processed_immediately ) {
								Aria_Logger::debug( "Aria: Successfully processed updated entry {$entry_id} immediately" );
							}
						} catch ( Exception $e ) {
							Aria_Logger::error( "Aria: Immediate processing failed for updated entry {$entry_id}, falling back to scheduling: " . $e->getMessage() );
						}
					}
					
					// If immediate processing failed, schedule it
					if ( ! $processed_immediately ) {
						$processor->schedule_embedding_generation( $entry_id );
						Aria_Logger::debug( "Aria: Scheduled background processing for updated entry {$entry_id}" );
					}
					
				} catch ( Exception $e ) {
					Aria_Logger::error( "Aria: Failed to process/schedule updated entry {$entry_id}: " . $e->getMessage() );
				}
			}
			
			return false !== $result ? $entry_id : false;
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$data['status'] = 'pending_processing';
			$result = $wpdb->insert( $table, $data );
			
			// Process immediately or schedule background processing
			if ( false !== $result ) {
				$new_id = $wpdb->insert_id;
				try {
					require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
					$processor = Aria_Background_Processor::instance();
					
					// Always try immediate processing first, with fallback to scheduling
					$processed_immediately = false;
					
					// Try to process immediately if not in a restricted context
					if ( ! wp_doing_cron() ) {
						try {
							$result = $processor->process_embeddings_async( $new_id );
							$processed_immediately = ( false !== $result );
							
							if ( $processed_immediately ) {
								Aria_Logger::debug( "Aria: Successfully processed entry {$new_id} immediately" );
							}
						} catch ( Exception $e ) {
							Aria_Logger::error( "Aria: Immediate processing failed for entry {$new_id}, falling back to scheduling: " . $e->getMessage() );
						}
					}
					
					// If immediate processing failed or wasn't attempted, schedule it
					if ( ! $processed_immediately ) {
						$processor->schedule_embedding_generation( $new_id );
						Aria_Logger::debug( "Aria: Scheduled background processing for entry {$new_id}" );
					}
					
				} catch ( Exception $e ) {
					Aria_Logger::error( "Aria: Failed to process/schedule new entry {$new_id}: " . $e->getMessage() );
				}
				return $new_id;
			}
			
			return false;
		}
	}

	/**
	 * Delete knowledge entry.
	 *
	 * @param int $entry_id Entry ID.
	 * @return bool Success status.
	 */
	public static function delete_knowledge_entry( $entry_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_entries';
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';

		// Delete associated chunks first
		$wpdb->delete(
			$chunks_table,
			array( 'entry_id' => $entry_id ),
			array( '%d' )
		);

		return false !== $wpdb->delete(
			$table,
			array( 'id' => $entry_id ),
			array( '%d' )
		);
	}

	/**
	 * Get knowledge categories.
	 *
	 * @return array Categories list.
	 */
	public static function get_knowledge_categories() {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_knowledge_entries';

		$categories = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT category FROM $table WHERE site_id = %d AND category != '' ORDER BY category",
				get_current_blog_id()
			)
		);

		return $categories ? $categories : array();
	}

	/**
	 * Get conversation analytics.
	 *
	 * @param string $period Time period (day, week, month).
	 * @return array Analytics data.
	 */
	public static function get_conversation_analytics( $period = 'week' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_conversations';

		$date_format = '%Y-%m-%d';
		$interval = '7 DAY';

		switch ( $period ) {
			case 'day':
				$date_format = '%Y-%m-%d %H:00:00';
				$interval = '1 DAY';
				break;
			case 'month':
				$date_format = '%Y-%m-%d';
				$interval = '30 DAY';
				break;
		}

		$sql = $wpdb->prepare(
			"SELECT 
				DATE_FORMAT(created_at, %s) as period,
				COUNT(*) as total_conversations,
				COUNT(DISTINCT guest_email) as unique_visitors,
				AVG(CASE WHEN satisfaction_rating > 0 THEN satisfaction_rating ELSE NULL END) as avg_satisfaction,
				SUM(CASE WHEN requires_human_review = 1 THEN 1 ELSE 0 END) as needs_review,
				AVG(lead_score) as avg_lead_score
			FROM $table 
			WHERE site_id = %d 
				AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
			GROUP BY period
			ORDER BY period ASC",
			$date_format,
			get_current_blog_id()
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get popular questions.
	 *
	 * @param int $limit Number of questions to return.
	 * @return array Popular questions.
	 */
	public static function get_popular_questions( $limit = 10 ) {
		global $wpdb;
		$table_learning = $wpdb->prefix . 'aria_learning_data';

		$sql = $wpdb->prepare(
			"SELECT 
				question,
				COUNT(*) as frequency,
				AVG(response_quality_score) as avg_quality,
				SUM(CASE WHEN knowledge_gap = 1 THEN 1 ELSE 0 END) as knowledge_gaps
			FROM $table_learning
			WHERE site_id = %d
			GROUP BY question
			ORDER BY frequency DESC
			LIMIT %d",
			get_current_blog_id(),
			$limit
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get learning data.
	 *
	 * @param array $args Query arguments.
	 * @return array Learning data entries.
	 */
	public static function get_learning_data( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		$defaults = array(
			'site_id'         => get_current_blog_id(),
			'conversation_id' => 0,
			'limit'           => 20,
			'offset'          => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array();
		$where[] = $wpdb->prepare( 'site_id = %d', $args['site_id'] );

		if ( ! empty( $args['conversation_id'] ) ) {
			$where[] = $wpdb->prepare( 'conversation_id = %d', $args['conversation_id'] );
		}

		$where_clause = implode( ' AND ', $where );

		$sql = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";

		return $wpdb->get_results(
			$wpdb->prepare( $sql, $args['limit'], $args['offset'] ),
			ARRAY_A
		);
	}

	/**
	 * Get knowledge gaps.
	 *
	 * @param int $limit Number of gaps to return.
	 * @return array Knowledge gaps.
	 */
	public static function get_knowledge_gaps( $limit = 20 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		$sql = $wpdb->prepare(
			"SELECT 
				question,
				response,
				COUNT(*) as occurrences
			FROM $table
			WHERE site_id = %d AND knowledge_gap = 1
			GROUP BY question
			ORDER BY occurrences DESC
			LIMIT %d",
			get_current_blog_id(),
			$limit
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Clean up old data.
	 *
	 * @param int $days Number of days to keep.
	 * @return int Number of records deleted.
	 */
	public static function cleanup_old_data( $days = 90 ) {
		global $wpdb;
		
		$tables = array(
			$wpdb->prefix . 'aria_conversations',
			$wpdb->prefix . 'aria_learning_data',
		);

		$total_deleted = 0;

		foreach ( $tables as $table ) {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
					$days
				)
			);
			$total_deleted += $deleted;
		}

		return $total_deleted;
	}
}
