<?php
/**
 * Personality management handler
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle personality settings and customization.
 */
class Aria_Personality {

	/**
	 * Personality traits configuration.
	 *
	 * @var array
	 */
	private static $personality_traits = array(
		'helpful'       => array(
			'label'       => 'Helpful',
			'description' => 'Always eager to assist and provide solutions',
		),
		'friendly'      => array(
			'label'       => 'Friendly',
			'description' => 'Warm and approachable in conversations',
		),
		'professional'  => array(
			'label'       => 'Professional',
			'description' => 'Maintains formal and business-like tone',
		),
		'knowledgeable' => array(
			'label'       => 'Knowledgeable',
			'description' => 'Demonstrates expertise and confidence',
		),
		'empathetic'    => array(
			'label'       => 'Empathetic',
			'description' => 'Shows understanding and concern for visitors',
		),
		'concise'       => array(
			'label'       => 'Concise',
			'description' => 'Provides brief and to-the-point responses',
		),
		'detailed'      => array(
			'label'       => 'Detailed',
			'description' => 'Gives comprehensive and thorough answers',
		),
		'witty'         => array(
			'label'       => 'Witty',
			'description' => 'Uses appropriate humor when suitable',
		),
	);

	/**
	 * Business type configurations.
	 *
	 * @var array
	 */
	private static $business_types = array(
		'general'      => array(
			'label'             => 'General Business',
			'default_tone'      => 'professional',
			'default_traits'    => array( 'helpful', 'professional', 'knowledgeable' ),
			'greeting_template' => 'Hello! I\'m Aria, your assistant. How can I help you today?',
		),
		'ecommerce'    => array(
			'label'             => 'E-commerce',
			'default_tone'      => 'friendly',
			'default_traits'    => array( 'helpful', 'friendly', 'knowledgeable' ),
			'greeting_template' => 'Hi there! I\'m Aria, here to help you find exactly what you\'re looking for. What can I assist you with today?',
		),
		'healthcare'   => array(
			'label'             => 'Healthcare',
			'default_tone'      => 'professional',
			'default_traits'    => array( 'helpful', 'professional', 'empathetic', 'concise' ),
			'greeting_template' => 'Hello, I\'m Aria, your healthcare assistant. How may I help you today? Please note that I cannot provide medical advice.',
		),
		'saas'         => array(
			'label'             => 'SaaS/Technology',
			'default_tone'      => 'professional',
			'default_traits'    => array( 'helpful', 'knowledgeable', 'detailed' ),
			'greeting_template' => 'Hi! I\'m Aria, your technical assistant. I can help you understand our features, pricing, or any technical questions you might have.',
		),
		'education'    => array(
			'label'             => 'Education',
			'default_tone'      => 'friendly',
			'default_traits'    => array( 'helpful', 'friendly', 'knowledgeable', 'detailed' ),
			'greeting_template' => 'Hello! I\'m Aria, here to help answer your questions about our programs and services. What would you like to know?',
		),
		'hospitality'  => array(
			'label'             => 'Hospitality',
			'default_tone'      => 'friendly',
			'default_traits'    => array( 'helpful', 'friendly', 'empathetic' ),
			'greeting_template' => 'Welcome! I\'m Aria, your personal concierge. How can I make your experience with us exceptional today?',
		),
		'legal'        => array(
			'label'             => 'Legal Services',
			'default_tone'      => 'formal',
			'default_traits'    => array( 'professional', 'knowledgeable', 'concise' ),
			'greeting_template' => 'Good day. I\'m Aria, the virtual assistant for our law firm. How may I direct your inquiry today?',
		),
		'nonprofit'    => array(
			'label'             => 'Non-profit',
			'default_tone'      => 'friendly',
			'default_traits'    => array( 'helpful', 'friendly', 'empathetic' ),
			'greeting_template' => 'Hello! I\'m Aria, and I\'m here to help you learn more about our mission and how you can get involved. What brings you here today?',
		),
	);

