/**
 * Aria Pages Visual Inspection
 */

const { test, expect } = require('@playwright/test');

// Correct Aria admin pages based on the menu investigation
const ARIA_PAGES = [
    {
        name: 'Dashboard',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria',
        expectedClass: '.aria-dashboard'
    },
    {
        name: 'Personality',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-personality',
        expectedClass: '.aria-personality'
    },
    {
        name: 'Knowledge Base',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-knowledge',
        expectedClass: '.aria-knowledge'
    },
    {
        name: 'Content Indexing',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-content-indexing',
        expectedClass: '.aria-content-indexing'
    },
    {
        name: 'AI Setup',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-ai-config',
        expectedClass: '.aria-ai-config'
    },
    {
        name: 'Design',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-design',
        expectedClass: '.aria-design'
    },
    {
        name: 'Conversations',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-conversations',
        expectedClass: '.aria-conversations'
    },
    {
        name: 'Settings',
        url: 'http://localhost:8080/wp-admin/admin.php?page=aria-settings',
        expectedClass: '.aria-settings'
    }
];

test.describe('Aria Pages Visual Inspection', () => {
    // Login once for all tests
    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin123');
        await page.click('#wp-submit');
        await page.waitForSelector('#wpadminbar', { timeout: 10000 });
        await page.close();
    });

    ARIA_PAGES.forEach(({ name, url, expectedClass }) => {
        test(`${name} Page Visual Inspection`, async ({ page }) => {
            console.log(`\n=== EXAMINING ${name.toUpperCase()} PAGE ===`);
            
            // Login first
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar', { timeout: 10000 });
            
            // Navigate to the page
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Take screenshot immediately
            await page.screenshot({ 
                path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}-raw.png`,
                fullPage: true 
            });
            
            console.log(`📸 ${name} screenshot saved`);
            
            // Check page title
            const title = await page.title();
            console.log(`📄 Page title: ${title}`);
            
            // Check if we have the expected class
            const hasExpectedClass = await page.locator(expectedClass).count() > 0;
            console.log(`🏷️  Expected class ${expectedClass}: ${hasExpectedClass ? '✅ Found' : '❌ Not found'}`);
            
            // Analyze page structure
            const pageAnalysis = await page.evaluate(() => {
                const analysis = {
                    ariaElements: 0,
                    wrapClass: 0,
                    pageContent: 0,
                    pageHeader: 0,
                    metricsGrid: 0,
                    metricCards: 0,
                    hasContent: false,
                    errors: []
                };
                
                // Count aria elements
                analysis.ariaElements = document.querySelectorAll('[class*="aria-"]').length;
                analysis.wrapClass = document.querySelectorAll('.wrap').length;
                analysis.pageContent = document.querySelectorAll('.aria-page-content').length;
                analysis.pageHeader = document.querySelectorAll('.aria-page-header').length;
                analysis.metricsGrid = document.querySelectorAll('.aria-metrics-grid').length;
                analysis.metricCards = document.querySelectorAll('.aria-metric-card').length;
                
                // Check for content
                const bodyText = document.body.innerText;
                analysis.hasContent = bodyText.length > 100;
                
                // Check for errors
                const errorElements = document.querySelectorAll('.error, .notice-error, .php-error');
                errorElements.forEach(el => {
                    analysis.errors.push(el.textContent.trim());
                });
                
                return analysis;
            });
            
            console.log('\n📊 PAGE ANALYSIS:');
            console.log(`   Elements with aria- classes: ${pageAnalysis.ariaElements}`);
            console.log(`   .wrap containers: ${pageAnalysis.wrapClass}`);
            console.log(`   .aria-page-content: ${pageAnalysis.pageContent}`);
            console.log(`   .aria-page-header: ${pageAnalysis.pageHeader}`);
            console.log(`   .aria-metrics-grid: ${pageAnalysis.metricsGrid}`);
            console.log(`   .aria-metric-card: ${pageAnalysis.metricCards}`);
            console.log(`   Has content: ${pageAnalysis.hasContent ? '✅ Yes' : '❌ No'}`);
            
            if (pageAnalysis.errors.length > 0) {
                console.log('\n🚨 ERRORS FOUND:');
                pageAnalysis.errors.forEach(error => console.log(`   - ${error}`));
            }
            
            // Check CSS loading
            const cssCheck = await page.evaluate(() => {
                const cards = document.querySelectorAll('.aria-metric-card');
                const cssIssues = [];
                
                if (cards.length > 0) {
                    const firstCard = cards[0];
                    const styles = window.getComputedStyle(firstCard);
                    
                    if (!styles.background || styles.background === 'rgba(0, 0, 0, 0)') {
                        cssIssues.push('Cards have no background');
                    }
                    if (!styles.border || styles.border.includes('0px')) {
                        cssIssues.push('Cards have no border');
                    }
                    if (!styles.borderRadius || styles.borderRadius === '0px') {
                        cssIssues.push('Cards have no border radius');
                    }
                    if (!styles.padding || styles.padding === '0px') {
                        cssIssues.push('Cards have no padding');
                    }
                } else {
                    cssIssues.push('No metric cards found');
                }
                
                return cssIssues;
            });
            
            if (cssCheck.length > 0) {
                console.log('\n🎨 CSS ISSUES:');
                cssCheck.forEach(issue => console.log(`   - ${issue}`));
            } else {
                console.log('\n✅ CSS appears to be loading correctly');
            }
            
            console.log(`\n${'='.repeat(50)}`);
        });
    });
});