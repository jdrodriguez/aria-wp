<?php
/**
 * Debug helper to check and create knowledge base entries
 * Run this from WordPress root: php wp-content/plugins/aria/debug-knowledge.php
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

global $wpdb;
$table = $wpdb->prefix . 'aria_knowledge_base';

echo "Aria Knowledge Base Debug\n";
echo "========================\n\n";

// Check current blog ID
echo "Current blog ID: " . get_current_blog_id() . "\n\n";

// Check existing entries
$all_entries = $wpdb->get_results( "SELECT id, title, site_id FROM $table", ARRAY_A );
echo "Total entries in table: " . count( $all_entries ) . "\n";

if ( count( $all_entries ) > 0 ) {
    echo "\nExisting entries:\n";
    foreach ( $all_entries as $entry ) {
        echo "- ID: {$entry['id']}, Title: {$entry['title']}, Site ID: {$entry['site_id']}\n";
    }
}

// Check if we should create a test entry
if ( isset( $argv[1] ) && $argv[1] === 'create' ) {
    echo "\nCreating test knowledge entry...\n";
    
    $test_data = array(
        'title'    => 'Careers and Employment',
        'content'  => 'We are always looking for passionate and dedicated individuals to join our growing family of restaurants. If you have a flair for hospitality and a commitment to excellence, we welcome you to explore our current openings. To submit your application and learn more about a career with us, please visit our careers page at https://markethospitalitygroup.com/index.php/careers/. We look forward to the possibility of you becoming part of our team!',
        'category' => 'Employment',
        'tags'     => 'careers,jobs,employment,hiring,work,positions,openings',
        'language' => 'en',
        'site_id'  => get_current_blog_id()
    );
    
    $result = $wpdb->insert( $table, $test_data );
    
    if ( $result !== false ) {
        echo "Successfully created test entry with ID: " . $wpdb->insert_id . "\n";
    } else {
        echo "Failed to create test entry. Error: " . $wpdb->last_error . "\n";
    }
}

echo "\nTo create a test entry, run: php debug-knowledge.php create\n";