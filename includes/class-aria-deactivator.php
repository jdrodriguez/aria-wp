<?php
/**
 * Fired during plugin deactivation
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Aria_Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * Clean up scheduled events and temporary data.
	 */
	public static function deactivate() {
		// Clear scheduled events
		self::clear_scheduled_events();

		// Clean up transients
		self::clean_transients();

		// Clear permalinks
		flush_rewrite_rules();
	}

	/**
	 * Clear all scheduled cron events.
	 */
	private static function clear_scheduled_events() {
		// Clear daily license check
		$timestamp = wp_next_scheduled( 'aria_daily_license_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aria_daily_license_check' );
		}

		// Clear analytics processing
		$timestamp = wp_next_scheduled( 'aria_process_analytics' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aria_process_analytics' );
		}
	}

	/**
	 * Clean up plugin transients.
	 */
	private static function clean_transients() {
		global $wpdb;

		// Delete all Aria transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_aria_%' 
			OR option_name LIKE '_transient_timeout_aria_%'"
		);
	}
}