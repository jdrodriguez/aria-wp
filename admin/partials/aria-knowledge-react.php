<?php
/**
 * Provide a React-based admin area view for the Knowledge Base page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
	$ajax_url        = admin_url( 'admin-ajax.php' );
	$admin_nonce     = wp_create_nonce( 'aria_admin_nonce' );
	$generate_nonce  = wp_create_nonce( 'aria_generate_knowledge' );
?>

<div class="wrap aria-knowledge">
	<div
		id="aria-knowledge-root"
		data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
		data-nonce="<?php echo esc_attr( $admin_nonce ); ?>"
		data-generate-nonce="<?php echo esc_attr( $generate_nonce ); ?>"
	></div>
</div>

<script type="text/javascript">
	// Pass WordPress data to React component
	window.ariaKnowledge = {
		nonce: '<?php echo esc_js( $admin_nonce ); ?>',
		generateNonce: '<?php echo esc_js( $generate_nonce ); ?>',
		ajaxUrl: '<?php echo esc_url( $ajax_url ); ?>',
		adminUrl: '<?php echo esc_url( admin_url() ); ?>',
		pluginUrl: '<?php echo esc_url( ARIA_PLUGIN_URL ); ?>',
		strings: {
			knowledgeBase: '<?php echo esc_js( __( 'Knowledge Base', 'aria' ) ); ?>',
			teachAria: '<?php echo esc_js( __( 'Teach Aria About Your Business', 'aria' ) ); ?>',
			searchPlaceholder: '<?php echo esc_js( __( 'Search knowledge entries...', 'aria' ) ); ?>',
			addNew: '<?php echo esc_js( __( 'Add New Entry', 'aria' ) ); ?>',
			edit: '<?php echo esc_js( __( 'Edit', 'aria' ) ); ?>',
			delete: '<?php echo esc_js( __( 'Delete', 'aria' ) ); ?>',
			confirmDelete: '<?php echo esc_js( __( 'Are you sure you want to delete this entry?', 'aria' ) ); ?>',
			noEntries: '<?php echo esc_js( __( 'No knowledge entries found.', 'aria' ) ); ?>',
			loading: '<?php echo esc_js( __( 'Loading...', 'aria' ) ); ?>',
			error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'aria' ) ); ?>',
			saved: '<?php echo esc_js( __( 'Knowledge entry saved successfully!', 'aria' ) ); ?>',
			deleted: '<?php echo esc_js( __( 'Knowledge entry deleted successfully!', 'aria' ) ); ?>',
			category: '<?php echo esc_js( __( 'Category', 'aria' ) ); ?>',
			question: '<?php echo esc_js( __( 'Question', 'aria' ) ); ?>',
			answer: '<?php echo esc_js( __( 'Answer', 'aria' ) ); ?>',
			lastModified: '<?php echo esc_js( __( 'Last Modified', 'aria' ) ); ?>',
			bulkGenerate: '<?php echo esc_js( __( 'Bulk Generate with AI', 'aria' ) ); ?>',
			importExport: '<?php echo esc_js( __( 'Import/Export', 'aria' ) ); ?>',
		}
	};
</script>
