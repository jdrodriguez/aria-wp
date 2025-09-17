<?php
/**
 * Centralized Admin Page Layout Component
 * 
 * This component provides a single source of truth for all admin page layouts.
 * It ensures consistent width, spacing, and structure across all Aria admin pages.
 *
 * @package    Aria
 * @subpackage Aria/admin/partials/components  
 * @since      1.0.0
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a complete admin page with standardized layout
 * 
 * @param string $page_id         Page identifier (e.g., 'dashboard', 'content-indexing')
 * @param string $page_title      Page title
 * @param string $page_description Page description
 * @param string $content         Page content HTML
 * @param array  $options         Optional settings
 */
function aria_render_admin_page( $page_id, $page_title, $page_description, $content, $options = array() ) {
	// Default options
	$defaults = array(
		'show_logo'        => true,
		'grid_type'        => 'two-column', // 'single-column', 'two-column', 'three-column', 'four-column'
		'container_class'  => '',
		'before_content'   => '',
		'after_content'    => '',
	);
	
	$options = wp_parse_args( $options, $defaults );
	
	// Sanitize inputs
	$page_id = sanitize_title( $page_id );
	$page_title = esc_html( $page_title );
	$page_description = esc_html( $page_description );
	
	// Build CSS classes
	$wrap_classes = array( 'wrap', 'aria-' . $page_id );
	if ( ! empty( $options['container_class'] ) ) {
		$wrap_classes[] = esc_attr( $options['container_class'] );
	}
	
	// Grid classes
	$grid_classes = array( 'aria-metrics-grid' );
	switch ( $options['grid_type'] ) {
		case 'single-column':
		case 'one-column':
			// Default is single column on mobile
			break;
		case 'three-column':
		case 'three-columns':
			$grid_classes[] = 'three-columns';
			break;
		case 'four-column':
		case 'four-columns':
			$grid_classes[] = 'four-columns';
			break;
		case 'two-column':
		case 'two-columns':
		default:
			// Default is two columns
			break;
	}
	
	?>
	<div class="<?php echo esc_attr( implode( ' ', $wrap_classes ) ); ?>">
		<!-- Styled with SCSS grok-inspired design system in admin.scss -->
		
		<!-- Page Header with Logo -->
		<div class="aria-page-header">
			<?php if ( $options['show_logo'] ) : ?>
				<?php 
				// Include centralized logo component
				$logo_path = ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
				if ( file_exists( $logo_path ) ) {
					include $logo_path;
				}
				?>
			<?php endif; ?>
			<div class="aria-page-info">
				<h1 class="aria-page-title"><?php echo $page_title; ?></h1>
				<?php if ( ! empty( $page_description ) ) : ?>
					<p class="aria-page-description"><?php echo $page_description; ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="aria-page-content">
			<?php if ( ! empty( $options['before_content'] ) ) : ?>
				<?php echo $options['before_content']; ?>
			<?php endif; ?>
			
			<div class="<?php echo esc_attr( implode( ' ', $grid_classes ) ); ?>">
				<?php echo $content; ?>
			</div>
			
			<?php if ( ! empty( $options['after_content'] ) ) : ?>
				<?php echo $options['after_content']; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Create a standardized metric card
 * 
 * @param string $title       Card title
 * @param string $icon        Dashicon name (without 'dashicons-' prefix)
 * @param string $content     Card content HTML
 * @param array  $options     Optional settings
 * @return string Card HTML
 */
function aria_create_metric_card( $title, $icon, $content, $options = array() ) {
	// Default options
	$defaults = array(
		'custom_class' => '',
		'actions'      => '', // HTML for action buttons/links in header
	);
	
	$options = wp_parse_args( $options, $defaults );
	
	// Sanitize inputs
	$title = esc_html( $title );
	$icon = esc_attr( $icon );
	
	// Build CSS classes
	$card_classes = array( 'aria-metric-card' );
	if ( ! empty( $options['custom_class'] ) ) {
		$card_classes[] = esc_attr( $options['custom_class'] );
	}
	
	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
		<div class="metric-header">
			<span class="metric-icon dashicons dashicons-<?php echo $icon; ?>"></span>
			<h3><?php echo $title; ?></h3>
			<?php if ( ! empty( $options['actions'] ) ) : ?>
				<?php echo $options['actions']; ?>
			<?php endif; ?>
		</div>
		<div class="metric-content">
			<?php echo $content; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Helper function to build card content quickly
 * 
 * @param array $cards Array of card definitions
 * @return string Combined cards HTML
 */
function aria_build_cards( $cards ) {
	$output = '';
	
	foreach ( $cards as $card ) {
		if ( ! isset( $card['title'], $card['icon'], $card['content'] ) ) {
			continue;
		}
		
		$options = isset( $card['options'] ) ? $card['options'] : array();
		$output .= aria_create_metric_card( $card['title'], $card['icon'], $card['content'], $options );
	}
	
	return $output;
}

/**
 * Wrapper function for easy page rendering with card data
 * 
 * @param string $page_id         Page identifier
 * @param string $page_title      Page title  
 * @param string $page_description Page description
 * @param array  $cards           Array of card definitions
 * @param array  $options         Page options
 */
function aria_render_admin_page_with_cards( $page_id, $page_title, $page_description, $cards, $options = array() ) {
	$content = aria_build_cards( $cards );
	aria_render_admin_page( $page_id, $page_title, $page_description, $content, $options );
}