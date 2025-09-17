<?php
/**
 * Content vectorization handler
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle WordPress content vectorization and search.
 */
class Aria_Content_Vectorizer {

	/**
	 * Index content with vector embeddings.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $content_type Content type.
	 * @return bool Success status.
	 */
	public function index_content( $content_id, $content_type ) {
		// Remove existing vectors for this content
		$this->remove_content_vectors( $content_id, $content_type );

		// Extract content parts
		$content_parts = $this->extract_content( $content_id, $content_type );
		if ( empty( $content_parts ) ) {
			return false;
		}

		// Combine all content into chunks
		$chunks = $this->chunk_content( $content_parts );
		if ( empty( $chunks ) ) {
			return false;
		}

		$success_count = 0;
		foreach ( $chunks as $index => $chunk ) {
			$success = $this->store_content_vector( $content_id, $content_type, $chunk, $index );
			if ( $success ) {
				$success_count++;
			}
		}

		return $success_count > 0;
	}

	/**
	 * Extract content from WordPress post/page/product.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $content_type Content type.
	 * @return array Content parts.
	 */
	public function extract_content( $content_id, $content_type ) {
		$post = get_post( $content_id );
		if ( ! $post ) {
			return array();
		}

		$content_parts = array();

		// Basic post content
		$content_parts[] = array(
			'type' => 'title',
			'text' => $post->post_title,
		);

		if ( ! empty( $post->post_content ) ) {
			$content_parts[] = array(
				'type' => 'content',
				'text' => wp_strip_all_tags( $post->post_content ),
			);
		}

		// Excerpt if available
		if ( ! empty( $post->post_excerpt ) ) {
			$content_parts[] = array(
				'type' => 'excerpt',
				'text' => $post->post_excerpt,
			);
		}

		// Product-specific data for WooCommerce
		if ( $content_type === 'product' && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $content_id );
			if ( $product ) {
				$short_desc = $product->get_short_description();
				if ( ! empty( $short_desc ) ) {
					$content_parts[] = array(
						'type' => 'product_description',
						'text' => wp_strip_all_tags( $short_desc ),
					);
				}

				$content_parts[] = array(
					'type' => 'product_price',
					'text' => 'Price: ' . wp_strip_all_tags( $product->get_price_html() ),
				);

				// Product categories
				$categories = wp_get_post_terms( $content_id, 'product_cat', array( 'fields' => 'names' ) );
				if ( ! empty( $categories ) ) {
					$content_parts[] = array(
						'type' => 'categories',
						'text' => 'Categories: ' . implode( ', ', $categories ),
					);
				}

				// Product tags
				$tags = wp_get_post_terms( $content_id, 'product_tag', array( 'fields' => 'names' ) );
				if ( ! empty( $tags ) ) {
					$content_parts[] = array(
						'type' => 'tags',
						'text' => 'Tags: ' . implode( ', ', $tags ),
					);
				}
			}
		}

		// Regular post categories and tags
		if ( in_array( $content_type, array( 'post', 'page' ), true ) ) {
			$categories = wp_get_post_terms( $content_id, 'category', array( 'fields' => 'names' ) );
			if ( ! empty( $categories ) ) {
				$content_parts[] = array(
					'type' => 'categories',
					'text' => 'Categories: ' . implode( ', ', $categories ),
				);
			}

			$tags = wp_get_post_terms( $content_id, 'post_tag', array( 'fields' => 'names' ) );
			if ( ! empty( $tags ) ) {
				$content_parts[] = array(
					'type' => 'tags',
					'text' => 'Tags: ' . implode( ', ', $tags ),
				);
			}
		}

		// Approved comments
		$comments = get_comments(
			array(
				'post_id' => $content_id,
				'status'  => 'approve',
				'type'    => 'comment',
			)
		);

		foreach ( $comments as $comment ) {
			$comment_text = wp_strip_all_tags( $comment->comment_content );
			if ( strlen( $comment_text ) > 20 ) { // Only meaningful comments
				$content_parts[] = array(
					'type' => 'comment',
					'text' => $comment_text,
				);
			}
		}

