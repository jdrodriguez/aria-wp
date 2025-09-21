<?php
/**
 * Google Gemini Provider
 *
 * @package    Aria
 * @subpackage Aria/includes/providers
 */

/**
 * Google Gemini API provider implementation.
 */
class Aria_Gemini_Provider extends Aria_AI_Provider_Base {

	/**
	 * Constructor.
	 *
	 * @param string $api_key Gemini API key.
	 */
	public function __construct( $api_key ) {
		$stored_model = get_option( 'aria_gemini_model', 'gemini-1.5-flash-8b' );
		$normalized_model = self::normalize_model_slug( $stored_model );

		$this->set_model( $normalized_model );

		if ( $normalized_model !== $stored_model ) {
			update_option( 'aria_gemini_model', $normalized_model );
		}

		parent::__construct( $api_key );
	}

	/**
	 * Authenticate with Google Gemini.
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
	 * Generate response from Gemini.
	 *
	 * @param string $prompt The prompt.
	 * @param string $context Conversation context.
	 * @return string AI response.
	 * @throws Exception If request fails.
	 */
	public function generate_response( $prompt, $context ) {
		$this->check_rate_limit();

		// Check if the prompt already contains role and knowledge base info
		if ( strpos( $prompt, 'You are Aria' ) === 0 ) {
			// Use our complete prompt that includes knowledge base
			$full_prompt = $prompt;
			
			if ( ! empty( $context ) ) {
				$full_prompt .= "\n\nPrevious conversation:\n" . $context;
			}
		} else {
			// Fallback to old behavior
			$full_prompt = Aria_Personality::generate_prompt_instructions() . "\n\n";
			
			if ( ! empty( $context ) ) {
				$full_prompt .= "Previous conversation:\n" . $context . "\n\n";
			}
			
			$full_prompt .= "User: " . $this->prepare_prompt( $prompt ) . "\n";
			$full_prompt .= "Assistant:";
		}

		$request_data = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => $full_prompt,
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => $this->temperature,
				'topK'            => 1,
				'topP'            => 1,
				'maxOutputTokens' => $this->max_tokens,
				'stopSequences'   => array(),
			),
			'safetySettings' => array(
				array(
					'category'  => 'HARM_CATEGORY_HARASSMENT',
					'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
				),
				array(
					'category'  => 'HARM_CATEGORY_HATE_SPEECH',
					'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
				),
				array(
					'category'  => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
					'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
				),
				array(
					'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
					'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
				),
			),
		);

		try {
			// Gemini uses API key as URL parameter
			$endpoint = $this->api_endpoint . '?key=' . $this->api_key;

			$response_data = $this->make_request( $endpoint, $request_data );

			if ( ! isset( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
				throw new Exception( 'Invalid response format from Gemini' );
			}

			$response = $response_data['candidates'][0]['content']['parts'][0]['text'];

			// Check for safety ratings
			if ( isset( $response_data['candidates'][0]['finishReason'] ) && 
			     'SAFETY' === $response_data['candidates'][0]['finishReason'] ) {
				throw new Exception( 'Response blocked due to safety filters' );
			}

			// Log usage
			$this->log_usage(
				array( 'prompt' => $prompt ),
				array(
					'response' => $response,
					'usage'    => array(
						'total_tokens' => $this->estimate_tokens( $full_prompt . $response ),
					),
				)
			);

			delete_transient( 'aria_ai_provider_error' );

			return trim( $response );

		} catch ( Exception $e ) {
			// Log error
			Aria_Logger::error( 'Aria Gemini Error: ' . $e->getMessage() );

			if ( function_exists( 'sanitize_text_field' ) ) {
				set_transient(
					'aria_ai_provider_error',
					array(
						'provider' => 'gemini',
						'message'  => sanitize_text_field( $e->getMessage() ),
					),
					HOUR_IN_SECONDS
				);
			}
			throw $e;
		}
	}

	/**
	 * Normalize and assign Gemini model while keeping endpoint in sync.
	 *
	 * @param string $model Gemini model identifier.
	 */
	private function set_model( $model ) {
		$this->model = $model;
		$this->api_endpoint = $this->build_endpoint( $model );
	}

	/**
	 * Build API endpoint for a given model.
	 *
	 * @param string $model Gemini model identifier.
	 * @return string
	 */
	private function build_endpoint( $model ) {
		return sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
			rawurlencode( $model )
		);
	}

	/**
	 * Ensure legacy model identifiers remain compatible with current Gemini API.
	 *
	 * @param string $model Stored model value.
	 * @return string Normalized model value.
	 */
	public static function normalize_model_slug( $model ) {
		$default_model = 'gemini-1.5-flash-8b';
		$model = is_string( $model ) ? trim( $model ) : '';

		if ( '' === $model ) {
			return $default_model;
		}

		if ( 0 === stripos( $model, 'models/' ) ) {
			$model = substr( $model, 7 );
		}

		$legacy_map = array(
			'gemini-pro'                     => 'gemini-1.5-flash',
			'gemini-pro-vision'              => 'gemini-1.5-flash',
			'gemini-ultra'                   => 'gemini-1.5-flash',
			'gemini-1.0-pro'                 => 'gemini-1.5-flash',
			'gemini-1.0-pro-vision'          => 'gemini-1.5-flash',
			'gemini-2.5-flash-lite-preview-06-17' => 'gemini-2.0-flash',
		);

		$normalized_lookup = strtolower( $model );
		if ( isset( $legacy_map[ $normalized_lookup ] ) ) {
			return $legacy_map[ $normalized_lookup ];
		}

		$available_models = array_keys( self::get_available_models() );
		if ( in_array( $model, $available_models, true ) ) {
			return $model;
		}

		foreach ( $available_models as $available_model ) {
			if ( strtolower( $available_model ) === $normalized_lookup ) {
				return $available_model;
			}
		}

		return $default_model;
	}

	/**
	 * Test Gemini connection.
	 *
	 * @return bool Connection status.
	 */
	public function test_connection() {
		try {
			// Make a simple API call to test the key
			$endpoint = $this->api_endpoint . '?key=' . $this->api_key;
			
			$request_data = array(
				'contents' => array(
					array(
						'parts' => array(
							array(
								'text' => 'Hello, this is a test. Please respond with "Connection successful."',
							),
						),
					),
				),
				'generationConfig' => array(
					'temperature'     => 0.1,
					'maxOutputTokens' => 50,
				),
			);

			$response_data = $this->make_request( $endpoint, $request_data );
			
			return isset( $response_data['candidates'][0]['content']['parts'][0]['text'] );
		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Gemini Test Connection Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get available Gemini models.
	 *
	 * @return array Available models.
	 */
	public static function get_available_models() {
		return array(
			'gemini-1.5-flash-8b' => array(
				'label'       => 'Gemini 1.5 Flash 8B',
				'description' => __( 'Ultra-efficient 8B model ideal for FAQs, quick replies, and high-volume support.', 'aria' ),
				'max_tokens'  => 8192,
				'cost_level'  => 'low',
				'cost_note'   => __( 'Lowest cost – free tier available, then ~$0.04 per million tokens.', 'aria' ),
			),
			'gemini-1.5-flash' => array(
				'label'       => 'Gemini 1.5 Flash',
				'description' => __( 'Balanced performance for richer conversations and smarter follow-ups.', 'aria' ),
				'max_tokens'  => 8192,
				'cost_level'  => 'medium',
				'cost_note'   => __( 'Balanced cost – great mix of speed and capability.', 'aria' ),
			),
			'gemini-1.5-pro' => array(
				'label'       => 'Gemini 1.5 Pro',
				'description' => __( 'Premium reasoning and multimodal support for complex customer questions.', 'aria' ),
				'max_tokens'  => 8192,
				'cost_level'  => 'high',
				'cost_note'   => __( 'Premium pricing – best for advanced use cases.', 'aria' ),
			),
			'gemini-2.0-flash' => array(
				'label'       => 'Gemini 2.0 Flash',
				'description' => __( 'Latest Flash generation with improved quality and tool-following.', 'aria' ),
				'max_tokens'  => 8192,
				'cost_level'  => 'medium',
				'cost_note'   => __( 'Next-gen Flash – competitive pricing with better output.', 'aria' ),
			),
		);
	}

	/**
	 * Estimate token usage.
	 *
	 * @param string $text Text to estimate.
	 * @return int Estimated tokens.
	 */
	private function estimate_tokens( $text ) {
		// Rough estimation for Gemini
		return ceil( strlen( $text ) / 4 );
	}

	/**
	 * Calculate cost estimate.
	 *
	 * @param int    $tokens Token count.
	 * @param string $model Model name.
	 * @return float Estimated cost in USD.
	 */
	public static function calculate_cost( $tokens, $model = 'gemini-1.5-flash-8b' ) {
		// Gemini pricing per 1M tokens (averaged input/output)
		$pricing = array(
			'gemini-1.5-flash-8b' => 0.0375, // $0.0375 per 1M tokens
			'gemini-1.5-flash'    => 0.06,   // Approximate list pricing
			'gemini-1.5-pro'      => 0.30,   // Premium tier pricing estimate
			'gemini-2.0-flash'    => 0.075,  // Next-gen Flash pricing
		);
		
		$cost_per_million = isset( $pricing[ $model ] ) ? $pricing[ $model ] : $pricing['gemini-1.5-flash-8b'];
		
		return ( $tokens / 1000000 ) * $cost_per_million;
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
			Aria_Logger::error( 'Aria Gemini Embedding Error: ' . $e->getMessage() );
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

		// Gemini embeddings model and endpoint
		$embedding_model = get_option( 'aria_gemini_embedding_model', 'text-embedding-004' );
		$embeddings_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $embedding_model . ':embedContent';

		$embeddings = array();

		try {
			// Process each text individually as Gemini requires single text per request
			foreach ( $texts as $text ) {
				$request_data = array(
					'content' => array(
						'parts' => array(
							array(
								'text' => $text,
							),
						),
					),
				);

				// Gemini uses API key as URL parameter
				$endpoint = $embeddings_endpoint . '?key=' . $this->api_key;

				$response_data = $this->make_request( $endpoint, $request_data );

				if ( ! isset( $response_data['embedding']['values'] ) ) {
					throw new Exception( 'Invalid embeddings response format from Gemini' );
				}

				$embeddings[] = $response_data['embedding']['values'];
			}

			// Log usage for embeddings
			$this->log_usage(
				array( 'texts' => $texts ),
				array(
					'embeddings_count' => count( $embeddings ),
					'model' => $embedding_model,
				)
			);

			// Return in the expected format for vector engine
			return array(
				'data' => array_map( function( $embedding ) {
					return array( 'embedding' => $embedding );
				}, $embeddings )
			);

		} catch ( Exception $e ) {
			Aria_Logger::error( 'Aria Gemini Embeddings Error: ' . $e->getMessage() );
			throw $e;
		}
	}
}
