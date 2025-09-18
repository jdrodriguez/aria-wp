<?php
/**
 * Admin Content Indexing Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

// Calculate metrics
$coverage_percentage = $indexable_count > 0 ? ( $total_vectors / $indexable_count ) * 100 : 0;
$vector_size_mb = isset( $indexing_stats['storage_mb'] ) ? round( $indexing_stats['storage_mb'], 2 ) : 0;
$avg_vector_size = $total_vectors > 0 ? ( $vector_size_mb * 1024 ) / $total_vectors : 0;
?>

<style>
/* High specificity CSS to override WordPress admin styles */
body.wp-admin .wrap.aria-content-indexing {
	margin: 0 !important;
	padding: 0 !important;
	background: #f8fafc !important;
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
}

body.wp-admin .wrap.aria-content-indexing * {
	box-sizing: border-box;
}

/* Logo Header - EXACT MATCH TO DASHBOARD */
body.wp-admin .wrap.aria-content-indexing .aria-logo-header {
	padding: 2rem 2rem 0 2rem !important;
	max-width: 1800px !important;
	margin: 0 auto !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-dashboard-container {
	max-width: 1800px;
	margin: 0 auto;
	padding: 2rem;
	min-height: 100vh;
}

/* Page Title Section - EXACT MATCH TO DASHBOARD */
body.wp-admin .wrap.aria-content-indexing .aria-page-title-section {
	margin-bottom: 2rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-main-title {
	font-size: 2.5rem !important;
	font-weight: 700 !important;
	color: #1a2842 !important;
	margin: 0 0 0.5rem 0 !important;
	letter-spacing: -0.02em !important;
	line-height: 1.2 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-main-subtitle {
	font-size: 1.125rem !important;
	color: #64748b !important;
	margin: 0 !important;
	font-weight: 400 !important;
	line-height: 1.5 !important;
}

/* Header Actions Row - EXACT MATCH TO DASHBOARD */
body.wp-admin .wrap.aria-content-indexing .aria-header-actions-row {
	display: flex !important;
	justify-content: flex-end !important;
	gap: 1rem !important;
	margin-bottom: 2rem !important;
}

/* Modern Header Design - Exact copy from dashboard with high specificity */
body.wp-admin .wrap.aria-content-indexing .aria-page-header {
	background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%) !important;
	border: 1px solid #e1e5e9 !important;
	border-radius: 1rem !important;
	padding: 2.5rem !important;
	margin-bottom: 2rem !important;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.05) !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-header-content {
	display: flex !important;
	justify-content: space-between !important;
	align-items: center !important;
	gap: 2rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-header-main {
	flex: 1 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-page-title {
	font-size: 2.5rem !important;
	font-weight: 700 !important;
	color: #1a2842 !important;
	margin: 0 0 0.5rem 0 !important;
	letter-spacing: -0.02em !important;
	line-height: 1.2 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-page-description {
	font-size: 1.125rem !important;
	color: #64748b !important;
	margin: 0 !important;
	font-weight: 400 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-header-actions {
	display: flex !important;
	gap: 1rem !important;
}

/* Buttons - Exact copy from dashboard with high specificity */
body.wp-admin .wrap.aria-content-indexing .aria-btn {
	display: inline-flex !important;
	align-items: center !important;
	gap: 0.75rem !important;
	padding: 0.875rem 1.5rem !important;
	font-size: 0.95rem !important;
	font-weight: 600 !important;
	text-decoration: none !important;
	border-radius: 0.75rem !important;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
	border: none !important;
	cursor: pointer !important;
	font-family: inherit !important;
	line-height: 1.5 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-btn--primary {
	background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%) !important;
	color: white !important;
	box-shadow: 0 4px 14px rgba(0, 102, 255, 0.25) !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-btn--primary:hover {
	transform: translateY(-2px) !important;
	box-shadow: 0 8px 25px rgba(0, 102, 255, 0.35) !important;
	background: linear-gradient(135deg, #0052cc 0%, #003d99 100%) !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-btn--secondary {
	background: white !important;
	color: #1a2842 !important;
	border: 2px solid #e1e5e9 !important;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-btn--secondary:hover {
	transform: translateY(-2px) !important;
	border-color: #0066ff !important;
	box-shadow: 0 4px 14px rgba(0, 102, 255, 0.1) !important;
}

/* Primary Section */
body.wp-admin .wrap.aria-content-indexing .aria-primary-section {
	margin-bottom: 3rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metrics-header {
	margin-bottom: 2rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-section-title {
	font-size: 1.875rem !important;
	font-weight: 700 !important;
	color: #1a2842 !important;
	margin: 0 0 0.5rem 0 !important;
	letter-spacing: -0.01em !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-section-description {
	font-size: 1rem !important;
	color: #64748b !important;
	margin: 0 !important;
}

/* Metrics Grid */
body.wp-admin .wrap.aria-content-indexing .aria-metrics-grid {
	display: grid !important;
	grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) !important;
	gap: 1.5rem !important;
}

/* Metric Cards */
body.wp-admin .wrap.aria-content-indexing .aria-metric-card {
	background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%) !important;
	border: 1px solid #e1e5e9 !important;
	border-radius: 1rem !important;
	padding: 2rem !important;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
	position: relative !important;
	overflow: hidden !important;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-card::before {
	content: '' !important;
	position: absolute !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	height: 4px !important;
	border-radius: 1rem 1rem 0 0 !important;
	opacity: 0 !important;
	transition: opacity 0.3s ease !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-card--info::before { 
	background: linear-gradient(90deg, #3b82f6, #2563eb) !important; 
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-card:hover {
	transform: translateY(-4px) !important;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 8px 20px rgba(0, 0, 0, 0.05) !important;
	border-color: transparent !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-card:hover::before {
	opacity: 1 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-header {
	display: flex !important;
	justify-content: space-between !important;
	align-items: flex-start !important;
	margin-bottom: 1.5rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-label {
	font-size: 1.125rem !important;
	font-weight: 600 !important;
	color: #1a2842 !important;
	margin: 0 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-trend {
	display: flex !important;
	align-items: center !important;
	gap: 0.5rem !important;
	padding: 0.5rem 0.75rem !important;
	border-radius: 0.5rem !important;
	font-size: 0.875rem !important;
	font-weight: 600 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-trend.positive {
	background: #d1fae5 !important;
	color: #065f46 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-trend.negative {
	background: #fee2e2 !important;
	color: #991b1b !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-trend.neutral {
	background: #f1f5f9 !important;
	color: #64748b !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-value {
	font-size: 2.5rem !important;
	font-weight: 800 !important;
	line-height: 1 !important;
	margin-bottom: 0.5rem !important;
	letter-spacing: -0.02em !important;
	color: #1a2842 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-metric-subtitle {
	font-size: 0.875rem !important;
	color: #64748b !important;
	line-height: 1.5 !important;
	margin: 0 !important;
}

/* Secondary Grid */
body.wp-admin .wrap.aria-content-indexing .aria-secondary-grid {
	display: grid !important;
	grid-template-columns: 1fr 1fr !important;
	gap: 1.5rem !important;
	margin-top: 2rem !important;
}

/* Actions List */
body.wp-admin .wrap.aria-content-indexing .aria-actions-list {
	display: flex !important;
	flex-direction: column !important;
	gap: 0.75rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-item {
	display: flex !important;
	align-items: center !important;
	gap: 1rem !important;
	padding: 1rem !important;
	background: #f8fafc !important;
	border: 1px solid #e2e8f0 !important;
	border-radius: 0.75rem !important;
	transition: all 0.2s ease !important;
	cursor: pointer !important;
	text-align: left !important;
	width: 100% !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-item:hover {
	background: #f1f5f9 !important;
	border-color: #cbd5e1 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-item:disabled {
	opacity: 0.6 !important;
	cursor: not-allowed !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-icon {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 40px !important;
	height: 40px !important;
	background: white !important;
	border-radius: 0.5rem !important;
	color: #64748b !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-content {
	flex: 1 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-title {
	display: block !important;
	font-weight: 600 !important;
	color: #1a2842 !important;
	font-size: 0.875rem !important;
	margin-bottom: 0.25rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-action-description {
	display: block !important;
	font-size: 0.75rem !important;
	color: #64748b !important;
}

/* Modal styles with highest specificity */
body.wp-admin .aria-modal-overlay {
	position: fixed !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	bottom: 0 !important;
	background: rgba(0, 0, 0, 0.5) !important;
	display: none !important;
	align-items: center !important;
	justify-content: center !important;
	z-index: 999999 !important;
}

body.wp-admin .aria-modal-overlay.aria-modal-show {
	display: flex !important;
}

body.wp-admin .aria-modal {
	background: white !important;
	border-radius: 1rem !important;
	padding: 2rem !important;
	max-width: 600px !important;
	width: 90vw !important;
	max-height: 80vh !important;
	overflow-y: auto !important;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
}

body.wp-admin .aria-modal-header {
	display: flex !important;
	justify-content: space-between !important;
	align-items: center !important;
	margin-bottom: 1.5rem !important;
	padding-bottom: 1rem !important;
	border-bottom: 1px solid #e2e8f0 !important;
}

body.wp-admin .aria-modal-header h3 {
	margin: 0 !important;
	font-size: 1.5rem !important;
	font-weight: 600 !important;
	color: #1a2842 !important;
}

body.wp-admin .aria-modal-close {
	background: none !important;
	border: none !important;
	color: #64748b !important;
	cursor: pointer !important;
	padding: 0.5rem !important;
	border-radius: 0.5rem !important;
	transition: background-color 0.2s ease !important;
}

body.wp-admin .aria-modal-close:hover {
	background: #f1f5f9 !important;
}

body.wp-admin .aria-search-form {
	display: flex !important;
	flex-direction: column !important;
	gap: 1rem !important;
	margin-bottom: 2rem !important;
}

body.wp-admin .aria-search-form label {
	font-weight: 600 !important;
	color: #1a2842 !important;
}

body.wp-admin .aria-search-form input {
	padding: 0.75rem !important;
	border: 1px solid #d1d5db !important;
	border-radius: 0.5rem !important;
	font-size: 1rem !important;
	width: 100% !important;
	margin: 0.5rem 0 !important;
}

/* Content breakdown styles */
body.wp-admin .wrap.aria-content-indexing .aria-content-breakdown {
	display: flex !important;
	flex-direction: column !important;
	gap: 1rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-content-item {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	padding: 0.75rem 0 !important;
	border-bottom: 1px solid #f1f5f9 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-content-item:last-child {
	border-bottom: none !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-content-info {
	display: flex !important;
	flex-direction: column !important;
	gap: 0.25rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-content-name {
	font-weight: 600 !important;
	color: #1e293b !important;
	font-size: 0.875rem !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-content-count {
	font-size: 0.75rem !important;
	color: #64748b !important;
}

/* Empty state */
body.wp-admin .wrap.aria-content-indexing .aria-conversation-empty {
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	justify-content: center !important;
	padding: 2rem !important;
	text-align: center !important;
	color: #64748b !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-conversation-empty svg {
	margin-bottom: 1rem !important;
	opacity: 0.6 !important;
}

body.wp-admin .wrap.aria-content-indexing .aria-conversation-empty p {
	margin: 0 !important;
}

/* Responsive Design */
@media (max-width: 1024px) {
	body.wp-admin .wrap.aria-content-indexing .aria-secondary-grid {
		grid-template-columns: 1fr !important;
	}
}

@media (max-width: 768px) {
	body.wp-admin .wrap.aria-content-indexing .aria-dashboard-container {
		padding: 1rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-page-header {
		padding: 1.5rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-header-content {
		flex-direction: column !important;
		align-items: stretch !important;
		gap: 1.5rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-page-title {
		font-size: 2rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-metrics-grid {
		grid-template-columns: 1fr !important;
		gap: 1rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-metric-card {
		padding: 1.5rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-header-actions {
		flex-direction: column !important;
		gap: 0.75rem !important;
	}
	
	body.wp-admin .wrap.aria-content-indexing .aria-btn {
		justify-content: center !important;
	}
}

/* Hide WordPress admin wrapper styles */
body.wp-admin .wrap.aria-content-indexing h1,
body.wp-admin .wrap.aria-content-indexing h2 {
	display: none !important;
}

body.wp-admin .wrap.aria-content-indexing .notice {
	margin: 0 0 2rem 0 !important;
	border-radius: 0.75rem !important;
}
</style>

<div class="wrap aria-content-indexing">
	<!-- Logo Component - EXACT MATCH TO DASHBOARD -->
	<div class="aria-logo-header">
		<?php 
		// Include centralized logo component - SAME AS DASHBOARD
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
	</div>

	<div class="aria-dashboard-container">
		<!-- Page Header - EXACT MATCH TO DASHBOARD STRUCTURE -->
		<div class="aria-page-title-section">
			<h1 class="aria-main-title">Content Indexing</h1>
			<p class="aria-main-subtitle">Manage your site's AI-powered content search and indexing system</p>
		</div>

		<!-- Action Buttons Row - EXACT MATCH TO DASHBOARD -->
		<div class="aria-header-actions-row">
			<button type="button" class="aria-btn aria-btn--secondary" id="aria-vector-help">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
					<circle cx="12" cy="17" r="1"/>
				</svg>
				What are Content Vectors?
			</button>
			<!-- Always show test button for demo purposes -->
			<button type="button" class="aria-btn aria-btn--primary" id="test-search-btn">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<circle cx="11" cy="11" r="8"/>
					<path d="m21 21-4.35-4.35"/>
				</svg>
				Test Search
			</button>
		</div>

		<!-- Page Content - Following exact dashboard pattern -->
		<div class="aria-page-content aria-page-content--active">
			
			<!-- Primary Focus: System Overview -->
			<div class="aria-primary-section">
				<div class="aria-metrics-header">
					<h2 class="aria-section-title">System Overview</h2>
					<p class="aria-section-description">Real-time monitoring of your AI-powered content system</p>
				</div>
				<div class="aria-metrics-grid">
					<!-- Content Vectors Card -->
					<div class="aria-metric-card">
						<div class="aria-metric-header">
							<h3 class="aria-metric-label">Content Vectors</h3>
							<span class="aria-metric-trend <?php echo $coverage_percentage >= 90 ? 'positive' : ($coverage_percentage >= 50 ? 'neutral' : 'negative'); ?>">
								<?php echo number_format( $coverage_percentage, 1 ); ?>% Coverage
							</span>
						</div>
						<div class="aria-metric-value"><?php echo number_format( $total_vectors ); ?></div>
						<div class="aria-metric-subtitle"><?php echo number_format( $indexable_count ); ?> total items available</div>
					</div>

					<!-- System Status Card -->
					<div class="aria-metric-card">
						<div class="aria-metric-header">
							<h3 class="aria-metric-label">System Status</h3>
							<span class="aria-metric-trend <?php echo $initial_indexing_complete ? 'positive' : 'neutral'; ?>">
								<?php echo $initial_indexing_complete ? 'Ready' : 'Processing'; ?>
							</span>
						</div>
						<div class="aria-metric-value"><?php echo $initial_indexing_complete ? 'Ready' : 'Working'; ?></div>
						<div class="aria-metric-subtitle"><?php echo $initial_indexing_complete ? 'All systems operational' : 'Indexing in progress'; ?></div>
					</div>

					<!-- Storage Usage Card -->
					<div class="aria-metric-card">
						<div class="aria-metric-header">
							<h3 class="aria-metric-label">Storage Usage</h3>
							<span class="aria-metric-trend neutral">
								<?php echo number_format( $avg_vector_size, 1 ); ?> KB avg
							</span>
						</div>
						<div class="aria-metric-value"><?php echo $vector_size_mb; ?> MB</div>
						<div class="aria-metric-subtitle">Vector database size</div>
					</div>
				</div>
			</div>

			<!-- Secondary: Content Management -->
			<div class="aria-secondary-grid">
				<!-- Content Types -->
				<div class="aria-metric-card aria-metric-card--info">
					<div class="aria-metric-header">
						<h3 class="aria-metric-label">Content Types</h3>
						<span class="aria-metric-trend neutral">
							<?php echo count( $indexing_stats['by_type'] ?? array() ); ?> types
						</span>
					</div>
					<div class="aria-content-breakdown">
						<?php if ( ! empty( $indexing_stats['by_type'] ) ) : ?>
							<?php foreach ( $indexing_stats['by_type'] as $type => $count ) : ?>
							<div class="aria-content-item">
								<div class="aria-content-info">
									<span class="aria-content-name"><?php echo esc_html( ucfirst( $type ) ); ?></span>
									<span class="aria-content-count"><?php echo number_format( $count ); ?> items</span>
								</div>
							</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="aria-conversation-empty">
								<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
									<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
									<polyline points="14,2 14,8 20,8"/>
								</svg>
								<p class="aria-metric-subtitle">No content types indexed yet</p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- System Actions -->
				<div class="aria-metric-card">
					<div class="aria-metric-header">
						<h3 class="aria-metric-label">Quick Actions</h3>
					</div>
					<div class="aria-actions-list">
						<button type="button" id="start-indexing" class="aria-action-item" <?php echo $indexing_in_progress ? 'disabled' : ''; ?>>
							<div class="aria-action-icon">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
									<path d="M21 3v5h-5"/>
									<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
									<path d="M8 16H3v5"/>
								</svg>
							</div>
							<div class="aria-action-content">
								<span class="aria-action-title">Re-index Content</span>
								<span class="aria-action-description">Start full re-indexing</span>
							</div>
						</button>
						<button type="button" id="export-vectors" class="aria-action-item">
							<div class="aria-action-icon">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
									<polyline points="7,10 12,15 17,10"/>
									<line x1="12" y1="15" x2="12" y2="3"/>
								</svg>
							</div>
							<div class="aria-action-content">
								<span class="aria-action-title">Export Data</span>
								<span class="aria-action-description">Download vector data</span>
							</div>
						</button>
						<button type="button" id="check-system-health" class="aria-action-item">
							<div class="aria-action-icon">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
								</svg>
							</div>
							<div class="aria-action-content">
								<span class="aria-action-title">Health Check</span>
								<span class="aria-action-description">Run system diagnostics</span>
							</div>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Test Search Modal - HIDDEN BY DEFAULT -->
	<div id="test-search-modal" class="aria-modal-overlay">
		<div class="aria-modal" onclick="event.stopPropagation()">
			<div class="aria-modal-header">
				<h3>Test Vector Search</h3>
				<button type="button" class="aria-modal-close" onclick="closeTestModal()">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"/>
						<line x1="6" y1="6" x2="18" y2="18"/>
					</svg>
				</button>
			</div>
			<div class="aria-modal-content">
				<div class="aria-search-form">
					<label for="test-query">Enter your search query:</label>
					<input type="text" id="test-query" placeholder="What would visitors ask about?" />
					<button type="button" id="test-vector-search" class="aria-btn aria-btn--primary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<path d="m21 21-4.35-4.35"/>
						</svg>
						Search
					</button>
				</div>
				<div id="test-results" style="display: none; border-top: 1px solid #e2e8f0; margin-top: 1.5rem; padding-top: 1.5rem;">
					<h4>Search Results:</h4>
					<div id="test-results-content"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
// MODAL MANAGEMENT - Fixed with proper state control
let isModalOpen = false;

function showTestModal() {
	console.log('Opening modal');
	const modal = document.getElementById('test-search-modal');
	if (modal) {
		modal.classList.add('aria-modal-show');
		isModalOpen = true;
		document.getElementById('test-query')?.focus();
	}
}

function closeTestModal() {
	console.log('Closing modal');
	const modal = document.getElementById('test-search-modal');
	if (modal) {
		modal.classList.remove('aria-modal-show');
		isModalOpen = false;
	}
}

// Ensure modal is hidden on page load
document.addEventListener('DOMContentLoaded', function() {
	console.log('DOM loaded, ensuring modal is hidden');
	const modal = document.getElementById('test-search-modal');
	if (modal) {
		modal.classList.remove('aria-modal-show');
		isModalOpen = false;
	}

	// Localized JavaScript variables
	const ariaContentIndexing = {
		ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		nonce: '<?php echo esc_js( wp_create_nonce( 'aria_content_indexing' ) ); ?>',
		strings: {
			confirmClear: '<?php echo esc_js( __( 'Are you sure you want to clear all vectors? This action cannot be undone.', 'aria' ) ); ?>',
			confirmReindex: '<?php echo esc_js( __( 'This will re-index all content. Continue?', 'aria' ) ); ?>',
			searchFailed: '<?php echo esc_js( __( 'Search failed. Please try again.', 'aria' ) ); ?>',
			noResults: '<?php echo esc_js( __( 'No results found.', 'aria' ) ); ?>',
			loading: '<?php echo esc_js( __( 'Loading...', 'aria' ) ); ?>',
			error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'aria' ) ); ?>'
		}
	};

	// Test search button - FIXED
	const testSearchBtn = document.getElementById('test-search-btn');
	if (testSearchBtn) {
		testSearchBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			console.log('Test search button clicked');
			showTestModal();
		});
	}

	// Help button
	const helpBtn = document.getElementById('aria-vector-help');
	if (helpBtn) {
		helpBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			showVectorHelpDialog();
		});
	}

	// Modal close handlers
	const modalOverlay = document.getElementById('test-search-modal');
	if (modalOverlay) {
		modalOverlay.addEventListener('click', function(e) {
			if (e.target === modalOverlay) {
				closeTestModal();
			}
		});
	}

	// Escape key handler
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && isModalOpen) {
			closeTestModal();
		}
	});

	// Vector search test
	document.getElementById('test-vector-search')?.addEventListener('click', function() {
		const query = document.getElementById('test-query').value.trim();
		if (!query) return;
		
		const resultsDiv = document.getElementById('test-results');
		const contentDiv = document.getElementById('test-results-content');
		
		resultsDiv.style.display = 'block';
		contentDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: #64748b;">' + ariaContentIndexing.strings.loading + '</div>';
		
		fetch(ariaContentIndexing.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'aria_test_vector_search',
				nonce: ariaContentIndexing.nonce,
				query: query
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.results) {
				displaySearchResults(data.data.results, contentDiv);
			} else {
				contentDiv.innerHTML = '<p>' + (data.data?.message || ariaContentIndexing.strings.noResults) + '</p>';
			}
		})
		.catch(error => {
			contentDiv.innerHTML = '<p style="color: #ef4444;">' + ariaContentIndexing.strings.searchFailed + '</p>';
		});
	});

	// System actions
	document.getElementById('start-indexing')?.addEventListener('click', function() {
		if (!confirm(ariaContentIndexing.strings.confirmReindex)) return;
		performSystemAction('start_indexing', this);
	});

	document.getElementById('export-vectors')?.addEventListener('click', function() {
		performSystemAction('export_vectors', this);
	});

	document.getElementById('check-system-health')?.addEventListener('click', function() {
		performSystemAction('check_health', this);
	});

	// Helper functions
	function displaySearchResults(results, container) {
		if (!results.length) {
			container.innerHTML = '<p>' + ariaContentIndexing.strings.noResults + '</p>';
			return;
		}
		
		let html = '<div>';
		results.forEach(result => {
			html += `
				<div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; margin-bottom: 1rem;">
					<h5 style="margin: 0 0 0.5rem 0; color: #1a2842;">${result.title}</h5>
					<p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.875rem;">${result.excerpt}</p>
					<div style="display: flex; gap: 1rem; font-size: 0.75rem; color: #64748b;">
						<span>Similarity: ${(result.similarity * 100).toFixed(1)}%</span>
						<span>${result.post_type}</span>
					</div>
				</div>
			`;
		});
		html += '</div>';
		container.innerHTML = html;
	}

	function performSystemAction(action, button) {
		const originalText = button.innerHTML;
		button.disabled = true;
		button.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>Processing...</div>';
		
		fetch(ariaContentIndexing.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'aria_system_action',
				nonce: ariaContentIndexing.nonce,
				system_action: action
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				if (action === 'export_vectors' && data.data.download_url) {
					window.location.href = data.data.download_url;
				} else {
					alert(data.data.message || 'Action completed successfully.');
					if (action === 'start_indexing') {
						location.reload();
					}
				}
			} else {
				alert(data.data?.message || 'Action failed. Please try again.');
			}
		})
		.catch(error => {
			alert('Request failed. Please try again.');
		})
		.finally(() => {
			button.disabled = false;
			button.innerHTML = originalText;
		});
	}

	function showVectorHelpDialog() {
		const helpContent = `
			<div style="line-height: 1.6;">
				<h3 style="margin: 0 0 1rem 0; color: #1a2842;">What are Content Vectors?</h3>
				<p style="margin-bottom: 1.5rem; color: #475569;">Content vectors are mathematical representations of your website's text content that enable AI-powered search and contextual responses.</p>
				
				<h4 style="margin: 0 0 0.75rem 0; color: #1a2842;">How it works:</h4>
				<ul style="margin-bottom: 1.5rem; color: #475569;">
					<li><strong>Text Processing:</strong> Your posts, pages, and other content are analyzed and converted into numerical vectors</li>
					<li><strong>Semantic Understanding:</strong> Similar content gets similar vector representations, regardless of exact wording</li>
					<li><strong>Fast Search:</strong> When visitors ask questions, Aria searches these vectors to find the most relevant content</li>
					<li><strong>Context-Aware Responses:</strong> The AI uses matching content to provide accurate, relevant answers</li>
				</ul>
				
				<h4 style="margin: 0 0 0.75rem 0; color: #1a2842;">Benefits:</h4>
				<ul style="margin-bottom: 1.5rem; color: #475569;">
					<li>Much faster than traditional database searches</li>
					<li>Understands meaning, not just keywords</li>
					<li>Enables conversational interactions</li>
					<li>Automatically finds related content</li>
				</ul>
				
				<p style="margin: 0; color: #475569;"><strong>Privacy:</strong> Vectors contain no readable text - they're just numbers that represent meaning.</p>
			</div>
		`;
		
		// Create modal dialog
		const modal = document.createElement('div');
		modal.className = 'aria-modal-overlay aria-modal-show';
		modal.onclick = function() { document.body.removeChild(modal); };
		modal.innerHTML = `
			<div class="aria-modal" onclick="event.stopPropagation()">
				<div class="aria-modal-header">
					<h3>Understanding Content Vectors</h3>
					<button type="button" class="aria-modal-close" onclick="document.body.removeChild(this.closest('.aria-modal-overlay'))">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"/>
							<line x1="6" y1="6" x2="18" y2="18"/>
						</svg>
					</button>
				</div>
				<div class="aria-modal-content">
					${helpContent}
					<div style="margin-top: 2rem; text-align: right;">
						<button type="button" class="aria-btn aria-btn--primary" onclick="document.body.removeChild(this.closest('.aria-modal-overlay'))">Got it!</button>
					</div>
				</div>
			</div>
		`;
		
		document.body.appendChild(modal);
	}
});

// Add spin animation
const style = document.createElement('style');
style.textContent = `
	@keyframes spin {
		from { transform: rotate(0deg); }
		to { transform: rotate(360deg); }
	}
`;
document.head.appendChild(style);
</script>