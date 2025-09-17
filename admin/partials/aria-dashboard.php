<?php
/**
 * Admin Dashboard - Modern Professional Design
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get analytics data
$today_start = date( 'Y-m-d 00:00:00' );
$yesterday_start = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );

// Get conversation counts
$total_conversations = Aria_Database::get_conversations_count();
$conversations_today = Aria_Database::get_conversations_count( array(
	'date_from' => $today_start,
) );
$conversations_yesterday = Aria_Database::get_conversations_count( array(
	'date_from' => $yesterday_start,
	'date_to' => $today_start,
) );

// Calculate conversation growth
$conversation_growth = 0;
if ( $conversations_yesterday > 0 ) {
	$conversation_growth = round( ( ( $conversations_today - $conversations_yesterday ) / $conversations_yesterday ) * 100, 1 );
}

// Get knowledge entries
$knowledge_entries = Aria_Database::get_knowledge_entries( array( 'limit' => 0 ) );
$knowledge_count = is_array( $knowledge_entries ) ? count( $knowledge_entries ) : 0;

// Get recent knowledge entries (last 7 days)
$recent_knowledge_count = 0;
if ( is_array( $knowledge_entries ) ) {
	$seven_days_ago = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
	foreach ( $knowledge_entries as $entry ) {
		if ( isset( $entry['created_at'] ) && $entry['created_at'] >= $seven_days_ago ) {
			$recent_knowledge_count++;
		}
	}
}

// Calculate average response quality (mock data for now)
$avg_response_quality = 89; // This would come from actual analytics
$quality_trend = 5; // Positive trend

// Get response time (mock data for now)
$avg_response_time = 2.3; // seconds
$response_time_trend = -0.4; // Improved by 0.4s

// Get license status
$admin = new Aria_Admin( 'aria', ARIA_VERSION );
$license_method = new ReflectionMethod( 'Aria_Admin', 'get_license_status' );
$license_method->setAccessible( true );
$license_status = $license_method->invoke( $admin );

// Get setup completion status
$setup_complete = get_option( 'aria_setup_complete', false );
$api_configured = ! empty( get_option( 'aria_ai_api_key' ) );
$personality_configured = get_option( 'aria_personality_configured', false );
$design_configured = get_option( 'aria_design_configured', false );

// Calculate setup progress
$setup_steps_total = 4;
$setup_steps_completed = 0;
if ( $api_configured ) $setup_steps_completed++;
if ( $knowledge_count > 0 ) $setup_steps_completed++;
if ( $personality_configured ) $setup_steps_completed++;
if ( $design_configured ) $setup_steps_completed++;
$setup_progress = round( ( $setup_steps_completed / $setup_steps_total ) * 100 );

// Get recent conversations
$recent_conversations = Aria_Database::get_conversations( array( 'limit' => 5 ) );
?>

<div class="wrap aria-dashboard">
	<div class="aria-dashboard-container">
		<!-- Simplified Header -->
		<header class="aria-page-header">
			<div class="aria-header-content">
				<div class="aria-header-main">
					<h1 class="aria-page-title">Dashboard</h1>
					<p class="aria-page-description">Welcome back! Here's what's happening with Aria.</p>
				</div>
				<div class="aria-header-actions">
					<a href="<?php echo esc_url( get_site_url() ); ?>" target="_blank" class="aria-btn aria-btn--primary">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="m18 13 4-4-4-4"/>
							<path d="M14 5h6v14h-6"/>
							<path d="M2 12h12"/>
						</svg>
						Test Aria
					</a>
				</div>
			</div>
		</header>

		<!-- Progressive Layout Based on Setup State -->
		<?php if ( ! $setup_complete ) : ?>
			<!-- NEW USER EXPERIENCE: Setup-Focused Layout -->
			<div class="aria-page-content aria-page-content--setup">
				
				<!-- Primary Focus: Setup Wizard -->
				<div class="aria-primary-section">
					<div class="aria-welcome-hero">
						<div class="aria-welcome-content">
							<h2 class="aria-welcome-title">Let's get Aria ready for your visitors</h2>
							<p class="aria-welcome-description">Complete these <?php echo $setup_steps_total; ?> essential steps to activate your AI assistant.</p>
							<div class="aria-setup-progress">
								<div class="aria-progress-info">
									<span class="aria-progress-label"><?php echo $setup_steps_completed; ?> of <?php echo $setup_steps_total; ?> completed</span>
									<span class="aria-progress-percentage"><?php echo $setup_progress; ?>%</span>
								</div>
								<div class="aria-progress-bar">
									<div class="aria-progress-fill" style="width: <?php echo $setup_progress; ?>%"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Setup Steps - Clean, prioritized list -->
					<div class="aria-setup-steps">
						<!-- Step 1: AI Configuration -->
						<div class="aria-setup-step <?php echo $api_configured ? 'completed' : 'active'; ?>">
							<div class="aria-step-indicator">
								<?php if ( $api_configured ) : ?>
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
										<polyline points="20,6 9,17 4,12"/>
									</svg>
								<?php else : ?>
									<span class="aria-step-number">1</span>
								<?php endif; ?>
							</div>
							<div class="aria-step-content">
								<h3 class="aria-step-title">Connect AI Provider</h3>
								<p class="aria-step-description">Add your OpenAI or Google API key to power conversations</p>
								<?php if ( ! $api_configured ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-ai-config' ) ); ?>" class="aria-btn aria-btn--primary">
										Configure AI Provider
									</a>
								<?php endif; ?>
							</div>
						</div>

						<!-- Step 2: Knowledge Base -->
						<div class="aria-setup-step <?php echo $knowledge_count > 0 ? 'completed' : ( $api_configured ? 'active' : 'inactive' ); ?>">
							<div class="aria-step-indicator">
								<?php if ( $knowledge_count > 0 ) : ?>
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
										<polyline points="20,6 9,17 4,12"/>
									</svg>
								<?php else : ?>
									<span class="aria-step-number">2</span>
								<?php endif; ?>
							</div>
							<div class="aria-step-content">
								<h3 class="aria-step-title">Add Business Knowledge</h3>
								<p class="aria-step-description">Teach Aria about your products, services, and policies</p>
								<?php if ( $knowledge_count == 0 && $api_configured ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-knowledge' ) ); ?>" class="aria-btn aria-btn--primary">
										Add Knowledge
									</a>
								<?php elseif ( $knowledge_count > 0 ) : ?>
									<span class="aria-step-status"><?php echo $knowledge_count; ?> entries added</span>
								<?php endif; ?>
							</div>
						</div>

						<!-- Step 3: Personality -->
						<div class="aria-setup-step <?php echo $personality_configured ? 'completed' : ( $knowledge_count > 0 ? 'active' : 'inactive' ); ?>">
							<div class="aria-step-indicator">
								<?php if ( $personality_configured ) : ?>
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
										<polyline points="20,6 9,17 4,12"/>
									</svg>
								<?php else : ?>
									<span class="aria-step-number">3</span>
								<?php endif; ?>
							</div>
							<div class="aria-step-content">
								<h3 class="aria-step-title">Customize Personality</h3>
								<p class="aria-step-description">Set Aria's tone and conversation style for your brand</p>
								<?php if ( ! $personality_configured && $knowledge_count > 0 ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-personality' ) ); ?>" class="aria-btn aria-btn--primary">
										Customize Personality
									</a>
								<?php elseif ( $personality_configured ) : ?>
									<span class="aria-step-status">Configured</span>
								<?php endif; ?>
							</div>
						</div>

						<!-- Step 4: Design -->
						<div class="aria-setup-step <?php echo $design_configured ? 'completed' : ( $personality_configured ? 'active' : 'inactive' ); ?>">
							<div class="aria-step-indicator">
								<?php if ( $design_configured ) : ?>
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
										<polyline points="20,6 9,17 4,12"/>
									</svg>
								<?php else : ?>
									<span class="aria-step-number">4</span>
								<?php endif; ?>
							</div>
							<div class="aria-step-content">
								<h3 class="aria-step-title">Style the Widget</h3>
								<p class="aria-step-description">Design the chat widget to match your website</p>
								<?php if ( ! $design_configured && $personality_configured ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-design' ) ); ?>" class="aria-btn aria-btn--primary">
										Customize Design
									</a>
								<?php elseif ( $design_configured ) : ?>
									<span class="aria-step-status">Styled</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Secondary: Quick Preview (for new users) -->
				<div class="aria-secondary-section">
					<div class="aria-preview-card">
						<div class="aria-preview-header">
							<h3 class="aria-preview-title">Ready to test?</h3>
							<p class="aria-preview-description">See how Aria will interact with your visitors</p>
						</div>
						<div class="aria-preview-actions">
							<a href="<?php echo esc_url( get_site_url() ); ?>" target="_blank" class="aria-btn aria-btn--secondary aria-btn--full">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="m18 13 4-4-4-4"/>
									<path d="M14 5h6v14h-6"/>
									<path d="M2 12h12"/>
								</svg>
								Preview Your Website
							</a>
						</div>
					</div>
				</div>
			</div>

		<?php else : ?>
			<!-- ACTIVE USER EXPERIENCE: Metrics-Focused Layout -->
			<div class="aria-page-content aria-page-content--active">
				
				<!-- Primary Focus: Performance Metrics -->
				<div class="aria-primary-section">
					<div class="aria-metrics-header">
						<h2 class="aria-section-title">Performance Overview</h2>
						<p class="aria-section-description">Monitor Aria's activity and effectiveness</p>
					</div>
					<div class="aria-metrics-grid">
						<!-- Key Metrics - Simplified Design -->
						<div class="aria-metric-card">
							<div class="aria-metric-header">
								<h3 class="aria-metric-label">Total Conversations</h3>
								<span class="aria-metric-trend <?php echo $conversation_growth >= 0 ? 'positive' : 'negative'; ?>">
									<?php echo $conversation_growth !== 0 ? ($conversation_growth > 0 ? '+' : '') . $conversation_growth . '%' : '—'; ?>
								</span>
							</div>
							<div class="aria-metric-value"><?php echo number_format( $total_conversations ); ?></div>
							<div class="aria-metric-subtitle">Since yesterday</div>
						</div>

						<div class="aria-metric-card">
							<div class="aria-metric-header">
								<h3 class="aria-metric-label">Satisfaction Rate</h3>
								<span class="aria-metric-trend positive">
									<?php echo $quality_trend > 0 ? '+' . $quality_trend . '%' : 'Stable'; ?>
								</span>
							</div>
							<div class="aria-metric-value"><?php echo $avg_response_quality; ?>%</div>
							<div class="aria-metric-subtitle">This week</div>
						</div>

						<div class="aria-metric-card">
							<div class="aria-metric-header">
								<h3 class="aria-metric-label">Response Time</h3>
								<span class="aria-metric-trend <?php echo $response_time_trend < 0 ? 'positive' : 'neutral'; ?>">
									<?php echo $response_time_trend !== 0 ? abs( $response_time_trend ) . 's' : 'Stable'; ?>
								</span>
							</div>
							<div class="aria-metric-value"><?php echo $avg_response_time; ?>s</div>
							<div class="aria-metric-subtitle">Average</div>
						</div>
					</div>
				</div>

				<!-- Secondary: Recent Activity & Quick Actions -->
				<div class="aria-secondary-grid">
					<!-- Recent Conversations -->
					<div class="aria-metric-card aria-metric-card--info">
						<div class="aria-metric-header">
							<h3 class="aria-metric-label">Recent Activity</h3>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations' ) ); ?>" class="aria-metric-trend neutral">
								View All
							</a>
						</div>
						<div class="aria-conversation-list">
							<?php if ( ! empty( $recent_conversations ) ) : ?>
								<?php foreach ( array_slice( $recent_conversations, 0, 3 ) as $conversation ) : ?>
								<div class="aria-conversation-item">
									<div class="aria-conversation-content">
										<div class="aria-conversation-header">
											<span class="aria-conversation-user"><?php echo esc_html( $conversation['guest_name'] ?: 'Anonymous' ); ?></span>
											<span class="aria-conversation-time"><?php echo esc_html( human_time_diff( strtotime( $conversation['created_at'] ) ) . ' ago' ); ?></span>
										</div>
										<div class="aria-conversation-preview"><?php echo esc_html( wp_trim_words( $conversation['initial_question'], 8 ) ); ?></div>
									</div>
									<span class="aria-status-badge aria-status-badge--<?php echo esc_attr( $conversation['status'] === 'active' ? 'success' : 'neutral' ); ?>">
										<?php echo esc_html( ucfirst( $conversation['status'] ) ); ?>
									</span>
								</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="aria-conversation-empty">
									<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
										<path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/>
									</svg>
									<p class="aria-metric-subtitle">No recent conversations</p>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Quick Management Actions -->
					<div class="aria-actions-section">
						<div class="aria-section-header">
							<h3 class="aria-section-title">Quick Actions</h3>
						</div>
						<div class="aria-actions-list">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-knowledge&action=new' ) ); ?>" class="aria-action-item">
								<div class="aria-action-icon">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<circle cx="12" cy="12" r="10"/>
										<line x1="12" y1="8" x2="12" y2="16"/>
										<line x1="8" y1="12" x2="16" y2="12"/>
									</svg>
								</div>
								<div class="aria-action-content">
									<span class="aria-action-title">Add Knowledge</span>
									<span class="aria-action-description">Teach Aria new information</span>
								</div>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-personality' ) ); ?>" class="aria-action-item">
								<div class="aria-action-icon">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
										<circle cx="12" cy="7" r="4"/>
									</svg>
								</div>
								<div class="aria-action-content">
									<span class="aria-action-title">Edit Personality</span>
									<span class="aria-action-description">Adjust tone and style</span>
								</div>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations' ) ); ?>" class="aria-action-item">
								<div class="aria-action-icon">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/>
									</svg>
								</div>
								<div class="aria-action-content">
									<span class="aria-action-title">View All Chats</span>
									<span class="aria-action-description">Browse conversations</span>
								</div>
							</a>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- License Notice (if trial) -->
		<?php if ( 'trial' === $license_status['status'] ) : ?>
		<div class="aria-trial-notice">
			<div class="aria-trial-content">
				<div class="aria-trial-info">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"/>
						<path d="M12 6v6l4 2"/>
					</svg>
					<span class="aria-trial-text">Trial Mode - <?php echo $license_status['days_remaining']; ?> days remaining</span>
				</div>
				<a href="https://ariaplugin.com/pricing" target="_blank" class="aria-btn aria-btn--small aria-btn--warning">
					Upgrade Now
				</a>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<style>
/* Modern Dashboard Design System */
.wrap.aria-dashboard {
	margin: 0 !important;
	padding: 0 !important;
	background: #f8fafc;
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
}