	/**
	 * Tone settings.
	 *
	 * @var array
	 */
	private static $tone_settings = array(
		'professional' => array(
			'label'       => 'Professional',
			'description' => 'Formal, courteous, and business-appropriate',
			'emoji_use'   => 'minimal',
			'formality'   => 'high',
		),
		'friendly'     => array(
			'label'       => 'Friendly',
			'description' => 'Warm, welcoming, and conversational',
			'emoji_use'   => 'moderate',
			'formality'   => 'medium',
		),
		'casual'       => array(
			'label'       => 'Casual',
			'description' => 'Relaxed, informal, and approachable',
			'emoji_use'   => 'frequent',
			'formality'   => 'low',
		),
		'formal'       => array(
			'label'       => 'Formal',
			'description' => 'Very professional and traditional',
			'emoji_use'   => 'none',
			'formality'   => 'very-high',
		),
	);

	/**
	 * Get personality traits.
	 *
	 * @return array Personality traits.
	 */
	public static function get_personality_traits() {
		return apply_filters( 'aria_personality_traits', self::$personality_traits );
	}

	/**
	 * Get business types.
	 *
	 * @return array Business types.
	 */
	public static function get_business_types() {
		return apply_filters( 'aria_business_types', self::$business_types );
	}

	/**
	 * Get tone settings.
	 *
	 * @return array Tone settings.
	 */
	public static function get_tone_settings() {
		return apply_filters( 'aria_tone_settings', self::$tone_settings );
	}

