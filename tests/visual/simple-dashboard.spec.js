/**
 * Simple Dashboard Page Investigation
 */

const { test, expect } = require('@playwright/test');

test.describe('Simple Dashboard Investigation', () => {
    test('Check what is on the dashboard page', async ({ page }) => {
        console.log('\n=== INVESTIGATING DASHBOARD PAGE ===');
        
        // Login first
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin123');
        await page.click('#wp-submit');
        await page.waitForSelector('#wpadminbar', { timeout: 10000 });
        
        // Navigate to Dashboard
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-dashboard');
        await page.waitForTimeout(3000);
        
        // Take screenshot immediately
        await page.screenshot({ 
            path: 'tests/visual/screenshots/dashboard-raw.png',
            fullPage: true 
        });
        
        console.log('📸 Raw dashboard screenshot saved');
        
        // Check page title
        const title = await page.title();
        console.log(`📄 Page title: ${title}`);
        
        // Check page URL
        console.log(`🔗 Page URL: ${page.url()}`);
        
        // Check if we have any Aria-related content
        const ariaElements = await page.evaluate(() => {
            const elements = [];
            
            // Look for elements with aria- classes
            const ariaClasses = document.querySelectorAll('[class*="aria-"]');
            elements.push(`Elements with aria- classes: ${ariaClasses.length}`);
            
            // Look for specific selectors
            const selectors = [
                '.wrap',
                '.aria-dashboard',
                '.aria-page-content',
                '.aria-page-header',
                '.aria-metrics-grid',
                '.aria-metric-card'
            ];
            
            selectors.forEach(selector => {
                const count = document.querySelectorAll(selector).length;
                elements.push(`${selector}: ${count} found`);
            });
            
            // Check page content
            const bodyText = document.body.innerText;
            if (bodyText.includes('Dashboard')) {
                elements.push('✅ "Dashboard" text found in page');
            }
            if (bodyText.includes('Aria')) {
                elements.push('✅ "Aria" text found in page');
            }
            
            return elements;
        });
        
        console.log('\n📊 PAGE ANALYSIS:');
        ariaElements.forEach(element => console.log(`   ${element}`));
        
        // Check for WordPress admin notices or errors
        const notices = await page.evaluate(() => {
            const notices = [];
            
            // Check for WordPress admin notices
            const adminNotices = document.querySelectorAll('.notice, .error, .updated');
            notices.push(`WordPress notices: ${adminNotices.length}`);
            
            if (adminNotices.length > 0) {
                adminNotices.forEach((notice, index) => {
                    notices.push(`  Notice ${index + 1}: ${notice.textContent.trim()}`);
                });
            }
            
            // Check for PHP errors
            const phpErrors = document.querySelectorAll('.php-error');
            notices.push(`PHP errors: ${phpErrors.length}`);
            
            return notices;
        });
        
        console.log('\n🔍 NOTICES/ERRORS:');
        notices.forEach(notice => console.log(`   ${notice}`));
        
        console.log('\n='.repeat(50));
    });
});