<?php
/**
 * Content indexing hooks
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle automatic content indexing hooks.
 */
class Aria_Content_Hooks {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// WordPress core content hooks
		add_action( 'save_post', array( $this, 'handle_content_save' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'handle_content_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'handle_content_delete' ) );

		// WooCommerce product hooks
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_update_product', array( $this, 'handle_product_update' ) );
			add_action( 'woocommerce_delete_product', array( $this, 'handle_content_delete' ) );
		}

		// Comment hooks
		add_action( 'comment_post', array( $this, 'handle_comment_save' ), 10, 2 );
		add_action( 'edit_comment', array( $this, 'handle_comment_save' ), 10, 2 );
		add_action( 'delete_comment', array( $this, 'handle_comment_delete' ) );
		add_action( 'wp_set_comment_status', array( $this, 'handle_comment_status_change' ), 10, 2 );

		// Bulk indexing hook
		add_action( 'aria_initial_content_indexing', array( $this, 'bulk_index_existing_content' ) );
		add_action( 'aria_index_single_content', array( $this, 'index_single_content' ), 10, 2 );

		// Privacy meta box
		add_action( 'add_meta_boxes', array( $this, 'add_privacy_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_privacy_meta' ) );
	}

	/**
	 * Handle content save/update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function handle_content_save( $post_id, $post ) {
		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Skip if doing bulk import or cron
		if ( wp_doing_cron() || ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ) {
			return;
		}

		Aria_Logger::debug( "handle_content_save triggered for {$post->post_type} {$post_id}" );

		$filter = new Aria_Content_Filter();
		if ( $filter->is_content_public( $post_id, $post->post_type ) ) {
			Aria_Logger::debug( "Content {$post_id} is public, indexing immediately" );
			
			// In Docker environments, cron is unreliable, so index immediately
			$this->index_single_content( $post_id, $post->post_type );
		} else {
		Aria_Logger::debug( "Content {$post_id} is not public, removing from index" );
			// Remove from index if no longer public
			$this->handle_content_delete( $post_id );
		}
	}

	/**
	 * Handle WooCommerce product updates.
	 *
	 * @param int $product_id Product ID.
	 */
	public function handle_product_update( $product_id ) {
		$filter = new Aria_Content_Filter();
		if ( $filter->is_content_public( $product_id, 'product' ) ) {
		Aria_Logger::debug( "Product {$product_id} is public, indexing immediately" );
			$this->index_single_content( $product_id, 'product' );
		} else {
			$this->handle_content_delete( $product_id );
		}
	}

	/**
	 * Handle content deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_content_delete( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			// Try to determine type from vectors table
			global $wpdb;
			$table = $wpdb->prefix . 'aria_content_vectors';
			$post_type = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT content_type FROM $table WHERE content_id = %d LIMIT 1",
					$post_id
				)
			);
		}

		if ( $post_type ) {
			$vectorizer = new Aria_Content_Vectorizer();
			$vectorizer->remove_content_vectors( $post_id, $post_type );
		}
	}

	/**
	 * Handle comment save/update.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $approved Approval status.
	 */
	public function handle_comment_save( $comment_id, $approved ) {
		// Only process approved comments
		if ( $approved === 1 ) {
			$comment = get_comment( $comment_id );
			$post_id = $comment->comment_post_ID;
			$post_type = get_post_type( $post_id );

			$filter = new Aria_Content_Filter();
			if ( $filter->is_content_public( $post_id, $post_type ) ) {
				// Re-index the parent post to include new comment
			Aria_Logger::debug( "Comment updated on {$post_type} {$post_id}, re-indexing immediately" );
				$this->index_single_content( $post_id, $post_type );
			}
		}
	}

	/**
	 * Handle comment deletion.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function handle_comment_delete( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( $comment ) {
			$post_id = $comment->comment_post_ID;
			$post_type = get_post_type( $post_id );

			// Re-index parent post to remove deleted comment
		Aria_Logger::debug( "Comment deleted on {$post_type} {$post_id}, re-indexing immediately" );
			$this->index_single_content( $post_id, $post_type );
		}
	}

	/**
	 * Handle comment status changes.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $status New status.
	 */
	public function handle_comment_status_change( $comment_id, $status ) {
		$comment = get_comment( $comment_id );
		if ( $comment ) {
			$post_id = $comment->comment_post_ID;
			$post_type = get_post_type( $post_id );

			$filter = new Aria_Content_Filter();
			if ( $filter->is_content_public( $post_id, $post_type ) ) {
				// Re-index parent post to reflect comment status change
			Aria_Logger::debug( "Comment status changed on {$post_type} {$post_id}, re-indexing immediately" );
				$this->index_single_content( $post_id, $post_type );
			}
		}
	}

