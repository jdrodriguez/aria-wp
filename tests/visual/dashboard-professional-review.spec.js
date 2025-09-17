/**
 * ARIA Dashboard Professional UI Review
 * Comprehensive analysis of the dashboard design implementation
 */

const { test, expect } = require('@playwright/test');

const WP_ADMIN_URL = 'http://localhost:8080/wp-admin';
const WP_USERNAME = 'admin';
const WP_PASSWORD = 'admin123';

test.describe('Dashboard Professional UI Review', () => {
    test('Comprehensive dashboard analysis and screenshot capture', async ({ page }) => {
        console.log('\n🎯 === ARIA DASHBOARD PROFESSIONAL REVIEW ===');
        
        // Set larger viewport for comprehensive view
        await page.setViewportSize({ width: 1400, height: 1200 });
        
        try {
            // Login to WordPress
            console.log('🔐 Logging into WordPress...');
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', WP_USERNAME);
            await page.fill('#user_pass', WP_PASSWORD);
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar', { timeout: 10000 });
            console.log('✅ Login successful');
            
            // Navigate to ARIA dashboard
            console.log('📍 Navigating to ARIA dashboard...');
            await page.goto(`${WP_ADMIN_URL}/admin.php?page=aria`);
            
            // Wait for dashboard to load
            await page.waitForSelector('.wrap.aria-dashboard', { timeout: 15000 });
            console.log('✅ Dashboard loaded');
            
            // Scroll through page to load all content
            console.log('📜 Scrolling through dashboard content...');
            await page.evaluate(async () => {
                const scrollStep = 300;
                const scrollDelay = 200;
                
                for (let i = 0; i < document.body.scrollHeight; i += scrollStep) {
                    window.scrollTo(0, i);
                    await new Promise(resolve => setTimeout(resolve, scrollDelay));
                }
                window.scrollTo(0, 0);
            });
            
            await page.waitForTimeout(1000);
            
            // Take comprehensive screenshot
            console.log('📸 Capturing full dashboard screenshot...');
            await page.screenshot({ 
                path: 'tests/visual/screenshots/dashboard-professional-review.png',
                fullPage: true 
            });
            
            console.log('\n🔍 === CSS ANALYSIS ===');
            
            // Check if CSS file is loaded
            const cssLoaded = await page.evaluate(() => {
                const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
                return links.some(link => link.href.includes('admin-style.css'));
            });
            console.log(`CSS File Loaded: ${cssLoaded ? '✅ YES' : '❌ NO'}`);
            
            // Get CSS file URL and check if accessible
            if (cssLoaded) {
                const cssUrl = await page.evaluate(() => {
                    const link = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
                        .find(link => link.href.includes('admin-style.css'));
                    return link ? link.href : null;
                });
                console.log(`CSS URL: ${cssUrl}`);
                
                // Test CSS file accessibility
                const cssResponse = await page.goto(cssUrl);
                const cssStatus = cssResponse.status();
                console.log(`CSS File Response: ${cssStatus === 200 ? '✅ Accessible' : `❌ Error ${cssStatus}`}`);
                
                // Go back to dashboard
                await page.goto(`${WP_ADMIN_URL}/admin.php?page=aria`);
                await page.waitForSelector('.wrap.aria-dashboard', { timeout: 10000 });
            }
            
            console.log('\n🎨 === VISUAL ELEMENT ANALYSIS ===');
            
            // Check main dashboard container
            const dashboardContainer = await page.locator('.aria-dashboard-container');
            const hasContainer = await dashboardContainer.count() > 0;
            console.log(`Dashboard Container: ${hasContainer ? '✅ Found' : '❌ Missing'}`);
            
            if (hasContainer) {
                const containerStyles = await dashboardContainer.first().evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        background: styles.backgroundColor,
                        padding: styles.padding,
                        maxWidth: styles.maxWidth
                    };
                });
                console.log(`  Background: ${containerStyles.background}`);
                console.log(`  Padding: ${containerStyles.padding}`);
                console.log(`  Max Width: ${containerStyles.maxWidth}`);
            }
            
            // Check metrics grid
            const metricsGrid = await page.locator('.aria-metrics-grid');
            const gridCount = await metricsGrid.count();
            console.log(`Metrics Grid: ${gridCount > 0 ? `✅ Found (${gridCount})` : '❌ Missing'}`);
            
            if (gridCount > 0) {
                const gridStyles = await metricsGrid.first().evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        display: styles.display,
                        gridTemplateColumns: styles.gridTemplateColumns,
                        gap: styles.gap
                    };
                });
                console.log(`  Display: ${gridStyles.display}`);
                console.log(`  Columns: ${gridStyles.gridTemplateColumns}`);
                console.log(`  Gap: ${gridStyles.gap}`);
            }
            
            // Check metric cards
            const metricCards = await page.locator('.aria-metric-card');
            const cardCount = await metricCards.count();
            console.log(`Metric Cards: ${cardCount > 0 ? `✅ Found (${cardCount})` : '❌ Missing'}`);
            
            if (cardCount > 0) {
                // Analyze first card styling
                const cardStyles = await metricCards.first().evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        background: styles.background,
                        borderRadius: styles.borderRadius,
                        border: styles.border,
                        padding: styles.padding,
                        boxShadow: styles.boxShadow,
                        fontFamily: styles.fontFamily
                    };
                });
                console.log(`  Background: ${cardStyles.background}`);
                console.log(`  Border Radius: ${cardStyles.borderRadius}`);
                console.log(`  Border: ${cardStyles.border}`);
                console.log(`  Box Shadow: ${cardStyles.boxShadow}`);
                console.log(`  Font Family: ${cardStyles.fontFamily}`);
            }
            
            // Check buttons
            const buttons = await page.locator('.aria-btn, .aria-action-btn, .button[class*="aria"]');
            const buttonCount = await buttons.count();
            console.log(`Professional Buttons: ${buttonCount > 0 ? `✅ Found (${buttonCount})` : '❌ Missing'}`);
            
            if (buttonCount > 0) {
                const buttonStyles = await buttons.first().evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        background: styles.background,
                        color: styles.color,
                        borderRadius: styles.borderRadius,
                        border: styles.border,
                        fontWeight: styles.fontWeight,
                        transition: styles.transition
                    };
                });
                console.log(`  Background: ${buttonStyles.background}`);
                console.log(`  Color: ${buttonStyles.color}`);
                console.log(`  Border Radius: ${buttonStyles.borderRadius}`);
                console.log(`  Font Weight: ${buttonStyles.fontWeight}`);
            }
            
            // Check section headers
            const sectionHeaders = await page.locator('.aria-section-header');
            const headerCount = await sectionHeaders.count();
            console.log(`Section Headers: ${headerCount > 0 ? `✅ Found (${headerCount})` : '❌ Missing'}`);
            
            // Check setup section
            const setupSection = await page.locator('.aria-setup-section');
            const setupCount = await setupSection.count();
            console.log(`Setup Section: ${setupCount > 0 ? `✅ Found (${setupCount})` : '❌ Missing'}`);
            
            // Check conversations section
            const conversationsSection = await page.locator('.aria-conversations-section');
            const convCount = await conversationsSection.count();
            console.log(`Conversations Section: ${convCount > 0 ? `✅ Found (${convCount})` : '❌ Missing'}`);
            
            // Check actions section
            const actionsSection = await page.locator('.aria-actions-section');
            const actionsCount = await actionsSection.count();
            console.log(`Actions Section: ${actionsCount > 0 ? `✅ Found (${actionsCount})` : '❌ Missing'}`);
            
            console.log('\n🔧 === INTERACTION TESTING ===');
            
            // Test button hover effects
            if (buttonCount > 0) {
                console.log('🖱️  Testing button hover effects...');
                const firstButton = buttons.first();
                
                // Get initial styles
                const initialStyles = await firstButton.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        transform: styles.transform,
                        boxShadow: styles.boxShadow
                    };
                });
                
                // Hover over button
                await firstButton.hover();
                await page.waitForTimeout(500);
                
                // Get hover styles
                const hoverStyles = await firstButton.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        transform: styles.transform,
                        boxShadow: styles.boxShadow
                    };
                });
                
                const hasHoverEffect = hoverStyles.transform !== initialStyles.transform || 
                                     hoverStyles.boxShadow !== initialStyles.boxShadow;
                console.log(`  Button Hover Effects: ${hasHoverEffect ? '✅ Working' : '❌ Not Working'}`);
            }
            
            // Test card hover effects
            if (cardCount > 0) {
                console.log('🖱️  Testing card hover effects...');
                const firstCard = metricCards.first();
                
                const initialCardStyles = await firstCard.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        transform: styles.transform,
                        boxShadow: styles.boxShadow
                    };
                });
                
                await firstCard.hover();
                await page.waitForTimeout(500);
                
                const hoverCardStyles = await firstCard.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        transform: styles.transform,
                        boxShadow: styles.boxShadow
                    };
                });
                
                const hasCardHoverEffect = hoverCardStyles.transform !== initialCardStyles.transform || 
                                          hoverCardStyles.boxShadow !== initialCardStyles.boxShadow;
                console.log(`  Card Hover Effects: ${hasCardHoverEffect ? '✅ Working' : '❌ Not Working'}`);
            }
            
            console.log('\n📱 === RESPONSIVE DESIGN TESTING ===');
            
            // Test tablet view
            console.log('📱 Testing tablet view (768px)...');
            await page.setViewportSize({ width: 768, height: 1024 });
            await page.waitForTimeout(500);
            
            const tabletGridStyles = await page.locator('.aria-metrics-grid').first().evaluate(el => {
                const styles = window.getComputedStyle(el);
                return styles.gridTemplateColumns;
            }).catch(() => 'not found');
            console.log(`  Tablet Grid Columns: ${tabletGridStyles}`);
            
            // Test mobile view
            console.log('📱 Testing mobile view (375px)...');
            await page.setViewportSize({ width: 375, height: 667 });
            await page.waitForTimeout(500);
            
            const mobileGridStyles = await page.locator('.aria-metrics-grid').first().evaluate(el => {
                const styles = window.getComputedStyle(el);
                return styles.gridTemplateColumns;
            }).catch(() => 'not found');
            console.log(`  Mobile Grid Columns: ${mobileGridStyles}`);
            
            // Take mobile screenshot
            await page.screenshot({ 
                path: 'tests/visual/screenshots/dashboard-mobile-review.png',
                fullPage: true 
            });
            
            // Reset to desktop
            await page.setViewportSize({ width: 1400, height: 1200 });
            
            console.log('\n🚨 === ISSUES ANALYSIS ===');
            
            // Collect all issues found
            const issues = [];
            
            if (!cssLoaded) issues.push('❌ CSS file not loading properly');
            if (!hasContainer) issues.push('❌ Dashboard container missing');
            if (gridCount === 0) issues.push('❌ Metrics grid not found');
            if (cardCount === 0) issues.push('❌ Metric cards not found');
            if (buttonCount === 0) issues.push('❌ Professional buttons not found');
            if (headerCount === 0) issues.push('❌ Section headers missing');
            if (setupCount === 0) issues.push('❌ Setup section missing');
            if (convCount === 0) issues.push('❌ Conversations section missing');
            if (actionsCount === 0) issues.push('❌ Actions section missing');
            
            if (issues.length > 0) {
                console.log('\n🔴 CRITICAL ISSUES FOUND:');
                issues.forEach(issue => console.log(`  ${issue}`));
            } else {
                console.log('✅ No critical structural issues found');
            }
            
            console.log('\n📊 === SUMMARY ===');
            console.log(`Screenshots saved:`);
            console.log(`  - tests/visual/screenshots/dashboard-professional-review.png`);
            console.log(`  - tests/visual/screenshots/dashboard-mobile-review.png`);
            console.log(`Total issues found: ${issues.length}`);
            console.log(`CSS loaded: ${cssLoaded ? 'YES' : 'NO'}`);
            console.log(`Professional elements detected: ${buttonCount + cardCount + headerCount}`);
            
            console.log('\n🎯 === REVIEW COMPLETE ===\n');
            
        } catch (error) {
            console.error('❌ Test failed:', error.message);
            
            // Take error screenshot
            await page.screenshot({ 
                path: 'tests/visual/screenshots/dashboard-error.png',
                fullPage: true 
            });
            
            throw error;
        }
    });
});