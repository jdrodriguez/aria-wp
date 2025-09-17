const { chromium } = require('playwright');

async function comparePages() {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();
    
    console.log('Navigating to WordPress admin...');
    
    try {
        // Navigate to WordPress admin
        await page.goto('http://localhost:8080/wp-admin/');
        
        // Wait for login form
        await page.waitForSelector('#loginform', { timeout: 10000 });
        
        // Check if already logged in
        if (await page.locator('#loginform').isVisible()) {
            console.log('Not logged in, attempting login...');
            // Use correct admin credentials
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            
            // Wait for login to complete
            await page.waitForTimeout(3000);
        }
        
        // Check if we're now in admin
        const isLoggedIn = await page.locator('#wpadminbar').isVisible() || 
                          await page.locator('.wp-admin').isVisible() ||
                          (await page.url()).includes('wp-admin');
        
        if (!isLoggedIn) {
            console.log('Login failed, taking screenshot of current page...');
            await page.screenshot({ 
                path: '/Users/josuerodriguez/Dropbox/ARIA/aria/login-issue-screenshot.png',
                fullPage: true
            });
            throw new Error('Could not log in to WordPress admin');
        }
        
        console.log('Successfully logged in!');
        
        // Navigate to Aria dashboard
        console.log('Navigating to Aria dashboard...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria');
        await page.waitForTimeout(2000);
        
        // Take screenshot of dashboard
        console.log('Taking dashboard screenshot...');
        await page.screenshot({ 
            path: '/Users/josuerodriguez/Dropbox/ARIA/aria/dashboard-screenshot.png',
            fullPage: true
        });
        
        // Navigate to Content Indexing page
        console.log('Navigating to Content Indexing page...');
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-content-indexing');
        await page.waitForTimeout(2000);
        
        // Take screenshot of content indexing page
        console.log('Taking content indexing screenshot...');
        await page.screenshot({ 
            path: '/Users/josuerodriguez/Dropbox/ARIA/aria/content-indexing-screenshot.png',
            fullPage: true
        });
        
        // Check if modal is visible on load
        const modalVisible = await page.isVisible('#test-search-modal');
        console.log('Modal visible on page load:', modalVisible);
        
        // Try to click the test search button
        const testButton = await page.locator('#test-search-btn');
        if (await testButton.isVisible()) {
            console.log('Test search button found, clicking...');
            await testButton.click();
            await page.waitForTimeout(1000);
            
            const modalVisibleAfterClick = await page.isVisible('#test-search-modal');
            console.log('Modal visible after clicking button:', modalVisibleAfterClick);
            
            // Take screenshot with modal open
            await page.screenshot({ 
                path: '/Users/josuerodriguez/Dropbox/ARIA/aria/content-indexing-modal-screenshot.png',
                fullPage: true
            });
            
            // Try to close modal
            const closeButton = await page.locator('.aria-modal-close');
            if (await closeButton.isVisible()) {
                console.log('Close button found, clicking...');
                await closeButton.click();
                await page.waitForTimeout(500);
                
                const modalVisibleAfterClose = await page.isVisible('#test-search-modal');
                console.log('Modal visible after close:', modalVisibleAfterClose);
            }
        } else {
            console.log('Test search button not found');
        }
        
        // Check CSS loading
        const headerStyles = await page.evaluate(() => {
            const header = document.querySelector('.aria-page-header');
            if (header) {
                const styles = window.getComputedStyle(header);
                return {
                    background: styles.background,
                    padding: styles.padding,
                    borderRadius: styles.borderRadius,
                    boxShadow: styles.boxShadow
                };
            }
            return null;
        });
        
        console.log('Header computed styles:', headerStyles);
        
        // Check if specific CSS classes exist
        const hasExpectedClasses = await page.evaluate(() => {
            return {
                wrapClass: document.querySelector('.wrap.aria-content-indexing') !== null,
                containerClass: document.querySelector('.aria-dashboard-container') !== null,
                headerClass: document.querySelector('.aria-page-header') !== null,
                primarySection: document.querySelector('.aria-primary-section') !== null,
                metricsGrid: document.querySelector('.aria-metrics-grid') !== null
            };
        });
        
        console.log('Expected CSS classes found:', hasExpectedClasses);
        
    } catch (error) {
        console.error('Error during testing:', error);
        
        // Take error screenshot
        await page.screenshot({ 
            path: '/Users/josuerodriguez/Dropbox/ARIA/aria/error-screenshot.png',
            fullPage: true
        });
    }
    
    await browser.close();
}

comparePages().catch(console.error);