		return $content_parts;
	}

	/**
	 * Chunk content into manageable pieces.
	 *
	 * @param array $content_parts Content parts array.
	 * @param int   $max_tokens Maximum tokens per chunk.
	 * @return array Content chunks.
	 */
	public function chunk_content( $content_parts, $max_tokens = 200 ) {
		$chunks = array();
		$title = '';
		$non_title_parts = array();
		
		// Separate title from other content parts
		foreach ( $content_parts as $part ) {
			if ( $part['type'] === 'title' ) {
				$title = $part['text'];
			} else {
				$non_title_parts[] = $part;
			}
		}
		
		// If we only have a title, create a single chunk
		if ( empty( $non_title_parts ) ) {
			if ( ! empty( $title ) ) {
				$chunks[] = $title;
			}
			return $chunks;
		}
		
		// Calculate title token cost (we'll prepend title to each chunk)
		$title_tokens = ! empty( $title ) ? $this->estimate_tokens( $title . "\n\n" ) : 0;
		$available_tokens = $max_tokens - $title_tokens;
		
		$current_chunk = '';
		$current_tokens = 0;

		foreach ( $non_title_parts as $part ) {
			$part_text = $part['text'];
			$part_tokens = $this->estimate_tokens( $part_text );

			// If this part alone exceeds available tokens, split it
			if ( $part_tokens > $available_tokens ) {
				// Save current chunk if not empty
				if ( ! empty( $current_chunk ) ) {
					$chunk_text = ! empty( $title ) ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
					$chunks[] = $chunk_text;
					$current_chunk = '';
					$current_tokens = 0;
				}

				// Split large part and add title to each sub-chunk
				$sub_chunks = $this->split_large_text( $part_text, $available_tokens );
				foreach ( $sub_chunks as $sub_chunk ) {
					$chunk_text = ! empty( $title ) ? $title . "\n\n" . $sub_chunk : $sub_chunk;
					$chunks[] = $chunk_text;
				}
			} else {
				// If adding this part would exceed limit, save current chunk
				if ( $current_tokens + $part_tokens > $available_tokens && ! empty( $current_chunk ) ) {
					$chunk_text = ! empty( $title ) ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
					$chunks[] = $chunk_text;
					$current_chunk = $part_text . "\n\n";
					$current_tokens = $part_tokens;
				} else {
					$current_chunk .= $part_text . "\n\n";
					$current_tokens += $part_tokens;
				}
			}
		}

		// Add final chunk with title
		if ( ! empty( $current_chunk ) ) {
			$chunk_text = ! empty( $title ) ? $title . "\n\n" . trim( $current_chunk ) : trim( $current_chunk );
			$chunks[] = $chunk_text;
		}

		return $chunks;
	}

	/**
	 * Split large text into smaller chunks.
	 *
	 * @param string $text Text to split.
	 * @param int    $max_tokens Maximum tokens per chunk.
	 * @return array Text chunks.
	 */
	private function split_large_text( $text, $max_tokens ) {
		$chunks = array();
		
		// First try paragraph-level splitting
		$paragraphs = preg_split( '/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY );
		
		foreach ( $paragraphs as $paragraph ) {
			$paragraph = trim( $paragraph );
			if ( empty( $paragraph ) ) {
				continue;
			}
			
			$paragraph_tokens = $this->estimate_tokens( $paragraph );
			
			// If paragraph fits in one chunk, add it
			if ( $paragraph_tokens <= $max_tokens ) {
				$chunks[] = $paragraph;
			} else {
				// Split paragraph into sentences
				$sentences = preg_split( '/(?<=[.!?])\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY );
				$current_chunk = '';
				$current_tokens = 0;

				foreach ( $sentences as $sentence ) {
					$sentence_tokens = $this->estimate_tokens( $sentence );

					if ( $current_tokens + $sentence_tokens > $max_tokens && ! empty( $current_chunk ) ) {
						$chunks[] = trim( $current_chunk );
						$current_chunk = $sentence . ' ';
						$current_tokens = $sentence_tokens;
					} else {
						$current_chunk .= $sentence . ' ';
						$current_tokens += $sentence_tokens;
					}
				}

				if ( ! empty( $current_chunk ) ) {
					$chunks[] = trim( $current_chunk );
				}
			}
		}

		return $chunks;
	}

	/**
	 * Estimate token count for text.
	 *
	 * @param string $text Text to analyze.
	 * @return int Estimated token count.
	 */
	private function estimate_tokens( $text ) {
		// More accurate estimation based on word count and character analysis
		$word_count = str_word_count( $text );
		$char_count = strlen( $text );
		
		// OpenAI tokenization is roughly 0.75 tokens per word for English
		// Plus some overhead for punctuation and formatting
		$word_based = $word_count * 0.75;
		$char_based = $char_count / 3.5; // More accurate than 4 chars per token
		
		// Use the higher estimate to be conservative
		return max( 1, (int) max( $word_based, $char_based ) );
	}

	/**
	 * Store content vector in database.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $content_type Content type.
	 * @param string $chunk_text Chunk text.
	 * @param int    $chunk_index Chunk index.
	 * @return bool Success status.
	 */
	private function store_content_vector( $content_id, $content_type, $chunk_text, $chunk_index ) {
		// Generate embedding
		$embedding = $this->generate_embedding( $chunk_text );
		if ( ! $embedding ) {
			return false;
		}

		// Prepare metadata
		$post = get_post( $content_id );
		$metadata = array(
			'title'        => $post->post_title,
			'url'          => get_permalink( $content_id ),
			'published'    => $post->post_date,
			'modified'     => $post->post_modified,
			'chunk_length' => strlen( $chunk_text ),
		);

		global $wpdb;
		$table = $wpdb->prefix . 'aria_content_vectors';

		return false !== $wpdb->insert(
			$table,
			array(
				'content_id'     => $content_id,
				'content_type'   => $content_type,
				'chunk_index'    => $chunk_index,
				'content_text'   => $chunk_text,
				'content_vector' => wp_json_encode( $embedding ),
				'metadata'       => wp_json_encode( $metadata ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Generate embedding for text.
	 *
	 * @param string $text Text to embed.
	 * @return array|false Embedding vector or false on failure.
	 */
	private function generate_embedding( $text ) {
		$ai_provider = get_option( 'aria_ai_provider', 'openai' );
		
		// Get API key
		$encrypted_api_key = get_option( 'aria_ai_api_key', '' );
		if ( empty( $encrypted_api_key ) ) {
			error_log( 'Aria embedding generation failed: No API key configured' );
			return false;
		}

		try {
			// Decrypt API key
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
			$api_key = Aria_Security::decrypt( $encrypted_api_key );
			
			if ( empty( $api_key ) ) {
				error_log( 'Aria embedding generation failed: API key decryption failed' );
				return false;
			}
			
			// Load base provider class
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ai-provider.php';
			
			if ( $ai_provider === 'openai' ) {
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-openai-provider.php';
				$provider = new Aria_OpenAI_Provider( $api_key );
			} elseif ( $ai_provider === 'gemini' ) {
				require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';
				$provider = new Aria_Gemini_Provider( $api_key );
			} else {
				error_log( 'Aria embedding generation failed: Unknown AI provider: ' . $ai_provider );
				return false;
			}

			// Check if provider supports embeddings
			if ( ! method_exists( $provider, 'generate_embedding' ) ) {
				error_log( 'Aria embedding generation failed: Provider does not support embeddings' );
				return false;
			}

			return $provider->generate_embedding( $text );
		} catch ( Exception $e ) {
			error_log( 'Aria embedding generation failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Search for similar content.
	 *
	 * @param string $query Search query.
	 * @param int    $limit Number of results.
	 * @param float  $min_similarity Minimum similarity threshold.
	 * @return array Similar content chunks.
	 */
	public function search_similar_content( $query, $limit = 5, $min_similarity = 0.3 ) {
		// Generate embedding for query
		$query_embedding = $this->generate_embedding( $query );
		if ( ! $query_embedding ) {
			return array();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'aria_content_vectors';

		// Get all vectors (we'll calculate similarity in PHP for now)
		$vectors = $wpdb->get_results(
			"SELECT * FROM $table ORDER BY created_at DESC",
			ARRAY_A
		);

		$similarities = array();
		foreach ( $vectors as $vector ) {
			$content_vector = json_decode( $vector['content_vector'], true );
			if ( ! $content_vector ) {
				continue;
			}

			$similarity = $this->calculate_cosine_similarity( $query_embedding, $content_vector );
			if ( $similarity >= $min_similarity ) {
				$vector['similarity'] = $similarity;
				$similarities[] = $vector;
			}
		}

		// Sort by similarity descending
		usort(
			$similarities,
			function ( $a, $b ) {
				return $b['similarity'] <=> $a['similarity'];
			}
		);

		return array_slice( $similarities, 0, $limit );
	}

	/**
	 * Calculate cosine similarity between two vectors.
	 *
	 * @param array $vector1 First vector.
	 * @param array $vector2 Second vector.
	 * @return float Similarity score (0-1).
	 */
	private function calculate_cosine_similarity( $vector1, $vector2 ) {
		if ( count( $vector1 ) !== count( $vector2 ) ) {
			return 0;
		}

		$dot_product = 0;
		$magnitude1 = 0;
		$magnitude2 = 0;

		for ( $i = 0; $i < count( $vector1 ); $i++ ) {
			$dot_product += $vector1[ $i ] * $vector2[ $i ];
			$magnitude1 += $vector1[ $i ] * $vector1[ $i ];
			$magnitude2 += $vector2[ $i ] * $vector2[ $i ];
		}

		$magnitude1 = sqrt( $magnitude1 );
		$magnitude2 = sqrt( $magnitude2 );

		if ( $magnitude1 == 0 || $magnitude2 == 0 ) {
			return 0;
		}

		return $dot_product / ( $magnitude1 * $magnitude2 );
	}

	/**
	 * Remove content vectors for specific content.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $content_type Content type.
	 * @return bool Success status.
	 */
	public function remove_content_vectors( $content_id, $content_type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_content_vectors';

		return false !== $wpdb->delete(
			$table,
			array(
				'content_id'   => $content_id,
				'content_type' => $content_type,
			),
			array( '%d', '%s' )
		);
	}

	/**
	 * Get indexing statistics.
	 *
	 * @return array Statistics data.
	 */
	public function get_indexing_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_content_vectors';
		$posts_table = $wpdb->posts;
		
		$stats = array();

		// Site-specific filtering: Only count vectors for content that exists on current site
		// Since aria_content_vectors doesn't have site_id, we JOIN with wp_posts to filter by current site's content
		$site_filter_join = "INNER JOIN $posts_table p ON cv.content_id = p.ID";
		
		// Total vectors (filtered by current site's content)
		$stats['total_vectors'] = (int) $wpdb->get_var( 
			"SELECT COUNT(*) FROM $table cv $site_filter_join" 
		);

		// Vectors by content type (filtered by current site's content)
		$type_counts = $wpdb->get_results(
			"SELECT cv.content_type, COUNT(*) as count 
			 FROM $table cv $site_filter_join 
			 GROUP BY cv.content_type",
			ARRAY_A
		);

		$stats['by_type'] = array();
		foreach ( $type_counts as $type ) {
			$stats['by_type'][ $type['content_type'] ] = (int) $type['count'];
		}

		// Recent indexing activity (filtered by current site's content)
		$stats['indexed_today'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $table cv $site_filter_join 
			 WHERE DATE(cv.created_at) = CURDATE()"
		);

		$stats['indexed_this_week'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $table cv $site_filter_join 
			 WHERE cv.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
		);

		// Add debug logging for troubleshooting
		error_log( "Aria Content Vectorizer Stats Debug:" );
		error_log( "  - Total vectors (site-filtered): " . $stats['total_vectors'] );
		error_log( "  - Vectors by type: " . wp_json_encode( $stats['by_type'] ) );
		error_log( "  - Using site filter JOIN: $site_filter_join" );
		
		// Also log unfiltered count for comparison
		$unfiltered_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		error_log( "  - Total vectors (unfiltered): $unfiltered_total" );

		return $stats;
	}

	/**
	 * Get detailed content indexing status for individual management.
	 *
	 * @return array Array with 'indexed' and 'pending' content items.
	 */
	public function get_content_indexing_status() {
		global $wpdb;
		
		$filter = new Aria_Content_Filter();
		// Get all indexable content using a large batch size
		$indexable_content = $filter->get_public_content_batch( 999999, 0 );
		
		$vectors_table = $wpdb->prefix . 'aria_content_vectors';
		
		// Get all indexed content IDs
		$indexed_ids = $wpdb->get_col( "SELECT DISTINCT content_id FROM $vectors_table" );
		
		$indexed = array();
		$pending = array();
		
		foreach ( $indexable_content as $content ) {
			$content_data = array(
				'id' => $content->ID,
				'title' => $content->post_title,
				'type' => $content->post_type,
				'status' => $content->post_status,
				'date' => $content->post_date,
				'edit_url' => get_edit_post_link( $content->ID ),
				'view_url' => get_permalink( $content->ID )
			);
			
			if ( in_array( $content->ID, $indexed_ids ) ) {
				// Get indexing date
				$indexed_date = $wpdb->get_var( $wpdb->prepare(
					"SELECT created_at FROM $vectors_table WHERE content_id = %d ORDER BY created_at DESC LIMIT 1",
					$content->ID
				) );
				$content_data['indexed_date'] = $indexed_date;
				$indexed[] = $content_data;
			} else {
				$pending[] = $content_data;
			}
		}
		
		return array(
			'indexed' => $indexed,
			'pending' => $pending
		);
	}
}