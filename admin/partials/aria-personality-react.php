<?php
/**
 * React-based Personality Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap aria-personality">
	<!-- Logo Component -->
	<div class="aria-logo-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
	</div>

	<!-- React component will be mounted here -->
	<div id="aria-personality-root"></div>
	
</div>