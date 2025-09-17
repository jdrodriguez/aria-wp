<?php
/**
 * Conversations Management Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle actions
$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$conversation_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

// Export action is now handled via AJAX - removed direct handling

// Handle bulk actions
if ( isset( $_POST['bulk_action'] ) && isset( $_POST['conversation_ids'] ) ) {
	check_admin_referer( 'aria_conversations_bulk_action' );
	
	$bulk_action = sanitize_text_field( $_POST['bulk_action'] );
	$ids = array_map( 'intval', $_POST['conversation_ids'] );
	
	switch ( $bulk_action ) {
		case 'delete':
			foreach ( $ids as $id ) {
				Aria_Database::delete_conversation( $id );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected conversations have been deleted.', 'aria' ) . '</p></div>';
			break;
			
		case 'mark_resolved':
			foreach ( $ids as $id ) {
				Aria_Database::update_conversation_status( $id, 'resolved' );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected conversations have been marked as resolved.', 'aria' ) . '</p></div>';
			break;
			
		case 'mark_unresolved':
			foreach ( $ids as $id ) {
				Aria_Database::update_conversation_status( $id, 'active' );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected conversations have been marked as unresolved.', 'aria' ) . '</p></div>';
			break;
	}
}

// Get conversation data for view mode
$conversation_data = null;
$messages = array();
if ( 'view' === $action && $conversation_id > 0 ) {
	$conversation_data = Aria_Database::get_conversation( $conversation_id );
	if ( $conversation_data ) {
		$messages = Aria_Database::get_conversation_messages( $conversation_id );
	} else {
		$action = 'list';
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Conversation not found.', 'aria' ) . '</p></div>';
	}
}
?>

<div class="wrap aria-conversations">
	<!-- Styled with SCSS grok-inspired design system in admin.scss -->
	
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'Conversations', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'View and manage conversations with your website visitors', 'aria' ); ?></p>
		</div>
	</div>

	<?php if ( 'list' === $action ) : ?>
	<!-- Page Actions -->
	<div class="aria-page-actions" style="margin-bottom: 1.5rem; text-align: right;">
		<button type="button" id="export-conversations-csv" class="button button-primary aria-btn-primary">
			<span class="dashicons dashicons-download"></span>
			<?php esc_html_e( 'Export CSV', 'aria' ); ?>
		</button>
	</div>
	<?php endif; ?>

	<div class="aria-page-content">

	<?php if ( 'view' === $action && $conversation_data ) : ?>
		<!-- Single Conversation View -->
		<div class="aria-conversation-view">
			<div class="aria-conversation-header">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations' ) ); ?>" class="button button-secondary aria-btn-secondary">
					← <?php esc_html_e( 'Back to Conversations', 'aria' ); ?>
				</a>
				
				<div class="aria-conversation-info">
					<div class="aria-visitor">
						<h2><?php echo esc_html( $conversation_data['guest_name'] ?: __( 'Anonymous Visitor', 'aria' ) ); ?></h2>
						<?php if ( $conversation_data['guest_email'] ) : ?>
							<a href="mailto:<?php echo esc_attr( $conversation_data['guest_email'] ); ?>">
								<?php echo esc_html( $conversation_data['guest_email'] ); ?>
							</a>
						<?php endif; ?>
					</div>
					
					<div class="aria-meta">
						<span class="aria-status-badge aria-status-<?php echo esc_attr( $conversation_data['status'] ); ?>">
							<?php echo esc_html( ucfirst( $conversation_data['status'] ) ); ?>
						</span>
						<span><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $conversation_data['created_at'] ) ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="aria-conversation-content">
				<div class="aria-messages-container">
					<h3><?php esc_html_e( 'Chat Transcript', 'aria' ); ?></h3>
					
					<div class="aria-messages-list">
						<?php if ( ! empty( $messages ) ) : ?>
							<?php foreach ( $messages as $message ) : ?>
								<div class="aria-message <?php echo esc_attr( 'user' === ( $message['sender'] ?? '' ) ? 'aria-message-user' : 'aria-message-bot' ); ?>">
									<div class="aria-message-header">
										<span class="aria-message-sender">
											<?php echo 'user' === ( $message['sender'] ?? '' ) ? esc_html( $conversation_data['guest_name'] ?: __( 'Visitor', 'aria' ) ) : 'Aria'; ?>
										</span>
										<span class="aria-message-time">
											<?php echo esc_html( wp_date( get_option( 'time_format' ), strtotime( $message['timestamp'] ?? current_time( 'mysql' ) ) ) ); ?>
										</span>
									</div>
									<div class="aria-message-content">
										<?php echo wp_kses_post( nl2br( $message['content'] ?? '' ) ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="aria-conversation-sidebar">
					<!-- Quick Actions -->
					<div class="aria-sidebar-section">
						<h3><?php esc_html_e( 'Quick Actions', 'aria' ); ?></h3>
						<div class="aria-quick-actions">
							<?php if ( 'resolved' !== $conversation_data['status'] ) : ?>
								<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
								<button type="button" class="button button-primary aria-btn-primary" id="mark-resolved" data-id="<?php echo esc_attr( $conversation_id ); ?>">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Mark as Resolved', 'aria' ); ?>
								</button>
							<?php else : ?>
								<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
								<button type="button" class="button button-secondary aria-btn-secondary" id="mark-unresolved" data-id="<?php echo esc_attr( $conversation_id ); ?>">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Reopen', 'aria' ); ?>
								</button>
							<?php endif; ?>
							
							<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
							<button type="button" class="button button-secondary aria-btn-secondary" id="email-transcript" data-id="<?php echo esc_attr( $conversation_id ); ?>">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Email Transcript', 'aria' ); ?>
							</button>
							
							<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
							<button type="button" class="button button-secondary aria-btn-secondary" id="add-note" data-id="<?php echo esc_attr( $conversation_id ); ?>">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Add Note', 'aria' ); ?>
							</button>
						</div>
					</div>

					<!-- Analytics -->
					<div class="aria-sidebar-section">
						<h3><?php esc_html_e( 'Analytics', 'aria' ); ?></h3>
						<div class="aria-conversation-stats">
							<div class="aria-stat-item">
								<span class="aria-stat-label"><?php esc_html_e( 'Messages:', 'aria' ); ?></span>
								<span class="aria-stat-value"><?php echo count( $messages ); ?></span>
							</div>
							<div class="aria-stat-item">
								<span class="aria-stat-label"><?php esc_html_e( 'Duration:', 'aria' ); ?></span>
								<span class="aria-stat-value">
									<?php
									if ( ! empty( $messages ) && count( $messages ) > 1 ) {
										$first_message = reset( $messages );
										$last_message = end( $messages );
										$first_time = strtotime( $first_message['timestamp'] ?? current_time( 'mysql' ) );
										$last_time = strtotime( $last_message['timestamp'] ?? current_time( 'mysql' ) );
										$duration = abs( $last_time - $first_time );
										echo $duration > 0 ? esc_html( human_time_diff( 0, $duration ) ) : '-';
									} else {
										echo '-';
									}
									?>
								</span>
							</div>
							<div class="aria-stat-item">
								<span class="aria-stat-label"><?php esc_html_e( 'Sentiment:', 'aria' ); ?></span>
								<span class="aria-stat-value">
									<?php
									$sentiment = isset( $conversation_data['sentiment'] ) ? $conversation_data['sentiment'] : 'neutral';
									$sentiment_labels = array(
										'positive' => __( 'Positive', 'aria' ),
										'neutral'  => __( 'Neutral', 'aria' ),
										'negative' => __( 'Negative', 'aria' ),
									);
									echo esc_html( $sentiment_labels[ $sentiment ] );
									?>
								</span>
							</div>
						</div>
					</div>

					<!-- Learning Insights -->
					<?php
					$learning_data = Aria_Database::get_learning_data( array( 'conversation_id' => $conversation_id ) );
					if ( ! empty( $learning_data ) ) :
					?>
					<div class="aria-sidebar-section">
						<h3><?php esc_html_e( 'Learning Insights', 'aria' ); ?></h3>
						<div class="aria-learning-insights">
							<?php foreach ( $learning_data as $insight ) : ?>
								<div class="aria-insight-item">
									<?php if ( ! empty( $insight['question'] ) ) : ?>
										<div class="aria-insight-question">
											<strong><?php esc_html_e( 'Q:', 'aria' ); ?></strong>
											<?php echo esc_html( $insight['question'] ); ?>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $insight['feedback_rating'] ) ) : ?>
										<div class="aria-insight-feedback">
											<strong><?php esc_html_e( 'Feedback:', 'aria' ); ?></strong>
											<?php echo esc_html( ucfirst( $insight['feedback_rating'] ) ); ?>
										</div>
									<?php endif; ?>
									<?php if ( $insight['knowledge_gap'] == 1 ) : ?>
										<div class="aria-insight-gap">
											<span class="aria-badge aria-badge-warning"><?php esc_html_e( 'Knowledge Gap', 'aria' ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $insight['response_quality_score'] ) ) : ?>
										<div class="aria-insight-quality">
											<strong><?php esc_html_e( 'Quality Score:', 'aria' ); ?></strong>
											<?php echo esc_html( $insight['response_quality_score'] ); ?>%
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

					<!-- Notes -->
					<?php
					$notes = get_post_meta( $conversation_id, '_aria_conversation_notes', true );
					?>
					<div class="aria-sidebar-section">
						<h3><?php esc_html_e( 'Internal Notes', 'aria' ); ?></h3>
						<div id="aria-notes-container">
							<?php if ( ! empty( $notes ) && is_array( $notes ) ) : ?>
								<?php foreach ( $notes as $note ) : ?>
									<div class="aria-note">
										<div class="aria-note-meta">
											<strong><?php echo esc_html( get_userdata( $note['user_id'] )->display_name ); ?></strong>
											<span><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $note['created_at'] ) ) ); ?></span>
										</div>
										<div class="aria-note-content">
											<?php echo esc_html( $note['content'] ); ?>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="aria-no-notes"><?php esc_html_e( 'No notes added yet.', 'aria' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- Conversation Statistics Cards -->
		<?php
		// Get statistics for metric cards
		$total_conversations = Aria_Database::get_conversations_count( array() );
		$active_conversations = Aria_Database::get_conversations_count( array( 'status' => 'active' ) );
		$resolved_conversations = Aria_Database::get_conversations_count( array( 'status' => 'resolved' ) );
		$today_conversations = Aria_Database::get_conversations_count( array( 'date_from' => date( 'Y-m-d 00:00:00' ) ) );
		?>
		
		<!-- Conversation Statistics Cards -->
		<div class="aria-metrics-grid">
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-admin-comments"></span>
					<h3><?php esc_html_e( 'Total Conversations', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="metric-item large">
						<span class="item-value primary"><?php echo number_format( $total_conversations ); ?></span>
						<span class="item-label"><?php esc_html_e( 'All Time', 'aria' ); ?></span>
					</div>
					<div class="metric-item">
						<span class="item-value"><?php echo number_format( $today_conversations ); ?></span>
						<span class="item-label"><?php esc_html_e( 'Today', 'aria' ); ?></span>
					</div>
				</div>
			</div>
			
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-chart-pie"></span>
					<h3><?php esc_html_e( 'Status Overview', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="metric-item large">
						<span class="item-value secondary"><?php echo number_format( $active_conversations ); ?></span>
						<span class="item-label"><?php esc_html_e( 'Active', 'aria' ); ?></span>
					</div>
					<div class="metric-item">
						<span class="item-value"><?php echo number_format( $resolved_conversations ); ?></span>
						<span class="item-label"><?php esc_html_e( 'Resolved', 'aria' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Conversations List -->
		<?php
		// Get filter parameters
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$date_range = isset( $_GET['date_range'] ) ? sanitize_text_field( $_GET['date_range'] ) : '';
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		
		// Build query args
		$args = array(
			'search' => $search,
			'status' => $status,
			'limit'  => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
		);
		
		// Add date filtering
		if ( $date_range ) {
			switch ( $date_range ) {
				case 'today':
					$args['date_from'] = date( 'Y-m-d 00:00:00' );
					break;
				case 'yesterday':
					$args['date_from'] = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );
					$args['date_to'] = date( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );
					break;
				case 'week':
					$args['date_from'] = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
					break;
				case 'month':
					$args['date_from'] = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
					break;
			}
		}
		
		// Get conversations
		$conversations = Aria_Database::get_conversations( $args );
		$total_conversations_filtered = Aria_Database::get_conversations_count( $args );
		$total_pages = ceil( $total_conversations_filtered / $per_page );
		?>
		
		<div class="aria-conversations-filters">
			<form method="get" action="">
				<input type="hidden" name="page" value="aria-conversations">
				
				<div class="filter-row">
					<div class="search-box">
						<input type="search" 
						       name="s" 
						       id="conversation-search" 
						       value="<?php echo esc_attr( $search ); ?>"
						       placeholder="<?php esc_attr_e( 'Search conversations...', 'aria' ); ?>">
						<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
						<input type="submit" class="button button-secondary aria-btn-secondary" value="<?php esc_attr_e( 'Search', 'aria' ); ?>">
					</div>
					
					<select name="status" onchange="this.form.submit()">
						<option value=""><?php esc_html_e( 'All Status', 'aria' ); ?></option>
						<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'aria' ); ?></option>
						<option value="resolved" <?php selected( $status, 'resolved' ); ?>><?php esc_html_e( 'Resolved', 'aria' ); ?></option>
						<option value="abandoned" <?php selected( $status, 'abandoned' ); ?>><?php esc_html_e( 'Abandoned', 'aria' ); ?></option>
					</select>
					
					<select name="date_range" onchange="this.form.submit()">
						<option value=""><?php esc_html_e( 'All Time', 'aria' ); ?></option>
						<option value="today" <?php selected( $date_range, 'today' ); ?>><?php esc_html_e( 'Today', 'aria' ); ?></option>
						<option value="yesterday" <?php selected( $date_range, 'yesterday' ); ?>><?php esc_html_e( 'Yesterday', 'aria' ); ?></option>
						<option value="week" <?php selected( $date_range, 'week' ); ?>><?php esc_html_e( 'Last 7 Days', 'aria' ); ?></option>
						<option value="month" <?php selected( $date_range, 'month' ); ?>><?php esc_html_e( 'Last 30 Days', 'aria' ); ?></option>
					</select>
				</div>
			</form>
		</div>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'aria_conversations_bulk_action' ); ?>
			
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="bulk_action">
						<option value=""><?php esc_html_e( 'Bulk Actions', 'aria' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'aria' ); ?></option>
						<option value="mark_resolved"><?php esc_html_e( 'Mark as Resolved', 'aria' ); ?></option>
						<option value="mark_unresolved"><?php esc_html_e( 'Mark as Unresolved', 'aria' ); ?></option>
					</select>
					<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
				<input type="submit" class="button button-secondary aria-btn-secondary action" value="<?php esc_attr_e( 'Apply', 'aria' ); ?>">
				</div>
				
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php printf( esc_html__( '%d conversations', 'aria' ), $total_conversations ); ?>
					</span>
					
					<?php if ( $total_pages > 1 ) : ?>
						<span class="pagination-links">
							<?php
							echo paginate_links( array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
							) );
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" id="cb-select-all">
						</td>
						<th class="manage-column column-visitor column-primary">
							<?php esc_html_e( 'Visitor', 'aria' ); ?>
						</th>
						<th class="manage-column">
							<?php esc_html_e( 'Initial Question', 'aria' ); ?>
						</th>
						<th class="manage-column">
							<?php esc_html_e( 'Page', 'aria' ); ?>
						</th>
						<th class="manage-column">
							<?php esc_html_e( 'Messages', 'aria' ); ?>
						</th>
						<th class="manage-column">
							<?php esc_html_e( 'Status', 'aria' ); ?>
						</th>
						<th class="manage-column">
							<?php esc_html_e( 'Date', 'aria' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $conversations ) ) : ?>
						<?php foreach ( $conversations as $conversation ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="conversation_ids[]" value="<?php echo esc_attr( $conversation['id'] ); ?>">
								</th>
								<td class="visitor column-visitor column-primary">
									<strong>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations&action=view&id=' . $conversation['id'] ) ); ?>">
											<?php echo esc_html( $conversation['guest_name'] ?: __( 'Anonymous', 'aria' ) ); ?>
										</a>
									</strong>
									<?php if ( $conversation['guest_email'] ) : ?>
										<br><small><?php echo esc_html( $conversation['guest_email'] ); ?></small>
									<?php endif; ?>
									<div class="row-actions">
										<span class="view">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-conversations&action=view&id=' . $conversation['id'] ) ); ?>">
												<?php esc_html_e( 'View', 'aria' ); ?>
											</a> |
										</span>
										<span class="trash">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=aria-conversations&action=delete&id=' . $conversation['id'] ), 'aria_delete_conversation_' . $conversation['id'] ) ); ?>" 
											   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this conversation?', 'aria' ); ?>')">
												<?php esc_html_e( 'Delete', 'aria' ); ?>
											</a>
										</span>
									</div>
									<!-- Styled with SCSS aria-button-ghost mixin in admin.scss -->
									<button type="button" class="toggle-row button aria-btn-ghost">
										<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'aria' ); ?></span>
									</button>
								</td>
								<td>
									<?php echo esc_html( wp_trim_words( $conversation['initial_question'], 15 ) ); ?>
								</td>
								<td>
									<?php if ( isset( $conversation['page_url'] ) && ! empty( $conversation['page_url'] ) ) : ?>
										<a href="<?php echo esc_url( $conversation['page_url'] ); ?>" target="_blank">
											<?php echo esc_html( wp_trim_words( ( isset( $conversation['page_title'] ) && ! empty( $conversation['page_title'] ) ) ? $conversation['page_title'] : $conversation['page_url'], 10 ) ); ?>
											<span class="dashicons dashicons-external"></span>
										</a>
									<?php else : ?>
										<span class="aria-no-data"><?php esc_html_e( 'No page data', 'aria' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( $conversation['message_count'] ); ?>
								</td>
								<td>
									<span class="aria-status-badge aria-status-<?php echo esc_attr( $conversation['status'] ); ?>">
										<?php echo esc_html( ucfirst( $conversation['status'] ) ); ?>
									</span>
								</td>
								<td>
									<?php echo esc_html( human_time_diff( strtotime( $conversation['created_at'] ) ) . ' ' . __( 'ago', 'aria' ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="7">
								<?php esc_html_e( 'No conversations found.', 'aria' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</form>
	<?php endif; ?>
	
	<!-- Email Modal -->
	<div id="aria-email-modal" class="aria-modal" style="display: none;">
		<div class="aria-modal-overlay"></div>
		<div class="aria-modal-content">
			<div class="aria-modal-header">
				<h3><?php esc_html_e( 'Email Transcript', 'aria' ); ?></h3>
				<button type="button" class="aria-modal-close">&times;</button>
			</div>
			<div class="aria-modal-body">
				<label for="email-input"><?php esc_html_e( 'Email Address:', 'aria' ); ?></label>
				<input type="email" id="email-input" class="regular-text" placeholder="<?php esc_attr_e( 'Enter email address', 'aria' ); ?>" />
			</div>
			<div class="aria-modal-footer">
				<button type="button" class="button button-secondary aria-btn-secondary aria-modal-cancel"><?php esc_html_e( 'Cancel', 'aria' ); ?></button>
				<button type="button" class="button button-primary aria-btn-primary" id="send-email"><?php esc_html_e( 'Send Transcript', 'aria' ); ?></button>
			</div>
		</div>
	</div>
	
	<!-- Note Modal -->
	<div id="aria-note-modal" class="aria-modal" style="display: none;">
		<div class="aria-modal-overlay"></div>
		<div class="aria-modal-content">
			<div class="aria-modal-header">
				<h3><?php esc_html_e( 'Add Note', 'aria' ); ?></h3>
				<button type="button" class="aria-modal-close">&times;</button>
			</div>
			<div class="aria-modal-body">
				<label for="note-input"><?php esc_html_e( 'Note:', 'aria' ); ?></label>
				<textarea id="note-input" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Enter your note here...', 'aria' ); ?>"></textarea>
			</div>
			<div class="aria-modal-footer">
				<button type="button" class="button button-secondary aria-btn-secondary aria-modal-cancel"><?php esc_html_e( 'Cancel', 'aria' ); ?></button>
				<button type="button" class="button button-primary aria-btn-primary" id="save-note"><?php esc_html_e( 'Save Note', 'aria' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Select all checkboxes
	$('#cb-select-all').on('change', function() {
		$('input[name="conversation_ids[]"]').prop('checked', $(this).prop('checked'));
	});
	
	// Mark as resolved/unresolved
	$('#mark-resolved, #mark-unresolved').on('click', function() {
		var $button = $(this);
		var conversationId = $button.data('id');
		var newStatus = $button.attr('id') === 'mark-resolved' ? 'resolved' : 'active';
		var originalText = $button.html();
		
		// Show loading state
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js( __( 'Updating...', 'aria' ) ); ?>');
		
		$.post(ajaxurl, {
			action: 'aria_update_conversation_status',
			conversation_id: conversationId,
			status: newStatus,
			nonce: '<?php echo wp_create_nonce( 'aria_admin_nonce' ); ?>'
		}, function(response) {
			if (response.success) {
				// Show success notification briefly before reload
				$('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(1500)
					.fadeOut(function() {
						location.reload();
					});
			} else {
				$button.prop('disabled', false).html(originalText);
				// Show error in a more elegant way
				$('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || '<?php echo esc_js( __( 'Error updating status', 'aria' ) ); ?>') + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(5000)
					.fadeOut();
			}
		});
	});
	
	// Email transcript
	var currentConversationId = null;
	
	$('#email-transcript').on('click', function() {
		currentConversationId = $(this).data('id');
		$('#email-input').val('');
		$('#aria-email-modal').fadeIn(200);
		$('#email-input').focus();
	});
	
	$('#send-email').on('click', function() {
		var email = $('#email-input').val().trim();
		var $button = $(this);
		
		if (!email) {
			$('#email-input').focus().css('border-color', '#dc3545');
			return;
		}
		
		$button.prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'aria' ) ); ?>');
		
		$.post(ajaxurl, {
			action: 'aria_email_transcript',
			conversation_id: currentConversationId,
			email: email,
			nonce: '<?php echo wp_create_nonce( 'aria_admin_nonce' ); ?>'
		}, function(response) {
			$button.prop('disabled', false).text('<?php echo esc_js( __( 'Send Transcript', 'aria' ) ); ?>');
			$('#aria-email-modal').fadeOut(200);
			
			if (response.success) {
				// Show success notification
				$('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(4000)
					.fadeOut();
			} else {
				// Show error notification
				$('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || '<?php echo esc_js( __( 'Error sending email', 'aria' ) ); ?>') + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(5000)
					.fadeOut();
			}
		});
	});
	
	// Add note
	$('#add-note').on('click', function() {
		currentConversationId = $(this).data('id');
		$('#note-input').val('');
		$('#aria-note-modal').fadeIn(200);
		$('#note-input').focus();
	});
	
	$('#save-note').on('click', function() {
		var note = $('#note-input').val().trim();
		var $button = $(this);
		
		if (!note) {
			$('#note-input').focus().css('border-color', '#dc3545');
			return;
		}
		
		$button.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'aria' ) ); ?>');
		
		$.post(ajaxurl, {
			action: 'aria_add_conversation_note',
			conversation_id: currentConversationId,
			note: note,
			nonce: '<?php echo wp_create_nonce( 'aria_admin_nonce' ); ?>'
		}, function(response) {
			$button.prop('disabled', false).text('<?php echo esc_js( __( 'Save Note', 'aria' ) ); ?>');
			$('#aria-note-modal').fadeOut(200);
			
			if (response.success) {
				// Show success notification and reload
				$('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(2000)
					.fadeOut(function() {
						location.reload();
					});
			} else {
				// Show error notification
				$('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || '<?php echo esc_js( __( 'Error adding note', 'aria' ) ); ?>') + '</p></div>')
					.insertAfter('.aria-conversation-header')
					.delay(5000)
					.fadeOut();
			}
		});
	});
	
	// Modal close handlers
	$('.aria-modal-close, .aria-modal-cancel').on('click', function() {
		$('.aria-modal').fadeOut(200);
	});
	
	$('.aria-modal-overlay').on('click', function() {
		$('.aria-modal').fadeOut(200);
	});
	
	// Close modal on Escape key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape') {
			$('.aria-modal').fadeOut(200);
		}
	});
	
	// Export conversations CSV
	$('#export-conversations-csv').on('click', function() {
		var button = $(this);
		var originalText = button.html();
		
		// Show loading state
		button.html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js( __( 'Exporting...', 'aria' ) ); ?>');
		button.prop('disabled', true);
		
		// Create form for file download
		var form = $('<form>', {
			'method': 'POST',
			'action': ajaxurl,
			'target': '_blank'
		});
		
		// Add hidden fields
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'action',
			'value': 'aria_export_conversations_csv'
		}));
		
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'nonce',
			'value': '<?php echo wp_create_nonce( 'aria_export_conversations' ); ?>'
		}));
		
		// Append form to body and submit
		$('body').append(form);
		form.submit();
		form.remove();
		
		// Reset button after a short delay
		setTimeout(function() {
			button.html(originalText);
			button.prop('disabled', false);
		}, 2000);
	});
});
</script>

<style>
/* Spinning animation for loading states */
.dashicons.spin {
	animation: spin 1s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.aria-conversations-filters {
	margin: 20px 0;
}

.aria-conversations-filters .filter-row {
	display: flex;
	gap: 10px;
	align-items: center;
}

.aria-conversations-filters .search-box {
	float: none;
	display: flex;
	gap: 5px;
}

.aria-status-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 600;
}

