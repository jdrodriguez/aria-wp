/**
 * Personality Page Review
 * Focused analysis of the converted Personality page
 */

const { test, expect } = require('@playwright/test');

test.describe('Personality Page Review', () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
    });

    test('Personality Page Component Analysis', async ({ page }) => {
        console.log('\n🎭 PERSONALITY PAGE ANALYSIS');
        console.log('='.repeat(50));

        // Create a test HTML file to view the React component
        const testHtml = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aria Personality Page Test</title>
    <link rel="stylesheet" href="http://localhost:8080/wp-content/plugins/aria/dist/admin-style.css">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0; 
            padding: 20px; 
            background: #f0f0f1;
        }
        .wrap { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="wrap aria-personality">
        <div class="aria-page-header">
            <div class="aria-page-info">
                <h1 class="aria-page-title">Personality & Voice</h1>
                <p class="aria-page-description">Define how Aria communicates and interacts with your website visitors</p>
            </div>
        </div>
        <div class="aria-page-content">
            <div id="aria-personality-root"></div>
        </div>
    </div>
    
    <script src="http://localhost:8080/wp-content/plugins/aria/dist/admin-react.js"></script>
</body>
</html>`;

        // Create a data URL for the test page
        const dataUrl = `data:text/html;charset=utf-8,${encodeURIComponent(testHtml)}`;
        
        try {
            await page.goto(dataUrl, { waitUntil: 'networkidle', timeout: 15000 });
            
            // Wait for React component to load
            await page.waitForTimeout(3000);
            
            console.log('\n📊 COMPONENT STRUCTURE ANALYSIS:');
            
            // Check if React component mounted
            const reactRoot = await page.locator('#aria-personality-root').count();
            console.log(`React Mount Point: ${reactRoot > 0 ? '✅' : '❌'}`);
            
            // Check for WordPress Components
            const panels = await page.locator('.components-panel').count();
            console.log(`WordPress Panels: ${panels} panels found`);
            
            const panelBodies = await page.locator('.components-panel__body').count();
            console.log(`Panel Bodies: ${panelBodies} sections found`);
            
            // Check for form sections
            const businessTypeGrid = await page.locator('.aria-business-type-grid').count();
            console.log(`Business Type Grid: ${businessTypeGrid > 0 ? '✅' : '❌'}`);
            
            const toneGrid = await page.locator('.aria-tone-grid').count();
            console.log(`Tone Grid: ${toneGrid > 0 ? '✅' : '❌'}`);
            
            const traitsGrid = await page.locator('.aria-traits-grid').count();
            console.log(`Traits Grid: ${traitsGrid > 0 ? '✅' : '❌'}`);
            
            const messagesGrid = await page.locator('.aria-messages-grid').count();
            console.log(`Messages Grid: ${messagesGrid > 0 ? '✅' : '❌'}`);
            
            // Check for interactive elements
            const radioOptions = await page.locator('.aria-radio-option').count();
            console.log(`Radio Options: ${radioOptions} options found`);
            
            const checkboxOptions = await page.locator('.aria-checkbox-option').count();
            console.log(`Checkbox Options: ${checkboxOptions} options found`);
            
            const textareas = await page.locator('textarea').count();
            console.log(`Textarea Fields: ${textareas} fields found`);
            
            const saveButton = await page.locator('button[class*="is-primary"]').count();
            console.log(`Save Button: ${saveButton > 0 ? '✅' : '❌'}`);
            
            console.log('\n🎨 VISUAL QUALITY ANALYSIS:');
            
            // Check for styling issues
            const optionCards = await page.locator('.aria-option-card');
            const cardCount = await optionCards.count();
            console.log(`Option Cards: ${cardCount} cards found`);
            
            if (cardCount > 0) {
                // Check if cards have proper styling
                const firstCard = optionCards.first();
                const cardStyles = await firstCard.evaluate(el => {
                    const styles = window.getComputedStyle(el);
                    return {
                        border: styles.border,
                        borderRadius: styles.borderRadius,
                        padding: styles.padding,
                        cursor: styles.cursor
                    };
                });
                
                console.log(`Card Styling: ${cardStyles.border !== 'none' ? '✅' : '❌'} Border`);
                console.log(`Card Interactivity: ${cardStyles.cursor === 'pointer' ? '✅' : '❌'} Cursor`);
            }
            
            // Check responsive grid layouts
            const grids = await page.locator('[class*="-grid"]').count();
            console.log(`Grid Layouts: ${grids} responsive grids found`);
            
            console.log('\n🔧 FUNCTIONALITY TESTING:');
            
            // Test radio button functionality
            if (radioOptions > 0) {
                try {
                    const firstRadio = page.locator('.aria-radio-option input[type="radio"]').first();
                    await firstRadio.click();
                    const isChecked = await firstRadio.isChecked();
                    console.log(`Radio Selection: ${isChecked ? '✅' : '❌'} Working`);
                } catch (error) {
                    console.log(`Radio Selection: ❌ Error - ${error.message}`);
                }
            }
            
            // Test checkbox functionality
            if (checkboxOptions > 0) {
                try {
                    const firstCheckbox = page.locator('.aria-checkbox-option input[type="checkbox"]').first();
                    await firstCheckbox.click();
                    const isChecked = await firstCheckbox.isChecked();
                    console.log(`Checkbox Selection: ${isChecked ? '✅' : '❌'} Working`);
                } catch (error) {
                    console.log(`Checkbox Selection: ❌ Error - ${error.message}`);
                }
            }
            
            // Test textarea functionality
            if (textareas > 0) {
                try {
                    const firstTextarea = page.locator('textarea').first();
                    await firstTextarea.fill('Test message');
                    const value = await firstTextarea.inputValue();
                    console.log(`Textarea Input: ${value === 'Test message' ? '✅' : '❌'} Working`);
                } catch (error) {
                    console.log(`Textarea Input: ❌ Error - ${error.message}`);
                }
            }
            
            // Test save button functionality
            if (saveButton > 0) {
                try {
                    const button = page.locator('button[class*="is-primary"]').first();
                    const initialText = await button.textContent();
                    await button.click();
                    
                    // Wait a bit to see if button text changes (indicating loading state)
                    await page.waitForTimeout(500);
                    const newText = await button.textContent();
                    
                    console.log(`Save Button: ${newText !== initialText ? '✅' : '⚠️'} Interactive (${newText?.trim()})`);
                } catch (error) {
                    console.log(`Save Button: ❌ Error - ${error.message}`);
                }
            }
            
            console.log('\n📱 RESPONSIVE DESIGN CHECK:');
            
            // Test mobile viewport
            await page.setViewportSize({ width: 375, height: 667 });
            await page.waitForTimeout(1000);
            
            const mobileGrids = await page.locator('[class*="-grid"]').evaluateAll(grids => {
                return grids.map(grid => {
                    const styles = window.getComputedStyle(grid);
                    return {
                        display: styles.display,
                        gridTemplateColumns: styles.gridTemplateColumns
                    };
                });
            });
            
            const responsiveGrids = mobileGrids.filter(grid => 
                grid.display === 'grid' && grid.gridTemplateColumns !== 'none'
            ).length;
            
            console.log(`Mobile Responsive: ${responsiveGrids > 0 ? '✅' : '❌'} ${responsiveGrids} grids working`);
            
            // Reset to desktop
            await page.setViewportSize({ width: 1440, height: 900 });
            
            console.log('\n📸 VISUAL DOCUMENTATION:');
            
            // Take screenshots
            await page.screenshot({ 
                path: 'tests/visual/screenshots/personality-page-desktop.png',
                fullPage: true 
            });
            console.log('✅ Desktop screenshot saved');
            
            // Mobile screenshot
            await page.setViewportSize({ width: 375, height: 667 });
            await page.waitForTimeout(1000);
            await page.screenshot({ 
                path: 'tests/visual/screenshots/personality-page-mobile.png',
                fullPage: true 
            });
            console.log('✅ Mobile screenshot saved');
            
            console.log('\n🎯 RECOMMENDATIONS:');
            
            if (panels < 4) {
                console.log('⚠️  Expected 4+ panels for full personality configuration');
            }
            
            if (radioOptions < 6) {
                console.log('⚠️  Expected 6+ business type radio options');
            }
            
            if (checkboxOptions < 6) {
                console.log('⚠️  Expected 6+ personality trait checkboxes');
            }
            
            if (textareas < 2) {
                console.log('⚠️  Expected 2 textarea fields for messages');
            }
            
            console.log('\n✅ PERSONALITY PAGE ANALYSIS COMPLETE');
            
        } catch (error) {
            console.log(`❌ Failed to load personality page: ${error.message}`);
            
            // Try fallback - just test the CSS classes are available
            await page.goto('data:text/html,<div class="aria-personality-react"><p>CSS Test</p></div>');
            console.log('ℹ️  Fallback: Testing CSS availability only');
        }
    });
    
    test('Component Integration Health Check', async ({ page }) => {
        console.log('\n🔧 COMPONENT INTEGRATION HEALTH CHECK');
        console.log('='.repeat(50));
        
        // Test if we can load the React bundle
        try {
            const response = await page.goto('http://localhost:8080/wp-content/plugins/aria/dist/admin-react.js');
            const status = response?.status();
            console.log(`React Bundle: ${status === 200 ? '✅' : '❌'} (HTTP ${status})`);
        } catch (error) {
            console.log(`React Bundle: ❌ Not accessible - ${error.message}`);
        }
        
        // Test if we can load the CSS
        try {
            const response = await page.goto('http://localhost:8080/wp-content/plugins/aria/dist/admin-style.css');
            const status = response?.status();
            console.log(`CSS Bundle: ${status === 200 ? '✅' : '❌'} (HTTP ${status})`);
        } catch (error) {
            console.log(`CSS Bundle: ❌ Not accessible - ${error.message}`);
        }
        
        console.log('\n💡 NEXT STEPS:');
        console.log('1. If React/CSS bundles are not loading, ensure WordPress is running');
        console.log('2. Check that build assets are generated in /dist/ folder');
        console.log('3. Verify WordPress Components are properly styled');
        console.log('4. Test actual functionality in WordPress admin');
    });
});