.aria-dashboard-container {
	max-width: 1800px;
	margin: 0 auto;
	padding: 2rem;
	min-height: 100vh;
}

/* Modern Header Design */
.aria-dashboard-header {
	background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
	border: 1px solid #e1e5e9;
	border-radius: 1rem;
	padding: 2.5rem;
	margin-bottom: 2rem;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.05);
}

.aria-header-content {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 2rem;
}

.aria-header-main {
	display: flex;
	align-items: center;
	gap: 2rem;
}

.aria-title-section h1.aria-main-title {
	font-size: 2.5rem !important;
	font-weight: 700 !important;
	color: #1a2842 !important;
	margin: 0 0 0.5rem 0 !important;
	letter-spacing: -0.02em;
	line-height: 1.2;
}

.aria-title-section .aria-main-subtitle {
	font-size: 1.125rem !important;
	color: #64748b !important;
	margin: 0 !important;
	font-weight: 400;
}

.aria-header-actions {
	display: flex;
	gap: 1rem;
}

/* Modern Action Buttons */
.aria-action-btn {
	display: inline-flex !important;
	align-items: center;
	gap: 0.75rem;
	padding: 0.875rem 1.5rem;
	font-size: 0.95rem;
	font-weight: 600;
	text-decoration: none !important;
	border-radius: 0.75rem;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	border: none;
	cursor: pointer;
	font-family: inherit;
	line-height: 1.5;
}

