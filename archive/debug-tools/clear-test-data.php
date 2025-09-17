<?php
/**
 * ARIA Test Data Cleanup Script
 * 
 * INSTRUCTIONS:
 * 1. Place this file in your WordPress root directory (same level as wp-config.php)
 * 2. Navigate to: http://localhost:8080/clear-test-data.php
 * 3. This will show what's in the database and optionally clear test data
 * 
 * WARNING: Only run this if you want to clear ALL conversation data!
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

global $wpdb;

echo "<h1>ARIA Database Debug Tool</h1>";

// Check what's in the conversations table
$conversations_table = $wpdb->prefix . 'aria_conversations';
$knowledge_table = $wpdb->prefix . 'aria_knowledge_base';
$license_table = $wpdb->prefix . 'aria_license';

echo "<h2>Current Database Contents</h2>";

// Check conversations
$conversations = $wpdb->get_results("SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT 10", ARRAY_A);
echo "<h3>Conversations Table ($conversations_table)</h3>";
echo "<p>Total conversations: " . $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table") . "</p>";

if (empty($conversations)) {
    echo "<p style='color: green;'>✅ No conversations found - this is correct for a fresh installation</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Found " . count($conversations) . " conversations (showing last 10):</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Guest Name</th><th>Email</th><th>Question</th><th>Created</th><th>Status</th></tr>";
    foreach ($conversations as $conv) {
        echo "<tr>";
        echo "<td>" . $conv['id'] . "</td>";
        echo "<td>" . ($conv['guest_name'] ?: 'Anonymous') . "</td>";
        echo "<td>" . ($conv['guest_email'] ?: 'No email') . "</td>";
        echo "<td>" . substr($conv['initial_question'], 0, 50) . "...</td>";
        echo "<td>" . $conv['created_at'] . "</td>";
        echo "<td>" . $conv['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check knowledge base
$knowledge_count = $wpdb->get_var("SELECT COUNT(*) FROM $knowledge_table");
echo "<h3>Knowledge Base Table ($knowledge_table)</h3>";
echo "<p>Total knowledge entries: $knowledge_count</p>";

// Check license
$license = $wpdb->get_row("SELECT * FROM $license_table", ARRAY_A);
echo "<h3>License Table ($license_table)</h3>";
if ($license) {
    echo "<p>License Status: " . $license['license_status'] . "</p>";
    echo "<p>Site URL: " . $license['site_url'] . "</p>";
    if ($license['trial_started']) {
        echo "<p>Trial Started: " . $license['trial_started'] . "</p>";
    }
} else {
    echo "<p>No license data found</p>";
}

// Clear data option
if (isset($_GET['clear']) && $_GET['clear'] === 'confirm') {
    echo "<h2 style='color: red;'>Clearing All Conversation Data...</h2>";
    
    $deleted = $wpdb->query("DELETE FROM $conversations_table");
    echo "<p>Deleted $deleted conversations</p>";
    
    echo "<p style='color: green;'>✅ All conversation data cleared. Refresh the dashboard to see changes.</p>";
    echo "<p><a href='?'>← Back to debug view</a></p>";
} else {
    if (!empty($conversations)) {
        echo "<h2>Clear Test Data</h2>";
        echo "<p style='color: red;'><strong>WARNING:</strong> This will permanently delete ALL conversations!</p>";
        echo "<p><a href='?clear=confirm' style='background: red; color: white; padding: 10px; text-decoration: none;'>🗑️ CLEAR ALL CONVERSATION DATA</a></p>";
        echo "<p><em>Only click this if you want to remove test/fake data</em></p>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>Go back to WordPress admin: <a href='/wp-admin/admin.php?page=aria'>ARIA Dashboard</a></li>";
echo "<li>Hard refresh the page (Ctrl+Shift+R or Cmd+Shift+R)</li>";
echo "<li>Check browser console for debug output</li>";
echo "<li>If still seeing issues, check WordPress error logs</li>";
echo "</ul>";

echo "<p><em>Debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>