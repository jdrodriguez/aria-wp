<?php
/**
 * Direct AJAX Endpoint Test for ARIA Dashboard Data
 * Access via: http://localhost:8080/wp-content/plugins/aria/test-ajax-direct.php
 */

// WordPress Bootstrap
require_once dirname(__FILE__) . '/../../../wp-config.php';
require_once ABSPATH . 'wp-load.php';

// Ensure user is logged in as admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You must be logged in as an administrator to run this test.');
}

echo "<!DOCTYPE html><html><head><title>ARIA AJAX Test</title></head><body>";
echo "<h1>ARIA Dashboard AJAX Endpoint Test</h1>";

// Test 1: Check if AJAX handler class exists and method is callable
echo "<h2>1. Class and Method Verification</h2>";
if (class_exists('Aria_Ajax_Handler')) {
    echo "✓ Aria_Ajax_Handler class exists<br>";
    
    $handler = new Aria_Ajax_Handler();
    if (method_exists($handler, 'handle_get_dashboard_data')) {
        echo "✓ handle_get_dashboard_data method exists<br>";
    } else {
        echo "✗ handle_get_dashboard_data method NOT found<br>";
    }
} else {
    echo "✗ Aria_Ajax_Handler class NOT found<br>";
}

// Test 2: Simulate the AJAX call directly
echo "<h2>2. Direct Method Call Test</h2>";
try {
    // Set up the $_POST data that would come from React
    $_POST['action'] = 'aria_get_dashboard_data';
    $_POST['nonce'] = wp_create_nonce('aria_admin_nonce');
    
    echo "Simulating AJAX call with:<br>";
    echo "- action: aria_get_dashboard_data<br>";
    echo "- nonce: {$_POST['nonce']}<br>";
    echo "- user_can('manage_options'): " . (current_user_can('manage_options') ? 'YES' : 'NO') . "<br><br>";
    
    // Capture output
    ob_start();
    
    if (class_exists('Aria_Ajax_Handler')) {
        $handler = new Aria_Ajax_Handler();
        $handler->handle_get_dashboard_data();
    }
    
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "AJAX Response:<br>";
        echo "<pre style='background:#f0f0f0; padding:10px; border:1px solid #ccc;'>";
        echo htmlspecialchars($output);
        echo "</pre>";
        
        // Try to decode JSON response
        $json_data = json_decode($output, true);
        if ($json_data) {
            echo "<h3>Parsed JSON Data:</h3>";
            echo "<pre style='background:#e8f5e8; padding:10px; border:1px solid #4CAF50;'>";
            print_r($json_data);
            echo "</pre>";
        }
    } else {
        echo "⚠️ No output from AJAX handler<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Error calling AJAX handler: " . $e->getMessage() . "<br>";
}

// Test 3: Check WordPress error log for our debug messages
echo "<h2>3. Recent Error Log Entries</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    echo "Error log location: $error_log_path<br>";
    $recent_logs = tail($error_log_path, 20);
    if ($recent_logs) {
        echo "<h4>Last 20 error log entries:</h4>";
        echo "<pre style='background:#fff3cd; padding:10px; border:1px solid #ffc107; max-height:300px; overflow-y:scroll;'>";
        echo htmlspecialchars($recent_logs);
        echo "</pre>";
    }
} else {
    echo "Error log not found or not accessible<br>";
}

// Test 4: JavaScript console test
echo "<h2>4. JavaScript Console Test</h2>";
echo "<p>Open browser console and check for messages from this test:</p>";
echo "<button onclick='testAjaxFromJS()'>Test AJAX from JavaScript</button>";
echo "<button onclick='testDebugBypass()' style='margin-left:10px; background:#ff6b6b; color:white;'>Test Debug Bypass (Temporary)</button>";
echo "<div id='js-results'></div>";

