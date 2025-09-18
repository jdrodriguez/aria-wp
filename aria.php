<?php
/**
 * Aria - Your Website's Voice
 *
 * @package           Aria
 * @author            Aria Development Team
 * @copyright         2024 Aria Plugin
 * @license           Commercial
 *
 * @wordpress-plugin
 * Plugin Name:       Aria - Your Website's Voice
 * Plugin URI:        https://ariaplugin.com
 * Description:       Transform your contact forms into intelligent AI-powered conversations. Aria learns your business and speaks with your brand's voice.
 * Version:           1.6.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Aria Development Team
 * Author URI:        https://ariaplugin.com
 * Text Domain:       aria
 * Domain Path:       /languages
 * License:           Commercial
 * License URI:       https://ariaplugin.com/license
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'ARIA_VERSION', '1.6.0' );

/**
 * Database version.
 */
define( 'ARIA_DB_VERSION', '1.6.0' );

/**
 * Plugin paths and URLs.
 */
define( 'ARIA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ARIA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aria-activator.php
 */
function aria_activate() {
	require_once ARIA_PLUGIN_PATH . 'includes/class-aria-activator.php';
	Aria_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aria-deactivator.php
 */
function aria_deactivate() {
	require_once ARIA_PLUGIN_PATH . 'includes/class-aria-deactivator.php';
	Aria_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'aria_activate' );
register_deactivation_hook( __FILE__, 'aria_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ARIA_PLUGIN_PATH . 'includes/class-aria-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function aria_run() {
	$plugin = new Aria_Core();
	$plugin->run();
}
aria_run();
