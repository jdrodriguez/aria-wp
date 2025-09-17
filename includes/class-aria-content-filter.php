<?php
/**
 * Content privacy filter
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle content privacy filtering for vectorization.
 */
class Aria_Content_Filter {

	/**
	 * Check if content is public and indexable.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @return bool Whether content is public.
	 */
	public function is_content_public( $post_id, $post_type ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Only published content
		if ( $post->post_status !== 'publish' ) {
			return false;
		}

		// Password protected content excluded
		if ( ! empty( $post->post_password ) ) {
			return false;
		}

		// Check if post type is public
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return false;
		}

		// Custom privacy meta check
		if ( get_post_meta( $post_id, '_aria_exclude_indexing', true ) ) {
			return false;
		}

		// Check if user has excluded this content type globally
		$excluded_types = get_option( 'aria_excluded_content_types', array() );
		if ( in_array( $post_type, $excluded_types, true ) ) {
			return false;
		}

		// Additional privacy filters
		return apply_filters( 'aria_is_content_indexable', true, $post_id, $post_type );
	}

	/**
	 * Get indexable content types.
	 *
	 * @return array Indexable content types.
	 */
	public function get_indexable_content_types() {
		$default_types = array( 'post', 'page' );

		// Add WooCommerce product if available
		if ( class_exists( 'WooCommerce' ) ) {
			$default_types[] = 'product';
		}

		// Get custom post types that are public
		$custom_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			)
		);

		$indexable_types = array_merge( $default_types, $custom_types );

		// Remove excluded types
		$excluded_types = get_option( 'aria_excluded_content_types', array() );
		$indexable_types = array_diff( $indexable_types, $excluded_types );

		return apply_filters( 'aria_indexable_content_types', $indexable_types );
	}

	/**
	 * Get all public content for bulk indexing.
	 *
	 * @param int $batch_size Number of posts per batch.
	 * @param int $offset Offset for pagination.
	 * @return array Posts array.
	 */
	public function get_public_content_batch( $batch_size = 10, $offset = 0 ) {
		$args = array(
			'post_type'      => $this->get_indexable_content_types(),
			'post_status'    => 'publish',
			'posts_per_page' => $batch_size,
			'offset'         => $offset,
			'has_password'   => false,
			'meta_query'     => array(
				array(
					'key'     => '_aria_exclude_indexing',
					'compare' => 'NOT EXISTS',
				),
			),
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		return get_posts( $args );
	}

	/**
	 * Count total indexable content.
	 *
	 * @return int Total count.
	 */
	public function count_indexable_content() {
		$args = array(
			'post_type'      => $this->get_indexable_content_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'has_password'   => false,
			'meta_query'     => array(
				array(
					'key'     => '_aria_exclude_indexing',
					'compare' => 'NOT EXISTS',
				),
			),
			'fields'         => 'ids',
		);

		$posts = get_posts( $args );
		return count( $posts );
	}

	/**
	 * Check if comment is indexable.
	 *
	 * @param int $comment_id Comment ID.
	 * @return bool Whether comment is indexable.
	 */
	public function is_comment_indexable( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return false;
		}

		// Only approved comments
		if ( $comment->comment_approved !== '1' ) {
			return false;
		}

		// Check if parent post is indexable
		$post_id = $comment->comment_post_ID;
		$post_type = get_post_type( $post_id );

		return $this->is_content_public( $post_id, $post_type );
	}

	/**
	 * Sanitize content for indexing.
	 *
	 * @param string $content Raw content.
	 * @return string Sanitized content.
	 */
	public function sanitize_content( $content ) {
		// Remove HTML tags
		$content = wp_strip_all_tags( $content );

		// Remove shortcodes
		$content = strip_shortcodes( $content );

		// Remove extra whitespace
		$content = preg_replace( '/\s+/', ' ', $content );

		// Remove potentially sensitive patterns
		$patterns = array(
			'/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/',  // Credit card numbers
			'/\b\d{3}-\d{2}-\d{4}\b/',                        // SSN patterns
			'/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email addresses
			'/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',               // Phone numbers
		);

		foreach ( $patterns as $pattern ) {
			$content = preg_replace( $pattern, '[REDACTED]', $content );
		}

		return trim( $content );
	}

	/**
	 * Get content exclusion rules.
	 *
	 * @return array Exclusion rules.
	 */
	public function get_exclusion_rules() {
		return array(
			'private_posts'       => true,
			'password_protected'  => true,
			'draft_posts'         => true,
			'trashed_posts'       => true,
			'private_post_types'  => true,
			'excluded_meta'       => true,
			'unapproved_comments' => true,
			'sensitive_data'      => true,
		);
	}

	/**
	 * Log privacy compliance action.
	 *
	 * @param string $action Action performed.
	 * @param array  $data Action data.
	 */
	public function log_privacy_action( $action, $data = array() ) {
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'action'    => $action,
			'data'      => $data,
			'user_id'   => get_current_user_id(),
			'site_id'   => get_current_blog_id(),
		);

		// Store in option for review
		$logs = get_option( 'aria_privacy_logs', array() );
		$logs[] = $log_entry;

		// Keep only last 100 entries
		if ( count( $logs ) > 100 ) {
			$logs = array_slice( $logs, -100 );
		}

		update_option( 'aria_privacy_logs', $logs );
	}

	/**
	 * Generate privacy report.
	 *
	 * @return array Privacy compliance report.
	 */
	public function generate_privacy_report() {
		global $wpdb;

		$report = array(
			'indexed_content'   => array(),
			'excluded_content'  => array(),
			'privacy_controls'  => array(),
			'compliance_status' => 'compliant',
		);

		// Count indexed content by type
		$vectors_table = $wpdb->prefix . 'aria_content_vectors';
		$indexed_counts = $wpdb->get_results(
			"SELECT content_type, COUNT(*) as count FROM $vectors_table GROUP BY content_type",
			ARRAY_A
		);

		foreach ( $indexed_counts as $count ) {
			$report['indexed_content'][ $count['content_type'] ] = (int) $count['count'];
		}

		// Count excluded content
		$excluded_types = get_option( 'aria_excluded_content_types', array() );
		foreach ( $excluded_types as $type ) {
			$count = wp_count_posts( $type );
			$report['excluded_content'][ $type ] = $count->publish ?? 0;
		}

		// Privacy controls status
		$report['privacy_controls'] = array(
			'exclusion_meta_enabled'    => true,
			'password_protection'       => true,
			'private_content_excluded'  => true,
			'sensitive_data_filtering'  => true,
			'comment_approval_required' => true,
		);

		return $report;
	}
}