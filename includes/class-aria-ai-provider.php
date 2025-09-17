<?php
/**
 * AI Provider Interface
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Interface for AI providers.
 */
interface Aria_AI_Provider_Interface {
	
	/**
	 * Authenticate with the AI service.
	 *
	 * @param string $api_key API key for authentication.
	 * @return bool Success status.
	 */
	public function authenticate( $api_key );

	/**
	 * Generate a response from the AI.
	 *
	 * @param string $prompt The prompt to send to the AI.
	 * @param string $context Additional context for the conversation.
	 * @return string The AI response.
	 * @throws Exception If the request fails.
	 */
	public function generate_response( $prompt, $context );

	/**
	 * Test the connection to the AI service.
	 *
	 * @return bool Connection status.
	 */
	public function test_connection();
}

/**
 * Base class for AI providers.
 */
abstract class Aria_AI_Provider_Base implements Aria_AI_Provider_Interface {
	
	/**
	 * API key.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	protected $api_endpoint;

	/**
	 * Model to use.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Maximum tokens for response.
	 *
	 * @var int
	 */
	protected $max_tokens = 500;

	/**
	 * Temperature for response generation.
	 *
	 * @var float
	 */
	protected $temperature = 0.7;

	/**
	 * Constructor.
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( $api_key ) {
		$this->authenticate( $api_key );
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $data Request data.
	 * @param array  $headers Additional headers.
	 * @return array Response data.
	 * @throws Exception If request fails.
	 */
	protected function make_request( $endpoint, $data, $headers = array() ) {
		$default_headers = array(
			'Content-Type' => 'application/json',
		);

		$headers = array_merge( $default_headers, $headers );

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( 'API request failed: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( $response_code >= 400 ) {
			$error_message = isset( $response_data['error']['message'] ) 
				? $response_data['error']['message'] 
				: 'API request failed with status ' . $response_code;
			throw new Exception( $error_message );
		}

		return $response_data;
	}

	/**
	 * Log API usage.
	 *
	 * @param array $request_data Request data.
	 * @param array $response_data Response data.
	 */
	protected function log_usage( $request_data, $response_data ) {
		$usage_log = array(
			'provider'    => get_class( $this ),
			'timestamp'   => current_time( 'mysql' ),
			'prompt_size' => strlen( $request_data['prompt'] ?? '' ),
			'response_size' => strlen( $response_data['response'] ?? '' ),
			'tokens_used' => $response_data['usage']['total_tokens'] ?? 0,
		);

		// Store usage data
		$usage_history = get_option( 'aria_ai_usage_history', array() );
		$usage_history[] = $usage_log;
		
		// Keep only last 1000 entries
		$usage_history = array_slice( $usage_history, -1000 );
		update_option( 'aria_ai_usage_history', $usage_history );

		// Update monthly usage
		$month_key = 'aria_ai_usage_' . date( 'Y_m' );
		$monthly_usage = get_option( $month_key, 0 );
		$monthly_usage += $usage_log['tokens_used'];
		update_option( $month_key, $monthly_usage );
	}

	/**
	 * Apply rate limiting.
	 *
	 * @throws Exception If rate limit exceeded.
	 */
	protected function check_rate_limit() {
		$transient_key = 'aria_ai_rate_limit_' . get_class( $this );
		$requests = get_transient( $transient_key );

		if ( false === $requests ) {
			set_transient( $transient_key, 1, MINUTE_IN_SECONDS );
			return;
		}

		// Allow 60 requests per minute
		if ( $requests >= 60 ) {
			throw new Exception( 'AI API rate limit exceeded. Please try again in a moment.' );
		}

		set_transient( $transient_key, $requests + 1, MINUTE_IN_SECONDS );
	}

	/**
	 * Clean and prepare prompt.
	 *
	 * @param string $prompt Raw prompt.
	 * @return string Cleaned prompt.
	 */
	protected function prepare_prompt( $prompt ) {
		// Remove excessive whitespace
		$prompt = preg_replace( '/\s+/', ' ', $prompt );
		
		// Trim
		$prompt = trim( $prompt );
		
		// Ensure it doesn't exceed token limits
		// Rough estimation: 1 token ≈ 4 characters
		$max_chars = $this->max_tokens * 4;
		if ( strlen( $prompt ) > $max_chars ) {
			$prompt = substr( $prompt, 0, $max_chars );
		}

		return $prompt;
	}

	/**
	 * Get model settings.
	 *
	 * @return array Model settings.
	 */
	public function get_model_settings() {
		return array(
			'model'       => $this->model,
			'max_tokens'  => $this->max_tokens,
			'temperature' => $this->temperature,
		);
	}

	/**
	 * Set model settings.
	 *
	 * @param array $settings Settings to apply.
	 */
	public function set_model_settings( $settings ) {
		if ( isset( $settings['model'] ) ) {
			$this->model = $settings['model'];
		}
		if ( isset( $settings['max_tokens'] ) ) {
			$this->max_tokens = intval( $settings['max_tokens'] );
		}
		if ( isset( $settings['temperature'] ) ) {
			$this->temperature = floatval( $settings['temperature'] );
		}
	}
}