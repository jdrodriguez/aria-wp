<?php
/**
 * Admin Content Indexing Page - Refactored with Layout Component
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the centralized layout component
require_once ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-page-layout.php';

// Get content indexing statistics
$content_vectorizer = new Aria_Content_Vectorizer();
$content_filter = new Aria_Content_Filter();

$indexing_stats = $content_vectorizer->get_indexing_stats();
$privacy_report = $content_filter->generate_privacy_report();
$indexable_count = $content_filter->count_indexable_content();

// Check if initial indexing is complete
$initial_indexing_complete = get_option( 'aria_initial_indexing_complete', false );
$initial_indexing_completed_at = get_option( 'aria_initial_indexing_completed_at' );

// Get excluded content types
$excluded_content_types = get_option( 'aria_excluded_content_types', array() );
$available_post_types = get_post_types( array( 'public' => true ), 'objects' );

// Get current indexing progress
$indexing_offset = get_option( 'aria_indexing_offset', 0 );

// Check if indexing appears complete but wasn't properly marked
$total_vectors = $indexing_stats['total_vectors'] ?? 0;
$has_vectors = $total_vectors > 0;

// If we have vectors equal to or greater than indexable count, mark as complete
if ( $has_vectors && $total_vectors >= $indexable_count && !$initial_indexing_complete ) {
	update_option( 'aria_initial_indexing_complete', true );
	update_option( 'aria_initial_indexing_completed_at', current_time( 'mysql' ) );
	delete_option( 'aria_indexing_offset' ); // Clean up the offset
	$initial_indexing_complete = true;
	$indexing_offset = 0;
}

$indexing_in_progress = $indexing_offset > 0 && !$initial_indexing_complete;

// Get detailed content status for individual management
$content_status = $content_vectorizer->get_content_indexing_status();

// Build cards data array
$cards = array();

// Card 1: System Status
ob_start();
?>
<div class="metric-item large">
	<span class="item-value primary"><?php echo number_format( $indexing_stats['total_vectors'] ?? 0 ); ?></span>
	<span class="item-label"><?php esc_html_e( 'Content Vectors', 'aria' ); ?></span>
</div>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Status', 'aria' ); ?></span>
	<span class="status-indicator <?php echo $initial_indexing_complete ? 'status-ready' : 'status-progress'; ?>">
		<?php echo $initial_indexing_complete ? esc_html__( 'System Ready', 'aria' ) : esc_html__( 'Indexing in Progress', 'aria' ); ?>
	</span>
</div>
<?php if ( $indexing_in_progress ): ?>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Progress', 'aria' ); ?></span>
	<div class="progress-section">
		<div class="progress-label">
			<?php printf( esc_html__( 'Processing %s items...', 'aria' ), number_format( $indexing_offset ) ); ?>
		</div>
		<div class="progress-bar">
			<div class="progress-fill" style="width: <?php echo min( 100, ( $indexing_stats['total_vectors'] / max( 1, $indexable_count ) ) * 100 ); ?>%"></div>
		</div>
	</div>
</div>
<?php endif; 
$system_status_content = ob_get_clean();

$help_button = '<button type="button" class="aria-help-button" id="aria-vector-help" style="margin-left: auto;"><span class="dashicons dashicons-editor-help"></span>' . esc_html__( 'What are Content Vectors?', 'aria' ) . '</button>';

$cards[] = array(
	'title' => __( 'System Status', 'aria' ),
	'icon' => 'admin-site-alt3',
	'content' => $system_status_content,
	'options' => array( 'actions' => $help_button )
);

// Card 2: Content Types
ob_start();
if ( ! empty( $indexing_stats['by_type'] ) ): 
	foreach ( $indexing_stats['by_type'] as $type => $count ): ?>
		<div class="metric-item">
			<span class="item-label"><?php echo esc_html( ucfirst( $type ) ); ?></span>
			<span class="item-value"><?php echo number_format( $count ); ?></span>
		</div>
	<?php endforeach;
else: ?>
	<div class="aria-empty-state">
		<span class="dashicons dashicons-info"></span>
		<p><?php esc_html_e( 'No content indexed yet', 'aria' ); ?></p>
	</div>
<?php endif;
$content_types_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Content Types', 'aria' ),
	'icon' => 'admin-post',
	'content' => $content_types_content
);

// Card 3: Content Summary
ob_start(); ?>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Vectors Created Today', 'aria' ); ?></span>
	<span class="item-value"><?php echo number_format( $indexing_stats['indexed_today'] ?? 0 ); ?></span>
</div>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Vectors This Week', 'aria' ); ?></span>
	<span class="item-value"><?php echo number_format( $indexing_stats['indexed_this_week'] ?? 0 ); ?></span>
</div>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Posts/Pages Available', 'aria' ); ?></span>
	<span class="item-value"><?php echo number_format( $indexable_count ); ?></span>
</div>
<?php 
$content_summary_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Content Summary', 'aria' ),
	'icon' => 'chart-line',
	'content' => $content_summary_content
);

// Card 4: Storage Usage
ob_start();
global $wpdb;
$table_size = $wpdb->get_var( "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_name='{$wpdb->prefix}aria_content_vectors'" );
?>
<div class="metric-item large">
	<span class="item-value primary"><?php echo $table_size ? $table_size . ' MB' : '< 1 MB'; ?></span>
	<span class="item-label"><?php esc_html_e( 'Vector Database Size', 'aria' ); ?></span>
</div>
<div class="metric-item">
	<span class="item-label"><?php esc_html_e( 'Total Vectors', 'aria' ); ?></span>
	<span class="item-value"><?php echo number_format( $indexing_stats['total_vectors'] ?? 0 ); ?></span>
</div>
<?php 
$storage_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Storage Usage', 'aria' ),
	'icon' => 'database',
	'content' => $storage_content
);

// Card 5: Test Vector Search
ob_start(); ?>
<p class="aria-section-description">
	<?php esc_html_e( 'Test how well the AI can find relevant content from your WordPress database using vector similarity search.', 'aria' ); ?>
</p>
<div class="aria-search-interface">
	<div class="search-input-group">
		<input type="text" 
			   id="test-query" 
			   class="search-input" 
			   placeholder="<?php esc_attr_e( 'Try searching for "Mangia", "Bevi", or any topic from your content...', 'aria' ); ?>">
		<button type="button" class="button button-primary aria-btn-primary" id="run-search-test">
			<span class="dashicons dashicons-search"></span>
			<?php esc_html_e( 'Search', 'aria' ); ?>
		</button>
		<button type="button" class="button button-secondary aria-btn-secondary" id="debug-vectors">
			<span class="dashicons dashicons-admin-tools"></span>
			<?php esc_html_e( 'Debug Vectors', 'aria' ); ?>
		</button>
	</div>
	<div id="search-test-results" class="search-results" style="display: none;">
		<!-- Search results will be populated here -->
	</div>
</div>
<?php 
$search_test_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Test Vector Search', 'aria' ),
	'icon' => 'search',
	'content' => $search_test_content
);

// Card 6: Privacy Controls
ob_start(); ?>
<p class="aria-section-description">
	<?php esc_html_e( 'Control which content types are included in AI indexing. Unchecked types will be excluded from responses.', 'aria' ); ?>
</p>

<form id="aria-content-settings-form" class="aria-privacy-form" method="post">
	<?php wp_nonce_field( 'aria_content_settings', 'aria_content_nonce' ); ?>
	
	<div class="aria-content-types-grid">
		<?php foreach ( $available_post_types as $post_type ): ?>
		<label class="aria-content-type-toggle">
			<input type="checkbox" 
				   name="excluded_content_types[]" 
				   value="<?php echo esc_attr( $post_type->name ); ?>"
				   <?php checked( ! in_array( $post_type->name, $excluded_content_types, true ) ); ?>>
			<span class="toggle-switch"></span>
			<span class="type-info">
				<span class="type-name"><?php echo esc_html( $post_type->labels->name ?? $post_type->name ); ?></span>
				<span class="type-count"><?php echo wp_count_posts( $post_type->name )->publish; ?> items</span>
			</span>
		</label>
		<?php endforeach; ?>
	</div>
	
	<div class="aria-form-actions">
		<button type="submit" class="button button-secondary aria-btn-secondary">
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Save Settings', 'aria' ); ?>
		</button>
	</div>
</form>

<!-- Privacy Compliance Info -->
<div class="aria-compliance-info">
	<h4><?php esc_html_e( 'Privacy Protection', 'aria' ); ?></h4>
	<div class="aria-compliance-badges">
		<div class="aria-compliance-badge">
			<span class="dashicons dashicons-yes"></span>
			<span class="badge-text"><?php esc_html_e( 'Public content only', 'aria' ); ?></span>
		</div>
		<div class="aria-compliance-badge">
			<span class="dashicons dashicons-lock"></span>
			<span class="badge-text"><?php esc_html_e( 'Password-protected excluded', 'aria' ); ?></span>
		</div>
		<div class="aria-compliance-badge">
			<span class="dashicons dashicons-shield"></span>
			<span class="badge-text"><?php esc_html_e( 'Sensitive data filtered', 'aria' ); ?></span>
		</div>
	</div>
</div>
<?php 
$privacy_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Content Privacy Controls', 'aria' ),
	'icon' => 'privacy',
	'content' => $privacy_content
);

// Card 7: Content Status
ob_start(); ?>
<p class="aria-section-description"><?php esc_html_e( 'Review and manage individual content indexing status', 'aria' ); ?></p>

<div class="content-status-tabs">
	<div class="tab-nav">
		<button type="button" class="tab-button active" data-tab="indexed">
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Indexed Content', 'aria' ); ?>
			<span class="tab-count"><?php echo count( $content_status['indexed'] ); ?></span>
		</button>
		<button type="button" class="tab-button" data-tab="pending">
			<span class="dashicons dashicons-clock"></span>
			<?php esc_html_e( 'Pending Content', 'aria' ); ?>
			<span class="tab-count"><?php echo count( $content_status['pending'] ); ?></span>
		</button>
	</div>
	
	<div class="tab-content">
		<!-- Indexed Content Tab -->
		<div class="tab-panel active" id="indexed-content">
			<?php if ( ! empty( $content_status['indexed'] ) ): ?>
				<div class="content-list">
					<?php foreach ( $content_status['indexed'] as $content ): ?>
						<div class="content-item indexed">
							<div class="content-info">
								<div class="content-title">
									<a href="<?php echo esc_url( $content['edit_url'] ); ?>" target="_blank">
										<?php echo esc_html( $content['title'] ?: __( '(No title)', 'aria' ) ); ?>
									</a>
								</div>
								<div class="content-meta">
									<span class="content-type"><?php echo esc_html( ucfirst( $content['type'] ) ); ?></span>
									<span class="content-date"><?php echo esc_html( human_time_diff( strtotime( $content['date'] ) ) ); ?> ago</span>
									<span class="indexed-date"><?php echo esc_html__( 'Indexed', 'aria' ); ?>: <?php echo esc_html( human_time_diff( strtotime( $content['indexed_date'] ) ) ); ?> ago</span>
								</div>
							</div>
							<div class="content-actions">
								<button type="button" class="button button-secondary aria-btn-secondary reindex-item" data-id="<?php echo esc_attr( $content['id'] ); ?>" data-type="<?php echo esc_attr( $content['type'] ); ?>">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Re-index', 'aria' ); ?>
								</button>
								<a href="<?php echo esc_url( $content['view_url'] ); ?>" target="_blank" class="button button-ghost aria-btn-ghost">
									<span class="dashicons dashicons-external"></span>
									<?php esc_html_e( 'View', 'aria' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="empty-state">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No content has been indexed yet.', 'aria' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- Pending Content Tab -->
		<div class="tab-panel" id="pending-content">
			<?php if ( ! empty( $content_status['pending'] ) ): ?>
				<div class="content-list">
					<?php foreach ( $content_status['pending'] as $content ): ?>
						<div class="content-item pending">
							<div class="content-info">
								<div class="content-title">
									<a href="<?php echo esc_url( $content['edit_url'] ); ?>" target="_blank">
										<?php echo esc_html( $content['title'] ?: __( '(No title)', 'aria' ) ); ?>
									</a>
								</div>
								<div class="content-meta">
									<span class="content-type"><?php echo esc_html( ucfirst( $content['type'] ) ); ?></span>
									<span class="content-date"><?php echo esc_html( human_time_diff( strtotime( $content['date'] ) ) ); ?> ago</span>
									<span class="pending-status"><?php esc_html_e( 'Not indexed', 'aria' ); ?></span>
								</div>
							</div>
							<div class="content-actions">
								<button type="button" class="button button-primary aria-btn-primary index-item" data-id="<?php echo esc_attr( $content['id'] ); ?>" data-type="<?php echo esc_attr( $content['type'] ); ?>">
									<span class="dashicons dashicons-plus"></span>
									<?php esc_html_e( 'Index Now', 'aria' ); ?>
								</button>
								<a href="<?php echo esc_url( $content['view_url'] ); ?>" target="_blank" class="button button-ghost aria-btn-ghost">
									<span class="dashicons dashicons-external"></span>
									<?php esc_html_e( 'View', 'aria' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="empty-state">
					<span class="dashicons dashicons-yes"></span>
					<p><?php esc_html_e( 'All content has been indexed! 🎉', 'aria' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php 
$content_status_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'Content Status', 'aria' ),
	'icon' => 'admin-page',
	'content' => $content_status_content,
	'options' => array( 'custom_class' => 'content-management-card' )
);

// Card 8: System Actions
ob_start(); ?>
<p class="aria-section-description"><?php esc_html_e( 'Manage content indexing and system maintenance', 'aria' ); ?></p>

<div class="aria-action-toolbar">
	<div class="primary-actions">
		<button type="button" class="button button-primary aria-btn-primary" id="aria-reindex-all">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Reindex All Content', 'aria' ); ?>
		</button>
	</div>
	<div class="secondary-actions">
		<button type="button" class="button button-secondary aria-btn-secondary" id="aria-clear-cache">
			<span class="dashicons dashicons-trash"></span>
			<?php esc_html_e( 'Clear Search Cache', 'aria' ); ?>
		</button>
	</div>
</div>
<?php 
$system_actions_content = ob_get_clean();

$cards[] = array(
	'title' => __( 'System Actions', 'aria' ),
	'icon' => 'admin-tools',
	'content' => $system_actions_content
);

// Build system notice for after content
$after_content = '';
if ( $indexing_in_progress ) {
	$after_content = '<div class="aria-system-notice">
		<div class="notice-icon">ℹ️</div>
		<div class="notice-content">
			<p>' . esc_html__( 'Background indexing is currently in progress. New content will be processed automatically.', 'aria' ) . '</p>
		</div>
	</div>';
}

// Render the page using the centralized layout component
aria_render_admin_page_with_cards(
	'content-indexing',
	__( 'Content Indexing', 'aria' ),
	__( 'Manage WordPress content vectorization for AI responses', 'aria' ),
	$cards,
	array(
		'grid_type' => 'two-column',
		'after_content' => $after_content
	)
);
?>

<script>
// Content Management Interface
// Localized strings and nonces for JavaScript
var ariaContentIndexing = {
	confirmReindex: <?php echo wp_json_encode( __( 'This will reindex all content. Continue?', 'aria' ) ); ?>,
	starting: <?php echo wp_json_encode( __( 'Starting...', 'aria' ) ); ?>,
	indexingStarted: <?php echo wp_json_encode( __( 'Indexing Started ✓', 'aria' ) ); ?>,
	failedReindex: <?php echo wp_json_encode( __( 'Failed to start reindexing.', 'aria' ) ); ?>,
	networkError: <?php echo wp_json_encode( __( 'Network error. Please try again.', 'aria' ) ); ?>,
	goToAiConfig: <?php echo wp_json_encode( __( 'Would you like to go to the AI Configuration page now?', 'aria' ) ); ?>,
	checkApiKey: <?php echo wp_json_encode( __( 'Please check your API key in the AI Configuration page.', 'aria' ) ); ?>,
	indexing: <?php echo wp_json_encode( __( 'Indexing...', 'aria' ) ); ?>,
	indexed: <?php echo wp_json_encode( __( 'Indexed ✓', 'aria' ) ); ?>,
	failedIndex: <?php echo wp_json_encode( __( 'Failed to index content.', 'aria' ) ); ?>,
	reindexing: <?php echo wp_json_encode( __( 'Re-indexing...', 'aria' ) ); ?>,
	reindexed: <?php echo wp_json_encode( __( 'Re-indexed ✓', 'aria' ) ); ?>,
	failedReindexItem: <?php echo wp_json_encode( __( 'Failed to re-index content.', 'aria' ) ); ?>,
	loading: <?php echo wp_json_encode( __( 'Loading...', 'aria' ) ); ?>,
	debugVectors: <?php echo wp_json_encode( __( 'Debug Vectors', 'aria' ) ); ?>,
	enterQuery: <?php echo wp_json_encode( __( 'Please enter a search query.', 'aria' ) ); ?>,
	testing: <?php echo wp_json_encode( __( 'Testing...', 'aria' ) ); ?>,
	testSearch: <?php echo wp_json_encode( __( 'Test Search', 'aria' ) ); ?>,
	clearing: <?php echo wp_json_encode( __( 'Clearing...', 'aria' ) ); ?>,
	cleared: <?php echo wp_json_encode( __( 'Cleared ✓', 'aria' ) ); ?>,
	failedClear: <?php echo wp_json_encode( __( 'Failed to clear cache.', 'aria' ) ); ?>,
	saving: <?php echo wp_json_encode( __( 'Saving...', 'aria' ) ); ?>,
	saved: <?php echo wp_json_encode( __( 'Saved ✓', 'aria' ) ); ?>,
	failedSave: <?php echo wp_json_encode( __( 'Failed to save settings.', 'aria' ) ); ?>,
	contentNonce: <?php echo wp_json_encode( wp_create_nonce( 'aria_content_nonce' ) ); ?>,
	aiConfigUrl: <?php echo wp_json_encode( admin_url( 'admin.php?page=aria-ai-config' ) ); ?>
};

jQuery(document).ready(function($) {
	// Reindex all content
	$('#aria-reindex-all').on('click', function() {
		if (!confirm(ariaContentIndexing.confirmReindex)) {
			return;
		}
		
		const $button = $(this);
		const originalText = $button.text();
		$button.text(ariaContentIndexing.starting).prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'aria_reindex_all_content',
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				$button.text(ariaContentIndexing.indexingStarted);
				showSuccessMessage(response.data.message);
				// Reload after 2 seconds to show progress
				setTimeout(function() {
					location.reload();
				}, 2000);
			} else {
				const errorData = response.data || {};
				const message = errorData.message || ariaContentIndexing.failedReindex;
				
				if (errorData.action_needed === 'configure_api') {
					showConfigurationNeeded(message);
				} else if (errorData.action_needed === 'fix_api') {
					showApiError(message);
				} else {
					showErrorMessage(message);
				}
				
				$button.text(originalText).prop('disabled', false);
			}
		}).fail(function() {
			showErrorMessage(ariaContentIndexing.networkError);
			$button.text(originalText).prop('disabled', false);
		});
	});

	// Show different types of messages
	function showSuccessMessage(message) {
		showMessage(message, 'success');
	}
	
	function showErrorMessage(message) {
		showMessage(message, 'error');
	}
	
	function showConfigurationNeeded(message) {
		const fullMessage = message + '\n\n' + ariaContentIndexing.goToAiConfig;
		if (confirm(fullMessage)) {
			window.location.href = ariaContentIndexing.aiConfigUrl;
		}
	}
	
	function showApiError(message) {
		const fullMessage = message + '\n\n' + ariaContentIndexing.checkApiKey;
		if (confirm(fullMessage)) {
			window.location.href = ariaContentIndexing.aiConfigUrl;
		}
	}
	
	function showMessage(message, type) {
		// Create notification element
		const notification = $('<div class="aria-notification aria-notification-' + type + '">' + 
			'<span class="message">' + message + '</span>' +
			'<button type="button" class="close-notification">&times;</button>' +
		'</div>');
		
		// Add to page
		$('.aria-page-content').prepend(notification);
		
		// Auto-remove after 5 seconds
		setTimeout(function() {
			notification.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
		
		// Manual close
		notification.find('.close-notification').on('click', function() {
			notification.fadeOut(function() {
				$(this).remove();
			});
		});
	}

	// Content Status Tab Navigation
	$('.tab-button').on('click', function() {
		const tabId = $(this).data('tab');
		
		// Update active tab button
		$('.tab-button').removeClass('active');
		$(this).addClass('active');
		
		// Update active tab panel
		$('.tab-panel').removeClass('active');
		$('#' + tabId + '-content').addClass('active');
	});

	// Individual Content Indexing
	$('.index-item').on('click', function() {
		const $button = $(this);
		const contentId = $button.data('id');
		const contentType = $button.data('type');
		const originalText = $button.text().trim();
		
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + ariaContentIndexing.indexing);
		
		$.post(ajaxurl, {
			action: 'aria_index_single_item',
			content_id: contentId,
			content_type: contentType,
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				$button.html('<span class="dashicons dashicons-yes"></span> ' + ariaContentIndexing.indexed);
				showSuccessMessage(response.data.message);
				
				// Move item to indexed tab after delay
				setTimeout(function() {
					location.reload();
				}, 2000);
			} else {
				$button.html('<span class="dashicons dashicons-plus"></span> ' + originalText).prop('disabled', false);
				showErrorMessage(response.data?.message || ariaContentIndexing.failedIndex);
			}
		}).fail(function() {
			$button.html('<span class="dashicons dashicons-plus"></span> ' + originalText).prop('disabled', false);
			showErrorMessage(ariaContentIndexing.networkError);
		});
	});

	// Individual Content Re-indexing
	$('.reindex-item').on('click', function() {
		const $button = $(this);
		const contentId = $button.data('id');
		const contentType = $button.data('type');
		const originalText = $button.text().trim();
		
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + ariaContentIndexing.reindexing);
		
		$.post(ajaxurl, {
			action: 'aria_index_single_item',
			content_id: contentId,
			content_type: contentType,
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				$button.html('<span class="dashicons dashicons-yes"></span> ' + ariaContentIndexing.reindexed);
				showSuccessMessage(response.data.message);
				
				setTimeout(function() {
					$button.html('<span class="dashicons dashicons-update"></span> ' + originalText).prop('disabled', false);
				}, 2000);
			} else {
				$button.html('<span class="dashicons dashicons-update"></span> ' + originalText).prop('disabled', false);
				showErrorMessage(response.data?.message || ariaContentIndexing.failedReindexItem);
			}
		}).fail(function() {
			$button.html('<span class="dashicons dashicons-update"></span> ' + originalText).prop('disabled', false);
			showErrorMessage(ariaContentIndexing.networkError);
		});
	});

	// Debug vectors
	$('#debug-vectors').on('click', function() {
		const $button = $(this);
		const $results = $('#search-test-results');
		$button.prop('disabled', true).text(ariaContentIndexing.loading);
		$results.html('<div class="search-loading">Loading vector data...</div>').show();
		
		$.post(ajaxurl, {
			action: 'aria_debug_vectors',
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				$results.html(response.data.html);
			} else {
				$results.html('<div class="search-error">Failed to load vector data.</div>');
			}
		}).always(function() {
			$button.prop('disabled', false).text(ariaContentIndexing.debugVectors);
		});
	});

	// Run search test
	$('#run-search-test').on('click', function() {
		const query = $('#test-query').val().trim();
		if (!query) {
			alert(ariaContentIndexing.enterQuery);
			return;
		}
		
		const $button = $(this);
		const $results = $('#search-test-results');
		$button.prop('disabled', true).text(ariaContentIndexing.testing);
		$results.html('<div class="search-loading">Searching vector database...</div>').show();
		
		$.post(ajaxurl, {
			action: 'aria_test_content_search',
			query: query,
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				$results.html(response.data.html);
			} else {
				$results.html('<div class="search-error">Search test failed. Please try again.</div>');
			}
		}).always(function() {
			$button.prop('disabled', false).text(ariaContentIndexing.testSearch);
		});
	});

	// Clear cache
	$('#aria-clear-cache').on('click', function() {
		const $button = $(this);
		const originalText = $button.text();
		$button.text(ariaContentIndexing.clearing).prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'aria_clear_search_cache',
			nonce: ariaContentIndexing.contentNonce
		}).done(function(response) {
			if (response.success) {
				// Show inline success message instead of alert
				$button.text(ariaContentIndexing.cleared);
				setTimeout(function() {
					$button.text(originalText).prop('disabled', false);
				}, 2000);
			} else {
				alert(ariaContentIndexing.failedClear);
				$button.text(originalText).prop('disabled', false);
			}
		});
	});

	// Save content settings
	$('#aria-content-settings-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitBtn = $form.find('button[type="submit"]');
		const originalText = $submitBtn.text();
		const formData = $form.serialize();
		
		$submitBtn.text(ariaContentIndexing.saving).prop('disabled', true);
		
		$.post(ajaxurl, formData + '&action=aria_save_content_settings').done(function(response) {
			if (response.success) {
				$submitBtn.text(ariaContentIndexing.saved);
				setTimeout(function() {
					$submitBtn.text(originalText).prop('disabled', false);
					// Settings now always visible - no auto-hide needed
				}, 1500);
			} else {
				alert(ariaContentIndexing.failedSave);
				$submitBtn.text(originalText).prop('disabled', false);
			}
		});
	});

	// Enhanced keyboard navigation for search input
	$('#test-query').on('keypress', function(e) {
		// Enter key triggers search
		if (e.which === 13) {
			$('#run-search-test').click();
		}
	});

	// Vector help modal
	$('#aria-vector-help').on('click', function() {
		$('#aria-vector-help-modal').show();
		$('body').addClass('aria-modal-open');
	});

	// Close modal
	$('.aria-modal-close, .aria-modal-backdrop').on('click', function() {
		$('#aria-vector-help-modal').hide();
		$('body').removeClass('aria-modal-open');
	});

	// Prevent modal close when clicking inside modal content
	$('.aria-modal-content').on('click', function(e) {
		e.stopPropagation();
	});

	// Close modal with Escape key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#aria-vector-help-modal').is(':visible')) {
			$('#aria-vector-help-modal').hide();
			$('body').removeClass('aria-modal-open');
		}
	});
});
</script>

<!-- Vector Help Modal -->
<div id="aria-vector-help-modal" class="aria-modal" style="display: none;">
	<div class="aria-modal-backdrop"></div>
	<div class="aria-modal-content">
		<div class="aria-modal-header">
			<h3><?php esc_html_e( 'What are Content Vectors?', 'aria' ); ?></h3>
			<button type="button" class="aria-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="aria-modal-body">
			<p><?php esc_html_e( 'Vectors are mathematical representations of your content that help Aria understand and find relevant information. When you publish posts or pages, they are broken into chunks and converted into vectors (768-dimensional arrays) that capture their meaning.', 'aria' ); ?></p>
			<p><?php esc_html_e( 'This allows Aria to quickly search through your content and provide accurate, contextual responses to visitors. The more content you have indexed, the better Aria can assist your users.', 'aria' ); ?></p>
			<div class="aria-help-example">
				<h4><?php esc_html_e( 'How it works:', 'aria' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Your posts and pages are analyzed and broken into manageable chunks', 'aria' ); ?></li>
					<li><?php esc_html_e( 'Each chunk is converted into a vector that represents its meaning', 'aria' ); ?></li>
					<li><?php esc_html_e( 'When visitors ask questions, Aria searches these vectors to find relevant content', 'aria' ); ?></li>
					<li><?php esc_html_e( 'The AI uses this content to generate helpful, accurate responses', 'aria' ); ?></li>
				</ol>
			</div>
		</div>
	</div>
</div>