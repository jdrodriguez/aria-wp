<?php
/**
 * Debug information for Aria plugin
 * 
 * Access this file directly to see debug info
 */

// Load WordPress
require_once( '../../../wp-load.php' );

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Aria Plugin Debug Info</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .ok { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Aria Plugin Debug Information</h1>
    
    <div class="section">
        <h2>Plugin Status</h2>
        <?php
        $active_plugins = get_option( 'active_plugins' );
        $aria_active = in_array( 'aria/aria.php', $active_plugins );
        ?>
        <p>Plugin Active: <span class="<?php echo $aria_active ? 'ok' : 'error'; ?>"><?php echo $aria_active ? 'YES' : 'NO'; ?></span></p>
    </div>

    <div class="section">
        <h2>Constants</h2>
        <p>ARIA_VERSION: <?php echo defined( 'ARIA_VERSION' ) ? ARIA_VERSION : 'NOT DEFINED'; ?></p>
        <p>ARIA_PLUGIN_PATH: <?php echo defined( 'ARIA_PLUGIN_PATH' ) ? ARIA_PLUGIN_PATH : 'NOT DEFINED'; ?></p>
        <p>ARIA_PLUGIN_URL: <?php echo defined( 'ARIA_PLUGIN_URL' ) ? ARIA_PLUGIN_URL : 'NOT DEFINED'; ?></p>
    </div>

    <div class="section">
        <h2>Database Tables</h2>
        <?php
        global $wpdb;
        $tables = array(
            'aria_knowledge_base',
            'aria_conversations',
            'aria_personality_settings',
            'aria_learning_data',
            'aria_license'
        );
        
        foreach ( $tables as $table ) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table;
            echo '<p>' . $full_table . ': <span class="' . ( $exists ? 'ok' : 'error' ) . '">' . ( $exists ? 'EXISTS' : 'MISSING' ) . '</span></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Plugin Options</h2>
        <p>AI Provider: <?php echo get_option( 'aria_ai_provider', 'not set' ); ?></p>
        <p>API Key Set: <?php echo get_option( 'aria_ai_api_key' ) ? 'YES' : 'NO'; ?></p>
        <p>Chat Enabled: <?php echo get_option( 'aria_enable_chat', 'not set' ); ?></p>
    </div>

    <div class="section">
        <h2>AJAX Actions</h2>
        <?php
        global $wp_filter;
        $ajax_actions = array( 'aria_test_api', 'aria_send_message', 'aria_save_knowledge' );
        
        foreach ( $ajax_actions as $action ) {
            $hook = 'wp_ajax_' . $action;
            $registered = isset( $wp_filter[$hook] ) && ! empty( $wp_filter[$hook]->callbacks );
            echo '<p>' . $hook . ': <span class="' . ( $registered ? 'ok' : 'error' ) . '">' . ( $registered ? 'REGISTERED' : 'NOT REGISTERED' ) . '</span></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>File Permissions</h2>
        <?php
        $files = array(
            'dist/admin.js',
            'dist/chat.js',
            'includes/class-aria-ajax-handler.php',
            'admin/partials/aria-ai-config.php'
        );
        
        foreach ( $files as $file ) {
            $full_path = ARIA_PLUGIN_PATH . $file;
            $exists = file_exists( $full_path );
            $readable = is_readable( $full_path );
            echo '<p>' . $file . ': <span class="' . ( $exists && $readable ? 'ok' : 'error' ) . '">';
            echo $exists ? ( $readable ? 'OK' : 'EXISTS but NOT READABLE' ) : 'MISSING';
            echo '</span></p>';
        }
        ?>
    </div>
</body>
</html>