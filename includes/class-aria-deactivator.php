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
		$hooks = array(
			'aria_daily_license_check',
			'aria_process_analytics',
			'aria_daily_summary_email',
			'aria_cleanup_cache',
			'aria_initial_content_indexing',
			'aria_process_learning',
			'aria_process_embeddings',
			'aria_process_migrated_entry',
			'aria_process_entry_batch',
			'aria_cleanup_processing',
			'aria_index_single_content',
		);

		foreach ( $hooks as $hook ) {
			self::unschedule_hook_events( $hook );
		}
	}

	/**
	 * Remove all scheduled events for a hook, regardless of arguments.
	 *
	 * @param string $hook Hook name.
	 */
	private static function unschedule_hook_events( $hook ) {
		if ( empty( $hook ) ) {
			return;
		}

		if ( ! function_exists( '_get_cron_array' ) ) {
			require_once ABSPATH . 'wp-includes/cron.php';
		}

		$crons = _get_cron_array();
		if ( empty( $crons ) || ! is_array( $crons ) ) {
			return;
		}

		foreach ( $crons as $timestamp => $events ) {
			if ( empty( $events[ $hook ] ) ) {
				continue;
			}

			foreach ( $events[ $hook ] as $event ) {
				$args = isset( $event['args'] ) ? $event['args'] : array();
				wp_unschedule_event( $timestamp, $hook, $args );
			}
		}

		// Best effort fallback for environments with wp_clear_scheduled_hook support.
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( $hook );
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