.aria-action-btn--primary {
	background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
	color: white !important;
	box-shadow: 0 4px 14px rgba(0, 102, 255, 0.25);
}

.aria-action-btn--primary:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(0, 102, 255, 0.35);
	background: linear-gradient(135deg, #0052cc 0%, #003d99 100%);
}

.aria-action-btn--secondary {
	background: white;
	color: #1a2842 !important;
	border: 2px solid #e1e5e9;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.aria-action-btn--secondary:hover {
	transform: translateY(-2px);
	border-color: #0066ff;
	box-shadow: 0 4px 14px rgba(0, 102, 255, 0.1);
}

/* Modern Metrics Section */
.aria-metrics-section {
	margin-bottom: 3rem;
}

.aria-metrics-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
	gap: 1.5rem;
}

/* Sophisticated Metric Cards */
.aria-metric-card {
	background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
	border: 1px solid #e1e5e9;
	border-radius: 1rem;
	padding: 2rem;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	position: relative;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.aria-metric-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
	border-radius: 1rem 1rem 0 0;
	opacity: 0;
	transition: opacity 0.3s ease;
}

.aria-metric-card--primary::before { background: linear-gradient(90deg, #0066ff, #0052cc); }
.aria-metric-card--success::before { background: linear-gradient(90deg, #10b981, #059669); }
.aria-metric-card--info::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.aria-metric-card--warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }

.aria-metric-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 8px 20px rgba(0, 0, 0, 0.05);
	border-color: transparent;
}

.aria-metric-card:hover::before {
	opacity: 1;
}

.aria-metric-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 1.5rem;
}

.aria-metric-icon {
	width: 56px;
	height: 56px;
	border-radius: 1rem;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.aria-metric-card--primary .aria-metric-icon {
	background: linear-gradient(135deg, #e6f0ff, #dbeafe);
	color: #0066ff;
}

.aria-metric-card--success .aria-metric-icon {
	background: linear-gradient(135deg, #d1fae5, #ecfdf5);
	color: #10b981;
}

.aria-metric-card--info .aria-metric-icon {
	background: linear-gradient(135deg, #dbeafe, #eff6ff);
	color: #3b82f6;
}

.aria-metric-card--warning .aria-metric-icon {
	background: linear-gradient(135deg, #fef3c7, #fffbeb);
	color: #f59e0b;
}

.aria-metric-trend {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 0.5rem;
	font-size: 0.875rem;
	font-weight: 600;
}

.aria-metric-trend.positive {
	background: #d1fae5;
	color: #065f46;
}

.aria-metric-trend.negative {
	background: #fee2e2;
	color: #991b1b;
}

.aria-metric-trend.neutral {
	background: #f1f5f9;
	color: #64748b;
}

.aria-metric-value {
	font-size: 2.5rem;
	font-weight: 800;
	line-height: 1;
	margin-bottom: 0.5rem;
	letter-spacing: -0.02em;
}

.aria-metric-card--primary .aria-metric-value { color: #0066ff; }
.aria-metric-card--success .aria-metric-value { color: #10b981; }
.aria-metric-card--info .aria-metric-value { color: #3b82f6; }
.aria-metric-card--warning .aria-metric-value { color: #f59e0b; }

.aria-metric-label {
	font-size: 1.125rem;
	font-weight: 600;
	color: #1a2842;
	margin-bottom: 0.5rem;
}

.aria-metric-subtitle {
	font-size: 0.875rem;
	color: #64748b;
	line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
	.aria-dashboard-container {
		padding: 1rem;
	}
	
	.aria-dashboard-header {
		padding: 1.5rem;
	}
	
	.aria-header-content {
		flex-direction: column;
		align-items: stretch;
		gap: 1.5rem;
	}
	
	.aria-header-main {
		flex-direction: column;
		align-items: flex-start;
		gap: 1rem;
	}
	
	.aria-title-section h1.aria-main-title {
		font-size: 2rem !important;
	}
	
	.aria-metrics-grid {
		grid-template-columns: 1fr;
		gap: 1rem;
	}
	
	.aria-metric-card {
		padding: 1.5rem;
	}
	
	.aria-header-actions {
		flex-direction: column;
		gap: 0.75rem;
	}
	
	.aria-action-btn {
		justify-content: center;
	}
}

/* Conversation List Styles */
.aria-conversation-list {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

.aria-conversation-item {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 1rem;
	padding: 1rem;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border: 1px solid #e2e8f0;
	border-radius: 0.75rem;
	transition: all 0.2s ease;
}

.aria-conversation-item:hover {
	background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
	border-color: #cbd5e1;
	transform: translateY(-1px);
}

.aria-conversation-content {
	flex: 1;
	min-width: 0;
}

.aria-conversation-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 0.5rem;
	gap: 1rem;
}

.aria-conversation-user {
	font-weight: 600;
	color: #1e293b;
	font-size: 0.875rem;
}

.aria-conversation-time {
	font-size: 0.75rem;
	color: #64748b;
	white-space: nowrap;
}

.aria-conversation-preview {
	color: #475569;
	font-size: 0.875rem;
	line-height: 1.4;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

.aria-status-badge {
	display: inline-flex;
	align-items: center;
	padding: 0.375rem 0.75rem;
	border-radius: 0.5rem;
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.025em;
	white-space: nowrap;
}

.aria-status-badge--success {
	background: linear-gradient(135deg, #d1fae5, #a7f3d0);
	color: #065f46;
	border: 1px solid #10b981;
}

.aria-status-badge--neutral {
	background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
	color: #475569;
	border: 1px solid #94a3b8;
}

.aria-conversation-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 2rem;
	text-align: center;
	color: #64748b;
}

.aria-conversation-empty svg {
	margin-bottom: 1rem;
	opacity: 0.6;
}

.aria-conversation-empty p {
	margin: 0;
}

/* Secondary Grid Layout */
.aria-secondary-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 1.5rem;
	margin-top: 2rem;
}

@media (max-width: 1024px) {
	.aria-secondary-grid {
		grid-template-columns: 1fr;
	}
}

/* Hide WordPress admin wrapper styles */
.wp-admin .wrap.aria-dashboard h1,
.wp-admin .wrap.aria-dashboard h2 {
	display: none;
}

.wp-admin .wrap.aria-dashboard .notice {
	margin: 0 0 2rem 0;
	border-radius: 0.75rem;
}
</style>