// Check if ariaAdmin is available (like React component expects)
echo "<h2>5. Check ariaAdmin JavaScript Object</h2>";
echo "<script>
// Check if ariaAdmin object exists (this is what React expects)
console.log('=== ARIA ADMIN OBJECT DEBUG ===');
console.log('ariaAdmin object exists:', typeof window.ariaAdmin !== 'undefined');
if (window.ariaAdmin) {
    console.log('ariaAdmin contents:', window.ariaAdmin);
    console.log('AJAX URL:', window.ariaAdmin.ajaxUrl);
    console.log('Nonce:', window.ariaAdmin.nonce);
} else {
    console.log('ariaAdmin object is missing - this is why React fails!');
}

function testAjaxFromJS() {
    console.log('🔧 Testing ARIA AJAX from JavaScript...');
    
    // Test both manual nonce and ariaAdmin nonce
    const manualNonce = '" . wp_create_nonce('aria_admin_nonce') . "';
    const reactNonce = window.ariaAdmin ? window.ariaAdmin.nonce : 'NOT_AVAILABLE';
    
    console.log('Manual nonce (PHP generated):', manualNonce);
    console.log('React nonce (ariaAdmin.nonce):', reactNonce);
    
    // Test with manual nonce first
    const formData = new FormData();
    formData.append('action', 'aria_get_dashboard_data');
    formData.append('nonce', manualNonce);
    
    console.log('Making request to:', '" . admin_url('admin-ajax.php') . "');
    
    fetch('" . admin_url('admin-ajax.php') . "', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(data => {
        console.log('Raw response:', data);
        document.getElementById('js-results').innerHTML = '<h4>Manual Nonce Test:</h4><pre>' + data + '</pre>';
        
        // Now test with React nonce if available
        if (window.ariaAdmin && window.ariaAdmin.nonce) {
            testWithReactNonce();
        } else {
            document.getElementById('js-results').innerHTML += '<p style=\"color:red;\">Cannot test React nonce - ariaAdmin object missing</p>';
        }
        
        try {
            const jsonData = JSON.parse(data);
            console.log('Parsed JSON:', jsonData);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        document.getElementById('js-results').innerHTML = '<p style=\"color:red;\">Error: ' + error.message + '</p>';
    });
}

function testWithReactNonce() {
    console.log('🔧 Testing with React nonce...');
    
    const formData = new FormData();
    formData.append('action', 'aria_get_dashboard_data');
    formData.append('nonce', window.ariaAdmin.nonce);
    
    fetch('" . admin_url('admin-ajax.php') . "', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('React nonce response:', data);
        document.getElementById('js-results').innerHTML += '<h4>React Nonce Test:</h4><pre>' + data + '</pre>';
    })
    .catch(error => {
        console.error('React nonce error:', error);
    });
}

function testDebugBypass() {
    console.log('🔧 Testing with debug bypass...');
    
    const formData = new FormData();
    formData.append('action', 'aria_get_dashboard_data');
    formData.append('nonce', '" . wp_create_nonce('aria_admin_nonce') . "');
    formData.append('debug_bypass', 'temporary_testing');
    
    fetch('" . admin_url('admin-ajax.php') . "', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Debug bypass response:', data);
        document.getElementById('js-results').innerHTML += '<h4>Debug Bypass Test (Security Disabled):</h4><pre>' + data + '</pre>';
        
        try {
            const jsonData = JSON.parse(data);
            console.log('Debug bypass parsed JSON:', jsonData);
            if (jsonData.success) {
                document.getElementById('js-results').innerHTML += '<p style=\"color:green;\">✅ Data queries work! Issue is nonce verification.</p>';
            }
        } catch (e) {
            console.error('Failed to parse debug bypass JSON:', e);
        }
    })
    .catch(error => {
        console.error('Debug bypass error:', error);
    });
}
</script>";

echo "</body></html>";

/**
 * Helper function to read last N lines of a file
 */
function tail($filename, $lines = 10) {
    if (!file_exists($filename)) {
        return false;
    }
    
    $handle = fopen($filename, "r");
    if (!$handle) {
        return false;
    }
    
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    
    return implode("", array_reverse($text));
}
?>