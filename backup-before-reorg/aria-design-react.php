<?php
/**
 * React-based Design Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap aria-design">
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'Design', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'Customize the appearance and behavior of your chat widget', 'aria' ); ?></p>
		</div>
	</div>

	<div class="aria-page-content">
		<!-- React component will be mounted here -->
		<div id="aria-design-root"></div>
	</div>
</div>