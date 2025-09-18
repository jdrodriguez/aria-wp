<?php
/**
 * Class Test_Aria_Core
 *
 * @package Aria
 */

/**
 * Test core plugin functionality.
 */
class Tests_Aria_Core extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var Aria
	 */
	private $plugin;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->plugin = new Aria();
	}

	/**
	 * Test plugin constants are defined.
	 */
	public function test_constants_defined() {
		$this->assertTrue( defined( 'ARIA_VERSION' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_PATH' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'ARIA_PLUGIN_BASENAME' ) );
	}

	/**
	 * Test plugin version.
	 */
	public function test_plugin_version() {
		$this->assertEquals( '1.6.0', ARIA_VERSION );
	}

	/**
	 * Test core class exists.
	 */
	public function test_core_class_exists() {
		$this->assertTrue( class_exists( 'Aria_Core' ) );
	}

	/**
	 * Test admin class exists.
	 */
	public function test_admin_class_exists() {
		$this->assertTrue( class_exists( 'Aria_Admin' ) );
	}

	/**
	 * Test public class exists.
	 */
	public function test_public_class_exists() {
		$this->assertTrue( class_exists( 'Aria_Public' ) );
	}

	/**
	 * Test database class exists.
	 */
	public function test_database_class_exists() {
		$this->assertTrue( class_exists( 'Aria_Database' ) );
	}

	/**
	 * Test plugin activation creates tables.
	 */
	public function test_activation_creates_tables() {
		global $wpdb;

		// Run activation.
		Aria_Activator::activate();

		// Check tables exist.
		$tables = array(
			$wpdb->prefix . 'aria_knowledge_base',
			$wpdb->prefix . 'aria_conversations',
			$wpdb->prefix . 'aria_personality_settings',
			$wpdb->prefix . 'aria_learning_data',
			$wpdb->prefix . 'aria_license',
		);

		foreach ( $tables as $table ) {
			$this->assertEquals( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		}
	}

	/**
	 * Test plugin hooks are registered.
	 */
	public function test_hooks_registered() {
		$this->assertTrue( has_action( 'wp_enqueue_scripts' ) );
		$this->assertTrue( has_action( 'admin_enqueue_scripts' ) );
		$this->assertTrue( has_action( 'wp_footer' ) );
	}

	/**
	 * Test AJAX actions are registered.
	 */
	public function test_ajax_actions_registered() {
		$ajax_actions = array(
			'aria_send_message',
			'aria_get_conversation',
			'aria_save_knowledge',
			'aria_test_api',
			'aria_save_personality',
		);

		foreach ( $ajax_actions as $action ) {
			$this->assertTrue( has_action( 'wp_ajax_' . $action ) );
			$this->assertTrue( has_action( 'wp_ajax_nopriv_' . $action ) );
		}
	}

	/**
	 * Test shortcode is registered.
	 */
	public function test_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'aria_chat' ) );
	}

	/**
	 * Test plugin deactivation.
	 */
	public function test_deactivation() {
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

		// Run deactivation.
		Aria_Deactivator::deactivate();

		// Check scheduled events are cleared.
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