.aria-status-active {
	background: #e8f5e9;
	color: #2e7d32;
}

.aria-status-resolved {
	background: #e3f2fd;
	color: #1976d2;
}

.aria-status-abandoned {
	background: #fff3e0;
	color: #f57c00;
}

/* Conversation View Styles */
.aria-conversation-view {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.aria-conversation-header {
	margin-bottom: 20px;
	padding-bottom: 20px;
	border-bottom: 1px solid #eee;
}

.aria-conversation-meta {
	margin-top: 15px;
	display: flex;
	gap: 30px;
	flex-wrap: wrap;
}

.aria-meta-item {
	font-size: 14px;
}

.aria-conversation-content {
	display: flex;
	gap: 30px;
}

.aria-messages-container {
	flex: 1;
}

.aria-messages-list {
	max-height: 600px;
	overflow-y: auto;
	padding: 20px;
	background: #f8f9fa;
	border-radius: 5px;
}

.aria-message {
	margin-bottom: 20px;
}

.aria-message-header {
	display: flex;
	justify-content: space-between;
	margin-bottom: 8px;
	font-size: 13px;
	color: #666;
}

.aria-message-sender {
	font-weight: 600;
}

.aria-message-content {
	padding: 12px 16px;
	border-radius: 8px;
	background: #fff;
	box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.aria-message-user .aria-message-content {
	background: #e3f2fd;
	margin-left: 50px;
}

.aria-message-bot .aria-message-content {
	background: #fff;
	margin-right: 50px;
}

.aria-conversation-sidebar {
	width: 300px;
}

.aria-sidebar-section {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
	margin-bottom: 20px;
}

.aria-sidebar-section h3 {
	margin-top: 0;
	margin-bottom: 15px;
}

.aria-quick-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.aria-quick-actions .button {
	width: 100%;
	text-align: left;
	display: flex;
	align-items: center;
	gap: 5px;
}

.aria-conversation-stats {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.aria-stat-item {
	display: flex;
	justify-content: space-between;
	font-size: 14px;
}

.aria-stat-label {
	color: #666;
}

.aria-stat-value {
	font-weight: 600;
}

.aria-note {
	margin-bottom: 15px;
	padding: 10px;
	background: #f8f9fa;
	border-radius: 5px;
}

.aria-note-meta {
	display: flex;
	justify-content: space-between;
	margin-bottom: 5px;
	font-size: 12px;
	color: #666;
}

.aria-note-content {
	font-size: 14px;
}

.aria-no-notes {
	color: #666;
	font-style: italic;
	margin: 0;
}

/* Beautiful Conversations Page Styles */
.aria-conversations-wrap {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	min-height: 100vh;
	margin: 0 -20px 0 -22px;
	padding: 30px;
	position: relative;
}

.aria-conversations-wrap::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: 
		radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
		radial-gradient(circle at 80% 20%, rgba(255, 118, 117, 0.2) 0%, transparent 50%),
		radial-gradient(circle at 40% 80%, rgba(255, 177, 153, 0.2) 0%, transparent 50%);
	pointer-events: none;
}

.aria-conversations-wrap > * {
	position: relative;
	z-index: 1;
}

.aria-conversations-wrap h1 {
	color: white;
	font-size: 2.5em;
	text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
	margin-bottom: 10px;
	font-weight: 300;
}

.aria-conversations-wrap .description {
	color: rgba(255, 255, 255, 0.9);
	font-size: 1.1em;
	margin-bottom: 30px;
}

.aria-page-actions {
	margin-bottom: 30px;
}

/* Page actions button styles moved to centralized SCSS system in admin.scss */

/* Conversation List Styles */
.aria-conversations-list {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20px);
	border-radius: 20px;
	padding: 30px;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
	border: 1px solid rgba(255, 255, 255, 0.2);
}

.aria-conversations-filters {
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	border-radius: 16px;
	padding: 25px;
	margin-bottom: 25px;
	box-shadow: 
		0 8px 32px rgba(0, 0, 0, 0.1),
		inset 0 1px 0 rgba(255, 255, 255, 0.8);
	border: 1px solid rgba(255, 255, 255, 0.3);
	display: flex;
	gap: 20px;
	align-items: center;
	flex-wrap: wrap;
}

.aria-conversations-filters .search-box input {
	padding: 12px 16px;
	border: 2px solid #e2e8f0;
	border-radius: 12px;
	font-size: 14px;
	background: white;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
	width: 300px;
}

.aria-conversations-filters .search-box input:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 
		0 1px 3px rgba(0, 0, 0, 0.1),
		0 0 0 3px rgba(102, 126, 234, 0.1);
	transform: translateY(-1px);
}

