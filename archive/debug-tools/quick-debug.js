// ARIA Knowledge Debug Script - Quick Version
console.log('=== ARIA KNOWLEDGE DEBUG ===');

if (!window.ariaAdmin) {
    console.error('❌ ariaAdmin not found - script localization failed');
} else {
    console.log('✅ ariaAdmin found:', window.ariaAdmin);
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'aria_get_dashboard_data');
    formData.append('nonce', window.ariaAdmin.nonce);
    
    console.log('🔄 Making AJAX request...');
    
    fetch(window.ariaAdmin.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Response status:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('📦 Full AJAX Response:', result);
        
        if (result.success) {
            console.log('✅ AJAX Success!');
            console.log('📊 Knowledge Count from Response:', result.data.knowledgeCount);
            console.log('📋 All Dashboard Data:', result.data);
            
            // Check specific values
            if (result.data.knowledgeCount === 0) {
                console.warn('⚠️ Knowledge count is 0 - check WordPress error logs for database query details');
            }
        } else {
            console.error('❌ AJAX Failed:', result.data?.message || 'Unknown error');
            console.error('Full error:', result);
        }
    })
    .catch(error => {
        console.error('❌ AJAX Error:', error);
    });
}