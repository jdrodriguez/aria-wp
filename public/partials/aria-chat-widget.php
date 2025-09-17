<?php
/**
 * Provide a public-facing view for the chat widget
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @package    Aria
 * @subpackage Aria/public/partials
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get widget settings
$enabled = get_option( 'aria_enable_chat', true );
if ( ! $enabled ) {
	return;
}

// Check if should display on current page
$show_on_pages = get_option( 'aria_show_on_pages', array( 'all' ) );
$hide_on_pages = get_option( 'aria_hide_on_pages', '' );

// Page visibility logic
$show_widget = false;

if ( in_array( 'all', $show_on_pages ) ) {
	$show_widget = true;
} else {
	if ( is_home() && in_array( 'home', $show_on_pages ) ) {
		$show_widget = true;
	}
	if ( is_single() && in_array( 'posts', $show_on_pages ) ) {
		$show_widget = true;
	}
	if ( is_page() && in_array( 'pages', $show_on_pages ) ) {
		$show_widget = true;
	}
	if ( function_exists( 'is_product' ) && is_product() && in_array( 'products', $show_on_pages ) ) {
		$show_widget = true;
	}
}

// Check hide pages
if ( $show_widget && ! empty( $hide_on_pages ) ) {
	$hide_list = array_map( 'trim', explode( "\n", $hide_on_pages ) );
	$current_url = home_url( $_SERVER['REQUEST_URI'] );
	$current_id = get_the_ID();
	
	foreach ( $hide_list as $hide_item ) {
		if ( $hide_item === $current_url || $hide_item == $current_id ) {
			$show_widget = false;
			break;
		}
	}
}

if ( ! $show_widget ) {
	return;
}

// The widget HTML is now handled by JavaScript
// We just need to ensure the container exists
?>
<!-- Aria Chat Widget Container -->
<div id="aria-chat-root" aria-live="polite"></div>