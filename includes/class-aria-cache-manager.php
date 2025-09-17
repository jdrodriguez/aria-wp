<?php
/**
 * Intelligent caching system for search results
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Multi-level cache management for search performance optimization.
 */
class Aria_Cache_Manager {

	/**
	 * Level 1 cache TTL (exact matches).
	 *
	 * @var int
	 */
	private $l1_cache_ttl = 300; // 5 minutes

	/**
	 * Level 2 cache TTL (similar queries).
	 *
	 * @var int
	 */
	private $l2_cache_ttl = 1800; // 30 minutes

	/**
	 * Level 3 cache TTL (topic-based cache).
	 *
	 * @var int
	 */
	private $l3_cache_ttl = 3600; // 1 hour

	/**
	 * Vector engine for similarity calculations.
	 *
	 * @var Aria_Vector_Engine
	 */
	private $vector_engine;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( class_exists( 'Aria_Vector_Engine' ) ) {
			$this->vector_engine = new Aria_Vector_Engine();
		}
	}

	/**
	 * Get cached result for similar query.
	 *
	 * @param string $query Search query.
	 * @return array|null Cached result or null.
	 */
	public function get_similar_query( $query ) {
		$query_hash = $this->normalize_query_for_matching( $query );
		
		// L1: Exact match cache
		$exact_match = wp_cache_get( "aria_exact_{$query_hash}", 'aria_responses' );
		if ( $exact_match ) {
			$this->record_cache_hit( 'L1', $query );
			return $exact_match;
		}
		
		// L2: Similar query cache (using query embeddings)
		$similar_match = $this->find_similar_cached_query( $query );
		if ( $similar_match && $similar_match['similarity'] > 0.85 ) {
			$this->record_cache_hit( 'L2', $query );
			return $similar_match['result'];
		}
		
		// L3: Topic-based cache
		$topic_match = $this->find_topic_based_cache( $query );
		if ( $topic_match ) {
			$this->record_cache_hit( 'L3', $query );
			return $topic_match;
		}
		
		return null;
	}

	/**
	 * Store search result in appropriate cache levels.
	 *
	 * @param string $query Search query.
	 * @param string $context Formatted context.
	 * @param array  $search_results Raw search results.
	 */
	public function store_result( $query, $context, $search_results ) {
		$query_hash = $this->normalize_query_for_matching( $query );
		$quality_score = $this->assess_result_quality( $search_results );
		
		$cache_data = array(
			'context' => $context,
			'confidence' => $quality_score,
			'search_stats' => array(
				'chunks_found' => count( $search_results ),
				'avg_relevance' => $this->calculate_avg_relevance( $search_results )
			),
			'timestamp' => time()
		);
		
		// Store in appropriate cache level based on quality
		if ( $quality_score > 0.9 ) {
			// High quality - store in all levels
			wp_cache_set( "aria_exact_{$query_hash}", $cache_data, 'aria_responses', $this->l1_cache_ttl );
			$this->store_in_similarity_cache( $query, $cache_data );
			$this->store_in_topic_cache( $query, $cache_data );
		} elseif ( $quality_score > 0.7 ) {
			// Good quality - store in L2 and L3
			$this->store_in_similarity_cache( $query, $cache_data );
			$this->store_in_topic_cache( $query, $cache_data );
		} elseif ( $quality_score > 0.5 ) {
			// Fair quality - store only in L3
			$this->store_in_topic_cache( $query, $cache_data );
		}
		
		// Also store in database for persistent cache
		$this->store_persistent_cache( $query_hash, $query, $cache_data );
	}

	/**
	 * Normalize query for consistent matching.
	 *
	 * @param string $query Original query.
	 * @return string Normalized query hash.
	 */
	private function normalize_query_for_matching( $query ) {
		// Convert to lowercase and remove punctuation
		$normalized = strtolower( trim( $query ) );
		$normalized = preg_replace( '/[^\w\s]/', '', $normalized );
		$normalized = preg_replace( '/\s+/', ' ', $normalized );
		
		return md5( $normalized );
	}

	/**
	 * Find similar cached query using vector similarity.
	 *
	 * @param string $query Search query.
	 * @return array|null Similar cached result or null.
	 */
	private function find_similar_cached_query( $query ) {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$cache_table}'" );
		if ( ! $table_exists ) {
			return null;
		}
		
		// Get recent cached queries
		$cached_queries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query_text, result_chunks, similarity_scores 
				FROM {$cache_table} 
				WHERE expires_at > NOW() 
				ORDER BY last_accessed DESC 
				LIMIT 50"
			),
			ARRAY_A
		);
		
		if ( empty( $cached_queries ) || ! $this->vector_engine ) {
			return null;
		}
		
		// Generate embedding for current query
		try {
			$query_embeddings = $this->vector_engine->generate_embeddings( array( $query ) );
			if ( empty( $query_embeddings ) ) {
				return null;
			}
			
			$query_embedding = $query_embeddings[0];
			$best_match = null;
			$best_similarity = 0;
			
			// Compare with cached queries (simplified approach)
			foreach ( $cached_queries as $cached_query ) {
				// For now, use simple text similarity as a proxy
				$text_similarity = $this->calculate_text_similarity( $query, $cached_query['query_text'] );
				
				if ( $text_similarity > $best_similarity ) {
					$best_similarity = $text_similarity;
					$best_match = array(
						'result' => array(
							'context' => 'Retrieved from similar cached query',
							'confidence' => $text_similarity
						),
						'similarity' => $text_similarity
					);
				}
			}
			
			return $best_match;
			
		} catch ( Exception $e ) {
			error_log( 'Aria Cache Manager: Vector similarity search failed: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Calculate text similarity between two strings.
	 *
	 * @param string $text1 First text.
	 * @param string $text2 Second text.
	 * @return float Similarity score.
	 */
	private function calculate_text_similarity( $text1, $text2 ) {
		$text1 = strtolower( trim( $text1 ) );
		$text2 = strtolower( trim( $text2 ) );
		
		if ( $text1 === $text2 ) {
			return 1.0;
		}
		
		// Simple word overlap calculation
		$words1 = array_unique( explode( ' ', $text1 ) );
		$words2 = array_unique( explode( ' ', $text2 ) );
		
		$intersection = array_intersect( $words1, $words2 );
		$union = array_unique( array_merge( $words1, $words2 ) );
		
		return count( $union ) > 0 ? count( $intersection ) / count( $union ) : 0;
	}

	/**
	 * Find topic-based cached result.
	 *
	 * @param string $query Search query.
	 * @return array|null Topic-based result or null.
	 */
	private function find_topic_based_cache( $query ) {
		// Extract topics from query
		$topics = $this->extract_query_topics( $query );
		
		if ( empty( $topics ) ) {
			return null;
		}
		
		// Look for cached results with similar topics
		foreach ( $topics as $topic ) {
			$cached_result = wp_cache_get( "aria_topic_{$topic}", 'aria_topics' );
			if ( $cached_result ) {
				return $cached_result;
			}
		}
		
		return null;
	}

	/**
	 * Extract topics from query.
	 *
	 * @param string $query Search query.
	 * @return array Extracted topics.
	 */
	private function extract_query_topics( $query ) {
		$query_lower = strtolower( $query );
		$topics = array();
		
		// Define topic keywords
		$topic_keywords = array(
			'support' => array( 'help', 'support', 'contact', 'assistance', 'problem', 'issue' ),
			'pricing' => array( 'price', 'cost', 'fee', 'payment', 'billing', 'charge' ),
			'features' => array( 'feature', 'function', 'capability', 'what can', 'how to' ),
			'account' => array( 'account', 'login', 'password', 'profile', 'user' ),
			'technical' => array( 'error', 'bug', 'not working', 'technical', 'system' ),
		);
		
		foreach ( $topic_keywords as $topic => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $query_lower, $keyword ) !== false ) {
					$topics[] = $topic;
					break;
				}
			}
		}
		
		return array_unique( $topics );
	}

	/**
	 * Store result in similarity cache.
	 *
	 * @param string $query Original query.
	 * @param array  $cache_data Cache data.
	 */
	private function store_in_similarity_cache( $query, $cache_data ) {
		// Store in memory cache with query-based key
		$similarity_key = "aria_similar_" . md5( $query );
		wp_cache_set( $similarity_key, $cache_data, 'aria_similar', $this->l2_cache_ttl );
	}

	/**
	 * Store result in topic cache.
	 *
	 * @param string $query Original query.
	 * @param array  $cache_data Cache data.
	 */
	private function store_in_topic_cache( $query, $cache_data ) {
		$topics = $this->extract_query_topics( $query );
		
		foreach ( $topics as $topic ) {
			wp_cache_set( "aria_topic_{$topic}", $cache_data, 'aria_topics', $this->l3_cache_ttl );
		}
	}

	/**
	 * Store result in persistent database cache.
	 *
	 * @param string $query_hash Query hash.
	 * @param string $query_text Original query.
	 * @param array  $cache_data Cache data.
	 */
	private function store_persistent_cache( $query_hash, $query_text, $cache_data ) {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$cache_table}'" );
		if ( ! $table_exists ) {
			return;
		}
		
		$expires_at = date( 'Y-m-d H:i:s', time() + get_option( 'aria_vector_cache_ttl', 3600 ) );
		
		// Insert or update cache entry
		$wpdb->replace(
			$cache_table,
			array(
				'query_hash' => $query_hash,
				'query_text' => substr( $query_text, 0, 500 ),
				'result_chunks' => wp_json_encode( $cache_data ),
				'similarity_scores' => wp_json_encode( array( 'confidence' => $cache_data['confidence'] ) ),
				'hit_count' => 1,
				'last_accessed' => current_time( 'mysql' ),
				'expires_at' => $expires_at
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Assess result quality for cache storage decisions.
	 *
	 * @param array $search_results Search results.
	 * @return float Quality score between 0 and 1.
	 */
	private function assess_result_quality( $search_results ) {
		if ( empty( $search_results ) ) {
			return 0;
		}
		
		$avg_relevance = $this->calculate_avg_relevance( $search_results );
		$top_result_relevance = $search_results[0]['relevance_score'] ?? 0;
		$result_count_factor = min( count( $search_results ) / 5, 1 ); // optimal around 5 results
		
		return ( $avg_relevance * 0.4 ) + ( $top_result_relevance * 0.5 ) + ( $result_count_factor * 0.1 );
	}

	/**
	 * Calculate average relevance of search results.
	 *
	 * @param array $search_results Search results.
	 * @return float Average relevance score.
	 */
	private function calculate_avg_relevance( $search_results ) {
		if ( empty( $search_results ) ) {
			return 0;
		}
		
		$total_relevance = 0;
		$count = 0;
		
		foreach ( $search_results as $result ) {
			if ( isset( $result['relevance_score'] ) ) {
				$total_relevance += $result['relevance_score'];
				$count++;
			}
		}
		
		return $count > 0 ? $total_relevance / $count : 0;
	}

	/**
	 * Record cache hit for analytics.
	 *
	 * @param string $cache_level Cache level (L1, L2, L3).
	 * @param string $query Original query.
	 */
	private function record_cache_hit( $cache_level, $query ) {
		// Update hit count in database if persistent cache exists
		if ( $cache_level === 'L1' ) {
			$this->update_persistent_cache_hit_count( $query );
		}
		
		// Trigger action for external analytics
		do_action( 'aria_cache_hit', $cache_level, $query );
		
		// Log for debugging
		error_log( "Aria Cache Hit: {$cache_level} - Query: " . substr( $query, 0, 50 ) );
	}

	/**
	 * Update persistent cache hit count.
	 *
	 * @param string $query Original query.
	 */
	private function update_persistent_cache_hit_count( $query ) {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		$query_hash = $this->normalize_query_for_matching( $query );
		
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$cache_table} 
				SET hit_count = hit_count + 1, last_accessed = NOW() 
				WHERE query_hash = %s",
				$query_hash
			)
		);
	}

	/**
	 * Clear expired cache entries.
	 */
	public function cleanup_expired_cache() {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$cache_table}'" );
		if ( ! $table_exists ) {
			return;
		}
		
		// Delete expired entries
		$deleted_count = $wpdb->query(
			"DELETE FROM {$cache_table} WHERE expires_at < NOW()"
		);
		
		// Also clean up old entries with low hit counts
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$cache_table} 
				WHERE last_accessed < DATE_SUB(NOW(), INTERVAL 7 DAY) 
				AND hit_count < %d",
				3
			)
		);
		
		if ( $deleted_count > 0 ) {
			error_log( "Aria Cache Cleanup: Removed {$deleted_count} expired cache entries" );
		}
	}

	/**
	 * Clear all cache for specific query pattern.
	 *
	 * @param string $pattern Query pattern to clear.
	 */
	public function clear_cache_pattern( $pattern ) {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$cache_table} WHERE query_text LIKE %s",
				'%' . $wpdb->esc_like( $pattern ) . '%'
			)
		);
		
		// Clear memory caches
		wp_cache_flush_group( 'aria_responses' );
		wp_cache_flush_group( 'aria_similar' );
		wp_cache_flush_group( 'aria_topics' );
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache statistics.
	 */
	public function get_cache_stats() {
		global $wpdb;
		
		$cache_table = $wpdb->prefix . 'aria_search_cache';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$cache_table}'" );
		if ( ! $table_exists ) {
			return array(
				'total_entries' => 0,
				'hit_rate' => 0,
				'avg_hit_count' => 0,
				'expired_entries' => 0
			);
		}
		
		$stats = array(
			'total_entries' => $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table}" ),
			'expired_entries' => $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table} WHERE expires_at < NOW()" ),
			'avg_hit_count' => $wpdb->get_var( "SELECT AVG(hit_count) FROM {$cache_table}" ),
			'total_hits' => $wpdb->get_var( "SELECT SUM(hit_count) FROM {$cache_table}" )
		);
		
		// Calculate hit rate (approximation)
		$recent_queries = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$cache_table} WHERE last_accessed > DATE_SUB(NOW(), INTERVAL 1 DAY)"
		);
		
		$stats['hit_rate'] = $recent_queries > 0 ? ( $stats['total_hits'] / $recent_queries ) * 100 : 0;
		
		return $stats;
	}

	/**
	 * Test cache manager functionality.
	 *
	 * @return array Test results.
	 */
	public function test_cache_manager() {
		$test_results = array(
			'basic_caching' => false,
			'cache_retrieval' => false,
			'quality_assessment' => false,
			'cleanup_function' => false
		);

		try {
			// Test basic caching
			$test_query = "test cache query";
			$test_context = "test context";
			$test_results_data = array(
				array( 'relevance_score' => 0.8, 'text' => 'test content' )
			);
			
			$this->store_result( $test_query, $test_context, $test_results_data );
			$test_results['basic_caching'] = true;

			// Test cache retrieval
			$cached_result = $this->get_similar_query( $test_query );
			if ( $cached_result ) {
				$test_results['cache_retrieval'] = true;
			}

			// Test quality assessment
			$quality = $this->assess_result_quality( $test_results_data );
			if ( $quality > 0 && $quality <= 1 ) {
				$test_results['quality_assessment'] = true;
			}

			// Test cleanup function
			$this->cleanup_expired_cache();
			$test_results['cleanup_function'] = true;

		} catch ( Exception $e ) {
			error_log( 'Aria Cache Manager Test Error: ' . $e->getMessage() );
		}

		return $test_results;
	}
}