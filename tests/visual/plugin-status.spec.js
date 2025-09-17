/**
 * Check Plugin Status and Admin Interface
 */

const { test, expect } = require('@playwright/test');

test.describe('Plugin Status Investigation', () => {
    test('Check Aria plugin status and admin interface', async ({ page }) => {
        console.log('\n=== CHECKING PLUGIN STATUS ===');
        
        // Login first
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin123');
        await page.click('#wp-submit');
        await page.waitForSelector('#wpadminbar', { timeout: 10000 });
        
        // Go to plugins page
        await page.goto('http://localhost:8080/wp-admin/plugins.php');
        await page.waitForTimeout(2000);
        
        // Take screenshot of plugins page
        await page.screenshot({ 
            path: 'tests/visual/screenshots/plugins-page.png',
            fullPage: true 
        });
        
        console.log('📸 Plugins page screenshot saved');
        
        // Check if Aria plugin is listed
        const ariaPlugin = await page.evaluate(() => {
            const pluginRows = Array.from(document.querySelectorAll('tr[data-slug*="aria"], tr[id*="aria"]'));
            const ariaRows = pluginRows.filter(row => 
                row.textContent.toLowerCase().includes('aria')
            );
            
            if (ariaRows.length > 0) {
                return ariaRows.map(row => ({
                    text: row.textContent.trim(),
                    classes: row.className,
                    id: row.id
                }));
            }
            
            // Also check for any plugin containing "aria" in the name
            const allPlugins = Array.from(document.querySelectorAll('.plugin-title strong')).map(el => el.textContent);
            const ariaPlugins = allPlugins.filter(name => name.toLowerCase().includes('aria'));
            
            return { allPlugins, ariaPlugins };
        });
        
        console.log('\n📦 PLUGIN STATUS:');
        console.log(JSON.stringify(ariaPlugin, null, 2));
        
        // Check WordPress admin menu for Aria
        const adminMenu = await page.evaluate(() => {
            const menuItems = Array.from(document.querySelectorAll('#adminmenu a')).map(a => ({
                text: a.textContent.trim(),
                href: a.href
            }));
            
            const ariaMenuItems = menuItems.filter(item => 
                item.text.toLowerCase().includes('aria') || 
                item.href.includes('aria')
            );
            
            return { totalMenuItems: menuItems.length, ariaMenuItems };
        });
        
        console.log('\n🔗 ADMIN MENU:');
        console.log(`Total menu items: ${adminMenu.totalMenuItems}`);
        console.log(`Aria menu items: ${adminMenu.ariaMenuItems.length}`);
        
        if (adminMenu.ariaMenuItems.length > 0) {
            console.log('Aria menu items found:');
            adminMenu.ariaMenuItems.forEach(item => {
                console.log(`  - ${item.text}: ${item.href}`);
            });
        }
        
        // Try to navigate to main WordPress admin page
        await page.goto('http://localhost:8080/wp-admin/');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ 
            path: 'tests/visual/screenshots/wp-admin-main.png',
            fullPage: true 
        });
        
        console.log('📸 Main WordPress admin screenshot saved');
        
        // Check if we can find any reference to Aria in the admin
        const adminPageContent = await page.evaluate(() => {
            const content = document.body.innerText;
            const ariaReferences = [];
            
            if (content.includes('Aria')) {
                ariaReferences.push('✅ "Aria" text found in admin');
            }
            if (content.includes('aria-dashboard')) {
                ariaReferences.push('✅ "aria-dashboard" found in admin');
            }
            
            // Check for any links with aria in them
            const ariaLinks = Array.from(document.querySelectorAll('a[href*="aria"]')).map(a => a.href);
            if (ariaLinks.length > 0) {
                ariaReferences.push(`✅ ${ariaLinks.length} links with "aria" found`);
                ariaLinks.forEach(link => ariaReferences.push(`   - ${link}`));
            }
            
            return ariaReferences;
        });
        
        console.log('\n🔍 ADMIN PAGE CONTENT:');
        adminPageContent.forEach(item => console.log(`   ${item}`));
        
        console.log('\n='.repeat(50));
    });
});