	/**
	 * Bulk index existing content.
	 */
	public function bulk_index_existing_content() {
		$filter = new Aria_Content_Filter();
		$vectorizer = new Aria_Content_Vectorizer();

		// Process in batches to avoid timeouts
		$batch_size = 10;
		$offset = get_option( 'aria_indexing_offset', 0 );

		$posts = $filter->get_public_content_batch( $batch_size, $offset );

		foreach ( $posts as $post ) {
			if ( $filter->is_content_public( $post->ID, $post->post_type ) ) {
				$success = $vectorizer->index_content( $post->ID, $post->post_type );
				if ( $success ) {
					Aria_Logger::debug( "Successfully indexed {$post->post_type} {$post->ID}" );
				} else {
					Aria_Logger::error( "Failed to index {$post->post_type} {$post->ID}" );
				}
			}

			// Small delay to prevent overwhelming the system
			usleep( 100000 ); // 0.1 seconds
		}

		// Schedule next batch if more content exists
		if ( count( $posts ) === $batch_size ) {
			update_option( 'aria_indexing_offset', $offset + $batch_size );
			wp_schedule_single_event( time() + 30, 'aria_initial_content_indexing' );
		} else {
			delete_option( 'aria_indexing_offset' );
			update_option( 'aria_initial_indexing_complete', true );
			update_option( 'aria_initial_indexing_completed_at', current_time( 'mysql' ) );

			// Log completion
			$total_indexed = $filter->get_indexing_stats()['total_vectors'] ?? 0;
		Aria_Logger::debug( 'Initial content indexing completed. Total vectors: ' . $total_indexed );
		}
	}

	/**
	 * Index single content item.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $content_type Content type.
	 */
	public function index_single_content( $content_id, $content_type ) {
		$filter = new Aria_Content_Filter();
		$vectorizer = new Aria_Content_Vectorizer();

		if ( $filter->is_content_public( $content_id, $content_type ) ) {
			$success = $vectorizer->index_content( $content_id, $content_type );
			if ( $success ) {
				Aria_Logger::debug( "Successfully indexed {$content_type} {$content_id}" );
			} else {
				Aria_Logger::error( "Failed to index {$content_type} {$content_id}" );
			}
		}
	}

	/**
	 * Add privacy meta box to posts/pages.
	 */
	public function add_privacy_meta_box() {
		$post_types = array( 'post', 'page', 'product' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'aria_privacy_control',
				__( 'Aria AI Indexing', 'aria' ),
				array( $this, 'privacy_meta_box_callback' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Privacy meta box callback.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function privacy_meta_box_callback( $post ) {
		wp_nonce_field( 'aria_privacy_meta', 'aria_privacy_nonce' );
		$exclude = get_post_meta( $post->ID, '_aria_exclude_indexing', true );
		?>
		<label>
			<input type="checkbox" name="aria_exclude_indexing" value="1" <?php checked( $exclude, '1' ); ?>>
			<?php esc_html_e( 'Exclude from Aria AI indexing', 'aria' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Check this to prevent Aria from learning from this content.', 'aria' ); ?>
		</p>
		<?php
	}

	/**
	 * Save privacy meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_privacy_meta( $post_id ) {
		// Check nonce
		if ( ! isset( $_POST['aria_privacy_nonce'] ) || ! wp_verify_nonce( $_POST['aria_privacy_nonce'], 'aria_privacy_meta' ) ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save meta
		$exclude = isset( $_POST['aria_exclude_indexing'] ) ? '1' : '';
		$old_value = get_post_meta( $post_id, '_aria_exclude_indexing', true );

		if ( $exclude !== $old_value ) {
			update_post_meta( $post_id, '_aria_exclude_indexing', $exclude );

			// If exclusion status changed, update indexing
			$post_type = get_post_type( $post_id );
			if ( $exclude ) {
				// Remove from index
				$this->handle_content_delete( $post_id );
			} else {
				// Add to index if public
				$filter = new Aria_Content_Filter();
				if ( $filter->is_content_public( $post_id, $post_type ) ) {
					wp_schedule_single_event( time() + 10, 'aria_index_single_content', array( $post_id, $post_type ) );
				}
			}
		}
	}
}
