/**
 * Simple Visual Review to Identify Design Issues
 * Taking screenshots and basic analysis of each page
 */

const { test, expect } = require('@playwright/test');

const ARIA_PAGES = [
    { name: 'Dashboard', url: 'http://localhost:8080/wp-admin/admin.php?page=aria' },
    { name: 'Personality', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-personality' },
    { name: 'Knowledge Base', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-knowledge' },
    { name: 'Content Indexing', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-content-indexing' },
    { name: 'AI Setup', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-ai-config' },
    { name: 'Design', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-design' },
    { name: 'Conversations', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-conversations' },
    { name: 'Settings', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-settings' }
];

test.describe('Simple Visual Review', () => {
    ARIA_PAGES.forEach(({ name, url }) => {
        test(`${name} - Visual Review`, async ({ page }) => {
            console.log(`\n${'📸'.repeat(30)}`);
            console.log(`📸 VISUAL REVIEW: ${name.toUpperCase()}`);
            console.log(`${'📸'.repeat(30)}`);
            
            // Login and navigate
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar');
            
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Take full page screenshot
            await page.screenshot({ 
                path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}-current-state.png`,
                fullPage: true 
            });
            
            // Basic page analysis
            const pageAnalysis = await page.evaluate(() => {
                const analysis = {
                    url: window.location.href,
                    title: document.title,
                    hasAriaClasses: document.querySelectorAll('[class*="aria-"]').length,
                    hasCards: document.querySelectorAll('.aria-metric-card').length,
                    hasGrid: !!document.querySelector('.aria-metrics-grid'),
                    hasHeader: !!document.querySelector('.aria-page-header'),
                    formElements: {
                        inputs: document.querySelectorAll('input').length,
                        buttons: document.querySelectorAll('button, .button').length,
                        selects: document.querySelectorAll('select').length,
                        textareas: document.querySelectorAll('textarea').length
                    },
                    tables: document.querySelectorAll('table').length,
                    hasWPTables: document.querySelectorAll('.wp-list-table').length,
                    contentHeight: Math.max(
                        document.body.scrollHeight,
                        document.body.offsetHeight,
                        document.documentElement.clientHeight,
                        document.documentElement.scrollHeight,
                        document.documentElement.offsetHeight
                    ),
                    specialElements: {
                        colorPickers: document.querySelectorAll('.wp-picker-container').length,
                        mediaPickers: document.querySelectorAll('.media-modal').length,
                        tabs: document.querySelectorAll('.nav-tab').length,
                        notices: document.querySelectorAll('.notice').length
                    }
                };
                
                // Check for obvious styling issues
                const cards = document.querySelectorAll('.aria-metric-card');
                analysis.cardStyling = {
                    total: cards.length,
                    withBackground: 0,
                    withBorder: 0,
                    withPadding: 0
                };
                
                cards.forEach(card => {
                    const styles = window.getComputedStyle(card);
                    if (styles.backgroundColor && styles.backgroundColor !== 'rgba(0, 0, 0, 0)') {
                        analysis.cardStyling.withBackground++;
                    }
                    if (styles.border && !styles.border.includes('0px')) {
                        analysis.cardStyling.withBorder++;
                    }
                    if (styles.padding && styles.padding !== '0px') {
                        analysis.cardStyling.withPadding++;
                    }
                });
                
                return analysis;
            });
            
            console.log(`📊 PAGE OVERVIEW:`);
            console.log(`   Title: ${pageAnalysis.title}`);
            console.log(`   Elements with aria- classes: ${pageAnalysis.hasAriaClasses}`);
            console.log(`   Has page header: ${pageAnalysis.hasHeader ? '✅' : '❌'}`);
            console.log(`   Has metrics grid: ${pageAnalysis.hasGrid ? '✅' : '❌'}`);
            console.log(`   Content height: ${pageAnalysis.contentHeight}px`);
            
            console.log(`\n🎴 CARD ANALYSIS:`);
            console.log(`   Total cards: ${pageAnalysis.cardStyling.total}`);
            console.log(`   Cards with background: ${pageAnalysis.cardStyling.withBackground}/${pageAnalysis.cardStyling.total}`);
            console.log(`   Cards with border: ${pageAnalysis.cardStyling.withBorder}/${pageAnalysis.cardStyling.total}`);
            console.log(`   Cards with padding: ${pageAnalysis.cardStyling.withPadding}/${pageAnalysis.cardStyling.total}`);
            
            console.log(`\n📋 FORM ELEMENTS:`);
            console.log(`   Inputs: ${pageAnalysis.formElements.inputs}`);
            console.log(`   Buttons: ${pageAnalysis.formElements.buttons}`);
            console.log(`   Selects: ${pageAnalysis.formElements.selects}`);
            console.log(`   Textareas: ${pageAnalysis.formElements.textareas}`);
            
            console.log(`\n📊 TABLES:`);
            console.log(`   Total tables: ${pageAnalysis.tables}`);
            console.log(`   WordPress tables: ${pageAnalysis.hasWPTables}`);
            
            console.log(`\n🎨 SPECIAL ELEMENTS:`);
            console.log(`   Color pickers: ${pageAnalysis.specialElements.colorPickers}`);
            console.log(`   Media pickers: ${pageAnalysis.specialElements.mediaPickers}`);
            console.log(`   Tabs: ${pageAnalysis.specialElements.tabs}`);
            console.log(`   Notices: ${pageAnalysis.specialElements.notices}`);
            
            console.log(`\n📸 Screenshot saved: ${name.toLowerCase().replace(/\s+/g, '-')}-current-state.png`);
            console.log(`${'📸'.repeat(30)}\n`);
        });
    });
});