/**
 * Playwright Visual Testing for Aria Admin Pages
 * 
 * This script examines each admin page to identify design issues
 */

const { test, expect } = require('@playwright/test');

// WordPress admin credentials and URL
const WP_ADMIN_URL = 'http://localhost:8080/wp-admin';
const WP_USERNAME = 'admin'; // WordPress username
const WP_PASSWORD = 'admin123'; // WordPress password

// List of Aria admin pages to examine
const ADMIN_PAGES = [
    {
        name: 'Dashboard',
        url: '/admin.php?page=aria-dashboard',
        selector: '.aria-dashboard'
    },
    {
        name: 'AI Configuration', 
        url: '/admin.php?page=aria-ai-config',
        selector: '.aria-ai-config'
    },
    {
        name: 'Conversations',
        url: '/admin.php?page=aria-conversations', 
        selector: '.aria-conversations'
    },
    {
        name: 'Design & Appearance',
        url: '/admin.php?page=aria-design',
        selector: '.aria-design'
    },
    {
        name: 'Knowledge Base',
        url: '/admin.php?page=aria-knowledge',
        selector: '.aria-knowledge'
    },
    {
        name: 'Personality & Voice',
        url: '/admin.php?page=aria-personality',
        selector: '.aria-personality'
    },
    {
        name: 'Content Indexing',
        url: '/admin.php?page=aria-content-indexing',
        selector: '.aria-content-indexing'
    },
    {
        name: 'Settings',
        url: '/admin.php?page=aria-settings',
        selector: '.aria-settings'
    }
];

