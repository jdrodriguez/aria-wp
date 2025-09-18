<?php
/**
 * React-based Dashboard Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap aria-dashboard">
	<!-- Logo Component -->
	<div class="aria-logo-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
	</div>


	<!-- React component will be mounted here -->
	<div id="aria-dashboard-root" 
		 data-ajax-url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
		 data-nonce="<?php echo esc_attr( wp_create_nonce( 'aria_admin_nonce' ) ); ?>"
		 data-admin-url="<?php echo esc_attr( admin_url() ); ?>"></div>
	
</div>