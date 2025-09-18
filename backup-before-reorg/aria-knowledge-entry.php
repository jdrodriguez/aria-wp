<?php
/**
 * Knowledge Entry Add/Edit Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enhanced output buffer management for header compatibility
if ( ob_get_level() === 0 ) {
	ob_start();
}

// WordPress core compatibility - ensure parameters are safe
// These should already be sanitized by admin class, but double-check for safety
$action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? $_GET['action'] : 'add';
$entry_id = isset( $_GET['id'] ) && is_string( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

// Final validation
$valid_actions = array( 'add', 'edit' );
if ( ! in_array( $action, $valid_actions, true ) ) {
	$action = 'add';
}

// Ensure edit mode has valid entry ID
if ( 'edit' === $action && $entry_id <= 0 ) {
	$action = 'add';
	$entry_id = 0;
}

// Basic capability check
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'You do not have permission to access this page.', 'aria' ) );
}

// Set page title based on action
$page_title = ( $action === 'edit' ) ? __( 'Edit Knowledge Entry', 'aria' ) : __( 'Add New Knowledge Entry', 'aria' );
?>

<div class="wrap aria-knowledge-entry">
	<!-- Logo Component -->
	<div class="aria-logo-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
	</div>

	<!-- Page Header with Breadcrumb -->
	<div class="aria-page-header">
		<nav aria-label="<?php esc_attr_e( 'Breadcrumb', 'aria' ); ?>" style="margin-bottom: 16px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-knowledge' ) ); ?>" 
			   style="color: #2271b1; text-decoration: none;">
				← <?php esc_html_e( 'Back to Knowledge Base', 'aria' ); ?>
			</a>
		</nav>
		
		<h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>
		
		<?php if ( $action === 'add' ) : ?>
			<p class="description">
				<?php esc_html_e( 'Create a new knowledge entry for your AI assistant. You can use AI to help structure your content or create it manually.', 'aria' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Update this knowledge entry. Changes will be immediately available to your AI assistant.', 'aria' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<!-- React component will be mounted here -->
	<div id="aria-knowledge-entry-root" 
		 data-ajax-url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
		 data-nonce="<?php echo esc_attr( wp_create_nonce( 'aria_admin_nonce' ) ); ?>"
		 data-generate-nonce="<?php echo esc_attr( wp_create_nonce( 'aria_generate_knowledge' ) ); ?>"
		 data-admin-url="<?php echo esc_attr( admin_url() ); ?>"
		 data-action="<?php echo esc_attr( $action ); ?>"
		 data-entry-id="<?php echo esc_attr( (string) $entry_id ); ?>"
		 data-return-url="<?php echo esc_attr( admin_url( 'admin.php?page=aria-knowledge' ) ); ?>"></div>
	
</div>

<?php
// Enhanced output buffer cleanup for WordPress core compatibility
if ( ob_get_level() > 0 ) {
	$content = ob_get_clean();
	
	// Ensure clean output without any null characters that could cause issues
	$content = str_replace( "\0", '', $content );
	
	// Output the content safely
	echo $content;
}
?>