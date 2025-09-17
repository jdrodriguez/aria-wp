<?php
/**
 * ARIA Admin Logo Component
 *
 * @package    Aria
 * @subpackage Aria/admin/partials/components
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get logo settings
$logo_alignment = isset( $args['alignment'] ) ? $args['alignment'] : 'left';
$logo_size = isset( $args['size'] ) ? $args['size'] : 'normal';
$show_text = isset( $args['show_text'] ) ? $args['show_text'] : false;

// Get the wordmark URL
$wordmark_url = ARIA_PLUGIN_URL . 'assets/images/wordmark.png';
?>

<div class="aria-admin-logo aria-admin-logo--<?php echo esc_attr( $logo_alignment ); ?> aria-admin-logo--<?php echo esc_attr( $logo_size ); ?>">
	<img src="<?php echo esc_url( $wordmark_url ); ?>" 
	     alt="<?php esc_attr_e( 'ARIA', 'aria' ); ?>" 
	     class="aria-logo-image">
	
	<?php if ( $show_text ) : ?>
		<span class="aria-logo-text"><?php esc_html_e( 'ARIA', 'aria' ); ?></span>
	<?php endif; ?>
</div>

<style>
.aria-admin-logo {
	display: flex;
	align-items: center;
	gap: 12px;
}

.aria-admin-logo--left {
	justify-content: flex-start;
}

.aria-admin-logo--center {
	justify-content: center;
}

.aria-admin-logo--right {
	justify-content: flex-end;
}

.aria-admin-logo--small .aria-logo-image {
	height: 24px;
	width: auto;
	max-width: 120px;
}

.aria-admin-logo--normal .aria-logo-image {
	height: 32px;
	width: auto;
	max-width: 160px;
}

.aria-admin-logo--large .aria-logo-image {
	height: 48px;
	width: auto;
	max-width: 240px;
}

.aria-logo-text {
	font-size: 1.5rem;
	font-weight: 700;
	color: #1A2842;
	letter-spacing: -0.02em;
}

/* Ensure logo looks good on different backgrounds */
.aria-admin-logo .aria-logo-image {
	display: block;
	object-fit: contain;
}
</style>