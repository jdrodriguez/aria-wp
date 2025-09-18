<?php
/**
 * Enhanced query handler with multi-stage retrieval
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle intelligent query processing with vector and keyword search.
 */
class Aria_Query_Handler {

	/**
	 * Vector engine instance.
	 *
	 * @var Aria_Vector_Engine
	 */
	private $vector_engine;

	/**
	 * Cache manager instance.
	 *
	 * @var Aria_Cache_Manager
	 */
	private $cache_manager;

	/**
	 * Analytics tracker instance.
	 *
	 * @var Aria_Analytics_Tracker
	 */
	private $analytics_tracker;

	/**
	 * Maximum context window size.
	 *
	 * @var int
	 */
	private $context_limit = 3000;

	/**
	 * Constructor.
	 */
	public function __construct() {
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-vector-engine.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-cache-manager.php';
		
		$this->vector_engine = new Aria_Vector_Engine();
		$this->cache_manager = new Aria_Cache_Manager();
		$this->analytics_tracker = new Aria_Analytics_Tracker();
	}

	/**
	 * Find relevant context for a user question.
	 *
	 * @param string $user_question User's question.
	 * @param array  $conversation_history Previous conversation context.
	 * @return string Formatted context for AI.
	 */
	public function find_relevant_context( $user_question, $conversation_history = array() ) {
		$start_time = microtime( true );
		
		try {
			// Stage 1: Check cache for similar queries
			if ( get_option( 'aria_vector_cache_enabled', true ) ) {
				$cached_result = $this->cache_manager->get_similar_query( $user_question );
				if ( $cached_result && $cached_result['confidence'] > 0.9 ) {
					$this->analytics_tracker->record_cache_hit( $user_question );
					return $cached_result['context'];
				}
			}
			
			// Stage 2: Enhance query with conversation context
			$enhanced_query = $this->enhance_query_with_context( $user_question, $conversation_history );
			
			// Stage 3: Vector similarity search (if enabled)
			$vector_results = array();
			if ( get_option( 'aria_vector_enabled', true ) ) {
				$similarity_threshold = get_option( 'aria_vector_similarity_threshold', 0.7 );
				$vector_results = $this->vector_engine->search_similar_content( $enhanced_query, 15, $similarity_threshold );
			}
			
			// Stage 4: Keyword fallback for specific terms
			$keyword_results = $this->keyword_search( $user_question );
			
			// Stage 5: Merge and rank all results
			$merged_results = $this->merge_and_rank_results( $vector_results, $keyword_results, $user_question );
			
			// Stage 6: Optimize for context window
			$optimized_context = $this->optimize_context_window( $merged_results );
			
			// Stage 7: Cache high-quality results
			if ( get_option( 'aria_vector_cache_enabled', true ) ) {
				$this->cache_manager->store_result( $user_question, $optimized_context, $merged_results );
			}
			
			// Stage 8: Record analytics
			$processing_time = ( microtime( true ) - $start_time ) * 1000;
			$this->analytics_tracker->record_search( $user_question, $merged_results, $processing_time );
			
			return $optimized_context;
			
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Query Handler Error: ' . $e->getMessage() );
			
			// Fallback to basic keyword search
			$fallback_results = $this->keyword_search( $user_question );
			return $this->format_context_for_ai( array_slice( $fallback_results, 0, 3 ) );
		}
	}

	/**
	 * Enhance query with conversation context.
	 *
	 * @param string $query Original query.
	 * @param array  $history Conversation history.
	 * @return string Enhanced query.
	 */
	private function enhance_query_with_context( $query, $history ) {
		if ( empty( $history ) ) {
			return $query;
		}
		
		// Extract relevant topics from recent conversation
		$recent_topics = $this->extract_topics_from_history( $history, 3 );
		
		if ( ! empty( $recent_topics ) ) {
			return $query . ' (Context: discussing ' . implode( ', ', $recent_topics ) . ')';
		}
		
		return $query;
	}

	/**
	 * Extract topics from conversation history.
	 *
	 * @param array $history Conversation history.
	 * @param int   $limit Maximum number of messages to analyze.
	 * @return array Extracted topics.
	 */
	private function extract_topics_from_history( $history, $limit = 3 ) {
		$topics = array();
		$recent_messages = array_slice( $history, -$limit );
		
		foreach ( $recent_messages as $message ) {
			$role = isset( $message['role'] ) ? $message['role'] : ( isset( $message['sender'] ) ? $message['sender'] : '' );
			if ( isset( $message['content'] ) && 'user' === $role ) {
				// Simple topic extraction using common keywords
				$content = strtolower( $message['content'] );
				$keywords = $this->extract_keywords( $content );
				$topics = array_merge( $topics, $keywords );
			}
		}
		
		// Remove duplicates and return unique topics
		return array_unique( array_filter( $topics ) );
	}

