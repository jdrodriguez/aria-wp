<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * The core plugin class.
 */
class Aria_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var    Aria_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The background processor instance.
	 *
	 * @var    Aria_Background_Processor    $background_processor    Handles background processing tasks.
	 */
	protected $background_processor;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version     = ARIA_VERSION;
		$this->plugin_name = 'aria';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->init_additional_services();
		
		// Run database updates
		add_action( 'init', array( $this, 'check_db_updates' ) );
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once ARIA_PLUGIN_PATH . 'admin/class-aria-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once ARIA_PLUGIN_PATH . 'public/class-aria-public.php';

		/**
		 * Shared utilities.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-logger.php';

		/**
		 * The class responsible for handling AJAX requests.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ajax-handler.php';

		/**
		 * The class responsible for database operations.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-database.php';

		/**
		 * The class responsible for personality management.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-personality.php';

		/**
		 * The class responsible for security operations.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-security.php';

		/**
		 * The class responsible for the learning system.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-learning.php';

		/**
		 * AI provider base class and interface.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-ai-provider.php';

		/**
		 * Content vectorization classes for WordPress content integration.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-vectorizer.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-filter.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-content-hooks.php';

		/**
		 * Vector system classes for enhanced search capabilities.
		 */
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-vector-engine.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-knowledge-processor.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-query-handler.php';
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-cache-manager.php';
		// Background processor loaded on demand to prevent early initialization

		$this->loader = new Aria_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new Aria_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {
		// Defer admin class instantiation until admin hooks actually fire
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'init_admin_class' );
		$this->loader->add_action( 'admin_menu', $this, 'init_admin_class' );
		$this->loader->add_action( 'admin_notices', $this, 'init_admin_class' );
		$this->loader->add_filter( 'plugin_action_links_' . ARIA_PLUGIN_BASENAME, $this, 'init_admin_class_filter' );
	}
	
	/**
	 * Initialize admin class when needed.
	 */
	public function init_admin_class() {
		static $admin_instance = null;
		
		if ( ! $admin_instance ) {
			$admin_instance = new Aria_Admin( $this->get_plugin_name(), $this->get_version() );
		}
		
		// Call the appropriate method based on current action
		$current_action = current_action();
		switch ( $current_action ) {
			case 'admin_enqueue_scripts':
				$admin_instance->enqueue_styles();
				$admin_instance->enqueue_scripts();
				break;
			case 'admin_menu':
				$admin_instance->add_admin_menu();
				break;
			case 'admin_notices':
				$admin_instance->display_admin_notices();
				break;
		}
	}
	
	/**
	 * Initialize admin class for filter hooks.
	 */
	public function init_admin_class_filter( $links ) {
		static $admin_instance = null;
		
		if ( ! $admin_instance ) {
			$admin_instance = new Aria_Admin( $this->get_plugin_name(), $this->get_version() );
		}
		
		return $admin_instance->add_action_links( $links );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {
		// Defer public class instantiation until hooks actually fire
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'init_public_class' );
		$this->loader->add_action( 'wp_footer', $this, 'init_public_class' );
		
		// Register shortcode immediately but defer handler instantiation
		add_shortcode( 'aria_chat', array( $this, 'handle_chat_shortcode' ) );

		// Register AJAX handlers with deferred instantiation
		$this->register_ajax_handlers();
	}
	
	/**
	 * Initialize public class when needed.
	 */
	public function init_public_class() {
		static $public_instance = null;
		
		if ( ! $public_instance ) {
			$public_instance = new Aria_Public( $this->get_plugin_name(), $this->get_version() );
		}
		
		// Call the appropriate method based on current action
		$current_action = current_action();
		switch ( $current_action ) {
			case 'wp_enqueue_scripts':
				$public_instance->enqueue_styles();
				$public_instance->enqueue_scripts();
				break;
			case 'wp_footer':
				$public_instance->render_chat_widget();
				break;
		}
	}
	
	/**
	 * Handle chat shortcode with deferred instantiation.
	 */
	public function handle_chat_shortcode( $atts ) {
		static $public_instance = null;
		
		if ( ! $public_instance ) {
			$public_instance = new Aria_Public( $this->get_plugin_name(), $this->get_version() );
		}
		
		return $public_instance->render_chat_shortcode( $atts );
	}
	
	/**
	 * Register AJAX handlers with deferred instantiation.
	 */
	private function register_ajax_handlers() {
		// Get list of all AJAX actions
		$ajax_actions = array(
			// Public AJAX actions
			'aria_start_conversation',
			'aria_send_message', 
			'aria_get_conversation',
			'aria_track_event',
			'aria_submit_feedback',
			// Admin AJAX actions
			'aria_save_knowledge',
			'aria_get_knowledge_data',
			'aria_delete_knowledge_entry',
			'aria_test_api',
			'aria_test_saved_api',
			'aria_test_notification',
			'aria_generate_knowledge_entry',
			// Vector system AJAX actions
			'aria_process_knowledge_entry',
			'aria_get_vector_stats',
			'aria_test_vector_system',
			'aria_migrate_vector_system',
			'aria_retry_failed_processing',
			// Debug AJAX actions
			'aria_debug_vector_system',
			'aria_test_process_entry',
			'aria_process_all_stuck_entries',
			'aria_toggle_immediate_processing',
			'aria_get_advanced_settings',
			'aria_save_advanced_settings',
				'aria_export_conversations_csv',
				'aria_get_conversations_data',
				// Conversation management
				'aria_update_conversation_status',
			'aria_email_transcript',
			'aria_add_conversation_note',
			// Content indexing
				'aria_get_content_indexing_data',
				'aria_reindex_all_content',
				'aria_test_content_search',
				'aria_clear_search_cache',
				'aria_save_content_settings',
				'aria_save_content_indexing_settings',
				'aria_index_single_item',
				'aria_toggle_content_indexing',
			'aria_debug_vectors',
			'aria_get_dashboard_data',
			// AI Config React AJAX actions
			'aria_get_ai_config',
			'aria_save_ai_config',
			'aria_get_usage_stats',
			'aria_get_general_settings',
			'aria_save_general_settings',
			'aria_get_design_settings',
			'aria_save_design_settings',
			'aria_get_notification_settings',
			'aria_save_notification_settings',
			'aria_get_privacy_settings',
			'aria_save_privacy_settings',
			'aria_get_license_settings',
			'aria_activate_license'
		);
		
		// Register handlers for both logged in and non-logged in users
		foreach ( $ajax_actions as $action ) {
			$this->loader->add_action( 'wp_ajax_' . $action, $this, 'handle_ajax_request' );
			$this->loader->add_action( 'wp_ajax_nopriv_' . $action, $this, 'handle_ajax_request' );
		}
	}
	
	/**
	 * Handle AJAX requests with deferred instantiation.
	 */
	public function handle_ajax_request() {
		static $ajax_handler = null;
		
		if ( ! $ajax_handler ) {
			$ajax_handler = new Aria_Ajax_Handler();
		}
		
		// Get the action from the current hook
		$current_action = current_action();
		$action_name = str_replace( array( 'wp_ajax_', 'wp_ajax_nopriv_' ), '', $current_action );
		
		// Map action to method name
		$method_name = 'handle_' . $action_name;
		
		// Call the appropriate method if it exists, with legacy fallback
		if ( method_exists( $ajax_handler, $method_name ) ) {
			$ajax_handler->$method_name();
			return;
		}

		// Legacy handlers dropped the `aria_` prefix
		$legacy_method = str_replace( 'handle_aria_', 'handle_', $method_name );
		if ( method_exists( $ajax_handler, $legacy_method ) ) {
			$ajax_handler->$legacy_method();
		}
	}

	/**
	 * Initialize remaining hooks and services.
	 */
	private function init_additional_services() {
		// Schedule daily summary email
		if ( ! wp_next_scheduled( 'aria_daily_summary_email' ) ) {
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'aria_daily_summary_email' );
		}
		$this->loader->add_action( 'aria_daily_summary_email', $this, 'send_daily_summary_email' );
		
		// Initialize content vectorization hooks (defer this too)
		add_action( 'init', array( $this, 'init_content_hooks' ), 5 );

		// Initialize vector system background processing after WordPress is loaded
		add_action( 'init', array( $this, 'init_background_processor' ) );
		
		// Schedule cache cleanup with backoff if scheduling fails (prevents repeated log spam).
		$cache_schedule_failed_at = (int) get_option( 'aria_cleanup_cache_schedule_failed', 0 );
		$retry_window_passed       = ( time() - $cache_schedule_failed_at ) > HOUR_IN_SECONDS;
		if ( ! wp_next_scheduled( 'aria_cleanup_cache' ) && ( 0 === $cache_schedule_failed_at || $retry_window_passed ) ) {
			$scheduled = wp_schedule_event( time(), 'hourly', 'aria_cleanup_cache' );
			if ( false === $scheduled ) {
				update_option( 'aria_cleanup_cache_schedule_failed', time() );
			} else {
				delete_option( 'aria_cleanup_cache_schedule_failed' );
			}
		}
		$this->loader->add_action( 'aria_cleanup_cache', $this, 'cleanup_vector_caches' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
	
	/**
	 * Send daily summary email.
	 */
	public function send_daily_summary_email() {
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-email-handler.php';
		$email_handler = new Aria_Email_Handler();
		$email_handler->send_daily_summary();
	}
	
	/**
	 * Check and run database updates.
	 */
	public function check_db_updates() {
		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-db-updater.php';
		Aria_DB_Updater::update();
	}
	
	/**
	 * Cleanup vector caches.
	 */
	public function cleanup_vector_caches() {
		try {
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-cache-manager.php';
			require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
			
			// Cleanup expired cache entries
			$cache_manager = new Aria_Cache_Manager();
			$cache_manager->cleanup_expired_cache();
			
			// Cleanup stuck processing tasks only if not in admin
			if ( ! is_admin() ) {
				require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
				$processor = new Aria_Background_Processor();
				$processor->cleanup_processing_tasks();
			}
			
		} catch ( Exception $e ) {
			Aria_Logger::error( 'cache cleanup error: ' . $e->getMessage() );
		}
	}
	
	/**
	 * Initialize content hooks after WordPress is loaded.
	 */
	public function init_content_hooks() {
		new Aria_Content_Hooks();
	}
	
	/**
	 * Initialize background processor after WordPress is loaded.
	 */
	public function init_background_processor() {
		// Skip initialization if we're on admin pages to avoid authentication issues
		if ( is_admin() ) {
			return;
		}
		
		// Only initialize if WordPress functions are available and we're not in admin context
		if ( function_exists( 'wp_salt' ) && ! is_admin() ) {
			try {
				// Load the background processor class only when needed
				require_once ARIA_PLUGIN_PATH . 'includes/class-aria-background-processor.php';
				$this->background_processor = Aria_Background_Processor::instance();
			} catch ( Exception $e ) {
				Aria_Logger::error( 'Failed to initialize background processor: ' . $e->getMessage() );
			}
		}
	}
}
