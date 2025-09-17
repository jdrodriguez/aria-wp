<?php
/**
 * OpenAI Provider
 *
 * @package    Aria
 * @subpackage Aria/includes/providers
 */

/**
 * OpenAI API provider implementation.
 */
class Aria_OpenAI_Provider extends Aria_AI_Provider_Base {

	/**
	 * Constructor.
	 *
	 * @param string $api_key OpenAI API key.
	 */
	public function __construct( $api_key ) {
		$this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
		$this->model = get_option( 'aria_openai_model', 'gpt-4.1-nano' );
		parent::__construct( $api_key );
	}

	/**
	 * Authenticate with OpenAI.
	 *
	 * @param string $api_key API key.
	 * @return bool Success status.
	 */
	public function authenticate( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Decrypt if encrypted
		if ( class_exists( 'Aria_Security' ) ) {
			$decrypted = Aria_Security::decrypt( $api_key );
			if ( ! empty( $decrypted ) ) {
				$api_key = $decrypted;
			}
		}

		$this->api_key = $api_key;
		return true;
	}

	/**
	 * Generate response from OpenAI.
	 *
	 * @param string $prompt The prompt.
	 * @param string $context Conversation context.
	 * @return string AI response.
	 * @throws Exception If request fails.
	 */
	public function generate_response( $prompt, $context ) {
		$this->check_rate_limit();

		$messages = array();

		// Check if the prompt already contains role and knowledge base info
		// If it starts with "You are Aria", use it as the system message
		if ( strpos( $prompt, 'You are Aria' ) === 0 ) {
			// Use our complete prompt as the system message
			$messages[] = array(
				'role'    => 'system',
				'content' => $prompt,
			);
			
			// Add context if provided
			if ( ! empty( $context ) ) {
				$messages[] = array(
					'role'    => 'assistant',
					'content' => 'I understand the context from our previous conversation.',
				);
			}
		} else {
			// Fallback to old behavior if prompt doesn't have our format
			$system_message = Aria_Personality::generate_prompt_instructions();
			$messages[] = array(
				'role'    => 'system',
				'content' => $system_message,
			);

			// Add context if provided
			if ( ! empty( $context ) ) {
				$messages[] = array(
					'role'    => 'system',
					'content' => 'Previous conversation context: ' . $context,
				);
			}

			// Add user message
			$messages[] = array(
				'role'    => 'user',
				'content' => $this->prepare_prompt( $prompt ),
			);
		}

		$request_data = array(
			'model'       => $this->model,
			'messages'    => $messages,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
			'n'           => 1,
			'stop'        => null,
		);

		try {
			$headers = array(
				'Authorization' => 'Bearer ' . $this->api_key,
			);

			$response_data = $this->make_request( $this->api_endpoint, $request_data, $headers );

			if ( ! isset( $response_data['choices'][0]['message']['content'] ) ) {
				throw new Exception( 'Invalid response format from OpenAI' );
			}

			$response = $response_data['choices'][0]['message']['content'];

			// Log usage
			$this->log_usage(
				array( 'prompt' => $prompt ),
				array(
					'response' => $response,
					'usage'    => $response_data['usage'] ?? array(),
				)
			);

			return trim( $response );

		} catch ( Exception $e ) {
			// Log error
			error_log( 'Aria OpenAI Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Test OpenAI connection.
	 *
	 * @return bool Connection status.
	 */
	public function test_connection() {
		try {
			// Make a simple API call to test the key
			$request_data = array(
				'model'    => $this->model,
				'messages' => array(
					array(
						'role'    => 'user',
						'content' => 'Hello, this is a test. Please respond with "Connection successful."',
					),
				),
				'temperature' => 0.1,
				'max_tokens'  => 50,
			);

			$headers = array(
				'Authorization' => 'Bearer ' . $this->api_key,
			);

			$response_data = $this->make_request( $this->api_endpoint, $request_data, $headers );
			
			return isset( $response_data['choices'][0]['message']['content'] );
		} catch ( Exception $e ) {
			error_log( 'Aria OpenAI Test Connection Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get available OpenAI models.
	 *
	 * @return array Available models.
	 */
	public static function get_available_models() {
		return array(
			'gpt-4.1-nano' => array(
				'label'       => 'GPT-4.1 Nano',
				'description' => 'Ultra-efficient model optimized for speed and cost. Perfect for high-volume customer service, simple FAQs, and basic information queries. Delivers instant responses at minimal cost.',
				'max_tokens'  => 16384,
				'cost_level'  => 'low',
				'cost_note'   => 'Lowest cost - $0.10/$0.40 per million tokens',
			),
			'gpt-4.1-mini' => array(
				'label'       => 'GPT-4.1 Mini',
				'description' => 'Balanced intelligence and efficiency. Handles complex conversations, multi-step reasoning, and context-aware responses. Ideal sweet spot for most business applications.',
				'max_tokens'  => 128000,
				'cost_level'  => 'medium',
				'cost_note'   => 'Great value - $0.40/$1.60 per million tokens',
			),
			'gpt-4.1' => array(
				'label'       => 'GPT-4.1 (Premium)',
				'description' => 'Most advanced model with superior reasoning capabilities. Best for complex problem-solving, nuanced customer support, and high-stakes business conversations.',
				'max_tokens'  => 128000,
				'cost_level'  => 'high',
				'cost_note'   => '⚠️ Premium pricing - $2.00/$8.00 per million tokens (20x more than Nano)',
			),
		);
	}

	/**
	 * Estimate token usage.
	 *
	 * @param string $text Text to estimate.
	 * @return int Estimated tokens.
	 */
	public static function estimate_tokens( $text ) {
		// Rough estimation: 1 token ≈ 4 characters
		// More accurate would be to use tiktoken library
		return ceil( strlen( $text ) / 4 );
	}

	/**
	 * Calculate cost estimate.
	 *
	 * @param int    $tokens Token count.
	 * @param string $model Model name.
	 * @return float Estimated cost in USD.
	 */
	public static function calculate_cost( $tokens, $model = 'gpt-4.1-nano' ) {
		// OpenAI pricing per 1M tokens
		$pricing = array(
			'gpt-4.1-nano' => array( 'input' => 0.10, 'output' => 0.40 ),   // $0.10/$0.40 per 1M tokens
			'gpt-4.1-mini' => array( 'input' => 0.40, 'output' => 1.60 ),   // $0.40/$1.60 per 1M tokens
			'gpt-4.1'      => array( 'input' => 2.00, 'output' => 8.00 ),   // $2.00/$8.00 per 1M tokens
		);

		$model_pricing = isset( $pricing[ $model ] ) ? $pricing[ $model ] : $pricing['gpt-4.1-nano'];
		
		// Rough estimate: 70% input, 30% output
		$input_tokens  = $tokens * 0.7;
		$output_tokens = $tokens * 0.3;
		
		$cost = ( $input_tokens / 1000000 * $model_pricing['input'] ) + 
		        ( $output_tokens / 1000000 * $model_pricing['output'] );
		
		return round( $cost, 6 );
	}

	/**
	 * Generate embedding for a single text.
	 *
	 * @param string $text Text to embed.
	 * @return array|false Embedding vector or false on failure.
	 */
	public function generate_embedding( $text ) {
		try {
			$embeddings = $this->create_embeddings( array( $text ) );
			if ( isset( $embeddings['data'][0]['embedding'] ) ) {
				return $embeddings['data'][0]['embedding'];
			}
			return false;
		} catch ( Exception $e ) {
			error_log( 'Aria OpenAI Embedding Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Create embeddings for text chunks.
	 *
	 * @param array $texts Array of text strings to create embeddings for.
	 * @return array Array of embedding vectors.
	 * @throws Exception If embeddings request fails.
	 */
	public function create_embeddings( $texts ) {
		if ( empty( $texts ) ) {
			return array();
		}

		$this->check_rate_limit();

		// OpenAI embeddings endpoint
		$embeddings_endpoint = 'https://api.openai.com/v1/embeddings';
		$embedding_model = get_option( 'aria_openai_embedding_model', 'text-embedding-ada-002' );

		$request_data = array(
			'model' => $embedding_model,
			'input' => $texts,
		);

		try {
			$headers = array(
				'Authorization' => 'Bearer ' . $this->api_key,
			);

			$response_data = $this->make_request( $embeddings_endpoint, $request_data, $headers );

			if ( ! isset( $response_data['data'] ) || ! is_array( $response_data['data'] ) ) {
				throw new Exception( 'Invalid embeddings response format from OpenAI' );
			}

			// Log usage for embeddings
			$this->log_usage(
				array( 'texts' => $texts ),
				array(
					'embeddings_count' => count( $response_data['data'] ),
					'usage' => $response_data['usage'] ?? array(),
				)
			);

			// Return the response in the expected format for vector engine
			return $response_data;

		} catch ( Exception $e ) {
			error_log( 'Aria OpenAI Embeddings Error: ' . $e->getMessage() );
			throw $e;
		}
	}
}