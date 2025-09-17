/**
 * Dashboard Page Visual Inspection
 */

const { test, expect } = require('@playwright/test');

const WP_ADMIN_URL = 'http://localhost:8080/wp-admin';
const WP_USERNAME = 'admin';
const WP_PASSWORD = 'admin123';

test.describe('Dashboard Visual Inspection', () => {
    test('Examine Dashboard page for design issues', async ({ page }) => {
        console.log('\n=== EXAMINING DASHBOARD PAGE ===');
        
        // Login first
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', WP_USERNAME);
        await page.fill('#user_pass', WP_PASSWORD);
        await page.click('#wp-submit');
        await page.waitForSelector('#wpadminbar', { timeout: 10000 });
        
        // Navigate to Dashboard
        await page.goto(`${WP_ADMIN_URL}/admin.php?page=aria-dashboard`);
        
        // Wait for the page to load
        await page.waitForSelector('.aria-dashboard', { timeout: 10000 });
        
        // Scroll through the page to load all content
        console.log('📜 Scrolling through Dashboard page...');
        const pageHeight = await page.evaluate(() => document.body.scrollHeight);
        console.log(`   Page height: ${pageHeight}px`);
        
        await page.evaluate(async () => {
            const scrollStep = 300;
            const scrollDelay = 100;
            
            for (let i = 0; i < document.body.scrollHeight; i += scrollStep) {
                window.scrollTo(0, i);
                await new Promise(resolve => setTimeout(resolve, scrollDelay));
            }
            
            window.scrollTo(0, 0);
        });
        
        await page.waitForTimeout(500);
        
        // Take screenshot
        await page.screenshot({ 
            path: 'tests/visual/screenshots/dashboard-examination.png',
            fullPage: true 
        });
        
        console.log('\n📊 ANALYZING DASHBOARD STRUCTURE:');
        
        // Check page content container
        const pageContent = await page.locator('.aria-page-content');
        if (await pageContent.count() > 0) {
            const contentBox = await pageContent.boundingBox();
            console.log(`✅ aria-page-content found - Width: ${contentBox?.width}px`);
        } else {
            console.log(`❌ aria-page-content NOT found`);
        }
        
        // Check metrics grid
        const metricsGrid = await page.locator('.aria-metrics-grid');
        const gridCount = await metricsGrid.count();
        console.log(`📦 Found ${gridCount} aria-metrics-grid containers`);
        
        if (gridCount > 0) {
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
        
        // Check for specific dashboard components
        const components = [
            '.aria-conversations-grid',
            '.aria-actions-grid',
            '.aria-insights-grid',
            '.aria-setup-grid',
            '.aria-empty-state'
        ];
        
        for (const component of components) {
            const count = await page.locator(component).count();
            console.log(`${component}: ${count} found`);
        }
        
        // Check layout issues
        const layoutIssues = [];
        
        // Check page header
        const pageHeader = await page.locator('.aria-page-header');
        if (await pageHeader.count() === 0) {
            layoutIssues.push('Missing aria-page-header');
        }
        
        // Check text alignment
        const descriptions = await page.locator('.aria-page-description');
        if (await descriptions.count() > 0) {
            const textAlign = await descriptions.first().evaluate(el => 
                window.getComputedStyle(el).textAlign
            );
            if (textAlign !== 'left' && textAlign !== 'start') {
                layoutIssues.push(`Page description not left-aligned: ${textAlign}`);
            }
        }
        
        // Check for CSS issues
        const cssIssues = await page.evaluate(() => {
            const issues = [];
            
            // Check metric cards
            const cards = document.querySelectorAll('.aria-metric-card');
            cards.forEach((card, index) => {
                const styles = window.getComputedStyle(card);
                if (!styles.background || styles.background === 'rgba(0, 0, 0, 0)') {
                    issues.push(`Card ${index + 1} has no background`);
                }
                if (!styles.border || styles.border.includes('0px')) {
                    issues.push(`Card ${index + 1} has no border`);
                }
                if (!styles.borderRadius || styles.borderRadius === '0px') {
                    issues.push(`Card ${index + 1} has no border radius`);
                }
                if (!styles.padding || styles.padding === '0px') {
                    issues.push(`Card ${index + 1} has no padding`);
                }
            });
            
            return issues;
        });
        
        // Report issues
        if (layoutIssues.length > 0) {
            console.log('\n🚨 LAYOUT ISSUES:');
            layoutIssues.forEach(issue => console.log(`   - ${issue}`));
        }
        
        if (cssIssues.length > 0) {
            console.log('\n🎨 CSS ISSUES:');
            cssIssues.forEach(issue => console.log(`   - ${issue}`));
        }
        
        if (layoutIssues.length === 0 && cssIssues.length === 0) {
            console.log('\n✅ No major issues detected');
        }
        
        console.log('\n📸 Screenshot saved: dashboard-examination.png');
        console.log('='.repeat(50));
    });
});