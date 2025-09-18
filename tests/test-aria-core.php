<?php
/**
 * Core plugin integration smoke tests.
 */

class Tests_Aria_Core extends WP_UnitTestCase {

	/**
	 * Core plugin instance used for assertions.
	 *
	 * @var Aria_Core
	 */
	private $core;

	/**
	 * Prepare a fresh core instance for each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->core = new Aria_Core();
		$this->core->run();

		require_once ARIA_PLUGIN_PATH . 'includes/class-aria-deactivator.php';
	}

	/**
	 * Plugin constants should always be defined by the bootstrap file.
	 */
	public function test_constants_defined() {
		$this->assertTrue( defined( 'ARIA_VERSION' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_PATH' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_BASENAME' ) );
	}

	/**
	 * Ensure the core class reports the packaged version string.
	 */
	public function test_plugin_version_matches_core_version() {
		$this->assertSame( ARIA_VERSION, $this->core->get_version() );
	}

	/**
	 * The primary service classes should all be available.
	 */
	public function test_service_classes_exist() {
		$this->assertTrue( class_exists( 'Aria_Core' ) );
		$this->assertTrue( class_exists( 'Aria_Admin' ) );
		$this->assertTrue( class_exists( 'Aria_Public' ) );
		$this->assertTrue( class_exists( 'Aria_Database' ) );
	}

	/**
	 * Activation should provision all database tables, including new vector stores.
	 */
	public function test_activation_creates_all_tables() {
		global $wpdb;

		Aria_Activator::activate();

		$tables = array(
			$wpdb->prefix . 'aria_knowledge_entries',
			$wpdb->prefix . 'aria_knowledge_base',
			$wpdb->prefix . 'aria_knowledge_chunks',
			$wpdb->prefix . 'aria_search_cache',
			$wpdb->prefix . 'aria_search_analytics',
			$wpdb->prefix . 'aria_conversations',
			$wpdb->prefix . 'aria_personality_settings',
			$wpdb->prefix . 'aria_learning_data',
			$wpdb->prefix . 'aria_content_vectors',
			$wpdb->prefix . 'aria_license',
		);

		foreach ( $tables as $table ) {
			$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		}
	}

	/**
	 * Core should register the deferred admin/public hooks via the loader.
	 */
	public function test_hooks_registered_via_loader() {
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $this->core, 'init_admin_class' ) ) );
		$this->assertNotFalse( has_action( 'admin_menu', array( $this->core, 'init_admin_class' ) ) );
		$this->assertNotFalse( has_action( 'admin_notices', array( $this->core, 'init_admin_class' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $this->core, 'init_public_class' ) ) );
		$this->assertNotFalse( has_action( 'wp_footer', array( $this->core, 'init_public_class' ) ) );
		$this->assertNotFalse( has_action( 'aria_cleanup_cache', array( $this->core, 'cleanup_vector_caches' ) ) );
	}

	/**
	 * AJAX routes should be wired to the deferred handler for both auth contexts.
	 */
	public function test_ajax_actions_registered() {
		$ajax_actions = array(
			'aria_send_message',
			'aria_get_conversation',
			'aria_save_knowledge',
			'aria_get_advanced_settings',
			'aria_save_advanced_settings',
		);

		foreach ( $ajax_actions as $action ) {
			$this->assertNotFalse( has_action( 'wp_ajax_' . $action, array( $this->core, 'handle_ajax_request' ) ) );
			$this->assertNotFalse( has_action( 'wp_ajax_nopriv_' . $action, array( $this->core, 'handle_ajax_request' ) ) );
		}
	}

	/**
	 * The public shortcode should still be available for the legacy widget.
	 */
	public function test_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'aria_chat' ) );
	}

	/**
	 * Deactivation should remove all scheduled events for a clean shutdown.
	 */
	public function test_deactivation_clears_cron_events() {
		$timestamp = time() + 60;

		wp_schedule_event( $timestamp, 'daily', 'aria_daily_license_check' );
		wp_schedule_event( $timestamp, 'hourly', 'aria_process_analytics' );
		wp_schedule_event( $timestamp, 'daily', 'aria_daily_summary_email' );
		wp_schedule_event( $timestamp, 'hourly', 'aria_cleanup_cache' );

		wp_schedule_single_event( $timestamp, 'aria_initial_content_indexing' );
		wp_schedule_single_event( $timestamp, 'aria_process_learning' );
		wp_schedule_single_event( $timestamp, 'aria_process_embeddings', array( 123 ) );
		wp_schedule_single_event( $timestamp, 'aria_process_migrated_entry', array( 456 ) );
		wp_schedule_single_event( $timestamp, 'aria_process_entry_batch', array( array( 1, 2, 3 ) ) );
		wp_schedule_single_event( $timestamp, 'aria_cleanup_processing' );
		wp_schedule_single_event( $timestamp, 'aria_index_single_content', array( 99, 'post' ) );

		Aria_Deactivator::deactivate();

		$this->assertFalse( wp_next_scheduled( 'aria_daily_license_check' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_process_analytics' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_daily_summary_email' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_cleanup_cache' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_initial_content_indexing' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_process_learning' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_process_embeddings', array( 123 ) ) );
		$this->assertFalse( wp_next_scheduled( 'aria_process_migrated_entry', array( 456 ) ) );
		$this->assertFalse( wp_next_scheduled( 'aria_process_entry_batch', array( array( 1, 2, 3 ) ) ) );
		$this->assertFalse( wp_next_scheduled( 'aria_cleanup_processing' ) );
		$this->assertFalse( wp_next_scheduled( 'aria_index_single_content', array( 99, 'post' ) ) );
	}
}