	/**
	 * Extract keywords from text.
	 *
	 * @param string $text Input text.
	 * @return array Extracted keywords.
	 */
	private function extract_keywords( $text ) {
		// Remove common stop words
		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'about', 'how', 'what', 'when', 'where', 'why', 'who' );
		
		$words = str_word_count( $text, 1 );
		$keywords = array();
		
		foreach ( $words as $word ) {
			$word = trim( strtolower( $word ) );
			if ( strlen( $word ) > 3 && ! in_array( $word, $stop_words ) ) {
				$keywords[] = $word;
			}
		}
		
		return array_slice( $keywords, 0, 5 ); // Return top 5 keywords
	}

	/**
	 * Perform keyword search as fallback.
	 *
	 * @param string $query Search query.
	 * @return array Search results.
	 */
	private function keyword_search( $query ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		
		// Check if vector system is available
		$chunks_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$chunks_table}'" );
		
		if ( $chunks_exist ) {
			// Search in chunks if available
			return $this->search_chunks_by_keywords( $query );
		} else {
			// Fallback to legacy knowledge base search
			return $this->search_legacy_knowledge_base( $query );
		}
	}

	/**
	 * Search chunks by keywords.
	 *
	 * @param string $query Search query.
	 * @return array Search results.
	 */
	private function search_chunks_by_keywords( $query ) {
		global $wpdb;
		
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		$keywords = explode( ' ', strtolower( $query ) );
		$where_conditions = array();
		
		foreach ( $keywords as $keyword ) {
			if ( strlen( $keyword ) > 2 ) {
				$escaped_keyword = $wpdb->esc_like( $keyword );
				$where_conditions[] = $wpdb->prepare( 
					'(c.chunk_text LIKE %s OR e.title LIKE %s)', 
					'%' . $escaped_keyword . '%', 
					'%' . $escaped_keyword . '%'
				);
			}
		}
		
		if ( empty( $where_conditions ) ) {
			return array();
		}
		
		$where_clause = implode( ' OR ', $where_conditions );
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.chunk_text as text, c.id, e.title as entry_title, e.id as entry_id
				FROM {$chunks_table} c
				JOIN {$entries_table} e ON c.entry_id = e.id
				WHERE e.status = 'active' AND e.site_id = %d AND ({$where_clause})
				ORDER BY c.usage_count DESC, e.priority DESC
				LIMIT 10",
				get_current_blog_id()
			),
			ARRAY_A
		);
		
		// Add relevance scores
		foreach ( $results as &$result ) {
			$result['relevance_score'] = $this->calculate_keyword_relevance( $result['text'], $query );
			$result['source'] = 'keyword';
		}
		
		return $results;
	}

	/**
	 * Search legacy knowledge base.
	 *
	 * @param string $query Search query.
	 * @return array Search results.
	 */
	private function search_legacy_knowledge_base( $query ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'aria_knowledge_base';
		
		$keywords = explode( ' ', strtolower( $query ) );
		$where_conditions = array();
		
		foreach ( $keywords as $keyword ) {
			if ( strlen( $keyword ) > 2 ) {
				$escaped_keyword = $wpdb->esc_like( $keyword );
				$where_conditions[] = $wpdb->prepare( 
					'(title LIKE %s OR content LIKE %s OR context LIKE %s OR response_instructions LIKE %s OR tags LIKE %s)', 
					'%' . $escaped_keyword . '%', 
					'%' . $escaped_keyword . '%',
					'%' . $escaped_keyword . '%',
					'%' . $escaped_keyword . '%',
					'%' . $escaped_keyword . '%'
				);
			}
		}
		
		if ( empty( $where_conditions ) ) {
			return array();
		}
		
		$where_clause = implode( ' OR ', $where_conditions );
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT title, content as text, id, title as entry_title
				FROM {$table} 
				WHERE site_id = %d AND ({$where_clause})
				LIMIT 5",
				get_current_blog_id()
			),
			ARRAY_A
		);
		
		// Add relevance scores
		foreach ( $results as &$result ) {
			$result['relevance_score'] = $this->calculate_keyword_relevance( $result['text'], $query );
			$result['source'] = 'keyword';
		}
		
		return $results;
	}

	/**
	 * Calculate keyword relevance score.
	 *
	 * @param string $text Text content.
	 * @param string $query Search query.
	 * @return float Relevance score.
	 */
	private function calculate_keyword_relevance( $text, $query ) {
		$text_lower = strtolower( $text );
		$query_lower = strtolower( $query );
		$keywords = explode( ' ', $query_lower );
		
		$matches = 0;
		$total_keywords = 0;
		
		foreach ( $keywords as $keyword ) {
			if ( strlen( $keyword ) > 2 ) {
				$total_keywords++;
				if ( strpos( $text_lower, $keyword ) !== false ) {
					$matches++;
				}
			}
		}
		
		return $total_keywords > 0 ? $matches / $total_keywords : 0;
	}

	/**
	 * Merge and rank results from different sources.
	 *
	 * @param array  $vector_results Results from vector search.
	 * @param array  $keyword_results Results from keyword search.
	 * @param string $original_query Original search query.
	 * @return array Merged and ranked results.
	 */
	private function merge_and_rank_results( $vector_results, $keyword_results, $original_query ) {
		$all_results = array();
		
		// Add vector results with semantic scores
		foreach ( $vector_results as $result ) {
			$all_results[] = array(
				'text' => $result['text'],
				'relevance_score' => $result['similarity_score'],
				'source' => 'vector',
				'chunk_id' => $result['id'],
				'entry_title' => $result['entry_title'],
				'entry_id' => $result['entry_id'] ?? null
			);
		}
		
		// Add keyword results with keyword matching scores
		foreach ( $keyword_results as $result ) {
			$keyword_score = $this->calculate_keyword_relevance( $result['text'], $original_query );
			
			$all_results[] = array(
				'text' => $result['text'],
				'relevance_score' => $keyword_score,
				'source' => 'keyword',
				'chunk_id' => $result['id'],
				'entry_title' => $result['entry_title'],
				'entry_id' => $result['entry_id'] ?? null
			);
		}
		
		// Remove duplicates and sort by relevance
		$unique_results = $this->remove_duplicate_chunks( $all_results );
		usort( $unique_results, function( $a, $b ) {
			return $b['relevance_score'] <=> $a['relevance_score'];
		} );
		
		return $unique_results;
	}

	/**
	 * Remove duplicate chunks from results.
	 *
	 * @param array $results Search results.
	 * @return array Deduplicated results.
	 */
	private function remove_duplicate_chunks( $results ) {
		$seen_texts = array();
		$unique_results = array();
		
		foreach ( $results as $result ) {
			$text_hash = md5( $result['text'] );
			
			if ( ! isset( $seen_texts[ $text_hash ] ) ) {
				$seen_texts[ $text_hash ] = true;
				$unique_results[] = $result;
			}
		}
		
		return $unique_results;
	}

	/**
	 * Optimize context window for AI consumption.
	 *
	 * @param array $results Search results.
	 * @return string Optimized context.
	 */
	private function optimize_context_window( $results ) {
		$optimized_chunks = array();
		$current_length = 0;
		$min_relevance = 0.5;
		
		foreach ( $results as $result ) {
			// Skip low-relevance results
			if ( $result['relevance_score'] < $min_relevance ) {
				continue;
			}
			
			$chunk_length = strlen( $result['text'] );
			
			// Check if chunk fits in remaining space
			if ( $current_length + $chunk_length <= $this->context_limit ) {
				$optimized_chunks[] = $result;
				$current_length += $chunk_length + 10; // +10 for separators
			} else {
				// Try to fit a truncated version if it's high relevance
				if ( $result['relevance_score'] > 0.8 ) {
					$available_space = $this->context_limit - $current_length - 10;
					if ( $available_space > 200 ) { // minimum useful chunk size
						$truncated = $this->smart_truncate( $result['text'], $available_space );
						$result['text'] = $truncated;
						$optimized_chunks[] = $result;
						break; // context window is full
					}
				}
				break;
			}
		}
		
		return $this->format_context_for_ai( $optimized_chunks );
	}

	/**
	 * Smart truncate text while preserving meaning.
	 *
	 * @param string $text Text to truncate.
	 * @param int    $max_length Maximum length.
	 * @return string Truncated text.
	 */
	private function smart_truncate( $text, $max_length ) {
		if ( strlen( $text ) <= $max_length ) {
			return $text;
		}
		
		// Try to break at sentence boundary
		$truncated = substr( $text, 0, $max_length );
		$last_period = strrpos( $truncated, '.' );
		$last_question = strrpos( $truncated, '?' );
		$last_exclamation = strrpos( $truncated, '!' );
		
		$last_sentence_end = max( $last_period, $last_question, $last_exclamation );
		
		if ( $last_sentence_end !== false && $last_sentence_end > $max_length * 0.7 ) {
			return substr( $text, 0, $last_sentence_end + 1 );
		}
		
		// Fallback to word boundary
		$last_space = strrpos( $truncated, ' ' );
		if ( $last_space !== false ) {
			return substr( $text, 0, $last_space ) . '...';
		}
		
		return $truncated . '...';
	}

	/**
	 * Format context for AI consumption.
	 *
	 * @param array $chunks Optimized chunks.
	 * @return string Formatted context.
	 */
	private function format_context_for_ai( $chunks ) {
		if ( empty( $chunks ) ) {
			return '';
		}
		
		$formatted_context = "=== RELEVANT KNOWLEDGE FROM DATABASE ===\n\n";
		
		foreach ( $chunks as $index => $chunk ) {
			$formatted_context .= "--- Knowledge Entry " . ( $index + 1 ) . " ---\n";
			$formatted_context .= "Source: {$chunk['entry_title']}\n";
			$formatted_context .= "Content: {$chunk['text']}\n";
			$formatted_context .= "Relevance: " . round( $chunk['relevance_score'] * 100 ) . "%\n";
			$formatted_context .= "Search Method: {$chunk['source']}\n\n";
		}
		
		$formatted_context .= "=== END OF KNOWLEDGE BASE ===\n\n";
		
		return $formatted_context;
	}

	/**
	 * Test query handler functionality.
	 *
	 * @return array Test results.
	 */
	public function test_query_handler() {
		$test_results = array(
			'basic_search' => false,
			'vector_integration' => false,
			'keyword_fallback' => false,
			'context_optimization' => false
		);

		try {
			// Test basic search
			$test_query = "How can I contact support?";
			$context = $this->find_relevant_context( $test_query );
			
			if ( ! empty( $context ) ) {
				$test_results['basic_search'] = true;
			}

			// Test vector integration
			if ( $this->vector_engine ) {
				$test_results['vector_integration'] = true;
			}

			// Test keyword fallback
			$keyword_results = $this->keyword_search( $test_query );
			if ( is_array( $keyword_results ) ) {
				$test_results['keyword_fallback'] = true;
			}

			// Test context optimization
			$mock_results = array(
				array( 'text' => 'Test content', 'relevance_score' => 0.8, 'source' => 'test', 'entry_title' => 'Test' )
			);
			$optimized = $this->optimize_context_window( $mock_results );
			if ( ! empty( $optimized ) ) {
				$test_results['context_optimization'] = true;
			}

		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Query Handler Test Error: ' . $e->getMessage() );
		}

		return $test_results;
	}
}

