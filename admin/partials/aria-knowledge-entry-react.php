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

<?php
	$action      = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'add';
	$entry_id    = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
	$return_url  = admin_url( 'admin.php?page=aria-knowledge' );
	$ajax_url    = admin_url( 'admin-ajax.php' );
	$admin_nonce = wp_create_nonce( 'aria_admin_nonce' );
	$generate_nonce = wp_create_nonce( 'aria_generate_knowledge' );
	$entry_config = array(
		'ajaxUrl'        => $ajax_url,
		'adminUrl'       => admin_url(),
		'pluginUrl'      => ARIA_PLUGIN_URL,
		'action'         => $action,
		'entryId'        => $entry_id,
		'nonce'          => $admin_nonce,
		'generateNonce'  => $generate_nonce,
		'returnUrl'      => $return_url,
	);
?>

<div class="wrap aria-knowledge-entry">
	<div
		id="aria-knowledge-entry-root"
		data-action="<?php echo esc_attr( $entry_config['action'] ); ?>"
		data-entry-id="<?php echo esc_attr( $entry_config['entryId'] ); ?>"
		data-return-url="<?php echo esc_url( $entry_config['returnUrl'] ); ?>"
		data-ajax-url="<?php echo esc_url( $entry_config['ajaxUrl'] ); ?>"
		data-nonce="<?php echo esc_attr( $entry_config['nonce'] ); ?>"
		data-generate-nonce="<?php echo esc_attr( $entry_config['generateNonce'] ); ?>"
	></div>
</div>

<script type="text/javascript">
	window.ariaKnowledgeEntry = <?php echo wp_json_encode( $entry_config ); ?>;
</script>
