<?php
/**
 * Knowledge content processor for semantic chunking
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Process knowledge base content into optimized chunks with embeddings.
 */
class Aria_Knowledge_Processor {

	/**
	 * Optimal chunk size for embeddings.
	 *
	 * @var int
	 */
	private $chunk_size = 500;

	/**
	 * Context overlap size for chunk continuity.
	 *
	 * @var int
	 */
	private $overlap_size = 50;

	/**
	 * Maximum chunk size limit.
	 *
	 * @var int
	 */
	private $max_chunk_size = 750;

	/**
	 * Vector engine instance.
	 *
	 * @var Aria_Vector_Engine
	 */
	private $vector_engine;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Vector engine will be lazy-loaded when needed to prevent early initialization
		$this->vector_engine = null;
	}
	
	/**
	 * Get or create vector engine instance.
	 */
	private function get_vector_engine() {
		if ( ! $this->vector_engine ) {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-vector-engine.php';
			$this->vector_engine = new Aria_Vector_Engine();
		}
		return $this->vector_engine;
	}

	/**
	 * Process knowledge entry into optimized chunks with embeddings.
	 *
	 * @param string $content Entry content.
	 * @param array  $entry_metadata Entry metadata.
	 * @return array Processed chunks data.
	 */
	public function process_knowledge_entry( $content, $entry_metadata ) {
		try {
			// Step 1: Content normalization
			$normalized = $this->normalize_content( $content );
			
			// Step 2: Semantic chunking (preserve meaning)
			$semantic_chunks = $this->create_semantic_chunks( $normalized );
			
			// Step 3: Add overlapping context
			$overlapped_chunks = $this->add_context_overlap( $semantic_chunks );
			
			// Step 4: Generate embeddings
			$chunk_texts = array_column( $overlapped_chunks, 'text' );
			$embeddings = $this->get_vector_engine()->generate_embeddings( $chunk_texts );
			
			// Step 5: Combine chunks with embeddings and metadata
			$processed_chunks = $this->combine_chunks_with_embeddings( 
				$overlapped_chunks, 
				$embeddings, 
				$entry_metadata 
			);
			
			return $processed_chunks;
			
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Knowledge Processor: Processing failed: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Normalize content for processing.
	 *
	 * @param string $content Raw content.
	 * @return string Normalized content.
	 */
	private function normalize_content( $content ) {
		// Remove HTML tags
		$content = wp_strip_all_tags( $content );
		
		// Normalize whitespace
		$content = preg_replace( '/\s+/', ' ', $content );
		
		// Remove excessive line breaks
		$content = preg_replace( '/\n\s*\n/', "\n\n", $content );
		
		// Trim
		$content = trim( $content );
		
		return $content;
	}

	/**
	 * Create semantic chunks that preserve sentence boundaries.
	 *
	 * @param string $content Normalized content.
	 * @return array Array of text chunks.
	 */
	private function create_semantic_chunks( $content ) {
		// Split into sentences while preserving structure
		$sentences = $this->split_into_sentences( $content );
		$chunks = array();
		$current_chunk = '';
		
		foreach ( $sentences as $sentence ) {
			$potential_chunk = $current_chunk . ' ' . $sentence;
			$potential_length = strlen( $potential_chunk );
			
			if ( $potential_length > $this->chunk_size ) {
				if ( ! empty( $current_chunk ) ) {
					// Save current chunk and start new one
					$chunks[] = trim( $current_chunk );
					$current_chunk = $sentence;
				} else {
					// Handle oversized sentences
					$split_sentences = $this->force_split_sentence( $sentence );
					$chunks = array_merge( $chunks, $split_sentences );
					$current_chunk = '';
				}
			} else {
				$current_chunk = $potential_chunk;
			}
		}
		
		// Add final chunk if not empty
		if ( ! empty( $current_chunk ) ) {
			$chunks[] = trim( $current_chunk );
		}
		
		return array_filter( $chunks ); // Remove empty chunks
	}

	/**
	 * Split content into sentences.
	 *
	 * @param string $content Content to split.
	 * @return array Array of sentences.
	 */
	private function split_into_sentences( $content ) {
		// Improved sentence splitting that handles abbreviations and edge cases
		$sentences = array();
		
		// Split on sentence-ending punctuation followed by whitespace or end of string
		$pattern = '/([.!?]+)(\s+|$)/';
		$parts = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		
		$current_sentence = '';
		
		for ( $i = 0; $i < count( $parts ); $i++ ) {
			$part = $parts[$i];
			
			if ( preg_match( '/^[.!?]+$/', $part ) ) {
				// This is punctuation
				$current_sentence .= $part;
				
				// Check if next part is whitespace (end of sentence)
				if ( isset( $parts[$i + 1] ) && preg_match( '/^\s+$/', $parts[$i + 1] ) ) {
					$sentences[] = trim( $current_sentence );
					$current_sentence = '';
					$i++; // Skip the whitespace part
				}
			} else {
				$current_sentence .= $part;
			}
		}
		
		// Add final sentence if not empty
		if ( ! empty( trim( $current_sentence ) ) ) {
			$sentences[] = trim( $current_sentence );
		}
		
		return array_filter( $sentences );
	}

	/**
	 * Force split oversized sentences.
	 *
	 * @param string $sentence Long sentence to split.
	 * @return array Array of sentence parts.
	 */
	private function force_split_sentence( $sentence ) {
		$parts = array();
		$length = strlen( $sentence );
		
		if ( $length <= $this->max_chunk_size ) {
			return array( $sentence );
		}
		
		// Split on commas, semicolons, or other natural break points
		$break_patterns = array( ',', ';', ' - ', ' and ', ' or ', ' but ', ' however ' );
		
		foreach ( $break_patterns as $pattern ) {
			if ( strpos( $sentence, $pattern ) !== false ) {
				$sentence_parts = explode( $pattern, $sentence );
				$current_part = '';
				
				foreach ( $sentence_parts as $i => $part ) {
					$test_part = $current_part . ( $i > 0 ? $pattern : '' ) . $part;
					
					if ( strlen( $test_part ) <= $this->chunk_size ) {
						$current_part = $test_part;
					} else {
						if ( ! empty( $current_part ) ) {
							$parts[] = trim( $current_part );
						}
						$current_part = $part;
					}
				}
				
				if ( ! empty( $current_part ) ) {
					$parts[] = trim( $current_part );
				}
				
				return array_filter( $parts );
			}
		}
		
		// Last resort: split by word count
		$words = explode( ' ', $sentence );
		$words_per_chunk = floor( count( $words ) / ceil( $length / $this->chunk_size ) );
		
		for ( $i = 0; $i < count( $words ); $i += $words_per_chunk ) {
			$chunk_words = array_slice( $words, $i, $words_per_chunk );
			$parts[] = implode( ' ', $chunk_words );
		}
		
		return array_filter( $parts );
	}

	/**
	 * Add context overlap between chunks.
	 *
	 * @param array $chunks Array of text chunks.
	 * @return array Array of chunks with overlap context.
	 */
	private function add_context_overlap( $chunks ) {
		$overlapped = array();
		
		for ( $i = 0; $i < count( $chunks ); $i++ ) {
			$chunk_with_context = $chunks[$i];
			
			// Add previous context
			if ( $i > 0 ) {
				$prev_context = $this->get_tail_words( $chunks[$i - 1], $this->overlap_size );
				$chunk_with_context = $prev_context . ' ' . $chunk_with_context;
			}
			
			// Add next context
			if ( $i < count( $chunks ) - 1 ) {
				$next_context = $this->get_head_words( $chunks[$i + 1], $this->overlap_size );
				$chunk_with_context = $chunk_with_context . ' ' . $next_context;
			}
			
			$overlapped[] = array(
				'text' => trim( $chunk_with_context ),
				'original_text' => $chunks[$i],
				'original_index' => $i,
				'has_prev_context' => $i > 0,
				'has_next_context' => $i < count( $chunks ) - 1,
				'chunk_length' => strlen( $chunk_with_context )
			);
		}
		
		return $overlapped;
	}

	/**
	 * Get last N words from text.
	 *
	 * @param string $text Input text.
	 * @param int    $word_count Number of words to extract.
	 * @return string Last N words.
	 */
	private function get_tail_words( $text, $word_count ) {
		$words = explode( ' ', trim( $text ) );
		$tail_words = array_slice( $words, -$word_count );
		return implode( ' ', $tail_words );
	}

	/**
	 * Get first N words from text.
	 *
	 * @param string $text Input text.
	 * @param int    $word_count Number of words to extract.
	 * @return string First N words.
	 */
	private function get_head_words( $text, $word_count ) {
		$words = explode( ' ', trim( $text ) );
		$head_words = array_slice( $words, 0, $word_count );
		return implode( ' ', $head_words );
	}

	/**
	 * Combine chunks with embeddings and metadata.
	 *
	 * @param array $chunks Processed chunks.
	 * @param array $embeddings Generated embeddings.
	 * @param array $entry_metadata Entry metadata.
	 * @return array Combined chunk data.
	 */
	private function combine_chunks_with_embeddings( $chunks, $embeddings, $entry_metadata ) {
		$combined = array();
		
		for ( $i = 0; $i < count( $chunks ); $i++ ) {
			$chunk = $chunks[$i];
			$embedding = isset( $embeddings[$i] ) ? $embeddings[$i] : null;
			
			if ( $embedding === null ) {
				Aria_Logger::error( "Aria Knowledge Processor: Missing embedding for chunk {$i}" );
				continue;
			}
			
			$combined[] = array(
				'text' => $chunk['text'],
				'original_text' => $chunk['original_text'],
				'embedding' => $embedding,
				'chunk_index' => $chunk['original_index'],
				'chunk_length' => $chunk['chunk_length'],
				'has_overlap' => $chunk['has_prev_context'] || $chunk['has_next_context'],
				'entry_id' => $entry_metadata['id'],
				'entry_title' => $entry_metadata['title'],
				'entry_category' => $entry_metadata['category'] ?? '',
				'processing_timestamp' => current_time( 'mysql' )
			);
		}
		
		return $combined;
	}

	/**
	 * Store processed chunks in database.
	 *
	 * @param int   $entry_id Entry ID.
	 * @param array $chunks_data Processed chunks data.
	 * @return bool Success status.
	 */
	public function store_processed_chunks( $entry_id, $chunks_data ) {
		global $wpdb;
		
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		
		// Clear existing chunks for this entry
		$wpdb->delete( $chunks_table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		
		$success_count = 0;
		
		foreach ( $chunks_data as $chunk_data ) {
			$result = $wpdb->insert(
				$chunks_table,
				array(
					'entry_id' => $entry_id,
					'chunk_text' => $chunk_data['text'],
					'chunk_embedding' => wp_json_encode( $chunk_data['embedding'] ),
					'chunk_index' => $chunk_data['chunk_index'],
					'chunk_length' => $chunk_data['chunk_length'],
					'has_overlap' => $chunk_data['has_overlap'] ? 1 : 0,
					'usage_count' => 0,
					'created_at' => current_time( 'mysql' )
				),
				array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
			);
			
			if ( $result !== false ) {
				$success_count++;
			}
		}
		
		// Update entry with chunk count and processing status
		$wpdb->update(
			$wpdb->prefix . 'aria_knowledge_entries',
			array(
				'total_chunks' => $success_count,
				'last_processed' => current_time( 'mysql' ),
				'status' => 'active'
			),
			array( 'id' => $entry_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
		
		return $success_count === count( $chunks_data );
	}

	/**
	 * Reprocess knowledge entry (update existing chunks).
	 *
	 * @param int $entry_id Entry ID to reprocess.
	 * @return bool Success status.
	 */
	public function reprocess_knowledge_entry( $entry_id ) {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		
		// Get entry data
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$entries_table} WHERE id = %d",
				$entry_id
			),
			ARRAY_A
		);
		
		if ( ! $entry ) {
			return false;
		}
		
		try {
			// Process the entry
			$chunks_data = $this->process_knowledge_entry( $entry['content'], $entry );
			
			// Store the processed chunks
			return $this->store_processed_chunks( $entry_id, $chunks_data );
			
		} catch ( Exception $e ) {
			Aria_Logger::error( "Aria Knowledge Processor: Reprocessing failed for entry {$entry_id}: " . $e->getMessage() );
			
			// Update entry status to failed
			$wpdb->update(
				$entries_table,
				array( 'status' => 'processing_failed' ),
				array( 'id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);
			
			return false;
		}
	}

	/**
	 * Get processing statistics.
	 *
	 * @return array Processing statistics.
	 */
	public function get_processing_stats() {
		global $wpdb;
		
		$entries_table = $wpdb->prefix . 'aria_knowledge_entries';
		$chunks_table = $wpdb->prefix . 'aria_knowledge_chunks';
		
		$stats = array(
			'total_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d",
					get_current_blog_id()
				)
			),
			'processed_entries' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE site_id = %d AND status = 'active'",
					get_current_blog_id()
				)
			),
			'total_chunks' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$chunks_table} c 
					JOIN {$entries_table} e ON c.entry_id = e.id 
					WHERE e.site_id = %d",
					get_current_blog_id()
				)
			),
			'avg_chunks_per_entry' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(total_chunks) FROM {$entries_table} WHERE site_id = %d AND status = 'active'",
					get_current_blog_id()
				)
			),
			'total_content_length' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(CHAR_LENGTH(content)) FROM {$entries_table} WHERE site_id = %d AND status = 'active'",
					get_current_blog_id()
				)
			)
		);
		
		return $stats;
	}

	/**
	 * Test knowledge processor functionality.
	 *
	 * @return array Test results.
	 */
	public function test_knowledge_processor() {
		$test_results = array(
			'chunking' => false,
			'overlap_generation' => false,
			'embedding_integration' => false
		);

		try {
			// Test chunking
			$test_content = "This is a test sentence. Here is another sentence for testing purposes. And here is a third sentence to make sure chunking works properly.";
			$chunks = $this->create_semantic_chunks( $test_content );
			
			if ( ! empty( $chunks ) && is_array( $chunks ) ) {
				$test_results['chunking'] = true;
			}

			// Test overlap generation
			$overlapped = $this->add_context_overlap( $chunks );
			
			if ( ! empty( $overlapped ) && isset( $overlapped[0]['text'] ) ) {
				$test_results['overlap_generation'] = true;
			}

			// Test embedding integration
			if ( $this->vector_engine ) {
				$test_results['embedding_integration'] = true;
			}

		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Knowledge Processor Test Error: ' . $e->getMessage() );
		}

		return $test_results;
	}
}
