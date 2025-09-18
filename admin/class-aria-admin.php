<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Aria
 * @subpackage Aria/admin
 */

/**
 * The admin-specific functionality of the plugin.
 */
class Aria_Admin {

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
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		// Only load on Aria admin pages with proper null safety
		$screen = get_current_screen();
		$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
		
		if ( $screen_id && str_contains( $screen_id, 'aria' ) ) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				ARIA_PLUGIN_URL . 'dist/admin-style.css',
				array(),
				$this->version,
				'all'
			);

			// WordPress color picker
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		// Only load on Aria admin pages with proper null safety
		$screen = get_current_screen();
		
		// Ensure screen and screen->id are not null before using string functions
		$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
		$page_param = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		// Load on Aria admin pages with null-safe string operations
		$should_load = ( $screen_id && str_contains( $screen_id, 'aria' ) ) || 
					   ( $page_param && str_contains( $page_param, 'aria' ) );
		
		if ( $should_load ) {
			// WordPress media uploader
			wp_enqueue_media();

			// Color picker
			wp_enqueue_script( 'wp-color-picker' );

			// Chart.js for analytics
			wp_enqueue_script(
				$this->plugin_name . '-chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
				array(),
				'4.4.0',
				true
			);

			// Unified React-based admin script (keep both names for compatibility)
			wp_enqueue_script(
				$this->plugin_name . '-admin',
				ARIA_PLUGIN_URL . 'dist/admin.js',
				array( 'wp-element', 'wp-components', 'wp-i18n', 'jquery', 'wp-color-picker' ),
				$this->version,
				true
			);

			// Also register as admin-react for backward compatibility with templates
			wp_enqueue_script(
				$this->plugin_name . '-admin-react',
				ARIA_PLUGIN_URL . 'dist/admin-react.js',
				array( 'wp-element', 'wp-components', 'wp-i18n' ),
				$this->version,
				true
			);

			// Localize script data for both admin scripts
			$localized_data = array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'adminUrl'    => admin_url(),
				'nonce'       => wp_create_nonce( 'aria_admin_nonce' ),
				'strings'     => array(
					'confirmDelete'    => __( 'Are you sure you want to delete this item?', 'aria' ),
					'saving'           => __( 'Saving...', 'aria' ),
					'saved'            => __( 'Saved successfully!', 'aria' ),
					'error'            => __( 'An error occurred. Please try again.', 'aria' ),
					'testingApi'       => __( 'Testing connection...', 'aria' ),
					'apiConnected'     => __( 'API connected successfully!', 'aria' ),
					'apiError'         => __( 'API connection failed. Please check your credentials.', 'aria' ),
					'enterValidKey'    => __( 'Please enter a valid API key.', 'aria' ),
				),
			);

			// Localize both admin scripts
			wp_localize_script(
				$this->plugin_name . '-admin',
				'ariaAdmin',
				$localized_data
			);
			wp_localize_script(
				$this->plugin_name . '-admin-react',
				'ariaAdmin',
				$localized_data
			);
		}
	}
	

	/**
	 * Add admin menu items.
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'Aria Dashboard', 'aria' ),
			__( 'Aria', 'aria' ),
			'manage_options',
			'aria',
			array( $this, 'display_dashboard_page' ),
			'dashicons-format-chat',
			26
		);

		// Dashboard submenu (rename the first item)
		add_submenu_page(
			'aria',
			__( 'Aria Dashboard', 'aria' ),
			__( 'Dashboard', 'aria' ),
			'manage_options',
			'aria',
			array( $this, 'display_dashboard_page' )
		);

		// Personality submenu
		add_submenu_page(
			'aria',
			__( 'Aria\'s Personality', 'aria' ),
			__( 'Personality', 'aria' ),
			'manage_options',
			'aria-personality',
			array( $this, 'display_personality_page' )
		);

		// Knowledge Base submenu
		add_submenu_page(
			'aria',
			__( 'Teach Aria', 'aria' ),
			__( 'Knowledge Base', 'aria' ),
			'manage_options',
			'aria-knowledge',
			array( $this, 'display_knowledge_page' )
		);

		// Content Indexing submenu
		add_submenu_page(
			'aria',
			__( 'Content Indexing', 'aria' ),
			__( 'Content Indexing', 'aria' ),
			'manage_options',
			'aria-content-indexing',
			array( $this, 'display_content_indexing_page' )
		);

		// AI Configuration submenu
		add_submenu_page(
			'aria',
			__( 'AI Configuration', 'aria' ),
			__( 'AI Setup', 'aria' ),
			'manage_options',
			'aria-ai-config',
			array( $this, 'display_ai_config_page' )
		);

		// Design submenu
		add_submenu_page(
			'aria',
			__( 'Aria\'s Look', 'aria' ),
			__( 'Design', 'aria' ),
			'manage_options',
			'aria-design',
			array( $this, 'display_design_page' )
		);

		// Conversations submenu
		add_submenu_page(
			'aria',
			__( 'Conversations', 'aria' ),
			__( 'Conversations', 'aria' ),
			'manage_options',
			'aria-conversations',
			array( $this, 'display_conversations_page' )
		);

		// Settings submenu
		add_submenu_page(
			'aria',
			__( 'Aria Settings', 'aria' ),
			__( 'Settings', 'aria' ),
			'manage_options',
			'aria-settings',
			array( $this, 'display_settings_page' )
		);

		// Knowledge Entry - Hidden page (not in menu) - PHP 8.1+ compatible
		add_submenu_page(
			'', // Empty string instead of null for PHP 8.1+ compatibility
			__( 'Knowledge Entry', 'aria' ),
			__( 'Knowledge Entry', 'aria' ),
			'manage_options',
			'aria-knowledge-entry',
			array( $this, 'display_knowledge_entry_page' )
		);
	}

	/**
	 * Display the dashboard page.
	 */
	public function display_dashboard_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-dashboard-react.php';
	}

	/**
	 * Display the personality configuration page.
	 */
	public function display_personality_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-personality-react.php';
	}

	/**
	 * Display the knowledge base page.
	 */
	public function display_knowledge_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-knowledge-react.php';
	}

	/**
	 * Display the knowledge entry page (add/edit).
	 */
	public function display_knowledge_entry_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-knowledge-entry-react.php';
	}

	/**
	 * Display the content indexing page.
	 */
	public function display_content_indexing_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-content-indexing-react.php';
	}

	/**
	 * Display the AI configuration page.
	 */
	public function display_ai_config_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-ai-config-react.php';
	}

	/**
	 * Display the design customization page.
	 */
	public function display_design_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-design-react.php';
	}

	/**
	 * Display the conversations page.
	 */
	public function display_conversations_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-conversations-react.php';
	}

	/**
	 * Display the settings page.
	 */
	public function display_settings_page() {
		require_once ARIA_PLUGIN_PATH . 'admin/partials/aria-settings-react.php';
	}



	/**
	 * Display admin notices.
	 */
	public function display_admin_notices() {
		// Check for activation redirect
		if ( get_transient( 'aria_activation_redirect' ) ) {
			delete_transient( 'aria_activation_redirect' );
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=aria' ) );
				exit;
			}
		}

		// Trial expiration notice
		$license_status = $this->get_license_status();
		if ( 'trial' === $license_status['status'] && $license_status['days_remaining'] <= 7 ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %d: days remaining */
						esc_html__( 'Your Aria trial expires in %d days. Upgrade now to keep Aria helping your visitors!', 'aria' ),
						intval( $license_status['days_remaining'] )
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-settings#license' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
						<?php esc_html_e( 'Upgrade Now', 'aria' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		// API key not configured notice
		if ( ! get_option( 'aria_ai_api_key' ) ) {
			$screen = get_current_screen();
			$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
			
			if ( $screen_id && str_contains( $screen_id, 'aria' ) && 'aria_page_aria-ai-config' !== $screen_id ) {
				?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'Aria needs an AI provider to start conversations. Please configure your API settings.', 'aria' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=aria-ai-config' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
							<?php esc_html_e( 'Configure AI', 'aria' ); ?>
						</a>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=aria' ),
			__( 'Settings', 'aria' )
		);
		array_unshift( $links, $settings_link );

		// Add "Upgrade" link if in trial
		$license_status = $this->get_license_status();
		if ( 'trial' === $license_status['status'] ) {
			$upgrade_link = sprintf(
				'<a href="%s" style="color: #00a32a; font-weight: bold;">%s</a>',
				'https://ariaplugin.com/pricing',
				__( 'Upgrade', 'aria' )
			);
			$links[] = $upgrade_link;
		}

		return $links;
	}

	/**
	 * Get current license status.
	 *
	 * @return array License status information.
	 */
	private function get_license_status() {
		global $wpdb;
		$table_license = $wpdb->prefix . 'aria_license';
		
		$license = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_license WHERE site_url = %s",
				get_site_url()
			),
			ARRAY_A
		);

		if ( ! $license ) {
			return array(
				'status'         => 'none',
				'days_remaining' => 0,
			);
		}

		if ( 'trial' === $license['license_status'] ) {
			$trial_start    = strtotime( $license['trial_started'] );
			$days_elapsed   = ( time() - $trial_start ) / DAY_IN_SECONDS;
			$days_remaining = max( 0, 30 - $days_elapsed );

			return array(
				'status'         => 'trial',
				'days_remaining' => floor( $days_remaining ),
			);
		}

		return array(
			'status'         => $license['license_status'],
			'days_remaining' => 0,
		);
	}

	/**
	 * WordPress Core Compatibility Layer - Sanitize admin globals early
	 * Prevents null parameters from reaching WordPress core functions
	 */
	public function sanitize_admin_globals() {
		if ( ! is_admin() ) {
			return;
		}
		
		// Ensure critical $_GET parameters that WordPress core expects are never null
		$safe_defaults = array(
			'page'   => '',
			'action' => '',
			'id'     => '0',
		);
		
		foreach ( $safe_defaults as $key => $default ) {
			if ( ! isset( $_GET[ $key ] ) || null === $_GET[ $key ] ) {
				$_GET[ $key ] = $default;
			}
		}
	}

	/**
	 * Validate knowledge entry request parameters early
	 * Comprehensive validation to prevent WordPress core warnings
	 */
	public function validate_knowledge_entry_request() {
		// Only process if this is our knowledge entry page
		if ( ! isset( $_GET['page'] ) || 'aria-knowledge-entry' !== $_GET['page'] ) {
			return;
		}
		
		// Ensure all parameters have safe non-null values before WordPress processes them
		$_GET['action'] = isset( $_GET['action'] ) && ! empty( $_GET['action'] ) 
			? sanitize_text_field( wp_unslash( $_GET['action'] ) ) 
			: 'add';
		
		$_GET['id'] = isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) 
			? sanitize_text_field( wp_unslash( $_GET['id'] ) ) 
			: '0';
		
		// Validate action parameter against allowed values
		$valid_actions = array( 'add', 'edit' );
		if ( ! in_array( $_GET['action'], $valid_actions, true ) ) {
			$_GET['action'] = 'add';
		}
		
		// Validate edit mode has valid entry ID
		if ( 'edit' === $_GET['action'] && intval( $_GET['id'] ) <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=aria-knowledge' ) );
			exit;
		}
	}
}