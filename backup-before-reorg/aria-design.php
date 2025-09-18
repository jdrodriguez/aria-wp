<?php
/**
 * Design Customization Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current design settings
$design_settings = get_option( 'aria_design_settings', array() );

// Default settings
$defaults = array(
	'position'          => 'bottom-right',
	'theme'             => 'light',
	'primary_color'     => '#2271b1',
	'secondary_color'   => '#f0f0f1',
	'text_color'        => '#1e1e1e',
	'chat_width'        => '380',
	'chat_height'       => '600',
	'button_size'       => 'medium',
	'button_style'      => 'rounded',
	'button_icon'       => 'chat-bubble',
	'custom_icon'       => '',
	'show_avatar'       => true,
	'avatar_style'      => 'initials',
	'custom_avatar'     => '',
	'animations'        => true,
	'sound_enabled'     => true,
	'mobile_fullscreen' => true,
	'custom_css'        => '',
	'embed_title'       => 'How can we help you today?',
	'welcome_message'   => 'Welcome! Please introduce yourself so I can assist you better.',
);

// Merge with defaults
$settings = wp_parse_args( $design_settings, $defaults );

// Handle form submission
if ( isset( $_POST['aria_design_nonce'] ) && wp_verify_nonce( $_POST['aria_design_nonce'], 'aria_design_save' ) ) {
	$new_settings = array(
		'position'          => sanitize_text_field( $_POST['position'] ),
		'theme'             => sanitize_text_field( $_POST['theme'] ),
		'primary_color'     => sanitize_hex_color( $_POST['primary_color'] ),
		'secondary_color'   => sanitize_hex_color( $_POST['secondary_color'] ),
		'text_color'        => sanitize_hex_color( $_POST['text_color'] ),
		'chat_width'        => intval( $_POST['chat_width'] ),
		'chat_height'       => intval( $_POST['chat_height'] ),
		'button_size'       => sanitize_text_field( $_POST['button_size'] ),
		'button_style'      => sanitize_text_field( $_POST['button_style'] ),
		'button_icon'       => sanitize_text_field( $_POST['button_icon'] ),
		'custom_icon'       => esc_url_raw( $_POST['custom_icon'] ),
		'show_avatar'       => isset( $_POST['show_avatar'] ),
		'avatar_style'      => sanitize_text_field( $_POST['avatar_style'] ),
		'custom_avatar'     => esc_url_raw( $_POST['custom_avatar'] ),
		'animations'        => isset( $_POST['animations'] ),
		'sound_enabled'     => isset( $_POST['sound_enabled'] ),
		'mobile_fullscreen' => isset( $_POST['mobile_fullscreen'] ),
		'custom_css'        => wp_strip_all_tags( $_POST['custom_css'] ),
		'embed_title'       => sanitize_text_field( $_POST['embed_title'] ),
		'welcome_message'   => sanitize_text_field( $_POST['welcome_message'] ),
	);
	
	update_option( 'aria_design_settings', $new_settings );
	update_option( 'aria_design_configured', true );
	
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Design settings saved successfully!', 'aria' ) . '</p></div>';
	$settings = $new_settings;
}
?>

<div class="wrap aria-design">
	<!-- Styled with SCSS grok-inspired design system in admin.scss -->
	
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'Design & Appearance', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'Customize how Aria appears and behaves on your website', 'aria' ); ?></p>
		</div>
	</div>

	<div class="aria-page-content">
	<p class="description"><?php esc_html_e( 'Customize how Aria appears on your website.', 'aria' ); ?></p>

	<form method="post" action="" class="aria-design-form">
		<?php wp_nonce_field( 'aria_design_save', 'aria_design_nonce' ); ?>

		<!-- Design Configuration Cards -->
		<div class="aria-metrics-grid">
			<!-- Position & Layout Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-move"></span>
					<h3><?php esc_html_e( 'Position & Layout', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="aria-design-fields">
						<div class="aria-field-group">
							<label for="position"><?php esc_html_e( 'Chat Position', 'aria' ); ?></label>
							<select name="position" id="position" class="aria-select">
								<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>>
									<?php esc_html_e( 'Bottom Right', 'aria' ); ?>
								</option>
								<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>>
									<?php esc_html_e( 'Bottom Left', 'aria' ); ?>
								</option>
								<option value="bottom-center" <?php selected( $settings['position'], 'bottom-center' ); ?>>
									<?php esc_html_e( 'Bottom Center', 'aria' ); ?>
								</option>
							</select>
						</div>
						
						<div class="aria-size-controls">
							<div class="aria-field-group">
								<label><?php esc_html_e( 'Width', 'aria' ); ?></label>
								<div class="aria-input-with-unit">
									<input type="number" 
									       name="chat_width" 
									       value="<?php echo esc_attr( $settings['chat_width'] ); ?>" 
									       min="300" 
									       max="600" 
									       step="10" 
									       class="aria-input-number" />
									<span class="unit">px</span>
								</div>
							</div>
							
							<div class="aria-field-group">
								<label><?php esc_html_e( 'Height', 'aria' ); ?></label>
								<div class="aria-input-with-unit">
									<input type="number" 
									       name="chat_height" 
									       value="<?php echo esc_attr( $settings['chat_height'] ); ?>" 
									       min="400" 
									       max="800" 
									       step="10" 
									       class="aria-input-number" />
									<span class="unit">px</span>
								</div>
							</div>
						</div>
						
						<div class="aria-field-group">
							<label class="aria-checkbox-label">
								<input type="checkbox" 
								       name="mobile_fullscreen" 
								       id="mobile_fullscreen" 
								       value="1" 
								       <?php checked( $settings['mobile_fullscreen'] ); ?> />
								<?php esc_html_e( 'Use fullscreen on mobile devices', 'aria' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Better experience on small screens', 'aria' ); ?></p>\n						</div>\n					</div>\n				</div>\n			</div>\n\n			<!-- Colors & Theme Card -->\n			<div class=\"aria-metric-card\">\n				<div class=\"metric-header\">\n					<span class=\"metric-icon dashicons dashicons-art\"></span>\n					<h3><?php esc_html_e( 'Colors & Theme', 'aria' ); ?></h3>\n				</div>\n				<div class=\"metric-content\">\n					<div class=\"aria-design-section\">\n						<h2><?php esc_html_e( 'Colors & Theme', 'aria' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Colors & Theme -->
				<div class="aria-design-section">
					<h2><?php esc_html_e( 'Colors & Theme', 'aria' ); ?></h2>
					
					<div class="aria-design-fields">
						<div class="aria-field-group">
							<label for="theme"><?php esc_html_e( 'Theme', 'aria' ); ?></label>
							<select name="theme" id="theme" class="aria-select">
								<option value="light" <?php selected( $settings['theme'], 'light' ); ?>>
									<?php esc_html_e( 'Light', 'aria' ); ?>
								</option>
								<option value="dark" <?php selected( $settings['theme'], 'dark' ); ?>>
									<?php esc_html_e( 'Dark', 'aria' ); ?>
								</option>
								<option value="auto" <?php selected( $settings['theme'], 'auto' ); ?>>
									<?php esc_html_e( 'Auto (match website)', 'aria' ); ?>
								</option>
							</select>
						</div>
						
						<div class="aria-color-controls">
							<div class="aria-field-group">
								<label for="primary_color"><?php esc_html_e( 'Primary Color', 'aria' ); ?></label>
								<input type="text" 
								       name="primary_color" 
								       id="primary_color" 
								       value="<?php echo esc_attr( $settings['primary_color'] ); ?>" 
								       class="aria-color-picker" />
								<p class="description"><?php esc_html_e( 'Header, buttons, Aria messages', 'aria' ); ?></p>
							</div>
							
							<div class="aria-field-group">
								<label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'aria' ); ?></label>
								<input type="text" 
								       name="secondary_color" 
								       id="secondary_color" 
								       value="<?php echo esc_attr( $settings['secondary_color'] ); ?>" 
								       class="aria-color-picker" />
								<p class="description"><?php esc_html_e( 'User messages and backgrounds', 'aria' ); ?></p>
							</div>
							
							<div class="aria-field-group">
								<label for="text_color"><?php esc_html_e( 'Text Color', 'aria' ); ?></label>
								<input type="text" 
								       name="text_color" 
								       id="text_color" 
								       value="<?php echo esc_attr( $settings['text_color'] ); ?>" 
								       class="aria-color-picker" />
								<p class="description"><?php esc_html_e( 'Main text color for readability', 'aria' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Chat Button -->
				<div class="aria-design-section">
					<h2><?php esc_html_e( 'Chat Button', 'aria' ); ?></h2>
					
					<div class="aria-design-fields">
						<div class="aria-button-controls">
							<div class="aria-field-group">
								<label for="button_size"><?php esc_html_e( 'Size', 'aria' ); ?></label>
								<select name="button_size" id="button_size" class="aria-select">
									<option value="small" <?php selected( $settings['button_size'], 'small' ); ?>>
										<?php esc_html_e( 'Small (50px)', 'aria' ); ?>
									</option>
									<option value="medium" <?php selected( $settings['button_size'], 'medium' ); ?>>
										<?php esc_html_e( 'Medium (60px)', 'aria' ); ?>
									</option>
									<option value="large" <?php selected( $settings['button_size'], 'large' ); ?>>
										<?php esc_html_e( 'Large (70px)', 'aria' ); ?>
									</option>
								</select>
							</div>
							
							<div class="aria-field-group">
								<label for="button_style"><?php esc_html_e( 'Style', 'aria' ); ?></label>
								<select name="button_style" id="button_style" class="aria-select">
									<option value="rounded" <?php selected( $settings['button_style'], 'rounded' ); ?>>
										<?php esc_html_e( 'Rounded', 'aria' ); ?>
									</option>
									<option value="square" <?php selected( $settings['button_style'], 'square' ); ?>>
										<?php esc_html_e( 'Square', 'aria' ); ?>
									</option>
									<option value="circle" <?php selected( $settings['button_style'], 'circle' ); ?>>
										<?php esc_html_e( 'Circle', 'aria' ); ?>
									</option>
								</select>
							</div>
						</div>
						
						<div class="aria-field-group">
							<label><?php esc_html_e( 'Icon', 'aria' ); ?></label>
							<div class="aria-icon-selector">
								<label class="aria-icon-option">
									<input type="radio" 
									       name="button_icon" 
									       value="chat-bubble" 
									       <?php checked( $settings['button_icon'], 'chat-bubble' ); ?> />
									<span class="aria-icon-preview">
										<svg viewBox="0 0 24 24" width="24" height="24">
											<path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 .97 4.29L1 23l6.71-1.97C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.41 0-2.73-.36-3.88-.98l-.28-.15-2.89.85.85-2.89-.15-.28C5.36 14.73 5 13.41 5 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>
										</svg>
									</span>
									<span><?php esc_html_e( 'Chat Bubble', 'aria' ); ?></span>
								</label>
								
								<label class="aria-icon-option">
									<input type="radio" 
									       name="button_icon" 
									       value="message" 
									       <?php checked( $settings['button_icon'], 'message' ); ?> />
									<span class="aria-icon-preview">
										<svg viewBox="0 0 24 24" width="24" height="24">
											<path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17l-.59.59-.58.58V4h16v12z"/>
										</svg>
									</span>
									<span><?php esc_html_e( 'Message', 'aria' ); ?></span>
								</label>
								
								<label class="aria-icon-option">
									<input type="radio" 
									       name="button_icon" 
									       value="help" 
									       <?php checked( $settings['button_icon'], 'help' ); ?> />
									<span class="aria-icon-preview">
										<svg viewBox="0 0 24 24" width="24" height="24">
											<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
										</svg>
									</span>
									<span><?php esc_html_e( 'Help', 'aria' ); ?></span>
								</label>
								
								<label class="aria-icon-option">
									<input type="radio" 
									       name="button_icon" 
									       value="custom" 
									       <?php checked( $settings['button_icon'], 'custom' ); ?> />
									<span class="aria-icon-preview">
										<?php if ( ! empty( $settings['custom_icon'] ) ) : ?>
											<img src="<?php echo esc_url( $settings['custom_icon'] ); ?>" width="24" height="24" />
										<?php else : ?>
											<span class="dashicons dashicons-upload"></span>
										<?php endif; ?>
									</span>
									<span><?php esc_html_e( 'Custom', 'aria' ); ?></span>
								</label>
							</div>
							
							<div id="custom-icon-upload" class="aria-custom-upload" style="<?php echo 'custom' !== $settings['button_icon'] ? 'display:none;' : ''; ?>">
								<input type="hidden" 
								       name="custom_icon" 
								       id="custom_icon" 
								       value="<?php echo esc_url( $settings['custom_icon'] ); ?>" />
								<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
								<button type="button" class="button button-secondary aria-btn-secondary" id="upload_icon_button">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Upload Icon', 'aria' ); ?>
								</button>
								<span id="custom_icon_preview">
									<?php if ( ! empty( $settings['custom_icon'] ) ) : ?>
										<img src="<?php echo esc_url( $settings['custom_icon'] ); ?>" class="preview-image" />
									<?php endif; ?>
								</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Avatar & Effects -->
				<div class="aria-design-section">
					<h2><?php esc_html_e( 'Avatar & Effects', 'aria' ); ?></h2>
					
					<div class="aria-design-fields">
						<div class="aria-field-group">
							<label class="aria-checkbox-label">
								<input type="checkbox" 
								       name="show_avatar" 
								       id="show_avatar" 
								       value="1" 
								       <?php checked( $settings['show_avatar'] ); ?> />
								<?php esc_html_e( 'Display Aria\'s avatar in chat', 'aria' ); ?>
							</label>
						</div>
						
						<div id="avatar-style-section" class="aria-field-group" style="<?php echo ! $settings['show_avatar'] ? 'display:none;' : ''; ?>">
							<label><?php esc_html_e( 'Avatar Style', 'aria' ); ?></label>
							<div class="aria-avatar-selector">
								<label class="aria-avatar-option">
									<input type="radio" 
									       name="avatar_style" 
									       value="initials" 
									       <?php checked( $settings['avatar_style'], 'initials' ); ?> />
									<span class="aria-avatar-preview aria-avatar-initials">
										<?php if ( file_exists( ARIA_PLUGIN_PATH . 'public/images/aria.png' ) ) : ?>
											<img src="<?php echo esc_url( ARIA_PLUGIN_URL . 'public/images/aria.png' ); ?>" alt="Aria" />
										<?php else : ?>
											<span>A</span>
										<?php endif; ?>
									</span>
									<span><?php esc_html_e( 'Default', 'aria' ); ?></span>
								</label>
								
								<label class="aria-avatar-option">
									<input type="radio" 
									       name="avatar_style" 
									       value="icon" 
									       <?php checked( $settings['avatar_style'], 'icon' ); ?> />
									<span class="aria-avatar-preview aria-avatar-icon">
										<span class="dashicons dashicons-format-chat"></span>
									</span>
									<span><?php esc_html_e( 'Icon', 'aria' ); ?></span>
								</label>
								
								<label class="aria-avatar-option">
									<input type="radio" 
									       name="avatar_style" 
									       value="custom" 
									       <?php checked( $settings['avatar_style'], 'custom' ); ?> />
									<span class="aria-avatar-preview">
										<?php if ( ! empty( $settings['custom_avatar'] ) ) : ?>
											<img src="<?php echo esc_url( $settings['custom_avatar'] ); ?>" />
										<?php else : ?>
											<span class="dashicons dashicons-upload"></span>
										<?php endif; ?>
									</span>
									<span><?php esc_html_e( 'Custom', 'aria' ); ?></span>
								</label>
							</div>
							
							<div id="custom-avatar-upload" class="aria-custom-upload" style="<?php echo 'custom' !== $settings['avatar_style'] ? 'display:none;' : ''; ?>">
								<input type="hidden" 
								       name="custom_avatar" 
								       id="custom_avatar" 
								       value="<?php echo esc_url( $settings['custom_avatar'] ); ?>" />
								<!-- Styled with SCSS aria-button-secondary mixin in admin.scss -->
								<button type="button" class="button button-secondary aria-btn-secondary" id="upload_avatar_button">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Upload Avatar', 'aria' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Square image, 100x100px recommended', 'aria' ); ?></p>
							</div>
						</div>
						
						<div class="aria-effects-controls">
							<div class="aria-field-group">
								<label class="aria-checkbox-label">
									<input type="checkbox" 
									       name="animations" 
									       id="animations" 
									       value="1" 
									       <?php checked( $settings['animations'] ); ?> />
									<?php esc_html_e( 'Enable smooth animations', 'aria' ); ?>
								</label>
							</div>
							
							<div class="aria-field-group">
								<label class="aria-checkbox-label">
									<input type="checkbox" 
									       name="sound_enabled" 
									       id="sound_enabled" 
									       value="1" 
									       <?php checked( $settings['sound_enabled'] ); ?> />
									<?php esc_html_e( 'Enable notification sounds', 'aria' ); ?>
								</label>
							</div>
						</div>
					</div>
				</div>

				<!-- Text Customization -->
				<div class="aria-design-section">
					<h2><?php esc_html_e( 'Text Customization', 'aria' ); ?></h2>
					
					<div class="aria-design-fields">
						<div class="aria-field-group">
							<label for="embed_title"><?php esc_html_e( 'Embed Form Title', 'aria' ); ?></label>
							<input type="text" 
							       name="embed_title" 
							       id="embed_title" 
							       value="<?php echo esc_attr( $settings['embed_title'] ); ?>" 
							       class="aria-input" />
							<p class="description"><?php esc_html_e( 'Title at the top of embed forms', 'aria' ); ?></p>
						</div>
						
						<div class="aria-field-group">
							<label for="welcome_message"><?php esc_html_e( 'Welcome Message', 'aria' ); ?></label>
							<input type="text" 
							       name="welcome_message" 
							       id="welcome_message" 
							       value="<?php echo esc_attr( $settings['welcome_message'] ); ?>" 
							       class="aria-input" />
							<p class="description"><?php esc_html_e( 'First message when users open chat', 'aria' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Custom CSS -->
				<div class="aria-design-section">
					<h2><?php esc_html_e( 'Custom CSS & Variables', 'aria' ); ?></h2>
					
					<div class="aria-design-fields">
						<div class="aria-field-group">
							<label for="custom_css"><?php esc_html_e( 'Additional Styles', 'aria' ); ?></label>
							<textarea name="custom_css" 
							          id="custom_css" 
							          rows="8" 
							          class="aria-textarea code"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
						</div>
						
						<div class="aria-css-documentation">
							<h4><?php esc_html_e( 'CSS Variables Reference', 'aria' ); ?></h4>
							<div class="aria-css-grid">
								<div class="aria-css-section">
									<h5><?php esc_html_e( 'Colors', 'aria' ); ?></h5>
									<ul class="aria-css-list">
										<li><code>--aria-primary-color</code> - Primary brand color</li>
										<li><code>--aria-secondary-color</code> - Secondary/background color</li>
										<li><code>--aria-text-color</code> - Main text color</li>
										<li><code>--aria-border-color</code> - Border and divider color</li>
									</ul>
								</div>
								
								<div class="aria-css-section">
									<h5><?php esc_html_e( 'Main Components', 'aria' ); ?></h5>
									<ul class="aria-css-list">
										<li><code>.aria-chat-widget</code> - Main chat container</li>
										<li><code>.aria-chat-button</code> - Chat button</li>
										<li><code>.aria-chat-window</code> - Chat window</li>
										<li><code>.aria-chat-header</code> - Chat header bar</li>
									</ul>
								</div>
								
								<div class="aria-css-section">
									<h5><?php esc_html_e( 'Messages', 'aria' ); ?></h5>
									<ul class="aria-css-list">
										<li><code>.aria-message</code> - Message container</li>
										<li><code>.aria-message-bot</code> - Bot messages</li>
										<li><code>.aria-message-user</code> - User messages</li>
										<li><code>.aria-message-avatar</code> - Message avatars</li>
									</ul>
								</div>
							</div>
							
							<h4><?php esc_html_e( 'Common Customizations', 'aria' ); ?></h4>
							<div class="aria-examples-grid">
								<div class="aria-example">
									<h5><?php esc_html_e( 'Custom Font', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-chat-widget {
  font-family: 'Georgia', serif;
}</code>
								</div>
								
								<div class="aria-example">
									<h5><?php esc_html_e( 'Rounded Chat Window', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-chat-window {
  border-radius: 25px;
  overflow: hidden;
}</code>
								</div>
								
								<div class="aria-example">
									<h5><?php esc_html_e( 'Custom Button Shadow', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-chat-button {
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}</code>
								</div>
								
								<div class="aria-example">
									<h5><?php esc_html_e( 'Message Styling', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-message-bot .aria-message-content {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}</code>
								</div>
								
								<div class="aria-example">
									<h5><?php esc_html_e( 'Hide Avatar', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-message-avatar {
  display: none;
}</code>
								</div>
								
								<div class="aria-example">
									<h5><?php esc_html_e( 'Custom Animation', 'aria' ); ?></h5>
									<code class="aria-code-block">.aria-chat-button {
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}</code>
								</div>
							</div>
							
							<div class="aria-css-tips">
								<h4><?php esc_html_e( 'Tips & Best Practices', 'aria' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Always test your CSS on different devices and screen sizes', 'aria' ); ?></li>
									<li><?php esc_html_e( 'Use CSS variables for consistent theming across components', 'aria' ); ?></li>
									<li><?php esc_html_e( 'Add !important only when necessary to override default styles', 'aria' ); ?></li>
									<li><?php esc_html_e( 'Keep accessibility in mind - ensure good color contrast', 'aria' ); ?></li>
									<li><?php esc_html_e( 'Test chat functionality after applying custom styles', 'aria' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				
				<p class="submit">
					<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
					<button type="submit" class="button button-primary aria-btn-primary">
						<?php esc_html_e( 'Save Design Settings', 'aria' ); ?>
					</button>
				</p>
			</div>

			<!-- Right Column: Live Preview -->
			<div class="aria-design-preview">
				<div class="aria-preview-container">
					<h3><?php esc_html_e( 'Live Preview', 'aria' ); ?></h3>
					
					<div class="aria-preview-device">
						<div class="aria-device-selector">
							<!-- Styled with SCSS aria-button-ghost mixin in admin.scss -->
							<button type="button" class="button button-secondary aria-btn-ghost aria-device-btn active" data-device="desktop">
								<span class="dashicons dashicons-desktop"></span>
								<?php esc_html_e( 'Desktop', 'aria' ); ?>
							</button>
							<!-- Styled with SCSS aria-button-ghost mixin in admin.scss -->
							<button type="button" class="button button-secondary aria-btn-ghost aria-device-btn" data-device="mobile">
								<span class="dashicons dashicons-smartphone"></span>
								<?php esc_html_e( 'Mobile', 'aria' ); ?>
							</button>
						</div>
						
						<div class="aria-device-frame">
							<div class="aria-preview-screen">
								<!-- Preview will be rendered here by JavaScript -->
								<div id="aria-preview-widget" class="aria-chat-preview">
									<div class="aria-chat-button-preview">
										<span class="aria-button-icon"></span>
									</div>
									
									<div class="aria-chat-window-preview">
										<div class="aria-chat-header">
											<div class="aria-header-content">
												<span class="aria-avatar-preview-small"></span>
												<div class="aria-header-text">
													<span class="aria-header-title"><?php esc_html_e( 'Aria', 'aria' ); ?></span>
													<span class="aria-header-status"><?php esc_html_e( 'Online', 'aria' ); ?></span>
												</div>
											</div>
											<!-- Styled with SCSS aria-button-ghost mixin in admin.scss -->
											<button class="aria-close-button button aria-btn-ghost">×</button>
										</div>
										
										<div class="aria-chat-messages">
											<div class="aria-message aria-message-bot">
												<span class="aria-message-avatar"></span>
												<div class="aria-message-content">
													<?php esc_html_e( 'Hi! I\'m Aria. How can I help you today?', 'aria' ); ?>
												</div>
											</div>
											<div class="aria-message aria-message-user">
												<div class="aria-message-content">
													<?php esc_html_e( 'Tell me about your services', 'aria' ); ?>
												</div>
											</div>
										</div>
										
										<div class="aria-chat-input">
											<input type="text" placeholder="<?php esc_attr_e( 'Type your message...', 'aria' ); ?>" disabled />
											<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
											<button disabled class="button button-primary aria-btn-primary"><?php esc_html_e( 'Send', 'aria' ); ?></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="aria-design-tips">
					<h3><?php esc_html_e( 'Design Tips', 'aria' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Choose colors that contrast well', 'aria' ); ?></li>
						<li><?php esc_html_e( 'Keep button visible but not intrusive', 'aria' ); ?></li>
						<li><?php esc_html_e( 'Test on mobile devices', 'aria' ); ?></li>
						<li><?php esc_html_e( 'Match your brand identity', 'aria' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</form>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Initialize color pickers
	$('.aria-color-picker').wpColorPicker({
		change: function(event, ui) {
			updatePreview();
		}
	});

	// Icon selection
	$('input[name="button_icon"]').on('change', function() {
		if ($(this).val() === 'custom') {
			$('#custom-icon-upload').show();
		} else {
			$('#custom-icon-upload').hide();
		}
		updatePreview();
	});

	// Avatar visibility
	$('#show_avatar').on('change', function() {
		if ($(this).is(':checked')) {
			$('#avatar-style-section').show();
		} else {
			$('#avatar-style-section').hide();
		}
		updatePreview();
	});

	// Avatar style selection
	$('input[name="avatar_style"]').on('change', function() {
		if ($(this).val() === 'custom') {
			$('#custom-avatar-upload').show();
		} else {
			$('#custom-avatar-upload').hide();
		}
		updatePreview();
	});

	// Device preview toggle
	$('.aria-device-btn').on('click', function() {
		$('.aria-device-btn').removeClass('active');
		$(this).addClass('active');
		var device = $(this).data('device');
		$('.aria-device-frame').removeClass('desktop mobile').addClass(device);
		updatePreview();
	});

	// Update preview function
	function updatePreview() {
		var primaryColor = $('#primary_color').val();
		var secondaryColor = $('#secondary_color').val();
		var textColor = $('#text_color').val();
		var buttonSize = $('#button_size').val();
		var buttonStyle = $('#button_style').val();
		var position = $('#position').val();
		var theme = $('#theme').val();
		var showAvatar = $('#show_avatar').is(':checked');
		var avatarStyle = $('input[name="avatar_style"]:checked').val();
		var buttonIcon = $('input[name="button_icon"]:checked').val();
		var chatWidth = $('#chat_width').val() || 380;
		var chatHeight = $('#chat_height').val() || 600;
		var welcomeMessage = $('#welcome_message').val() || 'Welcome! Please introduce yourself so I can assist you better.';
		
		var $preview = $('#aria-preview-widget');
		var $button = $preview.find('.aria-chat-button-preview');
		var $window = $preview.find('.aria-chat-window-preview');
		
		// Update position
		$preview.removeClass('bottom-right bottom-left bottom-center').addClass(position);
		
		// Update colors
		$preview.find('.aria-chat-header').css('background-color', primaryColor);
		$button.css('background-color', primaryColor);
		$preview.find('.aria-message-bot .aria-message-content').css({
			'background-color': 'white',
			'color': textColor,
			'border-left': '3px solid ' + primaryColor
		});
		$preview.find('.aria-message-user .aria-message-content').css('background-color', secondaryColor);
		$preview.find('.aria-message-avatar').css('background-color', primaryColor);
		$preview.find('.aria-avatar-preview-small').css('background-color', 'rgba(255,255,255,0.2)');
		
		// Update button size
		var sizes = { small: '50px', medium: '60px', large: '70px' };
		$button.css({
			width: sizes[buttonSize],
			height: sizes[buttonSize],
			'font-size': buttonSize === 'small' ? '18px' : buttonSize === 'large' ? '28px' : '24px'
		});
		
		// Update button style
		$button.removeClass('style-square style-rounded style-circle').addClass('style-' + buttonStyle);
		
		// Update button icon
		var iconHtml = '';
		switch(buttonIcon) {
			case 'chat-bubble':
				iconHtml = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 .97 4.29L1 23l6.71-1.97C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.41 0-2.73-.36-3.88-.98l-.28-.15-2.89.85.85-2.89-.15-.28C5.36 14.73 5 13.41 5 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/></svg>';
				break;
			case 'message':
				iconHtml = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17l-.59.59-.58.58V4h16v12z"/></svg>';
				break;
			case 'help':
				iconHtml = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>';
				break;
			case 'custom':
				var customIcon = $('#custom_icon').val();
				if (customIcon) {
					iconHtml = '<img src="' + customIcon + '" style="width: 20px; height: 20px; object-fit: cover;" />';
				} else {
					iconHtml = '💬';
				}
				break;
			default:
				iconHtml = '💬';
		}
		$button.find('.aria-button-icon').html(iconHtml);
		
		// Update chat window size
		$window.css({
			width: Math.min(parseInt(chatWidth), 400) + 'px',
			height: Math.min(parseInt(chatHeight), 500) + 'px'
		});
		
		// Update theme
		if (theme === 'dark') {
			$preview.addClass('theme-dark').removeClass('theme-light');
			$preview.find('.aria-preview-screen').css('background', '#2d3748');
			$preview.find('.aria-chat-messages').css('background', '#4a5568');
		} else {
			$preview.addClass('theme-light').removeClass('theme-dark');
			$preview.find('.aria-preview-screen').css('background', 'white');
			$preview.find('.aria-chat-messages').css('background', '#f8f9fa');
		}
		
		// Update avatar visibility and style
		if (showAvatar) {
			$preview.find('.aria-message-avatar, .aria-avatar-preview-small').show();
			
			// Update avatar style
			var $avatarSmall = $preview.find('.aria-avatar-preview-small');
			var $avatarMessage = $preview.find('.aria-message-avatar');
			
			switch(avatarStyle) {
				case 'initials':
					$avatarSmall.html('A').css('background-color', 'rgba(255,255,255,0.2)');
					$avatarMessage.html('A').css('background-color', primaryColor);
					break;
				case 'icon':
					$avatarSmall.html('<span class="dashicons dashicons-format-chat" style="font-size: 14px;"></span>');
					$avatarMessage.html('<span class="dashicons dashicons-format-chat" style="font-size: 12px;"></span>');
					break;
				case 'custom':
					var customAvatar = $('#custom_avatar').val();
					if (customAvatar) {
						$avatarSmall.html('<img src="' + customAvatar + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />');
						$avatarMessage.html('<img src="' + customAvatar + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />');
					}
					break;
			}
		} else {
			$preview.find('.aria-message-avatar, .aria-avatar-preview-small').hide();
		}
		
		// Update welcome message
		$preview.find('.aria-message-bot .aria-message-content').text(welcomeMessage);
	}

	// Initialize preview
	updatePreview();

	// Form field changes
	$('input, select').on('change input', updatePreview);

	// Media uploader for custom icon
	$('#upload_icon_button').on('click', function(e) {
		e.preventDefault();
		var mediaUploader = wp.media({
			title: '<?php echo esc_js( __( 'Choose Icon', 'aria' ) ); ?>',
			button: {
				text: '<?php echo esc_js( __( 'Use this icon', 'aria' ) ); ?>'
			},
			multiple: false
		}).on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#custom_icon').val(attachment.url);
			$('#custom_icon_preview').html('<img src="' + attachment.url + '" style="max-width: 60px; margin-left: 10px; vertical-align: middle;" />');
			updatePreview();
		}).open();
	});

	// Media uploader for custom avatar
	$('#upload_avatar_button').on('click', function(e) {
		e.preventDefault();
		var mediaUploader = wp.media({
			title: '<?php echo esc_js( __( 'Choose Avatar', 'aria' ) ); ?>',
			button: {
				text: '<?php echo esc_js( __( 'Use this avatar', 'aria' ) ); ?>'
			},
			multiple: false
		}).on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#custom_avatar').val(attachment.url);
			$('input[value="custom"] + .aria-avatar-preview').html('<img src="' + attachment.url + '" />');
			updatePreview();
		}).open();
	});

	// Device preview selector
	$('.aria-device-btn').on('click', function() {
		$('.aria-device-btn').removeClass('active');
		$(this).addClass('active');
		
		var device = $(this).data('device');
		if (device === 'mobile') {
			$('.aria-preview-screen').addClass('mobile-view');
		} else {
			$('.aria-preview-screen').removeClass('mobile-view');
		}
	});

	// Live preview update
	function updatePreview() {
		var position = $('#position').val();
		var theme = $('#theme').val();
		var primaryColor = $('#primary_color').val();
		var secondaryColor = $('#secondary_color').val();
		var textColor = $('#text_color').val();
		var buttonSize = $('#button_size').val();
		var buttonStyle = $('#button_style').val();
		var showAvatar = $('#show_avatar').is(':checked');
		
		// Update preview CSS
		var previewWidget = $('#aria-preview-widget');
		
		// Position
		previewWidget.removeClass('position-bottom-right position-bottom-left position-bottom-center');
		previewWidget.addClass('position-' + position);
		
		// Theme
		previewWidget.removeClass('theme-light theme-dark');
		previewWidget.addClass('theme-' + theme);
		
		// Colors
		previewWidget.find('.aria-chat-header').css('background-color', primaryColor);
		previewWidget.find('.aria-chat-button-preview').css('background-color', primaryColor);
		previewWidget.find('.aria-message-user .aria-message-content').css('background-color', secondaryColor);
		previewWidget.find('.aria-message-bot .aria-message-content').css('background-color', '#f8f9fa');
		
		// Button size
		var sizeMap = {
			'small': '50px',
			'medium': '60px',
			'large': '70px'
		};
		previewWidget.find('.aria-chat-button-preview').css({
			'width': sizeMap[buttonSize],
			'height': sizeMap[buttonSize]
		});
		
		// Button style
		previewWidget.find('.aria-chat-button-preview').removeClass('style-rounded style-square style-circle');
		previewWidget.find('.aria-chat-button-preview').addClass('style-' + buttonStyle);
		
		// Avatar visibility
		if (showAvatar) {
			previewWidget.find('.aria-message-avatar, .aria-avatar-preview-small').show();
		} else {
			previewWidget.find('.aria-message-avatar, .aria-avatar-preview-small').hide();
		}
	}

	// Initial preview update
	updatePreview();

	// Update preview on form changes
	$('input, select, textarea').on('change input', function() {
		updatePreview();
	});
});
</script>

<style>
/* Beautiful Design Page Layout */
.aria-admin-wrap {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	min-height: 100vh;
	margin: 0 -20px 0 -22px;
	padding: 30px;
	position: relative;
}

