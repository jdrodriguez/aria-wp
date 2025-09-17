/**
 * Debug Script for ARIA Dashboard Data Issues
 * 
 * INSTRUCTIONS:
 * 1. Go to your WordPress admin dashboard (http://localhost:8080/wp-admin)
 * 2. Navigate to the ARIA dashboard page
 * 3. Open browser developer tools (F12)
 * 4. Go to the Console tab
 * 5. Copy and paste this entire script into the console and press Enter
 * 6. Check the output to see what's happening with the AJAX calls
 */

console.log('=== ARIA DASHBOARD DEBUG SCRIPT ===');

// Check if WordPress admin variables are available
console.log('1. Checking WordPress admin variables:');
console.log('window.ariaAdmin:', window.ariaAdmin);

if (!window.ariaAdmin) {
    console.error('❌ ariaAdmin object not found! This means WordPress script localization failed.');
    console.log('📝 This indicates the React script is not getting WordPress variables.');
} else {
    console.log('✅ ariaAdmin object found');
    console.log('AJAX URL:', window.ariaAdmin.ajaxUrl);
    console.log('Nonce:', window.ariaAdmin.nonce);
    console.log('Admin URL:', window.ariaAdmin.adminUrl);
}

// Test the AJAX endpoint directly
async function testDashboardAjax() {
    console.log('\n2. Testing AJAX endpoint directly:');
    
    if (!window.ariaAdmin) {
        console.error('❌ Cannot test AJAX - no ariaAdmin object');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'aria_get_dashboard_data');
        formData.append('nonce', window.ariaAdmin.nonce);
        
        console.log('🔄 Making AJAX request...');
        console.log('URL:', window.ariaAdmin.ajaxUrl);
        console.log('Action:', 'aria_get_dashboard_data');
        console.log('Nonce:', window.ariaAdmin.nonce);
        
        const response = await fetch(window.ariaAdmin.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response ok:', response.ok);
        
        if (!response.ok) {
            console.error('❌ HTTP Error:', response.status, response.statusText);
            return;
        }
        
        const result = await response.json();
        console.log('📦 AJAX Response:', result);
        
        if (result.success) {
            console.log('✅ AJAX call successful!');
            console.log('📊 Dashboard data received:', result.data);
            
            // Analyze the data
            const data = result.data;
            console.log('\n3. Analyzing received data:');
            console.log('Conversations Today:', data.conversationsToday);
            console.log('Total Conversations:', data.totalConversations);
            console.log('Knowledge Count:', data.knowledgeCount);
            console.log('Recent Conversations:', data.recentConversations);
            console.log('License Status:', data.licenseStatus);
            
            if (data.conversationsToday === data.totalConversations && data.totalConversations > 0) {
                console.warn('⚠️  WARNING: Today\'s conversations equals total conversations. This suggests date filtering might not be working.');
            }
            
            if (data.recentConversations && data.recentConversations.length > 0) {
                console.log('📝 Recent conversations found:', data.recentConversations.length);
                console.log('First conversation:', data.recentConversations[0]);
            } else {
                console.log('📝 No recent conversations in database (this is expected for empty database)');
            }
            
            // Check knowledge base specifically
            console.log('\n📚 Knowledge Base Analysis:');
            console.log('Total Knowledge Count:', data.knowledgeCount);
            
            if (data.knowledgeCount > 0) {
                console.log('✅ Knowledge base entries found:', data.knowledgeCount);
                console.log('📝 This should include BOTH:');
                console.log('   - Manual entries from ARIA Knowledge page');
                console.log('   - WordPress content (posts/pages) that have been vectorized');
                console.log('💡 Check WordPress error logs for breakdown of these two sources');
            } else {
                console.warn('⚠️  Knowledge base shows 0 entries');
                console.log('🔍 This means you may have:');
                console.log('   - No manual knowledge entries in ARIA Knowledge page');
                console.log('   - No WordPress content has been vectorized yet');
                console.log('   - Check WordPress error logs for detailed breakdown');
            }
            
        } else {
            console.error('❌ AJAX call failed:', result.data?.message || 'Unknown error');
            console.error('Full error response:', result);
        }
        
    } catch (error) {
        console.error('❌ AJAX request failed:', error);
        console.error('Error details:', error.message);
    }
}

// Check React component mounting
console.log('\n4. Checking React component:');
const dashboardRoot = document.getElementById('aria-dashboard-root');
if (dashboardRoot) {
    console.log('✅ Dashboard root element found');
    console.log('Root element content:', dashboardRoot.innerHTML.substring(0, 200) + '...');
} else {
    console.error('❌ Dashboard root element not found');
}

// Check if we're on the right page
console.log('\n5. Page information:');
console.log('Current URL:', window.location.href);
console.log('Page query string:', window.location.search);

// Run the AJAX test
console.log('\n6. Running AJAX test...');
testDashboardAjax();

console.log('\n=== END DEBUG SCRIPT ===');
console.log('📋 Instructions:');
console.log('1. Check the output above for any ❌ errors');
console.log('2. If AJAX is working, check the dashboard data values');
console.log('3. If you see warnings about date filtering, that indicates the issue');
console.log('4. Share this console output to help debug the issue');