<?php
/**
 * Clean up duplicate or old knowledge base entries
 * Run this from WordPress root: php wp-content/plugins/aria/cleanup-knowledge.php
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

global $wpdb;
$table = $wpdb->prefix . 'aria_knowledge_base';

echo "Aria Knowledge Base Cleanup\n";
echo "===========================\n\n";

// First, show ALL entries in the knowledge base
$all_entries = $wpdb->get_results( 
    "SELECT id, title, site_id, created_at, updated_at 
     FROM $table 
     ORDER BY created_at DESC", 
    ARRAY_A 
);

echo "Total entries in knowledge base: " . count( $all_entries ) . "\n\n";

// Show all entries related to careers/employment
$career_entries = $wpdb->get_results( 
    "SELECT id, title, content, site_id, created_at, updated_at 
     FROM $table 
     WHERE content LIKE '%career%' 
        OR content LIKE '%employment%' 
        OR content LIKE '%hiring%'
        OR title LIKE '%career%'
        OR title LIKE '%employment%'
     ORDER BY created_at DESC", 
    ARRAY_A 
);

echo "Found " . count( $career_entries ) . " career-related entries:\n\n";

foreach ( $career_entries as $index => $entry ) {
    echo "Entry #" . ($index + 1) . " (ID: {$entry['id']})\n";
    echo "Title: {$entry['title']}\n";
    echo "Site ID: {$entry['site_id']}\n";
    echo "Created: {$entry['created_at']}\n";
    echo "Updated: {$entry['updated_at']}\n";
    echo "Content preview: " . substr( $entry['content'], 0, 150 ) . "...\n";
    
    // Check if this contains "current openings"
    if ( strpos( $entry['content'], 'current openings' ) !== false ) {
        echo "*** Contains 'current openings' ***\n";
    }
    
    echo "---\n\n";
}

// If run with 'delete-old' parameter, remove entries with "current openings"
if ( isset( $argv[1] ) && $argv[1] === 'delete-old' ) {
    echo "Deleting entries containing 'current openings'...\n";
    
    $deleted = $wpdb->query( 
        "DELETE FROM $table 
         WHERE content LIKE '%current openings%'" 
    );
    
    echo "Deleted $deleted entries.\n";
}

// If run with 'delete-duplicates' parameter, keep only the newest entry for each title
if ( isset( $argv[1] ) && $argv[1] === 'delete-duplicates' ) {
    echo "Removing duplicate entries (keeping newest)...\n";
    
    // Get duplicate titles
    $duplicates = $wpdb->get_results(
        "SELECT title, COUNT(*) as count, MAX(id) as keep_id
         FROM $table
         GROUP BY title
         HAVING count > 1",
        ARRAY_A
    );
    
    $total_deleted = 0;
    foreach ( $duplicates as $dup ) {
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE title = %s AND id != %d",
                $dup['title'],
                $dup['keep_id']
            )
        );
        $total_deleted += $deleted;
        echo "Removed $deleted duplicate(s) of '{$dup['title']}'\n";
    }
    
    echo "\nTotal duplicates removed: $total_deleted\n";
}

echo "\nUsage:\n";
echo "- View all career entries: php cleanup-knowledge.php\n";
echo "- Delete entries with 'current openings': php cleanup-knowledge.php delete-old\n";
echo "- Remove duplicate entries: php cleanup-knowledge.php delete-duplicates\n";