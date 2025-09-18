<?php
/**
 * Personality Configuration Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current personality settings
$current_settings = Aria_Personality::get_current_settings();
$business_types = Aria_Personality::get_business_types();
$tone_settings = Aria_Personality::get_tone_settings();
$personality_traits = Aria_Personality::get_personality_traits();

// Handle form submission
if ( isset( $_POST['aria_personality_nonce'] ) && wp_verify_nonce( $_POST['aria_personality_nonce'], 'aria_personality_config' ) ) {
	// Check user permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to perform this action.', 'aria' ) . '</p></div>';
	} else {
		$new_settings = array(
			'business_type'      => isset( $_POST['business_type'] ) ? sanitize_text_field( $_POST['business_type'] ) : 'general',
			'tone_setting'       => isset( $_POST['tone_setting'] ) ? sanitize_text_field( $_POST['tone_setting'] ) : 'professional',
			'personality_traits' => isset( $_POST['personality_traits'] ) ? array_map( 'sanitize_text_field', $_POST['personality_traits'] ) : array(),
			'greeting_message'   => isset( $_POST['greeting_message'] ) ? sanitize_textarea_field( $_POST['greeting_message'] ) : '',
			'farewell_message'   => isset( $_POST['farewell_message'] ) ? sanitize_textarea_field( $_POST['farewell_message'] ) : '',
		);
		
		
		if ( Aria_Personality::save_settings( $new_settings ) ) {
			update_option( 'aria_personality_configured', true );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Aria\'s personality has been updated successfully!', 'aria' ) . '</p></div>';
			$current_settings = $new_settings;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to save personality settings. Please try again.', 'aria' ) . '</p></div>';
		}
	}
}

// Note: Sample responses removed for cleaner interface
?>

<div class="wrap aria-personality">
	<!-- Styled with SCSS grok-inspired design system in admin.scss -->
	
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'Personality & Voice', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'Define how Aria communicates and interacts with your website visitors', 'aria' ); ?></p>
		</div>
	</div>

	<div class="aria-page-content">
		<form method="post" action="" class="aria-personality-form">
			<?php wp_nonce_field( 'aria_personality_config', 'aria_personality_nonce' ); ?>

			<div class="aria-metrics-grid">
				<!-- Business Type Card -->
				<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-store"></span>
					<h3><?php esc_html_e( 'Business Type', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<p class="aria-section-description"><?php esc_html_e( 'Select your business type to help Aria understand your context', 'aria' ); ?></p>
					
					<div class="aria-business-type-grid">
						<?php foreach ( $business_types as $type_key => $type_info ) : ?>
							<label class="aria-business-type-option">
								<input type="radio" 
								       name="business_type" 
								       value="<?php echo esc_attr( $type_key ); ?>"
								       <?php checked( $current_settings['business_type'], $type_key ); ?> />
								<div class="option-content">
									<span class="option-title"><?php echo esc_html( $type_info['label'] ); ?></span>
									<?php if ( isset( $type_info['description'] ) ) : ?>
										<span class="option-description"><?php echo esc_html( $type_info['description'] ); ?></span>
									<?php endif; ?>
								</div>
								<span class="option-check dashicons dashicons-yes-alt"></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Conversation Style Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-format-chat"></span>
					<h3><?php esc_html_e( 'Conversation Style', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<p class="aria-section-description"><?php esc_html_e( 'Choose the tone that best fits your brand', 'aria' ); ?></p>
					
					<div class="aria-tone-grid">
						<?php foreach ( $tone_settings as $tone_key => $tone_info ) : ?>
							<label class="aria-tone-option">
								<input type="radio" 
								       name="tone_setting" 
								       value="<?php echo esc_attr( $tone_key ); ?>"
								       <?php checked( $current_settings['tone_setting'], $tone_key ); ?> />
								<div class="option-content">
									<span class="option-title"><?php echo esc_html( $tone_info['label'] ); ?></span>
									<?php if ( isset( $tone_info['description'] ) ) : ?>
										<span class="option-description"><?php echo esc_html( $tone_info['description'] ); ?></span>
									<?php endif; ?>
								</div>
								<span class="option-check dashicons dashicons-yes-alt"></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Key Characteristics Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-admin-appearance"></span>
					<h3><?php esc_html_e( 'Key Characteristics', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<p class="aria-section-description"><?php esc_html_e( 'Select 2-3 traits that define Aria\'s approach', 'aria' ); ?></p>
					
					<div class="aria-traits-grid">
						<?php 
						$selected_traits = is_array( $current_settings['personality_traits'] ) 
							? $current_settings['personality_traits'] 
							: array();
						
						$simplified_traits = array(
							'helpful' => __( 'Helpful & Supportive', 'aria' ),
							'knowledgeable' => __( 'Knowledgeable', 'aria' ),
							'empathetic' => __( 'Empathetic', 'aria' ),
							'efficient' => __( 'Efficient', 'aria' ),
							'patient' => __( 'Patient', 'aria' ),
							'proactive' => __( 'Proactive', 'aria' ),
						);
						
						foreach ( $simplified_traits as $trait_key => $trait_label ) : 
						?>
							<label class="aria-trait-option">
								<input type="checkbox" 
								       name="personality_traits[]" 
								       value="<?php echo esc_attr( $trait_key ); ?>"
								       <?php checked( in_array( $trait_key, $selected_traits, true ) ); ?> />
								<div class="option-content">
									<span class="option-title"><?php echo esc_html( $trait_label ); ?></span>
								</div>
								<span class="option-check dashicons dashicons-yes-alt"></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Custom Messages Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-edit"></span>
					<h3><?php esc_html_e( 'Custom Messages', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<p class="aria-section-description"><?php esc_html_e( 'Customize Aria\'s greeting and farewell messages', 'aria' ); ?></p>
					
					<div class="aria-messages-grid">
						<div class="aria-message-field">
							<label for="greeting_message"><?php esc_html_e( 'Greeting Message', 'aria' ); ?></label>
							<textarea name="greeting_message" 
							          id="greeting_message" 
							          rows="3" 
							          placeholder="<?php esc_attr_e( 'Hi! I\'m Aria. How can I help you today?', 'aria' ); ?>"><?php echo esc_textarea( stripslashes( $current_settings['greeting_message'] ) ); ?></textarea>
						</div>
						
						<div class="aria-message-field">
							<label for="farewell_message"><?php esc_html_e( 'Farewell Message', 'aria' ); ?></label>
							<textarea name="farewell_message" 
							          id="farewell_message" 
							          rows="3" 
							          placeholder="<?php esc_attr_e( 'Thanks for chatting! Have a great day!', 'aria' ); ?>"><?php echo esc_textarea( stripslashes( $current_settings['farewell_message'] ) ); ?></textarea>
						</div>
					</div>
				</div>
			</div>
			</div>

			<div class="aria-form-actions">
				<button type="submit" class="button button-primary aria-btn-primary">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Personality Settings', 'aria' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>