.aria-conversations-filters select {
	padding: 12px 16px;
	border: 2px solid #e2e8f0;
	border-radius: 12px;
	font-size: 14px;
	background: white;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
}

.aria-conversations-filters select:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 
		0 1px 3px rgba(0, 0, 0, 0.1),
		0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Conversation filters button styles moved to centralized SCSS system in admin.scss */

/* Table Styles */
.wp-list-table {
	background: white;
	border-radius: 16px;
	overflow: hidden;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
	border: none;
}

.wp-list-table th {
	background: #f8f9fa;
	color: #2c3e50;
	padding: 20px;
	font-weight: 600;
	border: none;
	border-bottom: 2px solid #e2e8f0;
	text-transform: uppercase;
	font-size: 12px;
	letter-spacing: 0.5px;
}

.wp-list-table td {
	padding: 20px;
	border-bottom: 1px solid #f1f5f9;
	vertical-align: middle;
}

.wp-list-table tr:hover {
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
}

.aria-status-badge {
	display: inline-flex;
	align-items: center;
	padding: 6px 12px;
	border-radius: 20px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.aria-status-active {
	background: linear-gradient(135deg, #10b981, #34d399);
	color: white;
}

.aria-status-resolved {
	background: linear-gradient(135deg, #3b82f6, #60a5fa);
	color: white;
}

.aria-status-pending {
	background: linear-gradient(135deg, #f59e0b, #fbbf24);
	color: white;
}

/* Conversation View Styles */
.aria-conversation-view {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20px);
	border-radius: 20px;
	padding: 30px;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
	border: 1px solid rgba(255, 255, 255, 0.2);
}

.aria-conversation-header {
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	border-radius: 16px;
	padding: 25px;
	margin-bottom: 25px;
	box-shadow: 
		0 8px 32px rgba(0, 0, 0, 0.1),
		inset 0 1px 0 rgba(255, 255, 255, 0.8);
	border: 1px solid rgba(255, 255, 255, 0.3);
}

.aria-conversation-info h2 {
	margin: 0 0 20px 0;
	font-size: 1.4em;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 600;
}

.aria-conversation-meta {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 15px;
}

.aria-meta-item {
	display: flex;
	flex-direction: column;
	gap: 5px;
	padding: 15px;
	background: white;
	border-radius: 12px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.aria-meta-item strong {
	color: #4a5568;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

/* Messages Styles */
.aria-messages-container {
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	border-radius: 16px;
	padding: 25px;
	margin-bottom: 25px;
	box-shadow: 
		0 8px 32px rgba(0, 0, 0, 0.1),
		inset 0 1px 0 rgba(255, 255, 255, 0.8);
	border: 1px solid rgba(255, 255, 255, 0.3);
}

.aria-messages-container h3 {
	margin: 0 0 20px 0;
	font-size: 1.2em;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 600;
}

.aria-message {
	margin-bottom: 20px;
	padding: 20px;
	border-radius: 16px;
	transition: all 0.3s ease;
}

.aria-message:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.aria-message-bot {
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	border-left: 4px solid #667eea;
}

.aria-message-user {
	background: linear-gradient(145deg, #f1f5f9, #e2e8f0);
	border-left: 4px solid #10b981;
	margin-left: 40px;
}

.aria-message-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10px;
}

.aria-message-sender {
	font-weight: 600;
	color: #2d3748;
}

.aria-message-time {
	font-size: 12px;
	color: #64748b;
}

.aria-message-content {
	line-height: 1.6;
	color: #4a5568;
}

/* Responsive Design */
@media (max-width: 1200px) {
	.aria-conversations-wrap {
		margin: 0 -10px;
		padding: 20px;
	}
	
	.aria-conversation-meta {
		grid-template-columns: 1fr;
	}
}

@media (max-width: 768px) {
	.aria-conversations-wrap h1 {
		font-size: 2em;
	}
	
	.aria-conversations-filters {
		flex-direction: column;
		align-items: stretch;
	}
	
	.aria-conversations-filters .search-box input {
		width: 100%;
	}
	
	.aria-message-user {
		margin-left: 0;
	}
}

/* Modal Styles */
.aria-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 9999;
}

.aria-modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.5);
	backdrop-filter: blur(4px);
}

.aria-modal-content {
	position: relative;
	background: white;
	border-radius: 16px;
	max-width: 500px;
	margin: 10vh auto;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
	border: 1px solid rgba(255, 255, 255, 0.2);
	overflow: hidden;
}

.aria-modal-header {
	background: linear-gradient(135deg, #667eea, #764ba2);
	color: white;
	padding: 20px 25px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.aria-modal-header h3 {
	margin: 0;
	font-size: 1.1em;
	font-weight: 600;
}

.aria-modal-close {
	background: none;
	border: none;
	color: white;
	font-size: 24px;
	cursor: pointer;
	padding: 0;
	width: 30px;
	height: 30px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	transition: background-color 0.2s ease;
}

.aria-modal-close:hover {
	background: rgba(255, 255, 255, 0.1);
}

.aria-modal-body {
	padding: 25px;
}

.aria-modal-body label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #374151;
}

.aria-modal-body input,
.aria-modal-body textarea {
	width: 100%;
	padding: 12px 16px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	font-size: 14px;
	transition: border-color 0.2s ease;
}

.aria-modal-body input:focus,
.aria-modal-body textarea:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.aria-modal-footer {
	background: #f9fafb;
	padding: 20px 25px;
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}

.aria-modal-footer .button {
	min-width: 100px;
}

@media (max-width: 600px) {
	.aria-modal-content {
		margin: 5vh 20px;
		max-width: none;
	}
	
	.aria-modal-footer {
		flex-direction: column;
	}
	
	.aria-modal-footer .button {
		width: 100%;
	}
}
</style>