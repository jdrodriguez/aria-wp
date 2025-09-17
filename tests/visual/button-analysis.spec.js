/**
 * Focused Button Visibility Analysis
 * Identifies specific hidden or undersized buttons
 */

const { test, expect } = require('@playwright/test');

const ARIA_PAGES = [
    { name: 'Dashboard', url: 'http://localhost:8080/wp-admin/admin.php?page=aria' },
    { name: 'Settings', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-settings' },
    { name: 'Design', url: 'http://localhost:8080/wp-admin/admin.php?page=aria-design' }
];

test.describe('Button Visibility Analysis', () => {
    ARIA_PAGES.forEach(({ name, url }) => {
        test(`${name} - Button Issues Investigation`, async ({ page }) => {
            console.log(`\n${'🔍'.repeat(20)}`);
            console.log(`🔍 BUTTON ANALYSIS: ${name.toUpperCase()}`);
            console.log(`${'🔍'.repeat(20)}`);
            
            // Login and navigate
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar');
            
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Detailed button analysis
            const buttonAnalysis = await page.evaluate(() => {
                const analysis = {
                    allButtons: [],
                    hiddenButtons: [],
                    smallButtons: [],
                    problematicButtons: []
                };
                
                const buttons = document.querySelectorAll('button, .button, input[type="submit"], input[type="button"]');
                
                buttons.forEach((button, index) => {
                    const buttonStyles = window.getComputedStyle(button);
                    const buttonRect = button.getBoundingClientRect();
                    
                    const buttonInfo = {
                        index: index + 1,
                        tagName: button.tagName.toLowerCase(),
                        classes: button.className,
                        text: button.textContent.trim().substring(0, 50),
                        id: button.id,
                        width: buttonRect.width,
                        height: buttonRect.height,
                        display: buttonStyles.display,
                        visibility: buttonStyles.visibility,
                        opacity: buttonStyles.opacity,
                        overflow: buttonStyles.overflow,
                        position: buttonStyles.position,
                        zIndex: buttonStyles.zIndex,
                        fontSize: buttonStyles.fontSize,
                        padding: buttonStyles.padding,
                        margin: buttonStyles.margin,
                        background: buttonStyles.background,
                        border: buttonStyles.border,
                        issues: []
                    };
                    
                    // Check for hidden/invisible buttons
                    if (buttonRect.width === 0 || buttonRect.height === 0) {
                        buttonInfo.issues.push('Zero dimensions');
                        analysis.hiddenButtons.push(buttonInfo);
                    } else if (buttonStyles.display === 'none') {
                        buttonInfo.issues.push('Display: none');
                        analysis.hiddenButtons.push(buttonInfo);
                    } else if (buttonStyles.visibility === 'hidden') {
                        buttonInfo.issues.push('Visibility: hidden');
                        analysis.hiddenButtons.push(buttonInfo);
                    } else if (parseFloat(buttonStyles.opacity) < 0.1) {
                        buttonInfo.issues.push('Opacity too low');
                        analysis.hiddenButtons.push(buttonInfo);
                    }
                    
                    // Check for small buttons
                    if (buttonRect.height < 32 && buttonRect.height > 0) {
                        buttonInfo.issues.push(`Height: ${buttonRect.height}px (< 32px)`);
                        analysis.smallButtons.push(buttonInfo);
                    }
                    
                    // Check for poor contrast/styling
                    if (buttonStyles.background === 'rgba(0, 0, 0, 0)' && 
                        buttonStyles.border.includes('0px')) {
                        buttonInfo.issues.push('No background or border');
                    }
                    
                    // Check for problematic positioning
                    if (buttonStyles.position === 'absolute' || buttonStyles.position === 'fixed') {
                        const hasNegativeZIndex = parseInt(buttonStyles.zIndex) < 0;
                        if (hasNegativeZIndex) {
                            buttonInfo.issues.push('Negative z-index');
                        }
                    }
                    
                    analysis.allButtons.push(buttonInfo);
                    
                    if (buttonInfo.issues.length > 0) {
                        analysis.problematicButtons.push(buttonInfo);
                    }
                });
                
                return analysis;
            });
            
            console.log(`\n📊 BUTTON SUMMARY:`);
            console.log(`   Total buttons: ${buttonAnalysis.allButtons.length}`);
            console.log(`   Hidden buttons: ${buttonAnalysis.hiddenButtons.length}`);
            console.log(`   Small buttons: ${buttonAnalysis.smallButtons.length}`);
            console.log(`   Problematic buttons: ${buttonAnalysis.problematicButtons.length}`);
            
            if (buttonAnalysis.hiddenButtons.length > 0) {
                console.log(`\n❌ HIDDEN BUTTONS:`);
                buttonAnalysis.hiddenButtons.forEach((btn, i) => {
                    console.log(`   ${i + 1}. ${btn.tagName} - "${btn.text}"`);
                    console.log(`      Classes: ${btn.classes}`);
                    console.log(`      Issues: ${btn.issues.join(', ')}`);
                    console.log(`      Dimensions: ${btn.width}x${btn.height}px`);
                    console.log(`      Display: ${btn.display}, Visibility: ${btn.visibility}`);
                    console.log(`      ---`);
                });
            }
            
            if (buttonAnalysis.smallButtons.length > 0) {
                console.log(`\n⚠️  SMALL BUTTONS:`);
                buttonAnalysis.smallButtons.forEach((btn, i) => {
                    console.log(`   ${i + 1}. ${btn.tagName} - "${btn.text}"`);
                    console.log(`      Classes: ${btn.classes}`);
                    console.log(`      Height: ${btn.height}px (should be ≥32px)`);
                    console.log(`      Padding: ${btn.padding}`);
                    console.log(`      Font size: ${btn.fontSize}`);
                    console.log(`      ---`);
                });
            }
            
            console.log(`\n${'🔍'.repeat(20)}\n`);
        });
    });
});