<?php
/**
 * AI Configuration Page
 *
 * @package    Aria
 * @subpackage Aria/admin/partials
 */

// Security check
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include required classes
require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';
require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ai-provider.php';
require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-openai-provider.php';
require_once ARIA_PLUGIN_PATH . 'includes/providers/class-aria-gemini-provider.php';

// Get current settings
$ai_provider = get_option( 'aria_ai_provider', 'openai' );
$api_key = get_option( 'aria_ai_api_key', '' );
$model_settings = get_option( 'aria_ai_model_settings', array() );

// Check if we have a saved API key
$has_api_key = ! empty( $api_key );
if ( $has_api_key && class_exists( 'Aria_Security' ) ) {
	$decrypted = Aria_Security::decrypt( $api_key );
	$masked_key = substr( $decrypted, 0, 8 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
} else {
	$masked_key = '';
}

// Handle form submission
if ( isset( $_POST['aria_ai_config_nonce'] ) && wp_verify_nonce( $_POST['aria_ai_config_nonce'], 'aria_ai_config' ) ) {
	$new_provider = sanitize_text_field( $_POST['ai_provider'] );
	$new_api_key = sanitize_text_field( $_POST['api_key'] );
	
	// Only update API key if a new one is provided
	if ( ! empty( $new_api_key ) && ! strpos( $new_api_key, '*' ) ) {
		// Validate API key format
		if ( Aria_Security::validate_api_key_format( $new_api_key, $new_provider ) ) {
			// Encrypt and save
			$encrypted_key = Aria_Security::encrypt( $new_api_key );
			update_option( 'aria_ai_api_key', $encrypted_key );
			update_option( 'aria_ai_provider', $new_provider );
			
			echo '<div class="notice notice-success"><p>' . esc_html__( 'AI configuration saved successfully!', 'aria' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid API key format. Please check your key and try again.', 'aria' ) . '</p></div>';
		}
	} elseif ( $new_provider !== $ai_provider ) {
		update_option( 'aria_ai_provider', $new_provider );
		echo '<div class="notice notice-info"><p>' . esc_html__( 'AI provider updated. Please update your API key.', 'aria' ) . '</p></div>';
	}
	
	// Update model settings
	if ( isset( $_POST['model_settings'] ) ) {
		$model_settings = $_POST['model_settings'];
		
		// Update the model in the appropriate options
		if ( $new_provider === 'openai' && isset( $model_settings['openai_model'] ) ) {
			update_option( 'aria_openai_model', sanitize_text_field( $model_settings['openai_model'] ) );
		} elseif ( $new_provider === 'gemini' && isset( $model_settings['gemini_model'] ) ) {
			update_option( 'aria_gemini_model', sanitize_text_field( $model_settings['gemini_model'] ) );
		}
		
		update_option( 'aria_ai_model_settings', $model_settings );
	}
	
	// Refresh settings
	$ai_provider = get_option( 'aria_ai_provider', 'openai' );
}
?>

<div class="wrap aria-ai-config">
	<!-- Styled with SCSS grok-inspired design system in admin.scss -->
	
	<!-- Page Header with Logo -->
	<div class="aria-page-header">
		<?php 
		// Include centralized logo component
		include ARIA_PLUGIN_PATH . 'admin/partials/components/aria-admin-logo.php';
		?>
		<div class="aria-page-info">
			<h1 class="aria-page-title"><?php esc_html_e( 'AI Configuration', 'aria' ); ?></h1>
			<p class="aria-page-description"><?php esc_html_e( 'Configure your AI provider settings and customize how Aria responds', 'aria' ); ?></p>
		</div>
	</div>

	<div class="aria-page-content">

	<form method="post" action="" class="aria-ai-config-form">
		<?php wp_nonce_field( 'aria_ai_config', 'aria_ai_config_nonce' ); ?>

		<!-- AI Configuration Cards -->
		<div class="aria-metrics-grid">
			<!-- Provider Selection Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-admin-plugins"></span>
					<h3><?php esc_html_e( 'AI Provider', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="aria-field-group">
						<select name="ai_provider" id="ai_provider" class="aria-select">
							<option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>
								<?php esc_html_e( 'OpenAI (ChatGPT)', 'aria' ); ?>
							</option>
							<option value="gemini" <?php selected( $ai_provider, 'gemini' ); ?>>
								<?php esc_html_e( 'Google Gemini', 'aria' ); ?>
							</option>
						</select>
						<p class="description"><?php esc_html_e( 'Select your preferred AI service provider.', 'aria' ); ?></p>
					</div>
				</div>
			</div>

			<!-- API Key Configuration Card -->
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-lock"></span>
					<h3><?php esc_html_e( 'API Key', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="aria-api-key-input">
						<input type="password" 
						       name="api_key" 
						       id="api_key" 
						       class="aria-input" 
						       value=""
						       placeholder="<?php echo $has_api_key ? esc_attr__( 'Enter new API key (leave blank to keep current)', 'aria' ) : esc_attr__( 'Enter your API key', 'aria' ); ?>" />
						<div class="aria-api-buttons">
							<button type="button" class="button button-secondary aria-btn-secondary" id="toggle-api-key">
								<span class="dashicons dashicons-visibility"></span>
								<?php esc_html_e( 'Show', 'aria' ); ?>
							</button>
							<button type="button" class="button button-secondary aria-btn-secondary" id="test-api-connection">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Test', 'aria' ); ?>
							</button>
							<?php if ( $has_api_key ) : ?>
							<button type="button" class="button button-secondary aria-btn-secondary" id="test-saved-api">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Test Saved', 'aria' ); ?>
							</button>
							<?php endif; ?>
						</div>
					</div>
					
					<?php if ( $has_api_key ) : ?>
					<div class="aria-current-key">
						<strong><?php esc_html_e( 'Current API Key:', 'aria' ); ?></strong> 
						<code><?php echo esc_html( $masked_key ); ?></code>
					</div>
					<?php endif; ?>
					
					<?php
					// Direct inline test when requested
					if ( isset( $_GET['test_key'] ) && $_GET['test_key'] === '1' ) {
						echo '<div class="notice notice-info" style="margin: 10px 0;">';
						echo '<p><strong>' . esc_html__( 'Testing saved API key...', 'aria' ) . '</strong></p>';
						
						try {
							// Decrypt the key
							$test_key = Aria_Security::decrypt( get_option( 'aria_ai_api_key' ) );
							
							if ( $test_key ) {
								// Create provider
								$test_provider = $ai_provider === 'gemini' 
									? new Aria_Gemini_Provider( $test_key )
									: new Aria_OpenAI_Provider( $test_key );
								
								// Test connection
								$test_result = $test_provider->test_connection();
								
								if ( $test_result ) {
									echo '<p style="color: green;">✓ ' . esc_html__( 'API connection successful!', 'aria' ) . '</p>';
								} else {
									echo '<p style="color: red;">✗ ' . esc_html__( 'API connection failed.', 'aria' ) . '</p>';
								}
							} else {
								echo '<p style="color: red;">' . esc_html__( 'Failed to decrypt API key.', 'aria' ) . '</p>';
							}
						} catch ( Exception $e ) {
							echo '<p style="color: red;">' . esc_html__( 'Error:', 'aria' ) . ' ' . esc_html( $e->getMessage() ) . '</p>';
						}
						
						echo '</div>';
					}
					?>
					
					<div id="api-key-help" class="aria-help-text">
						<!-- Dynamic help text based on provider -->
					</div>
				</div>
			</div>
		</div>


		<!-- Provider-specific settings -->
		<div id="openai-settings" class="aria-provider-settings" <?php echo $ai_provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
			<div class="aria-config-section">
				<h2><?php esc_html_e( 'OpenAI Settings', 'aria' ); ?></h2>
				
				<div class="aria-model-config-grid">
					<!-- Model Selection -->
					<div class="aria-field-group aria-model-selection">
						<label for="openai_model"><?php esc_html_e( 'Model', 'aria' ); ?></label>
						<?php
						$openai_model = isset( $model_settings['openai_model'] ) ? $model_settings['openai_model'] : 'gpt-3.5-turbo';
						$openai_models = Aria_OpenAI_Provider::get_available_models();
						?>
						<select name="model_settings[openai_model]" id="openai_model" class="aria-select">
							<?php foreach ( $openai_models as $model_key => $model_info ) : ?>
								<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $openai_model, $model_key ); ?>>
									<?php echo esc_html( $model_info['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="aria-model-details" id="openai-model-details">
							<?php foreach ( $openai_models as $model_key => $model_info ) : ?>
								<div class="model-info" data-model="<?php echo esc_attr( $model_key ); ?>" <?php echo $openai_model === $model_key ? '' : 'style="display:none;"'; ?>>
									<p class="description"><?php echo esc_html( $model_info['description'] ); ?></p>
									<?php if ( $model_info['cost_level'] === 'high' ) : ?>
										<div class="aria-cost-warning">
											<span class="dashicons dashicons-warning"></span>
											<strong><?php esc_html_e( 'Cost Warning:', 'aria' ); ?></strong> <?php echo esc_html( $model_info['cost_note'] ); ?>
										</div>
									<?php elseif ( $model_info['cost_level'] === 'medium' ) : ?>
										<p class="aria-cost-info">
											<span class="dashicons dashicons-info"></span>
											<?php echo esc_html( $model_info['cost_note'] ); ?>
										</p>
									<?php else : ?>
										<p class="aria-cost-info success">
											<span class="dashicons dashicons-yes-alt"></span>
											<?php echo esc_html( $model_info['cost_note'] ); ?>
										</p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					
					<!-- Response Settings -->
					<div class="aria-response-settings">
						<div class="aria-field-group">
							<label for="openai_max_tokens"><?php esc_html_e( 'Max Response Length', 'aria' ); ?></label>
							<?php $max_tokens = isset( $model_settings['openai_max_tokens'] ) ? $model_settings['openai_max_tokens'] : 500; ?>
							<input type="number" 
							       name="model_settings[openai_max_tokens]" 
							       id="openai_max_tokens" 
							       class="aria-input-number"
							       value="<?php echo esc_attr( $max_tokens ); ?>"
							       min="50" 
							       max="2000" 
							       step="50" />
							<p class="description"><?php esc_html_e( 'Maximum tokens (1 token ≈ 4 characters)', 'aria' ); ?></p>
						</div>
						
						<div class="aria-field-group">
							<label for="openai_temperature"><?php esc_html_e( 'Response Creativity', 'aria' ); ?></label>
							<?php $temperature = isset( $model_settings['openai_temperature'] ) ? $model_settings['openai_temperature'] : 0.7; ?>
							<div class="aria-slider-container">
								<input type="range" 
								       name="model_settings[openai_temperature]" 
								       id="openai_temperature" 
								       class="aria-slider"
								       value="<?php echo esc_attr( $temperature ); ?>"
								       min="0" 
								       max="1" 
								       step="0.1" />
								<span id="temperature-value" class="aria-slider-value"><?php echo esc_html( $temperature ); ?></span>
							</div>
							<p class="description"><?php esc_html_e( 'Lower = focused, higher = creative', 'aria' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div id="gemini-settings" class="aria-provider-settings" <?php echo $ai_provider !== 'gemini' ? 'style="display:none;"' : ''; ?>>
			<div class="aria-config-section">
				<h2><?php esc_html_e( 'Google Gemini Settings', 'aria' ); ?></h2>
				
				<div class="aria-model-config-grid">
					<!-- Model Selection -->
					<div class="aria-field-group aria-model-selection">
						<label for="gemini_model"><?php esc_html_e( 'Model', 'aria' ); ?></label>
						<?php
						$gemini_model = isset( $model_settings['gemini_model'] ) ? $model_settings['gemini_model'] : 'gemini-pro';
						$gemini_models = Aria_Gemini_Provider::get_available_models();
						?>
						<select name="model_settings[gemini_model]" id="gemini_model" class="aria-select">
							<?php foreach ( $gemini_models as $model_key => $model_info ) : ?>
								<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $gemini_model, $model_key ); ?>>
									<?php echo esc_html( $model_info['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="aria-model-details" id="gemini-model-details">
							<?php foreach ( $gemini_models as $model_key => $model_info ) : ?>
								<div class="model-info" data-model="<?php echo esc_attr( $model_key ); ?>" <?php echo $gemini_model === $model_key ? '' : 'style="display:none;"'; ?>>
									<p class="description"><?php echo esc_html( $model_info['description'] ); ?></p>
									<?php if ( $model_info['cost_level'] === 'high' ) : ?>
										<div class="aria-cost-warning">
											<span class="dashicons dashicons-warning"></span>
											<strong><?php esc_html_e( 'Cost Warning:', 'aria' ); ?></strong> <?php echo esc_html( $model_info['cost_note'] ); ?>
										</div>
									<?php elseif ( $model_info['cost_level'] === 'medium' ) : ?>
										<p class="aria-cost-info">
											<span class="dashicons dashicons-info"></span>
											<?php echo esc_html( $model_info['cost_note'] ); ?>
										</p>
									<?php else : ?>
										<p class="aria-cost-info success">
											<span class="dashicons dashicons-yes-alt"></span>
											<?php echo esc_html( $model_info['cost_note'] ); ?>
										</p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					
					<!-- Response Settings -->
					<div class="aria-response-settings">
						<div class="aria-field-group">
							<label for="gemini_max_tokens"><?php esc_html_e( 'Max Response Length', 'aria' ); ?></label>
							<?php $max_tokens = isset( $model_settings['gemini_max_tokens'] ) ? $model_settings['gemini_max_tokens'] : 500; ?>
							<input type="number" 
							       name="model_settings[gemini_max_tokens]" 
							       id="gemini_max_tokens" 
							       class="aria-input-number"
							       value="<?php echo esc_attr( $max_tokens ); ?>"
							       min="50" 
							       max="2000" 
							       step="50" />
							<p class="description"><?php esc_html_e( 'Maximum tokens for responses', 'aria' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Usage Statistics Cards -->
		<?php
		$current_month = date( 'Y_m' );
		$monthly_usage = get_option( 'aria_ai_usage_' . $current_month, 0 );
		$usage_history = get_option( 'aria_ai_usage_history', array() );
		$recent_usage = array_slice( $usage_history, -7 );
		?>
		
		<div class="aria-metrics-grid">
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-chart-area"></span>
					<h3><?php esc_html_e( 'Usage Statistics', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="metric-item large">
						<span class="item-value primary"><?php echo number_format( $monthly_usage ); ?></span>
						<span class="item-label"><?php esc_html_e( 'Tokens This Month', 'aria' ); ?></span>
					</div>
					<?php if ( $ai_provider === 'openai' && isset( $openai_model ) ) : ?>
					<div class="metric-item">
						<span class="item-value secondary">$<?php echo number_format( Aria_OpenAI_Provider::calculate_cost( $monthly_usage, $openai_model ), 2 ); ?></span>
						<span class="item-label"><?php esc_html_e( 'Estimated Cost', 'aria' ); ?></span>
					</div>
					<?php endif; ?>
				</div>
			</div>
			
			<?php if ( ! empty( $recent_usage ) ) : ?>
			<div class="aria-metric-card">
				<div class="metric-header">
					<span class="metric-icon dashicons dashicons-clock"></span>
					<h3><?php esc_html_e( 'Recent Activity', 'aria' ); ?></h3>
				</div>
				<div class="metric-content">
					<div class="aria-recent-activity">
						<?php foreach ( array_slice( array_reverse( $recent_usage ), 0, 3 ) as $usage ) : ?>
						<div class="activity-item">
							<span class="activity-time"><?php echo esc_html( human_time_diff( strtotime( $usage['timestamp'] ) ) . ' ' . __( 'ago', 'aria' ) ); ?></span>
							<span class="activity-tokens"><?php echo esc_html( $usage['tokens_used'] ); ?> tokens</span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<p class="submit">
			<!-- Styled with SCSS aria-button-primary mixin in admin.scss -->
			<button type="submit" class="button button-primary aria-btn-primary">
				<?php esc_html_e( 'Save Configuration', 'aria' ); ?>
			</button>
		</p>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Provider selection
	$('#ai_provider').on('change', function() {
		$('.aria-provider-settings').hide();
		$('#' + $(this).val() + '-settings').show();
		updateApiKeyHelp($(this).val());
	});

	// Update help text
	function updateApiKeyHelp(provider) {
		var helpText = '';
		if (provider === 'openai') {
			helpText = '<p class="description"><?php echo esc_js( __( 'Get your API key from', 'aria' ) ); ?> <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>. <?php echo esc_js( __( 'Your key should start with "sk-".', 'aria' ) ); ?></p>';
		} else if (provider === 'gemini') {
			helpText = '<p class="description"><?php echo esc_js( __( 'Get your API key from', 'aria' ) ); ?> <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.</p>';
		}
		$('#api-key-help').html(helpText);
	}

	// Initialize
	updateApiKeyHelp($('#ai_provider').val());

	// Toggle API key visibility
	$('#toggle-api-key').on('click', function() {
		var input = $('#api_key');
		var button = $(this);
		
		if (input.attr('type') === 'password') {
			input.attr('type', 'text');
			button.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
			button.contents().last().replaceWith('<?php echo esc_js( __( 'Hide', 'aria' ) ); ?>');
		} else {
			input.attr('type', 'password');
			button.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
			button.contents().last().replaceWith('<?php echo esc_js( __( 'Show', 'aria' ) ); ?>');
		}
	});

	// Temperature slider
	$('#openai_temperature').on('input', function() {
		$('#temperature-value').text($(this).val());
	});

	// Model selection change handlers
	$('#openai_model').on('change', function() {
		var selectedModel = $(this).val();
		$('#openai-model-details .model-info').hide();
		$('#openai-model-details .model-info[data-model="' + selectedModel + '"]').show();
	});

	$('#gemini_model').on('change', function() {
		var selectedModel = $(this).val();
		$('#gemini-model-details .model-info').hide();
		$('#gemini-model-details .model-info[data-model="' + selectedModel + '"]').show();
	});

	// Test API connection
	$('#test-api-connection').on('click', function() {
		var button = $(this);
		var provider = $('#ai_provider').val();
		var apiKey = $('#api_key').val();
		
		if (!apiKey || apiKey.indexOf('*') !== -1) {
			alert('<?php echo esc_js( __( 'Please enter a valid API key.', 'aria' ) ); ?>');
			return;
		}
		
		button.prop('disabled', true);
		button.find('.dashicons').addClass('spin');
		
		$.post(ajaxurl, {
			action: 'aria_test_api',
			provider: provider,
			api_key: apiKey,
			nonce: ariaAdmin.nonce
		}, function(response) {
			button.prop('disabled', false);
			button.find('.dashicons').removeClass('spin');
			
			if (response.success) {
				alert(response.data.message);
			} else {
				alert(response.data.message || '<?php echo esc_js( __( 'Connection test failed.', 'aria' ) ); ?>');
			}
		});
	});
});
</script>

<style>
/* Beautiful AI Config Design */
.aria-ai-config-wrap {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	min-height: 100vh;
	margin: 0 -20px 0 -22px;
	padding: 30px;
	position: relative;
}

.aria-ai-config-wrap::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: radial-gradient(circle at 30% 20%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
	            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
	            radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.15) 0%, transparent 50%);
	animation: gradientShift 8s ease-in-out infinite;
}

@keyframes gradientShift {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.8; }
}

.aria-ai-config-wrap h1 {
	color: white;
	margin-bottom: 8px;
	font-size: 32px;
	font-weight: 600;
	text-shadow: 0 2px 4px rgba(0,0,0,0.1);
	position: relative;
	z-index: 2;
}

.aria-ai-config-wrap .description {
	color: rgba(255, 255, 255, 0.9);
	font-size: 16px;
	margin-bottom: 30px;
	position: relative;
	z-index: 2;
}

/* Form Container */
.aria-ai-config-form {
	max-width: none;
	position: relative;
	z-index: 2;
}

.aria-config-section {
	background: rgba(255, 255, 255, 0.95);
	padding: 30px;
	margin-bottom: 30px;
	border: 1px solid rgba(255, 255, 255, 0.3);
	border-radius: 16px;
	box-shadow: 0 8px 32px rgba(0,0,0,0.1);
	backdrop-filter: blur(20px);
	-webkit-backdrop-filter: blur(20px);
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aria-config-section:hover {
	transform: translateY(-4px);
	box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.aria-config-section h2 {
	margin-top: 0;
	margin-bottom: 20px;
	color: #2c3e50;
	font-size: 20px;
	font-weight: 600;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	border-bottom: 2px solid #e2e8f0;
	padding-bottom: 15px;
}

/* Grid Layout */
.aria-config-grid {
	display: grid;
	grid-template-columns: 1fr 2fr;
	gap: 30px;
	align-items: start;
}

.aria-model-config-grid {
	display: grid;
	grid-template-columns: 2fr 1fr;
	gap: 30px;
	align-items: start;
}

/* Field Groups */
.aria-field-group {
	margin-bottom: 20px;
}

.aria-field-group label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	font-size: 14px;
	color: #34495e;
}

.aria-field-group .description {
	margin-top: 8px;
	font-size: 13px;
	color: #7f8c8d;
	line-height: 1.5;
}

/* Form Controls */
.aria-select,
.aria-input,
.aria-input-number {
	width: 100%;
	padding: 12px 16px;
	border: 2px solid rgba(102, 126, 234, 0.2);
	border-radius: 10px;
	background: rgba(255, 255, 255, 0.9);
	color: #2c3e50;
	font-size: 14px;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.aria-select:focus,
.aria-input:focus,
.aria-input-number:focus {
	border-color: #667eea;
	outline: none;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15), 0 4px 12px rgba(0,0,0,0.1);
	transform: translateY(-1px);
}

.aria-input-number {
	width: 150px;
}

/* API Key Section */
.aria-api-key-section {
	position: relative;
}

.aria-api-key-input {
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.aria-api-key-input .aria-input {
	margin-bottom: 0;
}

.aria-api-buttons {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
}

/* Button styles moved to SCSS - see admin.scss */

.aria-current-key {
	margin-top: 15px;
	padding: 15px;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	border: 2px solid rgba(102, 126, 234, 0.2);
	border-radius: 10px;
	font-size: 14px;
}

.aria-current-key code {
	background: rgba(102, 126, 234, 0.1);
	padding: 4px 8px;
	border-radius: 6px;
	font-family: 'Courier New', monospace;
}

/* Slider Controls */
.aria-slider-container {
	display: flex;
	align-items: center;
	gap: 15px;
	padding: 15px;
	background: rgba(255, 255, 255, 0.8);
	border-radius: 10px;
	border: 1px solid rgba(102, 126, 234, 0.2);
}

.aria-slider {
	flex: 1;
	height: 8px;
	-webkit-appearance: none;
	appearance: none;
	background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
	border-radius: 4px;
	outline: none;
}

.aria-slider::-webkit-slider-thumb {
	-webkit-appearance: none;
	appearance: none;
	width: 20px;
	height: 20px;
	background: linear-gradient(135deg, #667eea, #764ba2);
	border-radius: 50%;
	cursor: pointer;
	box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.aria-slider::-moz-range-thumb {
	width: 20px;
	height: 20px;
	background: linear-gradient(135deg, #667eea, #764ba2);
	border-radius: 50%;
	border: none;
	cursor: pointer;
	box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.aria-slider-value {
	min-width: 45px;
	font-weight: 700;
	color: #667eea;
	text-align: center;
	font-size: 16px;
	background: rgba(102, 126, 234, 0.1);
	padding: 8px 12px;
	border-radius: 6px;
}

/* Model Details */
.aria-model-selection {
	position: relative;
}

.aria-model-details {
	margin-top: 20px;
}

.model-info {
	padding: 20px;
	border: 2px solid rgba(102, 126, 234, 0.2);
	border-radius: 10px;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	transition: all 0.3s ease;
}

.model-info:hover {
	border-color: #667eea;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.model-info .description {
	margin: 0 0 12px 0;
	font-size: 14px;
	color: #34495e;
	line-height: 1.6;
}

/* Cost Warnings */
.aria-cost-warning {
	background: linear-gradient(135deg, #fed7d7, #feb2b2);
	border: 2px solid #fc8181;
	border-radius: 10px;
	padding: 15px;
	margin-top: 15px;
	color: #c53030;
	font-size: 14px;
	font-weight: 500;
}

.aria-cost-warning .dashicons {
	color: #e53e3e;
	margin-right: 8px;
	vertical-align: text-top;
}

.aria-cost-info {
	background: linear-gradient(135deg, #bee3f8, #90cdf4);
	border: 2px solid #63b3ed;
	border-radius: 10px;
	padding: 15px;
	margin-top: 15px;
	color: #2b6cb0;
	font-size: 14px;
	font-weight: 500;
}

.aria-cost-info.success {
	background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
	border: 2px solid #68d391;
	color: #2f855a;
}

.aria-cost-info .dashicons {
	margin-right: 8px;
	vertical-align: text-top;
}

/* Usage Statistics */
.aria-usage-overview {
	display: flex;
	flex-direction: column;
	gap: 25px;
}

.aria-usage-stats {
	display: flex;
	gap: 20px;
}

.aria-stat-box {
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	padding: 25px;
	border-radius: 12px;
	border: 2px solid rgba(102, 126, 234, 0.2);
	text-align: center;
	min-width: 160px;
	transition: all 0.3s ease;
}

.aria-stat-box:hover {
	border-color: #667eea;
	transform: translateY(-4px);
	box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
}

.aria-stat-box h3 {
	margin: 0 0 10px 0;
	font-size: 28px;
	background: linear-gradient(135deg, #667eea, #764ba2);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	font-weight: 700;
}

.aria-stat-box p {
	margin: 0;
	color: #64748b;
	font-size: 14px;
	font-weight: 500;
}

.aria-recent-usage h3 {
	margin-bottom: 20px;
	font-size: 18px;
	color: #2c3e50;
	font-weight: 600;
}

.aria-recent-usage table {
	margin-top: 0;
	background: rgba(255, 255, 255, 0.95);
	border-radius: 10px;
	overflow: hidden;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
	border: none;
}

.aria-recent-usage table th {
	background: #f8f9fa;
	color: #2c3e50;
	padding: 15px;
	font-weight: 600;
	border: none;
	border-bottom: 2px solid #e2e8f0;
	font-size: 14px;
}

.aria-recent-usage table td {
	padding: 15px;
	border-bottom: 1px solid #f1f5f9;
	vertical-align: middle;
}

.aria-recent-usage table tr:hover {
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
}

/* Animations */
.dashicons.spin {
	animation: spin 2s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

/* Help Text */
.aria-help-text {
	margin-top: 15px;
	padding: 15px;
	background: linear-gradient(145deg, #f8f9ff, #f1f5f9);
	border-left: 4px solid #667eea;
	border-radius: 0 10px 10px 0;
}

.aria-help-text p {
	margin: 0;
	font-size: 14px;
	color: #34495e;
	line-height: 1.6;
}

.aria-help-text a {
	color: #667eea;
	text-decoration: none;
	font-weight: 500;
}

.aria-help-text a:hover {
	color: #764ba2;
	text-decoration: underline;
}

/* Submit Section - removed container styling for consistency with other admin pages */

/* Responsive Design */
@media (max-width: 1200px) {
	.aria-ai-config-wrap {
		margin: 0 -10px;
		padding: 20px;
	}
	
	.aria-config-grid,
	.aria-model-config-grid {
		grid-template-columns: 1fr;
		gap: 20px;
	}
	
	.aria-usage-stats {
		flex-direction: column;
		align-items: stretch;
	}
	
	.aria-stat-box {
		min-width: auto;
	}
}

@media (max-width: 782px) {
	.aria-ai-config-wrap {
		padding: 15px;
		margin: 0 -20px;
	}
	
	.aria-ai-config-wrap h1 {
		font-size: 24px;
	}
	
	.aria-api-buttons {
		flex-direction: column;
		align-items: stretch;
	}
	
	.aria-slider-container {
		flex-direction: column;
		align-items: stretch;
		gap: 15px;
	}
	
	.aria-slider-value {
		text-align: center;
		min-width: auto;
	}
}
</style>
	</div>
</div>