/**
 * Simple analytics tracker for search performance.
 */
class Aria_Analytics_Tracker {

	/**
	 * Record cache hit.
	 *
	 * @param string $query Search query.
	 */
	public function record_cache_hit( $query ) {
		// Implementation for cache hit tracking
		do_action( 'aria_cache_hit', $query );
	}

	/**
	 * Record search analytics.
	 *
	 * @param string $query Search query.
	 * @param array  $results Search results.
	 * @param float  $processing_time Processing time in milliseconds.
	 */
	public function record_search( $query, $results, $processing_time ) {
		global $wpdb;
		
		$analytics_table = $wpdb->prefix . 'aria_search_analytics';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$analytics_table}'" );
		
		if ( ! $table_exists ) {
			return;
		}
		
		$chunks_found = count( $results );
		$avg_similarity = 0;
		
		if ( $chunks_found > 0 ) {
			$total_similarity = array_sum( array_column( $results, 'relevance_score' ) );
			$avg_similarity = $total_similarity / $chunks_found;
		}
		
		// Determine result quality
		$result_quality = 'poor';
		if ( $avg_similarity > 0.8 ) {
			$result_quality = 'excellent';
		} elseif ( $avg_similarity > 0.6 ) {
			$result_quality = 'good';
		} elseif ( $avg_similarity > 0.4 ) {
			$result_quality = 'fair';
		}
		
		$wpdb->insert(
			$analytics_table,
			array(
				'query_text' => substr( $query, 0, 500 ),
				'chunks_found' => $chunks_found,
				'avg_similarity' => $avg_similarity,
				'response_time_ms' => round( $processing_time ),
				'result_quality' => $result_quality,
				'created_at' => current_time( 'mysql' )
			),
			array( '%s', '%d', '%f', '%d', '%s', '%s' )
		);
	}
}
