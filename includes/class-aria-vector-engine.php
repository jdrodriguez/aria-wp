<?php
/**
 * Vector embedding and similarity search engine
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle vector embeddings and similarity search for the plugin.
 */
class Aria_Vector_Engine {

	/**
	 * OpenAI embedding model.
	 *
	 * @var string
	 */
	private $embedding_model = 'text-embedding-ada-002';

	/**
	 * Embedding dimension size.
	 *
	 * @var int
	 */
	private $embedding_dimension = 1536;

	/**
	 * Batch size for processing embeddings.
	 *
	 * @var int
	 */
	private $batch_size = 100;

	/**
	 * AI provider instance.
	 *
	 * @var object
	 */
	private $ai_provider;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// AI provider will be lazy-loaded when needed to prevent early wp_salt() calls
		$this->ai_provider = null;
	}

	/**
	 * Generate embeddings for text chunks.
	 *
	 * @param array $text_chunks Array of text chunks.
	 * @return array Array of embeddings.
	 */
	public function generate_embeddings( $text_chunks ) {
		if ( empty( $text_chunks ) ) {
			return array();
		}

		// Lazy-load AI provider if not already loaded
		if ( ! $this->ai_provider ) {
			$this->ai_provider = $this->get_ai_provider();
		}

		$all_embeddings = array();
		
		// Process in batches for efficiency
		$batches = array_chunk( $text_chunks, $this->batch_size );
		
		foreach ( $batches as $batch_index => $batch ) {
			try {
				$embeddings = $this->create_embedding_batch( $batch );
				$all_embeddings = array_merge( $all_embeddings, $embeddings );
				
				// Progress tracking for large knowledge bases
				$this->update_processing_progress( $batch_index + 1, count( $batches ) );
				
			} catch ( Exception $e ) {
				Aria_Logger::error( 'Vector Engine: Batch embedding failed: ' . $e->getMessage() );
				
				// Fallback to individual processing
				$individual_embeddings = $this->process_batch_individually( $batch );
				$all_embeddings = array_merge( $all_embeddings, $individual_embeddings );
			}
		}
		
		return $all_embeddings;
	}

	/**
	 * Create embeddings for a batch of texts.
	 *
	 * @param array $texts Array of text strings.
	 * @return array Array of embeddings.
	 */
	private function create_embedding_batch( $texts ) {
		if ( ! $this->ai_provider ) {
			throw new Exception( 'AI provider not available for embeddings' );
		}

		// Clean and prepare texts
		$cleaned_texts = array_map( array( $this, 'clean_text_for_embedding' ), $texts );
		
		// Generate embeddings using AI provider
		$response = $this->ai_provider->create_embeddings( $cleaned_texts );
		
		if ( ! $response || ! isset( $response['data'] ) ) {
			throw new Exception( 'Invalid embedding response from AI provider' );
		}

		return array_map( function( $embedding_data ) {
			return $embedding_data['embedding'];
		}, $response['data'] );
	}

	/**
	 * Process batch individually when batch processing fails.
	 *
	 * @param array $texts Array of text strings.
	 * @return array Array of embeddings.
	 */
	private function process_batch_individually( $texts ) {
		$embeddings = array();
		
		foreach ( $texts as $text ) {
			try {
				$embedding = $this->create_embedding_batch( array( $text ) );
				$embeddings[] = $embedding[0];
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Vector Engine: Individual embedding failed for text: ' . substr( $text, 0, 100 ) );
				// Use zero vector as fallback
				$embeddings[] = array_fill( 0, $this->embedding_dimension, 0.0 );
			}
		}
		
		return $embeddings;
	}

	/**
	 * Search for similar content using vector similarity.
	 *
	 * @param string $query Search query.
	 * @param int    $limit Maximum number of results.
	 * @param float  $similarity_threshold Minimum similarity score.
	 * @return array Array of similar chunks.
	 */
	public function search_similar_content( $query, $limit = 10, $similarity_threshold = 0.3 ) {
		try {
			// Generate query embedding
			$query_embeddings = $this->generate_embeddings( array( $query ) );
			
			if ( empty( $query_embeddings ) ) {
				return array();
			}
			
			$query_embedding = $query_embeddings[0];
			
			// Search database for similar vectors
			$similar_chunks = $this->vector_similarity_search( $query_embedding, $limit * 2 );
			
			// Filter by similarity threshold
			$filtered_results = array_filter( $similar_chunks, function( $chunk ) use ( $similarity_threshold ) {
				return $chunk['similarity_score'] >= $similarity_threshold;
			} );
			
			// Return top results
			return array_slice( $filtered_results, 0, $limit );
			
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Vector Engine: Search failed: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Perform vector similarity search in database.
	 *
	 * @param array $query_vector Query embedding vector.
	 * @param int   $limit Maximum number of results.
	 * @return array Array of similar chunks with scores.
	 */
	private function vector_similarity_search( $query_vector, $limit ) {
		global $wpdb;
		
		// Use optimized approach based on database capabilities
		if ( $this->has_native_vector_support() ) {
			return $this->native_vector_search( $query_vector, $limit );
		}
		
		// Fallback to PHP-based calculation
		return $this->php_vector_search( $query_vector, $limit );
	}

	/**
	 * Check if database has native vector support.
	 *
	 * @return bool True if native vector operations are available.
	 */
	private function has_native_vector_support() {
		global $wpdb;
		
		// Check MySQL version for JSON functions
		$version = $wpdb->get_var( 'SELECT VERSION()' );
		$mysql_version = floatval( $version );
		
		// MySQL 8.0+ has better JSON support
		return $mysql_version >= 8.0;
	}

	/**
	 * Native vector search using database JSON functions.
	 *
	 * @param array $query_vector Query embedding vector.
	 * @param int   $limit Maximum number of results.
	 * @return array Array of similar chunks with scores.
	 */
	private function native_vector_search( $query_vector, $limit ) {
		global $wpdb;
		
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		// For now, fall back to PHP search until we implement native SQL vector operations
		return $this->php_vector_search( $query_vector, $limit );
	}

	/**
	 * PHP-based vector similarity search.
	 *
	 * @param array $query_vector Query embedding vector.
	 * @param int   $limit Maximum number of results.
	 * @return array Array of similar chunks with scores.
	 */
	private function php_vector_search( $query_vector, $limit ) {
		global $wpdb;
		
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		// Get all chunks with embeddings
		$chunks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.id, c.chunk_text, c.chunk_embedding, c.entry_id, e.title as entry_title
				FROM {$chunks_table} c
				JOIN {$entries_table} e ON c.entry_id = e.id
				WHERE e.status = 'active' AND e.site_id = %d
				ORDER BY c.usage_count DESC
				LIMIT 1000",
				get_current_blog_id()
			),
			ARRAY_A
		);
		
		$results = array();
		
		foreach ( $chunks as $chunk ) {
			$chunk_embedding = json_decode( $chunk['chunk_embedding'], true );
			
			if ( ! is_array( $chunk_embedding ) || count( $chunk_embedding ) !== count( $query_vector ) ) {
				continue;
			}
			
			$similarity = $this->calculate_cosine_similarity( $query_vector, $chunk_embedding );
			
			$results[] = array(
				'id' => $chunk['id'],
				'text' => $chunk['chunk_text'],
				'similarity_score' => $similarity,
				'entry_id' => $chunk['entry_id'],
				'entry_title' => $chunk['entry_title']
			);
		}
		
		// Sort by similarity score
		usort( $results, function( $a, $b ) {
			return $b['similarity_score'] <=> $a['similarity_score'];
		} );
		
		// Update usage counts for returned results
		$result_slice = array_slice( $results, 0, $limit );
		$this->update_chunk_usage_counts( $result_slice );
		
		return $result_slice;
	}

	/**
	 * Calculate cosine similarity between two vectors.
	 *
	 * @param array $vector_a First vector.
	 * @param array $vector_b Second vector.
	 * @return float Similarity score between 0 and 1.
	 */
	private function calculate_cosine_similarity( $vector_a, $vector_b ) {
		if ( count( $vector_a ) !== count( $vector_b ) ) {
			return 0.0;
		}
		
		$dot_product = 0;
		$norm_a = 0;
		$norm_b = 0;
		
		for ( $i = 0; $i < count( $vector_a ); $i++ ) {
			$dot_product += $vector_a[$i] * $vector_b[$i];
			$norm_a += $vector_a[$i] * $vector_a[$i];
			$norm_b += $vector_b[$i] * $vector_b[$i];
		}
		
		$norm_product = sqrt( $norm_a ) * sqrt( $norm_b );
		
		if ( $norm_product == 0 ) {
			return 0.0;
		}
		
		return $dot_product / $norm_product;
	}

	/**
	 * Clean text for embedding generation.
	 *
	 * @param string $text Input text.
	 * @return string Cleaned text.
	 */
	private function clean_text_for_embedding( $text ) {
		// Remove excessive whitespace
		$text = preg_replace( '/\s+/', ' ', $text );
		
		// Remove HTML tags
		$text = wp_strip_all_tags( $text );
		
		// Trim and limit length
		$text = trim( $text );
		$text = substr( $text, 0, 8000 ); // OpenAI token limit
		
		return $text;
	}

	/**
	 * Update processing progress.
	 *
	 * @param int $current Current batch number.
	 * @param int $total Total number of batches.
	 */
	private function update_processing_progress( $current, $total ) {
		$progress = round( ( $current / $total ) * 100 );
		
		// Store progress in transient for admin display
		set_transient( 'aria_embedding_progress', array(
			'current' => $current,
			'total' => $total,
			'percentage' => $progress,
			'timestamp' => time()
		), 3600 );
		
		// Log progress for debugging
		if ( $current % 10 === 0 || $current === $total ) {
			Aria_Logger::debug( "Vector Engine: Processing batch {$current}/{$total} ({$progress}%)" );
		}
	}

	/**
	 * Update usage counts for chunks.
	 *
	 * @param array $chunks Array of chunk results.
	 */
	private function update_chunk_usage_counts( $chunks ) {
		global $wpdb;
		
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		$chunk_ids = array_column( $chunks, 'id' );
		
		if ( ! empty( $chunk_ids ) ) {
			$ids_placeholder = implode( ',', array_fill( 0, count( $chunk_ids ), '%d' ) );
			
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$chunks_table} 
					SET usage_count = usage_count + 1, last_used = NOW() 
					WHERE id IN ({$ids_placeholder})",
					...$chunk_ids
				)
			);
		}
	}

	/**
	 * Get AI provider instance.
	 *
	 * @return object|false AI provider instance or false.
	 */
	private function get_ai_provider() {
		$provider = get_option( 'aria_ai_provider', 'openai' );
		$encrypted_key = get_option( 'aria_ai_api_key' );

		if ( empty( $encrypted_key ) ) {
			return false;
		}

		// Decrypt the API key
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
		$api_key = Aria_Security::decrypt( $encrypted_key );

		if ( empty( $api_key ) ) {
			return false;
		}

		return $this->create_ai_provider( $provider, $api_key );
	}

	/**
	 * Create AI provider instance.
	 *
	 * @param string $provider Provider name.
	 * @param string $api_key API key.
	 * @return object AI provider instance.
	 */
	private function create_ai_provider( $provider, $api_key ) {
		// Ensure base AI provider class is loaded
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ai-provider.php';
		
		switch ( $provider ) {
			case 'openai':
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-openai-provider.php';
				return new Aria_OpenAI_Provider( $api_key );

			case 'gemini':
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';
				return new Aria_Gemini_Provider( $api_key );

			default:
				throw new Exception( 'Invalid AI provider for vector engine' );
		}
	}

	/**
	 * Test vector engine functionality.
	 *
	 * @return array Test results.
	 */
	public function test_vector_engine() {
		$test_results = array(
			'embedding_generation' => false,
			'similarity_calculation' => false,
			'database_connection' => false,
			'ai_provider' => false
		);

		try {
			// Test AI provider
			if ( $this->ai_provider ) {
				$test_results['ai_provider'] = true;
			}

			// Test embedding generation
			$test_embeddings = $this->generate_embeddings( array( 'This is a test sentence.' ) );
			if ( ! empty( $test_embeddings ) && is_array( $test_embeddings[0] ) ) {
				$test_results['embedding_generation'] = true;
			}

			// Test similarity calculation
			$vector_a = array_fill( 0, 5, 0.5 );
			$vector_b = array_fill( 0, 5, 0.5 );
			$similarity = $this->calculate_cosine_similarity( $vector_a, $vector_b );
			if ( $similarity > 0.9 ) {
				$test_results['similarity_calculation'] = true;
			}

			// Test database connection
			global $wpdb;
			$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$chunks_table}'" );
			if ( $table_exists ) {
				$test_results['database_connection'] = true;
			}

		} catch ( Exception $e ) {
			Aria_Logger::error( 'Vector Engine Test Error: ' . $e->getMessage() );
		}

		return $test_results;
	}
}