.aria-admin-wrap::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: 
		radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
		radial-gradient(circle at 80% 20%, rgba(255, 118, 117, 0.2) 0%, transparent 50%),
		radial-gradient(circle at 40% 80%, rgba(255, 177, 153, 0.2) 0%, transparent 50%);
	pointer-events: none;
}

.aria-admin-wrap > * {
	position: relative;
	z-index: 1;
}

.aria-admin-wrap h1 {
	color: white;
	font-size: 2.5em;
	text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
	margin-bottom: 10px;
	font-weight: 300;
}

.aria-admin-wrap .description {
	color: rgba(255, 255, 255, 0.9);
	font-size: 1.1em;
	margin-bottom: 30px;
}

.aria-design-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 30px;
	margin-top: 20px;
}

/* Configuration Panel */
.aria-design-config {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20px);
	border-radius: 20px;
	padding: 30px;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
	border: 1px solid rgba(255, 255, 255, 0.2);
}

.aria-design-section {
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	border-radius: 16px;
	padding: 25px;
	margin-bottom: 25px;
	box-shadow: 
		0 8px 32px rgba(0, 0, 0, 0.1),
		inset 0 1px 0 rgba(255, 255, 255, 0.8);
	border: 1px solid rgba(255, 255, 255, 0.3);
	transition: all 0.3s ease;
}