test.describe('Aria Admin Pages Visual Inspection', () => {
    test.beforeEach(async ({ page }) => {
        // Login to WordPress admin
        await page.goto(`${WP_ADMIN_URL}/wp-login.php`);
        
        // Fill login form
        await page.fill('#user_login', WP_USERNAME);
        await page.fill('#user_pass', WP_PASSWORD);
        await page.click('#wp-submit');
        
        // Wait for dashboard to load
        await page.waitForSelector('#wpadminbar');
    });

    // Test each admin page individually
    ADMIN_PAGES.forEach(({ name, url, selector }) => {
        test(`${name} Page Visual Inspection`, async ({ page }) => {
            console.log(`\n=== EXAMINING ${name.toUpperCase()} PAGE ===`);
            
            // Navigate to the page
            await page.goto(`${WP_ADMIN_URL}${url}`);
            
            // Wait for the page to load
            try {
                await page.waitForSelector(selector, { timeout: 10000 });
            } catch (error) {
                console.log(`❌ Page selector '${selector}' not found for ${name}`);
                await page.screenshot({ 
                    path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}-error.png`,
                    fullPage: true 
                });
                return;
            }
            
            // Scroll through the page to ensure all content is loaded
            console.log(`📜 Scrolling through ${name} page to load all content...`);
            
            // Get page height and scroll through it
            const pageHeight = await page.evaluate(() => document.body.scrollHeight);
            console.log(`   Page height: ${pageHeight}px`);
            
            // Scroll to bottom slowly to load any lazy content
            await page.evaluate(async () => {
                const scrollStep = 300;
                const scrollDelay = 100;
                
                for (let i = 0; i < document.body.scrollHeight; i += scrollStep) {
                    window.scrollTo(0, i);
                    await new Promise(resolve => setTimeout(resolve, scrollDelay));
                }
                
                // Scroll back to top for screenshot
                window.scrollTo(0, 0);
            });
            
            // Wait a moment for any animations to complete
            await page.waitForTimeout(500);
            
            // Take a full page screenshot
            await page.screenshot({ 
                path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}.png`,
                fullPage: true 
            });
            
            // Analyze page layout and structure
            console.log(`\n📊 ANALYZING ${name} PAGE STRUCTURE:`);
            
            // Check if aria-page-content exists and get its width
            const pageContent = await page.locator('.aria-page-content');
            if (await pageContent.count() > 0) {
                const contentBox = await pageContent.boundingBox();
                console.log(`✅ aria-page-content found - Width: ${contentBox?.width}px`);
            } else {
                console.log(`❌ aria-page-content NOT found`);
            }
            
            // Check if aria-metrics-grid exists and analyze its layout
            const metricsGrid = await page.locator('.aria-metrics-grid');
            const gridCount = await metricsGrid.count();
            console.log(`📦 Found ${gridCount} aria-metrics-grid containers`);
            
            if (gridCount > 0) {
                // Get grid properties
                const gridStyles = await metricsGrid.first().evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        display: styles.display,
                        gridTemplateColumns: styles.gridTemplateColumns,
                        gap: styles.gap,
                        marginBottom: styles.marginBottom
                    };
                });
                console.log(`   Grid display: ${gridStyles.display}`);
                console.log(`   Grid columns: ${gridStyles.gridTemplateColumns}`);
                console.log(`   Grid gap: ${gridStyles.gap}`);
                console.log(`   Margin bottom: ${gridStyles.marginBottom}`);
            }
            
            // Count metric cards
            const metricCards = await page.locator('.aria-metric-card');
            const cardCount = await metricCards.count();
            console.log(`🎴 Found ${cardCount} metric cards`);
            
            // Check for layout issues
            const layoutIssues = [];
            
            // Check if cards are properly aligned
            if (cardCount > 1) {
                const cardBoxes = [];
                for (let i = 0; i < Math.min(cardCount, 4); i++) {
                    const box = await metricCards.nth(i).boundingBox();
                    if (box) cardBoxes.push(box);
                }
                
                // Check if cards have consistent heights in same row
                if (cardBoxes.length >= 2) {
                    const heightDiff = Math.abs(cardBoxes[0].height - cardBoxes[1].height);
                    if (heightDiff > 10) {
                        layoutIssues.push(`Card height inconsistency: ${heightDiff}px difference`);
                    }
                }
            }
            
            // Check header structure
            const pageHeader = await page.locator('.aria-page-header');
            if (await pageHeader.count() > 0) {
                const headerStyles = await pageHeader.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        display: styles.display,
                        alignItems: styles.alignItems,
                        marginBottom: styles.marginBottom,
                        padding: styles.padding
                    };
                });
                console.log(`📋 Header styles: ${JSON.stringify(headerStyles)}`);
            } else {
                layoutIssues.push('Missing aria-page-header');
            }
            
            // Check for text alignment issues
            const descriptions = await page.locator('.aria-page-description');
            if (await descriptions.count() > 0) {
                const textAlign = await descriptions.first().evaluate(el => 
                    window.getComputedStyle(el).textAlign
                );
                if (textAlign !== 'left' && textAlign !== 'start') {
                    layoutIssues.push(`Page description not left-aligned: ${textAlign}`);
                }
            }
            
            // Report layout issues
            if (layoutIssues.length > 0) {
                console.log(`\n🚨 LAYOUT ISSUES FOUND:`);
                layoutIssues.forEach(issue => console.log(`   - ${issue}`));
            } else {
                console.log(`\n✅ No major layout issues detected`);
            }
            
            // Check for missing CSS by looking for unstyled elements
            const unstyledElements = await page.evaluate(() => {
                const issues = [];
                
                // Check for elements with no computed styles
                const cards = document.querySelectorAll('.aria-metric-card');
                cards.forEach((card, index) => {
                    const styles = window.getComputedStyle(card);
                    if (styles.background === 'rgba(0, 0, 0, 0)' || styles.background === 'transparent') {
                        issues.push(`Card ${index + 1} has no background`);
                    }
                    if (styles.border === '0px none rgb(0, 0, 0)' || !styles.border.includes('solid')) {
                        issues.push(`Card ${index + 1} has no border`);
                    }
                });
                
                return issues;
            });
            
            if (unstyledElements.length > 0) {
                console.log(`\n🎨 STYLING ISSUES:`);
                unstyledElements.forEach(issue => console.log(`   - ${issue}`));
            }
            
            console.log(`\n📸 Screenshot saved: ${name.toLowerCase().replace(/\s+/g, '-')}.png`);
            console.log(`${'='.repeat(50)}\n`);
        });
    });
    
    // Overview test to compare all pages
    test('Page Width Consistency Check', async ({ page }) => {
        console.log('\n=== PAGE WIDTH CONSISTENCY CHECK ===');
        
        const pageWidths = [];
        
        for (const { name, url } of ADMIN_PAGES) {
            await page.goto(`${WP_ADMIN_URL}${url}`);
            
            try {
                await page.waitForSelector('.aria-page-content', { timeout: 5000 });
                const width = await page.locator('.aria-page-content').evaluate(el => 
                    el.getBoundingClientRect().width
                );
                pageWidths.push({ name, width });
                console.log(`${name}: ${width}px`);
            } catch (error) {
                console.log(`${name}: ERROR - Could not measure width`);
            }
        }
        
        // Check for width consistency
        const widths = pageWidths.map(p => p.width).filter(w => w > 0);
        const minWidth = Math.min(...widths);
        const maxWidth = Math.max(...widths);
        const widthDiff = maxWidth - minWidth;
        
        console.log(`\nWidth range: ${minWidth}px - ${maxWidth}px (difference: ${widthDiff}px)`);
        
        if (widthDiff > 5) {
            console.log(`🚨 Width inconsistency detected! Pages should have consistent widths.`);
        } else {
            console.log(`✅ Page widths are consistent.`);
        }
    });
});