	/**
	 * Get current personality settings.
	 *
	 * @return array Current settings.
	 */
	public static function get_current_settings() {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_personality_settings';

		$settings = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE site_id = %d",
				get_current_blog_id()
			),
			ARRAY_A
		);

		if ( ! $settings ) {
			// Return defaults
			return self::get_default_settings();
		}

		// Parse traits if stored as comma-separated string
		if ( ! empty( $settings['personality_traits'] ) && is_string( $settings['personality_traits'] ) ) {
			$settings['personality_traits'] = explode( ',', $settings['personality_traits'] );
		}

		// Parse custom responses if stored as JSON
		if ( ! empty( $settings['custom_responses'] ) ) {
			$settings['custom_responses'] = json_decode( $settings['custom_responses'], true );
		}

		return $settings;
	}

	/**
	 * Get default settings.
	 *
	 * @param string $business_type Optional business type.
	 * @return array Default settings.
	 */
	public static function get_default_settings( $business_type = 'general' ) {
		$business_types = self::get_business_types();
		
		if ( ! isset( $business_types[ $business_type ] ) ) {
			$business_type = 'general';
		}

		$business_config = $business_types[ $business_type ];

		return array(
			'business_type'      => $business_type,
			'tone_setting'       => $business_config['default_tone'],
			'personality_traits' => $business_config['default_traits'],
			'greeting_message'   => $business_config['greeting_template'],
			'farewell_message'   => __( 'Thank you for chatting with me. Have a great day!', 'aria' ),
			'custom_responses'   => array(),
		);
	}

	/**
	 * Save personality settings.
	 *
	 * @param array $settings Settings to save.
	 * @return bool Success status.
	 */
	public static function save_settings( $settings ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_personality_settings';

		// Prepare data
		$data = array(
			'site_id'            => get_current_blog_id(),
			'business_type'      => sanitize_text_field( $settings['business_type'] ),
			'tone_setting'       => sanitize_text_field( $settings['tone_setting'] ),
			'personality_traits' => '',
			'custom_responses'   => '',
			'greeting_message'   => sanitize_textarea_field( $settings['greeting_message'] ),
			'farewell_message'   => sanitize_textarea_field( $settings['farewell_message'] ),
		);

		// Handle personality traits
		if ( is_array( $settings['personality_traits'] ) ) {
			$data['personality_traits'] = implode( ',', array_map( 'sanitize_text_field', $settings['personality_traits'] ) );
		} else {
			$data['personality_traits'] = sanitize_text_field( $settings['personality_traits'] );
		}

		// Handle custom responses
		if ( ! empty( $settings['custom_responses'] ) ) {
			$data['custom_responses'] = wp_json_encode( $settings['custom_responses'] );
		}

		// Check if settings exist
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE site_id = %d",
				get_current_blog_id()
			)
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$table,
				$data,
				array( 'site_id' => get_current_blog_id() )
			);
		} else {
			$result = $wpdb->insert( $table, $data );
		}

		return false !== $result;
	}

	/**
	 * Generate personality prompt instructions.
	 *
	 * @param array $settings Personality settings.
	 * @return string Prompt instructions.
	 */
	public static function generate_prompt_instructions( $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_current_settings();
		}

		$tone_settings = self::get_tone_settings();
		$trait_definitions = self::get_personality_traits();

		$instructions = "You are Aria, an AI assistant with the following personality:\n\n";

		// Tone
		if ( isset( $tone_settings[ $settings['tone_setting'] ] ) ) {
			$tone = $tone_settings[ $settings['tone_setting'] ];
			$instructions .= "Tone: {$tone['label']} - {$tone['description']}\n";
			
			// Emoji usage
			switch ( $tone['emoji_use'] ) {
				case 'none':
					$instructions .= "Do not use emojis in your responses.\n";
					break;
				case 'minimal':
					$instructions .= "Use emojis sparingly, only when it significantly enhances the message.\n";
					break;
				case 'moderate':
					$instructions .= "Use emojis occasionally to add warmth to your responses.\n";
					break;
				case 'frequent':
					$instructions .= "Feel free to use emojis to make conversations more engaging.\n";
					break;
			}
		}

		// Traits
		if ( ! empty( $settings['personality_traits'] ) ) {
			$instructions .= "\nPersonality traits:\n";
			foreach ( $settings['personality_traits'] as $trait ) {
				if ( isset( $trait_definitions[ $trait ] ) ) {
					$trait_info = $trait_definitions[ $trait ];
					$instructions .= "- {$trait_info['label']}: {$trait_info['description']}\n";
				}
			}
		}

		// Business context
		$business_types = self::get_business_types();
		if ( isset( $business_types[ $settings['business_type'] ] ) ) {
			$business = $business_types[ $settings['business_type'] ];
			$instructions .= "\nBusiness type: {$business['label']}\n";
		}

		// Custom responses
		if ( ! empty( $settings['custom_responses'] ) ) {
			$instructions .= "\nCustom response patterns:\n";
			foreach ( $settings['custom_responses'] as $pattern => $response ) {
				$instructions .= "- When asked about '{$pattern}', respond with: '{$response}'\n";
			}
		}

		$instructions .= "\nGeneral guidelines:\n";
		$instructions .= "- Always maintain the personality traits listed above\n";
		$instructions .= "- Adapt your language style to match the tone setting\n";
		$instructions .= "- Be consistent throughout the conversation\n";
		$instructions .= "- If you don't know something, admit it politely and offer to connect them with a human\n";

		return $instructions;
	}

	/**
	 * Get greeting message based on current settings.
	 *
	 * @param string $user_name Optional user name for personalization.
	 * @return string Greeting message.
	 */
	public static function get_greeting_message( $user_name = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_personality_settings';
		
		$settings = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE site_id = %d",
				get_current_blog_id()
			),
			ARRAY_A
		);
		
		$greeting = '';
		
		if ( $settings && ! empty( $settings['greeting_message'] ) ) {
			$greeting = $settings['greeting_message'];
		} else {
			// Return default greeting
			$business_type = $settings['business_type'] ?? 'general';
			if ( isset( self::$business_types[ $business_type ]['greeting_template'] ) ) {
				$greeting = self::$business_types[ $business_type ]['greeting_template'];
			} else {
				$greeting = __( 'Hello! I\'m Aria, your AI assistant. How can I help you today?', 'aria' );
			}
		}
		
		// Personalize with name if provided
		if ( ! empty( $user_name ) ) {
			// Replace generic greetings with personalized ones
			$greeting = str_replace( 'Hello!', sprintf( __( 'Hello %s!', 'aria' ), $user_name ), $greeting );
			$greeting = str_replace( 'Hi!', sprintf( __( 'Hi %s!', 'aria' ), $user_name ), $greeting );
			$greeting = str_replace( 'Welcome!', sprintf( __( 'Welcome %s!', 'aria' ), $user_name ), $greeting );
			
			// If no replacements were made, prepend the name
			if ( strpos( $greeting, $user_name ) === false ) {
				$greeting = sprintf( __( 'Hi %s! ', 'aria' ), $user_name ) . $greeting;
			}
		}
		
		return $greeting;
	}

	/**
	 * Get sample responses for preview.
	 *
	 * @param array $settings Personality settings.
	 * @return array Sample responses.
	 */
	public static function get_sample_responses( $settings ) {
		$samples = array();

		// Greeting
		$samples['greeting'] = ! empty( $settings['greeting_message'] ) 
			? $settings['greeting_message'] 
			: self::generate_greeting( $settings );

		// Common questions
		$tone = $settings['tone_setting'];
		
		switch ( $tone ) {
			case 'professional':
				$samples['product_inquiry'] = 'I\'d be happy to provide information about our products. Could you please specify which product or service you\'re interested in?';
				$samples['pricing'] = 'Our pricing varies based on your specific needs. I can help you find the most suitable option. What features are most important to you?';
				$samples['unknown'] = 'I apologize, but I don\'t have specific information about that. Would you like me to connect you with someone who can assist you further?';
				break;
				
			case 'friendly':
				$samples['product_inquiry'] = 'I\'d love to tell you about our products! 😊 Which one caught your eye?';
				$samples['pricing'] = 'Great question about pricing! Let me help you find the perfect fit for your budget. What are you looking to accomplish?';
				$samples['unknown'] = 'Hmm, that\'s a great question but I\'m not sure about that one. Let me connect you with someone who can give you the best answer!';
				break;
				
			case 'casual':
				$samples['product_inquiry'] = 'Hey! Happy to chat about our products 🎉 What are you curious about?';
				$samples['pricing'] = 'Pricing? You got it! Let\'s figure out what works best for you. What\'s your main goal here?';
				$samples['unknown'] = 'Oops, you\'ve stumped me there! 😅 Let me get someone who knows more about that to help you out.';
				break;
				
			case 'formal':
				$samples['product_inquiry'] = 'I would be pleased to provide detailed information regarding our product offerings. Please indicate your area of interest.';
				$samples['pricing'] = 'Regarding pricing structures, I shall be glad to assist. May I inquire about your specific requirements?';
				$samples['unknown'] = 'I regret that I am unable to provide information on that particular matter. Shall I arrange for a specialist to contact you?';
				break;
		}

		// Farewell
		$samples['farewell'] = ! empty( $settings['farewell_message'] ) 
			? $settings['farewell_message'] 
			: self::generate_farewell( $settings );

		return $samples;
	}

	/**
	 * Generate greeting based on settings.
	 *
	 * @param array $settings Personality settings.
	 * @return string Generated greeting.
	 */
	private static function generate_greeting( $settings ) {
		$business_types = self::get_business_types();
		
		if ( isset( $business_types[ $settings['business_type'] ] ) ) {
			return $business_types[ $settings['business_type'] ]['greeting_template'];
		}
		
		return __( 'Hello! I\'m Aria, your assistant. How can I help you today?', 'aria' );
	}

	/**
	 * Generate farewell based on settings.
	 *
	 * @param array $settings Personality settings.
	 * @return string Generated farewell.
	 */
	private static function generate_farewell( $settings ) {
		$tone = $settings['tone_setting'];
		
		switch ( $tone ) {
			case 'professional':
				return __( 'Thank you for your time. Please don\'t hesitate to reach out if you need further assistance.', 'aria' );
				
			case 'friendly':
				return __( 'Thanks for chatting! Feel free to come back anytime you need help. Have a wonderful day! 😊', 'aria' );
				
			case 'casual':
				return __( 'Great talking with you! Hit me up anytime you need help. Take care! 👋', 'aria' );
				
			case 'formal':
				return __( 'Thank you for your inquiry. We remain at your service should you require any further assistance.', 'aria' );
				
			default:
				return __( 'Thank you for chatting with me. Have a great day!', 'aria' );
		}
	}
}