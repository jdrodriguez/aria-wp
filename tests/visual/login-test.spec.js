/**
 * Simple test to check WordPress login and find the correct admin credentials
 */

const { test, expect } = require('@playwright/test');

test.describe('WordPress Login Test', () => {
    test('Find correct admin credentials', async ({ page }) => {
    console.log('Testing WordPress login...');
    
    // Navigate to login page
    await page.goto('http://localhost:8080/wp-login.php');
    
    // Try common admin credentials
    const credentials = [
        { username: 'admin', password: 'admin' },
        { username: 'admin', password: 'password' },
        { username: 'admin', password: 'admin123' },
        { username: 'wordpress', password: 'wordpress' },
        { username: 'user', password: 'user' }
    ];
    
    for (const { username, password } of credentials) {
        console.log(`Trying: ${username}/${password}`);
        
        // Fill login form
        await page.fill('#user_login', username);
        await page.fill('#user_pass', password);
        await page.click('#wp-submit');
        
        // Check if we're redirected to dashboard
        await page.waitForTimeout(2000);
        
        if (page.url().includes('wp-admin') && !page.url().includes('wp-login.php')) {
            console.log(`✅ Login successful with: ${username}/${password}`);
            
            // Check what admin pages are available
            console.log('Checking for Aria admin pages...');
            
            // Try to navigate to Aria dashboard
            try {
                await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-dashboard');
                await page.waitForTimeout(2000);
                
                if (page.url().includes('aria-dashboard')) {
                    console.log('✅ Aria dashboard page accessible');
                    
                    // Take a screenshot
                    await page.screenshot({ 
                        path: 'tests/visual/screenshots/login-success.png',
                        fullPage: true 
                    });
                } else {
                    console.log('❌ Aria dashboard not found, checking WordPress admin menu...');
                    
                    // Look for Aria in the admin menu
                    const ariaMenu = await page.locator('a[href*="aria"]').count();
                    console.log(`Found ${ariaMenu} Aria menu items`);
                    
                    if (ariaMenu > 0) {
                        const ariaLinks = await page.locator('a[href*="aria"]').all();
                        for (const link of ariaLinks) {
                            const href = await link.getAttribute('href');
                            const text = await link.textContent();
                            console.log(`  - ${text}: ${href}`);
                        }
                    }
                }
            } catch (error) {
                console.log(`❌ Error accessing Aria dashboard: ${error.message}`);
            }
            
            return;
        } else {
            console.log(`❌ Login failed with: ${username}/${password}`);
            // Go back to login page for next attempt
            await page.goto('http://localhost:8080/wp-login.php');
        }
    }
    
    console.log('❌ All login attempts failed');
});
});