<?php
/**
 * Provide a React-based admin area view for the Conversations page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap aria-conversations">
	<div id="aria-conversations-root"></div>
</div>

<script type="text/javascript">
	// Pass WordPress data to React component
	window.ariaConversations = {
		nonce: '<?php echo esc_js( wp_create_nonce( 'aria_admin_nonce' ) ); ?>',
		ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		adminUrl: '<?php echo esc_url( admin_url() ); ?>',
		pluginUrl: '<?php echo esc_url( ARIA_PLUGIN_URL ); ?>',
		strings: {
			conversations: '<?php echo esc_js( __( 'Conversations', 'aria' ) ); ?>',
			viewAllConversations: '<?php echo esc_js( __( 'View and manage all chat conversations', 'aria' ) ); ?>',
			search: '<?php echo esc_js( __( 'Search conversations...', 'aria' ) ); ?>',
			export: '<?php echo esc_js( __( 'Export', 'aria' ) ); ?>',
			filter: '<?php echo esc_js( __( 'Filter', 'aria' ) ); ?>',
			noConversations: '<?php echo esc_js( __( 'No conversations found.', 'aria' ) ); ?>',
			loading: '<?php echo esc_js( __( 'Loading conversations...', 'aria' ) ); ?>',
			error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'aria' ) ); ?>',
		}
	};
</script>
