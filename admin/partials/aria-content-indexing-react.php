<?php
/**
 * Provide a React-based admin area view for the Content Indexing page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap aria-content-indexing">
	<div id="aria-content-indexing-root"></div>
</div>

<script type="text/javascript">
	// Pass WordPress data to React component
	window.ariaContentIndexing = {
		nonce: '<?php echo esc_js( wp_create_nonce( 'aria_admin_nonce' ) ); ?>',
		ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		adminUrl: '<?php echo esc_url( admin_url() ); ?>',
		pluginUrl: '<?php echo esc_url( ARIA_PLUGIN_URL ); ?>',
		strings: {
			contentIndexing: '<?php echo esc_js( __( 'Content Indexing', 'aria' ) ); ?>',
			indexYourContent: '<?php echo esc_js( __( 'Index your website content for AI understanding', 'aria' ) ); ?>',
			selectContent: '<?php echo esc_js( __( 'Select Content to Index', 'aria' ) ); ?>',
			startIndexing: '<?php echo esc_js( __( 'Start Indexing', 'aria' ) ); ?>',
			stopIndexing: '<?php echo esc_js( __( 'Stop Indexing', 'aria' ) ); ?>',
			indexingInProgress: '<?php echo esc_js( __( 'Indexing in progress...', 'aria' ) ); ?>',
			indexingComplete: '<?php echo esc_js( __( 'Indexing complete!', 'aria' ) ); ?>',
			pages: '<?php echo esc_js( __( 'Pages', 'aria' ) ); ?>',
			posts: '<?php echo esc_js( __( 'Posts', 'aria' ) ); ?>',
			products: '<?php echo esc_js( __( 'Products', 'aria' ) ); ?>',
			loading: '<?php echo esc_js( __( 'Loading...', 'aria' ) ); ?>',
			error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'aria' ) ); ?>',
		}
	};
</script>
