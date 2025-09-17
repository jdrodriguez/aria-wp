/**
 * Professional UI/UX Design Audit for Aria Admin Pages
 * Examining each page through the lens of professional design principles
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

test.describe('Professional Design Audit', () => {
    ARIA_PAGES.forEach(({ name, url }) => {
        test(`${name} - Design & UX Audit`, async ({ page }) => {
            console.log(`\n${'🎨'.repeat(20)}`);
            console.log(`🎨 DESIGN AUDIT: ${name.toUpperCase()}`);
            console.log(`${'🎨'.repeat(20)}`);
            
            // Login and navigate
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar');
            
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Take high-quality screenshot for design review
            await page.screenshot({ 
                path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}-design-audit.png`,
                fullPage: true 
            });
            
            // 1. HEADER DESIGN AUDIT
            console.log('\n📋 HEADER DESIGN ANALYSIS:');
            const headerAudit = await page.evaluate(() => {
                const audit = { issues: [], recommendations: [] };
                
                const header = document.querySelector('.aria-page-header');
                if (!header) {
                    audit.issues.push('❌ No page header found');
                    return audit;
                }
                
                const headerStyles = window.getComputedStyle(header);
                const headerRect = header.getBoundingClientRect();
                
                // Check header layout
                audit.headerHeight = headerRect.height;
                audit.headerPadding = headerStyles.padding;
                audit.headerMargin = headerStyles.marginBottom;
                
                // Check logo positioning
                const logo = document.querySelector('.aria-admin-logo');
                const pageInfo = document.querySelector('.aria-page-info');
                
                if (logo && pageInfo) {
                    const logoRect = logo.getBoundingClientRect();
                    const infoRect = pageInfo.getBoundingClientRect();
                    
                    // Check if logo and text are side-by-side (bad) or stacked (good)
                    const isHorizontal = Math.abs(logoRect.top - infoRect.top) < 10;
                    if (isHorizontal) {
                        audit.issues.push('❌ Logo and text are side-by-side instead of stacked');
                        audit.recommendations.push('✨ Stack logo above page title for better hierarchy');
                    }
                    
                    // Check spacing between logo and text
                    const spacing = Math.abs(infoRect.top - logoRect.bottom);
                    audit.logoToTextSpacing = spacing;
                    
                    if (spacing < 16) {
                        audit.issues.push('❌ Insufficient spacing between logo and page title');
                        audit.recommendations.push('✨ Add 24-32px spacing between logo and title');
                    }
                }
                
                // Check title typography
                const title = document.querySelector('.aria-page-title');
                if (title) {
                    const titleStyles = window.getComputedStyle(title);
                    audit.titleFontSize = titleStyles.fontSize;
                    audit.titleLineHeight = titleStyles.lineHeight;
                    audit.titleMargin = titleStyles.margin;
                    
                    // Check line height
                    const fontSize = parseFloat(titleStyles.fontSize);
                    const lineHeight = parseFloat(titleStyles.lineHeight);
                    const lineHeightRatio = lineHeight / fontSize;
                    
                    if (lineHeightRatio < 1.2 || lineHeightRatio > 1.6) {
                        audit.issues.push(`❌ Title line height ratio is ${lineHeightRatio.toFixed(2)} (should be 1.2-1.6)`);
                        audit.recommendations.push('✨ Set title line height to 1.3-1.4 for better readability');
                    }
                }
                
                // Check description typography
                const description = document.querySelector('.aria-page-description');
                if (description) {
                    const descStyles = window.getComputedStyle(description);
                    audit.descFontSize = descStyles.fontSize;
                    audit.descLineHeight = descStyles.lineHeight;
                    audit.descColor = descStyles.color;
                    
                    // Check line height
                    const fontSize = parseFloat(descStyles.fontSize);
                    const lineHeight = parseFloat(descStyles.lineHeight);
                    const lineHeightRatio = lineHeight / fontSize;
                    
                    if (lineHeightRatio < 1.4 || lineHeightRatio > 1.8) {
                        audit.issues.push(`❌ Description line height ratio is ${lineHeightRatio.toFixed(2)} (should be 1.4-1.8)`);
                        audit.recommendations.push('✨ Set description line height to 1.5-1.6 for better readability');
                    }
                }
                
                return audit;
            });
            
            // Display header audit results
            console.log(`   Header height: ${headerAudit.headerHeight}px`);
            console.log(`   Header padding: ${headerAudit.headerPadding}`);
            console.log(`   Header margin bottom: ${headerAudit.headerMargin}`);
            if (headerAudit.logoToTextSpacing) {
                console.log(`   Logo to text spacing: ${headerAudit.logoToTextSpacing}px`);
            }
            
            if (headerAudit.issues.length > 0) {
                console.log('   🚨 HEADER ISSUES:');
                headerAudit.issues.forEach(issue => console.log(`     ${issue}`));
            }
            
            if (headerAudit.recommendations.length > 0) {
                console.log('   💡 HEADER RECOMMENDATIONS:');
                headerAudit.recommendations.forEach(rec => console.log(`     ${rec}`));
            }
            
            // 2. TYPOGRAPHY AUDIT
            console.log('\n📝 TYPOGRAPHY ANALYSIS:');
            const typographyAudit = await page.evaluate(() => {
                const audit = { issues: [], recommendations: [] };
                
                // Check all text elements
                const textElements = document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span, div, label, button');
                let smallTextCount = 0;
                let poorLineHeightCount = 0;
                
                textElements.forEach(el => {
                    const styles = window.getComputedStyle(el);
                    const fontSize = parseFloat(styles.fontSize);
                    const lineHeight = parseFloat(styles.lineHeight);
                    
                    // Check for too small text
                    if (fontSize < 12) {
                        smallTextCount++;
                    }
                    
                    // Check line height
                    if (lineHeight > 0 && fontSize > 0) {
                        const ratio = lineHeight / fontSize;
                        if (ratio < 1.2 || ratio > 2.0) {
                            poorLineHeightCount++;
                        }
                    }
                });
                
                if (smallTextCount > 0) {
                    audit.issues.push(`❌ ${smallTextCount} elements have text smaller than 12px`);
                    audit.recommendations.push('✨ Use minimum 12px font size for accessibility');
                }
                
                if (poorLineHeightCount > 0) {
                    audit.issues.push(`❌ ${poorLineHeightCount} elements have poor line height ratios`);
                    audit.recommendations.push('✨ Set line heights between 1.2-2.0 times font size');
                }
                
                return audit;
            });
            
            if (typographyAudit.issues.length > 0) {
                console.log('   🚨 TYPOGRAPHY ISSUES:');
                typographyAudit.issues.forEach(issue => console.log(`     ${issue}`));
            }
            
            if (typographyAudit.recommendations.length > 0) {
                console.log('   💡 TYPOGRAPHY RECOMMENDATIONS:');
                typographyAudit.recommendations.forEach(rec => console.log(`     ${rec}`));
            }
            
            // 3. CARD DESIGN AUDIT
            console.log('\n🎴 CARD DESIGN ANALYSIS:');
            const cardAudit = await page.evaluate(() => {
                const audit = { issues: [], recommendations: [] };
                
                const cards = document.querySelectorAll('.aria-metric-card');
                audit.cardCount = cards.length;
                
                if (cards.length === 0) {
                    audit.issues.push('❌ No metric cards found');
                    return audit;
                }
                
                cards.forEach((card, index) => {
                    const cardStyles = window.getComputedStyle(card);
                    const cardRect = card.getBoundingClientRect();
                    
                    // Check card padding
                    const padding = parseFloat(cardStyles.paddingTop);
                    if (padding < 16) {
                        audit.issues.push(`❌ Card ${index + 1} has insufficient padding (${padding}px)`);
                    }
                    
                    // Check card spacing
                    const marginBottom = parseFloat(cardStyles.marginBottom);
                    if (marginBottom > 0 && marginBottom < 16) {
                        audit.issues.push(`❌ Card ${index + 1} has insufficient bottom margin (${marginBottom}px)`);
                    }
                    
                    // Check card header
                    const cardHeader = card.querySelector('.metric-header');
                    if (cardHeader) {
                        const headerStyles = window.getComputedStyle(cardHeader);
                        const headerMarginBottom = parseFloat(headerStyles.marginBottom);
                        
                        if (headerMarginBottom < 16) {
                            audit.issues.push(`❌ Card ${index + 1} header has insufficient bottom margin (${headerMarginBottom}px)`);
                        }
                    }
                    
                    // Check card content
                    const cardContent = card.querySelector('.metric-content');
                    if (cardContent) {
                        const contentItems = cardContent.querySelectorAll('.metric-item');
                        contentItems.forEach((item, itemIndex) => {
                            const itemStyles = window.getComputedStyle(item);
                            const itemPadding = parseFloat(itemStyles.paddingTop);
                            
                            if (itemPadding < 8) {
                                audit.issues.push(`❌ Card ${index + 1} content item ${itemIndex + 1} has insufficient padding`);
                            }
                        });
                    }
                });
                
                if (audit.issues.length === 0) {
                    audit.recommendations.push('✅ Card spacing and padding look good');
                } else {
                    audit.recommendations.push('✨ Increase card padding to 24px minimum');
                    audit.recommendations.push('✨ Add 24px margin between card header and content');
                    audit.recommendations.push('✨ Ensure minimum 16px padding on card content items');
                }
                
                return audit;
            });
            
            console.log(`   Cards found: ${cardAudit.cardCount}`);
            if (cardAudit.issues.length > 0) {
                console.log('   🚨 CARD ISSUES:');
                cardAudit.issues.forEach(issue => console.log(`     ${issue}`));
            }
            
            if (cardAudit.recommendations.length > 0) {
                console.log('   💡 CARD RECOMMENDATIONS:');
                cardAudit.recommendations.forEach(rec => console.log(`     ${rec}`));
            }
            
            // 4. BUTTON & INTERACTION AUDIT
            console.log('\n🔘 BUTTON & INTERACTION ANALYSIS:');
            const buttonAudit = await page.evaluate(() => {
                const audit = { issues: [], recommendations: [] };
                
                const buttons = document.querySelectorAll('button, .button, input[type="submit"], input[type="button"]');
                audit.buttonCount = buttons.length;
                
                let hiddenButtons = 0;
                let smallButtons = 0;
                let poorContrastButtons = 0;
                
                buttons.forEach((button, index) => {
                    const buttonStyles = window.getComputedStyle(button);
                    const buttonRect = button.getBoundingClientRect();
                    
                    // Check visibility
                    if (buttonRect.width === 0 || buttonRect.height === 0 || buttonStyles.display === 'none' || buttonStyles.visibility === 'hidden') {
                        hiddenButtons++;
                    }
                    
                    // Check size
                    if (buttonRect.height < 32) {
                        smallButtons++;
                    }
                    
                    // Check if button has proper styling
                    const hasBackground = buttonStyles.backgroundColor !== 'rgba(0, 0, 0, 0)' && buttonStyles.backgroundColor !== 'transparent';
                    const hasBorder = buttonStyles.border !== '0px none rgb(0, 0, 0)';
                    
                    if (!hasBackground && !hasBorder) {
                        poorContrastButtons++;
                    }
                });
                
                if (hiddenButtons > 0) {
                    audit.issues.push(`❌ ${hiddenButtons} buttons are hidden or have zero dimensions`);
                    audit.recommendations.push('✨ Ensure all buttons are visible and properly sized');
                }
                
                if (smallButtons > 0) {
                    audit.issues.push(`❌ ${smallButtons} buttons are smaller than 32px height`);
                    audit.recommendations.push('✨ Make buttons at least 32px tall for better usability');
                }
                
                if (poorContrastButtons > 0) {
                    audit.issues.push(`❌ ${poorContrastButtons} buttons have poor visual contrast`);
                    audit.recommendations.push('✨ Add background colors or borders to make buttons more visible');
                }
                
                return audit;
            });
            
            console.log(`   Buttons found: ${buttonAudit.buttonCount}`);
            if (buttonAudit.issues.length > 0) {
                console.log('   🚨 BUTTON ISSUES:');
                buttonAudit.issues.forEach(issue => console.log(`     ${issue}`));
            }
            
            if (buttonAudit.recommendations.length > 0) {
                console.log('   💡 BUTTON RECOMMENDATIONS:');
                buttonAudit.recommendations.forEach(rec => console.log(`     ${rec}`));
            }
            
            // 5. VISUAL HIERARCHY AUDIT
            console.log('\n🏗️  VISUAL HIERARCHY ANALYSIS:');
            const hierarchyAudit = await page.evaluate(() => {
                const audit = { issues: [], recommendations: [] };
                
                // Check heading hierarchy
                const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                const headingSizes = [];
                
                headings.forEach(heading => {
                    const styles = window.getComputedStyle(heading);
                    const fontSize = parseFloat(styles.fontSize);
                    headingSizes.push({ tag: heading.tagName, size: fontSize });
                });
                
                // Check if heading sizes follow proper hierarchy
                let hierarchyBroken = false;
                for (let i = 0; i < headingSizes.length - 1; i++) {
                    const current = headingSizes[i];
                    const next = headingSizes[i + 1];
                    
                    if (current.tag === 'H1' && next.tag === 'H2' && current.size <= next.size) {
                        hierarchyBroken = true;
                        break;
                    }
                }
                
                if (hierarchyBroken) {
                    audit.issues.push('❌ Heading hierarchy is broken (H1 should be larger than H2, etc.)');
                    audit.recommendations.push('✨ Ensure H1 > H2 > H3 in font size for proper hierarchy');
                }
                
                // Check for proper spacing between sections
                const sections = document.querySelectorAll('.aria-metric-card, .aria-page-header, .aria-page-content > *');
                let insufficientSpacing = 0;
                
                sections.forEach((section, index) => {
                    if (index > 0) {
                        const prevSection = sections[index - 1];
                        const currentRect = section.getBoundingClientRect();
                        const prevRect = prevSection.getBoundingClientRect();
                        
                        const spacing = currentRect.top - prevRect.bottom;
                        if (spacing < 16) {
                            insufficientSpacing++;
                        }
                    }
                });
                
                if (insufficientSpacing > 0) {
                    audit.issues.push(`❌ ${insufficientSpacing} sections have insufficient spacing`);
                    audit.recommendations.push('✨ Add minimum 24px spacing between major sections');
                }
                
                return audit;
            });
            
            if (hierarchyAudit.issues.length > 0) {
                console.log('   🚨 HIERARCHY ISSUES:');
                hierarchyAudit.issues.forEach(issue => console.log(`     ${issue}`));
            }
            
            if (hierarchyAudit.recommendations.length > 0) {
                console.log('   💡 HIERARCHY RECOMMENDATIONS:');
                hierarchyAudit.recommendations.forEach(rec => console.log(`     ${rec}`));
            }
            
            // 6. OVERALL DESIGN SCORE
            const totalIssues = [
                ...headerAudit.issues,
                ...typographyAudit.issues,
                ...cardAudit.issues,
                ...buttonAudit.issues,
                ...hierarchyAudit.issues
            ].length;
            
            console.log('\n🏆 OVERALL DESIGN ASSESSMENT:');
            if (totalIssues === 0) {
                console.log('   ✅ Excellent! No design issues found.');
            } else if (totalIssues <= 5) {
                console.log(`   ⚠️  Good with minor issues (${totalIssues} issues found)`);
            } else if (totalIssues <= 10) {
                console.log(`   ⚠️  Needs improvement (${totalIssues} issues found)`);
            } else {
                console.log(`   ❌ Significant design issues (${totalIssues} issues found)`);
            }
            
            console.log(`\n${'🎨'.repeat(20)}\n`);
        });
    });
});