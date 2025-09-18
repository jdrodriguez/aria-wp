<?php
/**
 * Provide a React-based admin area view for the Knowledge Entry page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<div id="aria-knowledge-entry-root"></div>
</div>

<script type="text/javascript">
	// Pass WordPress data to React component
	window.ariaKnowledgeEntry = {
		nonce: '<?php echo esc_js( wp_create_nonce( 'aria_admin_nonce' ) ); ?>',
		ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		adminUrl: '<?php echo esc_url( admin_url() ); ?>',
		pluginUrl: '<?php echo esc_url( ARIA_PLUGIN_URL ); ?>',
		entryId: '<?php echo isset( $_GET['id'] ) ? esc_js( sanitize_text_field( $_GET['id'] ) ) : ''; ?>',
		action: '<?php echo isset( $_GET['action'] ) ? esc_js( sanitize_text_field( $_GET['action'] ) ) : 'add'; ?>',
		strings: {
			addKnowledge: '<?php echo esc_js( __( 'Add Knowledge Entry', 'aria' ) ); ?>',
			editKnowledge: '<?php echo esc_js( __( 'Edit Knowledge Entry', 'aria' ) ); ?>',
			question: '<?php echo esc_js( __( 'Question', 'aria' ) ); ?>',
			answer: '<?php echo esc_js( __( 'Answer', 'aria' ) ); ?>',
			category: '<?php echo esc_js( __( 'Category', 'aria' ) ); ?>',
			saveEntry: '<?php echo esc_js( __( 'Save Entry', 'aria' ) ); ?>',
			cancel: '<?php echo esc_js( __( 'Cancel', 'aria' ) ); ?>',
			saving: '<?php echo esc_js( __( 'Saving...', 'aria' ) ); ?>',
			saved: '<?php echo esc_js( __( 'Entry saved successfully!', 'aria' ) ); ?>',
			error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'aria' ) ); ?>',
		}
	};
</script>