.aria-design-section:hover {
	transform: translateY(-2px);
	box-shadow: 
		0 12px 40px rgba(0, 0, 0, 0.15),
		inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.aria-design-section h2 {
	margin: 0 0 20px 0;
	font-size: 1.4em;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 10px;
}

.aria-design-section h2::before {
	content: '';
	width: 4px;
	height: 24px;
	background: linear-gradient(135deg, #667eea, #764ba2);
	border-radius: 2px;
}

/* Form Controls */
.aria-design-fields {
	display: grid;
	gap: 20px;
}

.aria-field-group {
	margin-bottom: 0;
}

.aria-field-group label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #2d3748;
	font-size: 14px;
}

.aria-select,
.aria-input,
.aria-input-number,
.aria-textarea {
	width: 100%;
	padding: 12px 16px;
	border: 2px solid #e2e8f0;
	border-radius: 12px;
	font-size: 14px;
	transition: all 0.3s ease;
	background: white;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.aria-select:focus,
.aria-input:focus,
.aria-input-number:focus,
.aria-textarea:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 
		0 1px 3px rgba(0, 0, 0, 0.1),
		0 0 0 3px rgba(102, 126, 234, 0.1);
	transform: translateY(-1px);
}

.aria-input-with-unit {
	display: flex;
	align-items: center;
	position: relative;
}

.aria-input-with-unit .unit {
	position: absolute;
	right: 12px;
	color: #64748b;
	font-weight: 500;
	background: #f1f5f9;
	padding: 2px 6px;
	border-radius: 4px;
	font-size: 12px;
}

/* Grid Layouts */
.aria-size-controls {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 15px;
}

.aria-color-controls {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
}

.aria-button-controls {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 15px;
}

.aria-effects-controls {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 15px;
}

/* Color Picker Enhancements */
.aria-color-picker {
	display: inline-block;
	border-radius: 8px !important;
}

.wp-color-result {
	border-radius: 8px !important;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
	border: 2px solid #e2e8f0 !important;
	transition: all 0.3s ease !important;
}

.wp-color-result:hover {
	transform: translateY(-1px);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
}

/* Icon and Avatar Selectors */
.aria-icon-selector,
.aria-avatar-selector {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
	gap: 12px;
	margin-top: 12px;
}

.aria-icon-option,
.aria-avatar-option {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 16px;
	border: 2px solid #e2e8f0;
	border-radius: 12px;
	cursor: pointer;
	transition: all 0.3s ease;
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
	text-align: center;
}

.aria-icon-option:hover,
.aria-avatar-option:hover {
	border-color: #667eea;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.aria-icon-option input[type="radio"]:checked + .aria-icon-preview,
.aria-avatar-option input[type="radio"]:checked + .aria-avatar-preview {
	background: linear-gradient(135deg, #667eea, #764ba2);
	color: white;
}

.aria-icon-option input[type="radio"]:checked + .aria-icon-preview + span,
.aria-avatar-option input[type="radio"]:checked + .aria-avatar-preview + span {
	color: #667eea;
	font-weight: 600;
}

.aria-icon-preview,
.aria-avatar-preview {
	width: 40px;
	height: 40px;
	border-radius: 10px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #f1f5f9;
	transition: all 0.3s ease;
}

.aria-icon-preview svg,
.aria-avatar-preview svg {
	width: 20px;
	height: 20px;
	fill: currentColor;
}

.aria-icon-preview img,
.aria-avatar-preview img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	border-radius: inherit;
}

/* Checkbox Styling */
.aria-checkbox-label {
	display: flex;
	align-items: center;
	gap: 10px;
	cursor: pointer;
	padding: 12px 16px;
	border: 2px solid #e2e8f0;
	border-radius: 12px;
	transition: all 0.3s ease;
	background: linear-gradient(145deg, #ffffff, #f8f9ff);
}

.aria-checkbox-label:hover {
	border-color: #667eea;
	transform: translateY(-1px);
	box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.aria-checkbox-label input[type="checkbox"] {
	width: 18px;
	height: 18px;
	border-radius: 4px;
	border: 2px solid #e2e8f0;
	margin: 0;
}

.aria-checkbox-label input[type="checkbox"]:checked {
	background: linear-gradient(135deg, #667eea, #764ba2);
	border-color: #667eea;
}

/* Custom Upload Areas */
.aria-custom-upload {
	margin-top: 15px;
	padding: 20px;
	border: 2px dashed #e2e8f0;
	border-radius: 12px;
	text-align: center;
	background: linear-gradient(145deg, #fafbff, #f1f5f9);
	transition: all 0.3s ease;
}

.aria-custom-upload:hover {
	border-color: #667eea;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
}

/* Custom upload button styles moved to centralized SCSS system in admin.scss */

/* Preview Panel */
.aria-design-preview {
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20px);
	border-radius: 20px;
	padding: 30px;
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
	border: 1px solid rgba(255, 255, 255, 0.2);
	position: sticky;
	top: 30px;
	height: fit-content;
}

.aria-preview-container h3 {
	margin: 0 0 20px 0;
	font-size: 1.3em;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 600;
}

.aria-device-selector {
	display: flex;
	gap: 10px;
	margin-bottom: 20px;
	background: #f1f5f9;
	padding: 4px;
	border-radius: 12px;
}

.aria-device-btn {
	flex: 1;
	padding: 8px 12px;
	background: transparent;
	border: none;
	border-radius: 8px;
	font-size: 12px;
	font-weight: 500;
	color: #64748b;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
}

.aria-device-btn.active {
	background: linear-gradient(135deg, #667eea, #764ba2);
	color: white;
	box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.aria-device-frame {
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	border-radius: 16px;
	padding: 20px;
	border: 2px solid #e2e8f0;
	position: relative;
	overflow: hidden;
}

.aria-device-frame::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: 
		linear-gradient(45deg, transparent 45%, rgba(255, 255, 255, 0.1) 50%, transparent 55%);
	pointer-events: none;
	animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
	0%, 100% { transform: translateX(-100%); }
	50% { transform: translateX(100%); }
}

.aria-preview-screen {
	background: white;
	border-radius: 12px;
	min-height: 600px;
	height: 600px;
	position: relative;
	overflow: hidden;
	box-shadow: 
		0 10px 30px rgba(0, 0, 0, 0.1),
		inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

/* Chat Preview Styles */
.aria-chat-preview {
	position: relative;
	height: 100%;
	background: #f8f9fa;
	display: flex;
	flex-direction: column;
}

.aria-chat-button-preview {
	position: absolute;
	bottom: 20px;
	right: 20px;
	width: 60px;
	height: 60px;
	background: #667eea;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	color: white;
	font-size: 24px;
	box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
	animation: pulse 2s ease-in-out infinite;
	transition: all 0.3s ease;
	cursor: pointer;
}

/* Position Classes */
.aria-chat-preview.bottom-left .aria-chat-button-preview,
.aria-chat-preview.bottom-left .aria-chat-window-preview {
	right: auto;
	left: 20px;
}

.aria-chat-preview.bottom-center .aria-chat-button-preview {
	right: auto;
	left: 50%;
	transform: translateX(-50%);
}

.aria-chat-preview.bottom-center .aria-chat-window-preview {
	right: auto;
	left: 50%;
	transform: translateX(-50%);
}

/* Button Style Classes */
.aria-chat-button-preview.style-square {
	border-radius: 12px;
}

.aria-chat-button-preview.style-rounded {
	border-radius: 20px;
}

.aria-chat-button-preview.style-circle {
	border-radius: 50%;
}

@keyframes pulse {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.05); }
}

.aria-chat-window-preview {
	position: absolute;
	bottom: 120px;
	right: 20px;
	width: 320px;
	height: 450px;
	background: white;
	border-radius: 16px;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
	display: flex;
	flex-direction: column;
	overflow: hidden;
	opacity: 0.95;
	transition: all 0.3s ease;
}

.aria-chat-header {
	background: #667eea;
	color: white;
	padding: 16px;
	display: flex;
	align-items: center;
	justify-content: between;
}

.aria-header-content {
	display: flex;
	align-items: center;
	gap: 10px;
	flex: 1;
}

.aria-avatar-preview-small {
	width: 32px;
	height: 32px;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 14px;
	font-weight: 600;
}

.aria-header-text {
	display: flex;
	flex-direction: column;
}

.aria-header-title {
	font-weight: 600;
	font-size: 14px;
}

.aria-header-status {
	font-size: 11px;
	opacity: 0.8;
}

.aria-close-button {
	background: none;
	border: none;
	color: white;
	font-size: 18px;
	width: 24px;
	height: 24px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	opacity: 0.7;
	cursor: pointer;
}

.aria-chat-messages {
	flex: 1;
	padding: 16px;
	display: flex;
	flex-direction: column;
	gap: 12px;
	background: #f8f9fa;
}

.aria-message {
	display: flex;
	align-items: flex-start;
	gap: 8px;
}

.aria-message-user {
	flex-direction: row-reverse;
}

.aria-message-avatar {
	width: 24px;
	height: 24px;
	background: #667eea;
	border-radius: 50%;
	flex-shrink: 0;
}

.aria-message-content {
	padding: 8px 12px;
	border-radius: 12px;
	font-size: 13px;
	max-width: 200px;
}

.aria-message-bot .aria-message-content {
	background: white;
	color: #2d3748;
}

.aria-message-user .aria-message-content {
	background: #e2e8f0;
	color: #2d3748;
}

.aria-chat-input {
	padding: 16px;
	background: white;
	border-top: 1px solid #e2e8f0;
	display: flex;
	gap: 8px;
}

.aria-chat-input input {
	flex: 1;
	padding: 8px 12px;
	border: 1px solid #e2e8f0;
	border-radius: 20px;
	font-size: 13px;
}

.aria-chat-input button {
	padding: 8px 16px;
	background: #667eea;
	color: white;
	border: none;
	border-radius: 20px;
	font-size: 13px;
	font-weight: 500;
}

/* Design Tips */
.aria-design-tips {
	margin-top: 30px;
	padding: 20px;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	border-radius: 12px;
	border: 1px solid #e2e8f0;
}

.aria-design-tips h3 {
	margin: 0 0 15px 0;
	font-size: 1.1em;
	color: #2d3748;
	font-weight: 600;
}

.aria-design-tips ul {
	margin: 0;
	padding-left: 20px;
}

.aria-design-tips li {
	margin-bottom: 8px;
	font-size: 13px;
	color: #4a5568;
}

/* Submit Button - styles moved to centralized SCSS system in admin.scss */

/* Descriptions */
.description {
	color: #64748b;
	font-size: 12px;
	margin-top: 6px;
	line-height: 1.4;
}

/* CSS Documentation Styles */
.aria-css-documentation {
	margin-top: 25px;
	padding: 25px;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	border-radius: 16px;
	border: 2px solid #e2e8f0;
}

.aria-css-documentation h4 {
	margin: 0 0 20px 0;
	font-size: 1.2em;
	color: #2d3748;
	font-weight: 600;
	border-bottom: 2px solid #e2e8f0;
	padding-bottom: 10px;
}

.aria-css-documentation h5 {
	margin: 0 0 12px 0;
	font-size: 1em;
	color: #4a5568;
	font-weight: 600;
}

.aria-css-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.aria-css-section {
	background: white;
	padding: 20px;
	border-radius: 12px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.aria-css-list {
	margin: 0;
	padding-left: 0;
	list-style: none;
}

.aria-css-list li {
	margin-bottom: 8px;
	font-size: 13px;
	display: flex;
	align-items: flex-start;
	gap: 8px;
}

.aria-css-list code {
	background: linear-gradient(135deg, #667eea, #764ba2);
	color: white;
	padding: 2px 8px;
	border-radius: 4px;
	font-size: 11px;
	font-weight: 500;
	min-width: fit-content;
}

.aria-examples-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.aria-example {
	background: white;
	padding: 20px;
	border-radius: 12px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.aria-example h5 {
	margin-bottom: 15px;
	color: #667eea;
}

.aria-code-block {
	display: block;
	background: #2d3748;
	color: #e2e8f0;
	padding: 15px;
	border-radius: 8px;
	font-size: 12px;
	line-height: 1.6;
	overflow-x: auto;
	white-space: pre;
	font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
	border: 1px solid #4a5568;
}

.aria-css-tips {
	background: white;
	padding: 20px;
	border-radius: 12px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.aria-css-tips h4 {
	margin-bottom: 15px;
	color: #2d3748;
}

.aria-css-tips ul {
	margin: 0;
	padding-left: 20px;
}

.aria-css-tips li {
	margin-bottom: 8px;
	font-size: 13px;
	color: #4a5568;
	line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 1200px) {
	.aria-design-grid {
		grid-template-columns: 1fr;
		gap: 20px;
	}
	
	.aria-design-preview {
		position: static;
		order: -1;
	}
	
	.aria-color-controls {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.aria-button-controls {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.aria-admin-wrap {
		margin: 0 -10px;
		padding: 20px;
	}
}

@media (max-width: 768px) {
	.aria-size-controls {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.aria-icon-selector,
	.aria-avatar-selector {
		grid-template-columns: repeat(2, 1fr);
	}
	
	.aria-device-frame {
		padding: 15px;
		min-height: 300px;
	}
	
	.aria-preview-screen {
		height: 280px;
	}
	
	.aria-chat-window-preview {
		width: 280px;
		height: 380px;
	}
	
	.aria-preview-screen {
		height: 500px;
		min-height: 500px;
	}
	
	.aria-admin-wrap h1 {
		font-size: 2em;
	}
	
	.aria-effects-controls {
		grid-template-columns: 1fr;
	}
}
</style>
