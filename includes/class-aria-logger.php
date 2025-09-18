<?php
/**
 * Central logging utility.
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Provide gated logging helpers that respect plugin debug settings.
 */
class Aria_Logger {

	/**
	 * Option key for enabling debug logging via the UI.
	 */
	const OPTION_DEBUG_LOGGING = 'aria_debug_logging';

	/**
	 * Write an informational/debug message when logging is enabled.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context appended as JSON.
	 */
	public static function debug( $message, $context = array() ) {
		if ( ! self::is_debug_enabled() ) {
			return;
		}

		self::write( $message, $context );
	}

	/**
	 * Write an error message (always logged).
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context appended as JSON.
	 */
	public static function error( $message, $context = array() ) {
		self::write( $message, $context );
	}

	/**
	 * Determine if debug logging should be emitted.
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {
		$option_enabled = (bool) get_option( self::OPTION_DEBUG_LOGGING, false );

		if ( $option_enabled ) {
			return true;
		}

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Append a message to the PHP error log.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context appended as JSON.
	 */
	private static function write( $message, $context = array() ) {
		$log_message = self::normalize_message( $message );

		if ( ! empty( $context ) ) {
			$encoded_context = wp_json_encode( $context );
			if ( $encoded_context ) {
				$log_message .= ' | context: ' . $encoded_context;
			}
		}

		error_log( $log_message );
	}

	/**
	 * Ensure messages are consistently prefixed.
	 *
	 * @param string $message Raw message.
	 * @return string
	 */
	private static function normalize_message( $message ) {
		$message = is_string( $message ) ? $message : wp_json_encode( $message );

		if ( false === stripos( $message, 'Aria:' ) ) {
			return 'Aria: ' . $message;
		}

		return $message;
	}
}
