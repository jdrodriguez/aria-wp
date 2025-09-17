/**
 * Button Context Analysis
 * Understanding why specific buttons are hidden and if they should be visible
 */

const { test, expect } = require('@playwright/test');

test.describe('Button Context Analysis', () => {
    test('Design Page - Button Context Investigation', async ({ page }) => {
        console.log(`\n${'🔬'.repeat(30)}`);
        console.log('🔬 BUTTON CONTEXT ANALYSIS: DESIGN PAGE');
        console.log(`${'🔬'.repeat(30)}`);
        
        // Login and navigate
        await page.goto('http://localhost:8080/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin123');
        await page.click('#wp-submit');
        await page.waitForSelector('#wpadminbar');
        
        await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-design');
        await page.waitForTimeout(3000);
        
        // Analyze problematic buttons with context
        const contextAnalysis = await page.evaluate(() => {
            const analysis = {
                colorPickers: [],
                uploadButtons: [],
                contextualInfo: []
            };
            
            // Analyze color picker buttons
            const colorPickerButtons = document.querySelectorAll('.wp-picker-clear');
            colorPickerButtons.forEach((btn, index) => {
                const parent = btn.closest('.wp-picker-container') || btn.parentElement;
                const colorInput = parent ? parent.querySelector('input[type="text"]') : null;
                const wpColorResult = parent ? parent.querySelector('.wp-color-result') : null;
                
                const info = {
                    index: index + 1,
                    hasParentContainer: !!parent,
                    parentClasses: parent ? parent.className : 'none',
                    colorInputValue: colorInput ? colorInput.value : 'none',
                    wpColorResultStyles: wpColorResult ? window.getComputedStyle(wpColorResult).display : 'none',
                    buttonPosition: {
                        top: btn.offsetTop,
                        left: btn.offsetLeft,
                        width: btn.offsetWidth,
                        height: btn.offsetHeight
                    },
                    computedStyles: {
                        display: window.getComputedStyle(btn).display,
                        visibility: window.getComputedStyle(btn).visibility,
                        opacity: window.getComputedStyle(btn).opacity,
                        position: window.getComputedStyle(btn).position,
                        width: window.getComputedStyle(btn).width,
                        height: window.getComputedStyle(btn).height
                    }
                };
                
                analysis.colorPickers.push(info);
            });
            
            // Analyze upload buttons
            const uploadButtons = document.querySelectorAll('.aria-btn-secondary');
            uploadButtons.forEach((btn, index) => {
                const parentCard = btn.closest('.aria-metric-card');
                const fileInput = parentCard ? parentCard.querySelector('input[type="file"]') : null;
                const preview = parentCard ? parentCard.querySelector('.preview, .image-preview') : null;
                
                const info = {
                    index: index + 1,
                    text: btn.textContent.trim(),
                    parentCardTitle: parentCard ? parentCard.querySelector('h3')?.textContent : 'none',
                    hasFileInput: !!fileInput,
                    hasPreview: !!preview,
                    previewContent: preview ? preview.innerHTML.length > 0 : false,
                    buttonPosition: {
                        top: btn.offsetTop,
                        left: btn.offsetLeft,
                        width: btn.offsetWidth,
                        height: btn.offsetHeight
                    },
                    computedStyles: {
                        display: window.getComputedStyle(btn).display,
                        visibility: window.getComputedStyle(btn).visibility,
                        opacity: window.getComputedStyle(btn).opacity,
                        position: window.getComputedStyle(btn).position,
                        width: window.getComputedStyle(btn).width,
                        height: window.getComputedStyle(btn).height
                    },
                    parentHTML: btn.parentElement.innerHTML.substring(0, 200)
                };
                
                analysis.uploadButtons.push(info);
            });
            
            // Check for any JavaScript that might be hiding buttons
            const scripts = Array.from(document.querySelectorAll('script')).map(script => {
                const content = script.textContent || script.innerHTML || '';
                if (content.includes('display') || content.includes('visibility') || content.includes('hide')) {
                    return content.substring(0, 100) + '...';
                }
                return null;
            }).filter(Boolean);
            
            analysis.contextualInfo = {
                totalScripts: document.querySelectorAll('script').length,
                potentialHidingScripts: scripts.length,
                isWordPressColorPickerLoaded: typeof jQuery !== 'undefined' && typeof jQuery.wp !== 'undefined',
                hasWPColorPickerCSS: !!document.querySelector('link[href*="wp-color-picker"]'),
                documentReadyState: document.readyState
            };
            
            return analysis;
        });
        
        console.log('\n🎨 COLOR PICKER BUTTONS:');
        contextAnalysis.colorPickers.forEach((picker, i) => {
            console.log(`   ${i + 1}. Color Picker Clear Button:`);
            console.log(`      Parent container: ${picker.hasParentContainer ? 'Yes' : 'No'}`);
            console.log(`      Parent classes: ${picker.parentClasses}`);
            console.log(`      Color input value: ${picker.colorInputValue}`);
            console.log(`      Position: ${picker.buttonPosition.width}x${picker.buttonPosition.height}px`);
            console.log(`      Computed width: ${picker.computedStyles.width}`);
            console.log(`      Computed height: ${picker.computedStyles.height}`);
            console.log(`      Display: ${picker.computedStyles.display}`);
            console.log(`      ---`);
        });
        
        console.log('\n📤 UPLOAD BUTTONS:');
        contextAnalysis.uploadButtons.forEach((upload, i) => {
            console.log(`   ${i + 1}. Upload Button: "${upload.text}"`);
            console.log(`      Parent card: ${upload.parentCardTitle}`);
            console.log(`      Has file input: ${upload.hasFileInput ? 'Yes' : 'No'}`);
            console.log(`      Has preview: ${upload.hasPreview ? 'Yes' : 'No'}`);
            console.log(`      Position: ${upload.buttonPosition.width}x${upload.buttonPosition.height}px`);
            console.log(`      Computed width: ${upload.computedStyles.width}`);
            console.log(`      Computed height: ${upload.computedStyles.height}`);
            console.log(`      Display: ${upload.computedStyles.display}`);
            console.log(`      Parent HTML preview: ${upload.parentHTML}`);
            console.log(`      ---`);
        });
        
        console.log('\n📋 CONTEXTUAL INFO:');
        console.log(`   Total scripts: ${contextAnalysis.contextualInfo.totalScripts}`);
        console.log(`   Scripts potentially hiding elements: ${contextAnalysis.contextualInfo.potentialHidingScripts}`);
        console.log(`   WordPress color picker loaded: ${contextAnalysis.contextualInfo.isWordPressColorPickerLoaded ? 'Yes' : 'No'}`);
        console.log(`   WP Color Picker CSS: ${contextAnalysis.contextualInfo.hasWPColorPickerCSS ? 'Yes' : 'No'}`);
        console.log(`   Document ready state: ${contextAnalysis.contextualInfo.documentReadyState}`);
        
        console.log(`\n${'🔬'.repeat(30)}\n`);
        
        // Take a screenshot to visually examine the design page
        await page.screenshot({ 
            path: 'tests/visual/screenshots/design-page-button-context.png',
            fullPage: true 
        });
        console.log('📸 Screenshot saved: design-page-button-context.png');
    });
});