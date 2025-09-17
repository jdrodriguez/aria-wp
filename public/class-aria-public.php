<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Aria
 * @subpackage Aria/public
 */

/**
 * The public-facing functionality of the plugin.
 */
class Aria_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		// Only load if widget is enabled
		if ( ! get_option( 'aria_widget_enabled', true ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			ARIA_PLUGIN_URL . 'dist/chat-style.css',
			array(),
			$this->version,
			'all'
		);

		// Add inline styles for customization
		$custom_css = $this->get_custom_styles();
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( $this->plugin_name, $custom_css );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		// Only load if widget is enabled
		if ( ! get_option( 'aria_widget_enabled', true ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			ARIA_PLUGIN_URL . 'dist/chat.js',
			array(),
			$this->version,
			true
		);

		// Get design settings for text customization
		$design_settings = get_option( 'aria_design_settings', array() );
		
		// Localize script
		wp_localize_script(
			$this->plugin_name,
			'ariaChat',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'aria_public_nonce' ),
				'pluginUrl'    => ARIA_PLUGIN_URL,
				'enabled'      => true,
				'config'       => $this->get_widget_config(),
				'gdprMessage'  => get_option( 'aria_gdpr_message', __( 'By using this chat, you agree to our privacy policy.', 'aria' ) ),
				'strings'      => array(
					'title'              => get_option( 'aria_chat_title', __( 'Hi! I\'m Aria', 'aria' ) ),
					'subtitle'           => __( 'How can I help you today?', 'aria' ),
					'embedTitle'         => isset( $design_settings['embed_title'] ) && ! empty( $design_settings['embed_title'] ) ? $design_settings['embed_title'] : __( 'How can we help you today?', 'aria' ),
					'typeMessage'        => get_option( 'aria_placeholder_text', __( 'Type your message...', 'aria' ) ),
					'sending'            => __( 'Sending...', 'aria' ),
					'thinking'           => __( 'Aria is thinking...', 'aria' ),
					'error'              => __( 'Something went wrong. Please try again.', 'aria' ),
					'offline'            => __( 'Aria is currently offline. Please try again later.', 'aria' ),
					'acceptPrivacy'      => __( 'Accept & Continue', 'aria' ),
					'online'             => __( 'Online', 'aria' ),
					'minimize'           => __( 'Minimize', 'aria' ),
					'close'              => __( 'Close', 'aria' ),
					'send'               => __( 'Send', 'aria' ),
					'poweredBy'          => __( 'Powered by', 'aria' ),
					'errorMessage'       => __( 'Something went wrong. Please try again.', 'aria' ),
					'connectingHuman'    => __( 'Connecting you with a human...', 'aria' ),
					'viewProduct'        => __( 'View Product', 'aria' ),
					'wasHelpful'         => __( 'Was this conversation helpful?', 'aria' ),
					'thanksFeedback'     => __( 'Thank you for your feedback!', 'aria' ),
					'invalidEmail'       => __( 'Please enter a valid email address.', 'aria' ),
					'thankYouEmail'      => __( 'Thank you! How can I help you today?', 'aria' ),
					'enterEmail'         => __( 'Your email', 'aria' ),
					'enterName'          => __( 'Your name', 'aria' ),
					'enterPhone'         => __( 'Your phone number (optional)', 'aria' ),
					'addNote'            => __( 'How can we help you today?', 'aria' ),
					'startChat'          => __( 'Start Chat', 'aria' ),
					'welcomeMessage'     => __( 'Welcome! Please introduce yourself so I can assist you better.', 'aria' ),
					'confirmDeactivate'  => __( 'Are you sure you want to deactivate your license?', 'aria' ),
					'saving'             => __( 'Saving...', 'aria' ),
					'saved'              => __( 'Saved successfully!', 'aria' ),
					'chooseIcon'         => __( 'Choose Icon', 'aria' ),
					'useIcon'            => __( 'Use this icon', 'aria' ),
					'chooseAvatar'       => __( 'Choose Avatar', 'aria' ),
					'useAvatar'          => __( 'Use this avatar', 'aria' ),
					'getApiKey'          => __( 'Get your API key from', 'aria' ),
					'apiKeyFormat'       => __( 'Your key should start with "sk-".', 'aria' ),
					'hide'               => __( 'Hide', 'aria' ),
					'show'               => __( 'Show', 'aria' ),
					'enterValidKey'      => __( 'Please enter a valid API key.', 'aria' ),
					'apiConnected'       => __( 'API connected successfully!', 'aria' ),
					'apiError'           => __( 'API connection failed. Please check your credentials.', 'aria' ),
					'emailRequired'      => __( 'Please provide your email to continue.', 'aria' ),
					'nameRequired'       => __( 'Please provide your name to continue.', 'aria' ),
					'rateConversation'   => __( 'How was your experience?', 'aria' ),
					'thankYou'           => __( 'Thank you for your feedback!', 'aria' ),
					'welcomeMessage'     => isset( $design_settings['welcome_message'] ) && ! empty( $design_settings['welcome_message'] ) ? $design_settings['welcome_message'] : __( 'Welcome! Please introduce yourself so I can assist you better.', 'aria' ),
					'enterName'          => __( 'Your name', 'aria' ),
					'startChat'          => __( 'Start Chat', 'aria' ),
				),
			)
		);
	}

	/**
	 * Render chat widget in footer.
	 */
	public function render_chat_widget() {
		// Check if widget is enabled
		if ( ! get_option( 'aria_widget_enabled', true ) ) {
			return;
		}

		// Check if API is configured
		if ( ! get_option( 'aria_ai_api_key' ) ) {
			return;
		}

		// Check if should show on this page
		if ( ! $this->should_show_on_page() ) {
			return;
		}

		// Widget is rendered by JavaScript
		// No HTML output needed here as the JS creates everything
	}

	/**
	 * Check if widget should show on current page.
	 *
	 * @return bool Should show widget.
	 */
	private function should_show_on_page() {
		$show_on = get_option( 'aria_show_on_pages', array( 'all' ) );

		if ( in_array( 'all', $show_on ) ) {
			return true;
		}

		if ( is_front_page() && in_array( 'home', $show_on ) ) {
			return true;
		}

		if ( is_single() && in_array( 'posts', $show_on ) ) {
			return true;
		}

		if ( is_page() && in_array( 'pages', $show_on ) ) {
			return true;
		}

		if ( is_archive() && in_array( 'archives', $show_on ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render chat shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_chat_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'height'      => '600px',
				'class'       => '',
				'mode'        => 'form', // 'form' or 'chat'
				'title'       => get_option( 'aria_chat_title', __( 'Hi! I\'m Aria', 'aria' ) ),
				'subtitle'    => __( 'How can I help you today?', 'aria' ),
				'button_text' => __( 'Send Message', 'aria' ),
			),
			$atts,
			'aria_chat'
		);

		// Check if API is configured
		if ( ! get_option( 'aria_ai_api_key' ) ) {
			return '<p>' . esc_html__( 'Aria chat is not configured yet.', 'aria' ) . '</p>';
		}

		// Check license status
		if ( ! $this->is_license_valid() ) {
			return '<p>' . esc_html__( 'Aria chat is not available.', 'aria' ) . '</p>';
		}

		// Enqueue scripts and styles if not already loaded
		if ( ! wp_script_is( $this->plugin_name, 'enqueued' ) ) {
			$this->enqueue_scripts();
			$this->enqueue_styles();
		}

		ob_start();
		$design_settings = get_option( 'aria_design_settings', array() );
		$theme = isset( $design_settings['theme'] ) ? $design_settings['theme'] : 'light';
		
		?>
		<div 
			class="aria-embed-container <?php echo esc_attr( $atts['class'] ); ?>" 
			data-aria-embed="true"
			data-aria-mode="<?php echo esc_attr( $atts['mode'] ); ?>"
			data-aria-height="<?php echo esc_attr( $atts['height'] ); ?>"
			data-theme="<?php echo esc_attr( $theme ); ?>"
		>
			<!-- Initial Form View -->
			<div class="aria-embed-form-view">
				<h2 class="aria-embed-title"><?php echo esc_html( isset( $design_settings['embed_title'] ) && ! empty( $design_settings['embed_title'] ) ? $design_settings['embed_title'] : __( 'How can we help you today?', 'aria' ) ); ?></h2>
				
				<form class="aria-embed-intake-form" id="aria-embed-intake-form">
					<div class="aria-form-group">
						<input 
							type="text" 
							id="aria-embed-name" 
							class="aria-form-input" 
							placeholder="<?php esc_attr_e( 'Your name', 'aria' ); ?>"
							required 
						/>
					</div>
					<div class="aria-form-group">
						<input 
							type="email" 
							id="aria-embed-email" 
							class="aria-form-input" 
							placeholder="<?php esc_attr_e( 'Your email', 'aria' ); ?>"
							required 
						/>
					</div>
					<div class="aria-form-group">
						<input 
							type="tel" 
							id="aria-embed-phone" 
							class="aria-form-input" 
							placeholder="<?php esc_attr_e( 'Your phone number (optional)', 'aria' ); ?>"
							pattern="[+]?[0-9]{1,4}?[-.\s]?[(]?[0-9]{1,3}?[)]?[-.\s]?[0-9]{1,4}[-.\s]?[0-9]{1,4}[-.\s]?[0-9]{1,9}"
						/>
					</div>
					<div class="aria-form-group">
						<textarea 
							id="aria-embed-message" 
							class="aria-form-textarea" 
							placeholder="<?php esc_attr_e( 'How can we help you today?', 'aria' ); ?>"
							rows="4"
							required
						></textarea>
					</div>
					<button type="submit" class="aria-form-submit aria-embed-submit">
						<?php echo esc_html( $atts['button_text'] ); ?>
					</button>
				</form>
			</div>
			
			<!-- Chat View (Hidden Initially) -->
			<div class="aria-embed-chat-view" style="display: none;">
				<div class="aria-embed-chat-header">
					<div class="aria-embed-header-content">
						<div class="aria-avatar <?php echo esc_attr( $theme === 'dark' ? 'aria-logo-dark' : 'aria-logo-light' ); ?>" aria-label="Aria"></div>
						<div class="aria-header-info">
							<h3 class="aria-header-title"><?php echo esc_html( $atts['title'] ); ?></h3>
							<span class="aria-header-status">
								<span class="aria-status-dot"></span>
								<?php esc_html_e( 'Online', 'aria' ); ?>
							</span>
						</div>
					</div>
					<button class="aria-close-btn" aria-label="<?php esc_attr_e( 'Close', 'aria' ); ?>">
						<svg viewBox="0 0 24 24" width="20" height="20">
							<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
						</svg>
					</button>
				</div>
				
				<div class="aria-embed-chat-messages" role="log" aria-live="polite">
					<div class="aria-embed-messages-container"></div>
					<div class="aria-typing-indicator" style="display: none;">
						<span></span>
						<span></span>
						<span></span>
					</div>
				</div>
				
				<div class="aria-embed-chat-input-container">
					<form class="aria-embed-chat-form">
						<div class="aria-input-group">
							<input 
								type="text" 
								class="aria-embed-message-input" 
								placeholder="<?php echo esc_attr( get_option( 'aria_placeholder_text', __( 'Type your message...', 'aria' ) ) ); ?>"
								autocomplete="off"
							/>
							<button type="submit" class="aria-send-btn">
								<svg viewBox="0 0 24 24" width="20" height="20">
									<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
								</svg>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get widget configuration.
	 *
	 * @return array Widget configuration.
	 */
	private function get_widget_config() {
		$personality = $this->get_personality_settings();
		$design_settings = get_option( 'aria_design_settings', array() );

		return array(
			'position'         => isset( $design_settings['position'] ) ? $design_settings['position'] : 'bottom-right',
			'theme'            => isset( $design_settings['theme'] ) ? $design_settings['theme'] : 'light',
			'primaryColor'     => isset( $design_settings['primary_color'] ) ? $design_settings['primary_color'] : '#2271b1',
			'secondaryColor'   => isset( $design_settings['secondary_color'] ) ? $design_settings['secondary_color'] : '#f0f0f1',
			'textColor'        => isset( $design_settings['text_color'] ) ? $design_settings['text_color'] : '#1e1e1e',
			'chatWidth'        => isset( $design_settings['chat_width'] ) ? intval( $design_settings['chat_width'] ) : 380,
			'chatHeight'       => isset( $design_settings['chat_height'] ) ? intval( $design_settings['chat_height'] ) : 600,
			'buttonSize'       => isset( $design_settings['button_size'] ) ? $design_settings['button_size'] : 'medium',
			'buttonStyle'      => isset( $design_settings['button_style'] ) ? $design_settings['button_style'] : 'rounded',
			'buttonIcon'       => isset( $design_settings['button_icon'] ) ? $design_settings['button_icon'] : 'chat-bubble',
			'customIcon'       => isset( $design_settings['custom_icon'] ) ? $design_settings['custom_icon'] : '',
			'avatarStyle'      => isset( $design_settings['avatar_style'] ) ? $design_settings['avatar_style'] : 'initials',
			'customAvatar'     => isset( $design_settings['custom_avatar'] ) ? $design_settings['custom_avatar'] : '',
			'personality'      => $personality,
			'gdprEnabled'      => get_option( 'aria_gdpr_enabled', true ),
			'soundEnabled'     => isset( $design_settings['sound_enabled'] ) ? $design_settings['sound_enabled'] : true,
			'enableAnimations' => isset( $design_settings['animations'] ) ? $design_settings['animations'] : true,
			'showAvatar'       => isset( $design_settings['show_avatar'] ) ? $design_settings['show_avatar'] : true,
			'mobileFullscreen' => isset( $design_settings['mobile_fullscreen'] ) ? $design_settings['mobile_fullscreen'] : true,
			'detectSystemTheme'=> false, // Can be enabled in future if needed
			'autoOpen'         => get_option( 'aria_auto_open', false ),
			'autoOpenDelay'    => get_option( 'aria_auto_open_delay', 5000 ),
		);
	}

	/**
	 * Get custom styles based on settings.
	 *
	 * @return string Custom CSS.
	 */
	private function get_custom_styles() {
		$design_settings = get_option( 'aria_design_settings', array() );
		$primary_color = isset( $design_settings['primary_color'] ) ? $design_settings['primary_color'] : '#2271b1';
		$position      = isset( $design_settings['position'] ) ? $design_settings['position'] : 'bottom-right';

		$css = ':root {';
		$css .= '--aria-primary-color: ' . esc_attr( $primary_color ) . ';';
		$css .= '--aria-primary: ' . esc_attr( $primary_color ) . ';';
		$css .= '--aria-primary-rgb: ' . esc_attr( $this->hex_to_rgb( $primary_color ) ) . ';';
		$css .= '}';

		// Position styles
		$positions = array(
			'bottom-right' => 'bottom: 20px; right: 20px;',
			'bottom-left'  => 'bottom: 20px; left: 20px;',
			'top-right'    => 'top: 20px; right: 20px;',
			'top-left'     => 'top: 20px; left: 20px;',
		);

		if ( isset( $positions[ $position ] ) ) {
			$css .= '.aria-chat-widget { ' . $positions[ $position ] . ' }';
		}

		// Custom CSS from settings
		$custom_css = isset( $design_settings['custom_css'] ) ? $design_settings['custom_css'] : '';
		if ( ! empty( $custom_css ) ) {
			$css .= "\n" . wp_strip_all_tags( $custom_css );
		}

		return $css;
	}

	/**
	 * Convert hex color to RGB.
	 *
	 * @param string $hex Hex color code.
	 * @return string RGB values.
	 */
	private function hex_to_rgb( $hex ) {
		$hex = str_replace( '#', '', $hex );
		
		if ( strlen( $hex ) === 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}
		
		return "$r, $g, $b";
	}

	/**
	 * Get personality settings.
	 *
	 * @return array Personality settings.
	 */
	private function get_personality_settings() {
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
			return array(
				'business_type'      => 'general',
				'tone_setting'       => 'professional',
				'personality_traits' => 'helpful,knowledgeable,friendly',
				'greeting_message'   => __( 'Hello! I\'m Aria, your assistant. How can I help you today?', 'aria' ),
				'farewell_message'   => __( 'Thank you for chatting with me. Have a great day!', 'aria' ),
			);
		}

		return $settings;
	}

	/**
	 * Check if license is valid.
	 *
	 * @return bool True if license is valid.
	 */
	private function is_license_valid() {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_license';
		
		$license = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE site_url = %s",
				get_site_url()
			)
		);

		if ( ! $license ) {
			return false;
		}

		// Check trial status
		if ( 'trial' === $license->license_status ) {
			$trial_start  = strtotime( $license->trial_started );
			$days_elapsed = ( time() - $trial_start ) / DAY_IN_SECONDS;
			
			return $days_elapsed <= 30;
		}

		// Check active license
		if ( 'active' === $license->license_status ) {
			if ( $license->license_expires ) {
				return strtotime( $license->license_expires ) > time();
			}
			return true;
		}

		return false;
	}
}