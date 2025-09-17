/**
 * Comprehensive Frontend Design Review
 * Examining actual visual appearance to identify missing design implementations
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

test.describe('Frontend Design Review', () => {
    ARIA_PAGES.forEach(({ name, url }) => {
        test(`${name} - Frontend Visual Review`, async ({ page }) => {
            console.log(`\n${'🎨'.repeat(25)}`);
            console.log(`🎨 FRONTEND REVIEW: ${name.toUpperCase()}`);
            console.log(`${'🎨'.repeat(25)}`);
            
            // Login and navigate
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar');
            
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Take high-quality screenshots for visual inspection
            await page.screenshot({ 
                path: `tests/visual/screenshots/${name.toLowerCase().replace(/\s+/g, '-')}-frontend-review.png`,
                fullPage: true 
            });
            
            // Comprehensive visual analysis
            const visualAnalysis = await page.evaluate(() => {
                const analysis = {
                    pageStructure: {
                        hasAriaWrapper: false,
                        hasPageHeader: false,
                        hasPageContent: false,
                        hasMetricsGrid: false,
                        cardCount: 0
                    },
                    stylingIssues: {
                        unstyledElements: [],
                        missingBackgrounds: [],
                        poorSpacing: [],
                        typographyIssues: [],
                        colorIssues: []
                    },
                    componentAnalysis: {
                        forms: [],
                        tables: [],
                        buttons: [],
                        inputs: [],
                        selects: []
                    },
                    visualProblems: []
                };
                
                // Check page structure
                const ariaWrapper = document.querySelector('.wrap[class*="aria-"]');
                analysis.pageStructure.hasAriaWrapper = !!ariaWrapper;
                analysis.pageStructure.hasPageHeader = !!document.querySelector('.aria-page-header');
                analysis.pageStructure.hasPageContent = !!document.querySelector('.aria-page-content');
                analysis.pageStructure.hasMetricsGrid = !!document.querySelector('.aria-metrics-grid');
                analysis.pageStructure.cardCount = document.querySelectorAll('.aria-metric-card').length;
                
                // Check for unstyled elements
                const allElements = document.querySelectorAll('*');
                allElements.forEach(el => {
                    const styles = window.getComputedStyle(el);
                    const tagName = el.tagName.toLowerCase();
                    const classes = el.className;
                    
                    // Check for elements that look unstyled
                    if (tagName === 'table' && !classes.includes('aria-') && !classes.includes('wp-list-table')) {
                        const hasBasicStyling = styles.border !== '0px none rgb(0, 0, 0)' || 
                                              styles.backgroundColor !== 'rgba(0, 0, 0, 0)';
                        if (!hasBasicStyling) {
                            analysis.stylingIssues.unstyledElements.push({
                                tag: tagName,
                                classes: classes,
                                text: el.textContent.substring(0, 50)
                            });
                        }
                    }
                    
                    // Check for missing card backgrounds
                    if (classes.includes('aria-metric-card')) {
                        if (styles.backgroundColor === 'rgba(0, 0, 0, 0)' || 
                            styles.border.includes('0px')) {
                            analysis.stylingIssues.missingBackgrounds.push({
                                element: 'metric-card',
                                background: styles.backgroundColor,
                                border: styles.border
                            });
                        }
                    }
                    
                    // Check form elements
                    if (['input', 'select', 'textarea', 'button'].includes(tagName)) {
                        const elementInfo = {
                            tag: tagName,
                            type: el.type || 'none',
                            classes: classes,
                            styles: {
                                height: styles.height,
                                padding: styles.padding,
                                border: styles.border,
                                borderRadius: styles.borderRadius,
                                fontSize: styles.fontSize,
                                background: styles.backgroundColor
                            },
                            hasAriaClass: classes.includes('aria-'),
                            looksStyled: false
                        };
                        
                        // Determine if element looks properly styled
                        elementInfo.looksStyled = (
                            parseFloat(styles.height) >= 30 &&
                            styles.padding !== '0px' &&
                            !styles.border.includes('0px') &&
                            styles.fontSize !== '0px'
                        );
                        
                        analysis.componentAnalysis[tagName + 's'].push(elementInfo);
                    }
                });
                
                // Check for spacing issues
                const sections = document.querySelectorAll('.aria-metric-card, .aria-page-section, .form-table');
                sections.forEach((section, index) => {
                    const rect = section.getBoundingClientRect();
                    const styles = window.getComputedStyle(section);
                    
                    if (parseFloat(styles.marginBottom) < 16 && index < sections.length - 1) {
                        analysis.stylingIssues.poorSpacing.push({
                            element: section.className,
                            marginBottom: styles.marginBottom,
                            index: index
                        });
                    }
                });
                
                // Check typography
                const textElements = document.querySelectorAll('h1, h2, h3, h4, p, span, label, td, th');
                let smallTextCount = 0;
                let inconsistentHeadings = 0;
                
                textElements.forEach(el => {
                    const styles = window.getComputedStyle(el);
                    const fontSize = parseFloat(styles.fontSize);
                    
                    if (fontSize < 12) {
                        smallTextCount++;
                    }
                    
                    // Check heading consistency
                    if (el.tagName.match(/H[1-6]/)) {
                        const expectedSizes = { H1: 24, H2: 20, H3: 18, H4: 16, H5: 14, H6: 14 };
                        const expected = expectedSizes[el.tagName];
                        if (Math.abs(fontSize - expected) > 2) {
                            inconsistentHeadings++;
                        }
                    }
                });
                
                analysis.stylingIssues.typographyIssues = {
                    smallTextCount,
                    inconsistentHeadings
                };
                
                // Overall visual assessment
                const hasProperStructure = analysis.pageStructure.hasAriaWrapper && 
                                         analysis.pageStructure.hasPageHeader;
                const hasStyledCards = analysis.pageStructure.cardCount > 0 && 
                                     analysis.stylingIssues.missingBackgrounds.length === 0;
                const hasGoodTypography = smallTextCount < 10 && inconsistentHeadings < 3;
                
                if (!hasProperStructure) {
                    analysis.visualProblems.push('Missing proper page structure (wrapper/header)');
                }
                if (!hasStyledCards && analysis.pageStructure.cardCount > 0) {
                    analysis.visualProblems.push('Cards missing proper styling');
                }
                if (!hasGoodTypography) {
                    analysis.visualProblems.push('Typography inconsistencies detected');
                }
                if (analysis.stylingIssues.unstyledElements.length > 0) {
                    analysis.visualProblems.push('Unstyled elements detected');
                }
                
                return analysis;
            });
            
            // Report findings
            console.log('📊 PAGE STRUCTURE:');
            console.log(`   Aria wrapper: ${visualAnalysis.pageStructure.hasAriaWrapper ? '✅' : '❌'}`);
            console.log(`   Page header: ${visualAnalysis.pageStructure.hasPageHeader ? '✅' : '❌'}`);
            console.log(`   Page content: ${visualAnalysis.pageStructure.hasPageContent ? '✅' : '❌'}`);
            console.log(`   Metrics grid: ${visualAnalysis.pageStructure.hasMetricsGrid ? '✅' : '❌'}`);
            console.log(`   Card count: ${visualAnalysis.pageStructure.cardCount}`);
            
            if (visualAnalysis.stylingIssues.unstyledElements.length > 0) {
                console.log('\n❌ UNSTYLED ELEMENTS:');
                visualAnalysis.stylingIssues.unstyledElements.forEach((el, i) => {
                    console.log(`   ${i + 1}. ${el.tag} - "${el.text}"`);
                    console.log(`      Classes: ${el.classes || 'none'}`);
                });
            }
            
            if (visualAnalysis.stylingIssues.missingBackgrounds.length > 0) {
                console.log('\n❌ MISSING CARD STYLING:');
                visualAnalysis.stylingIssues.missingBackgrounds.forEach((card, i) => {
                    console.log(`   ${i + 1}. ${card.element}`);
                    console.log(`      Background: ${card.background}`);
                    console.log(`      Border: ${card.border}`);
                });
            }
            
            console.log('\n📝 TYPOGRAPHY ANALYSIS:');
            console.log(`   Small text elements: ${visualAnalysis.stylingIssues.typographyIssues.smallTextCount}`);
            console.log(`   Inconsistent headings: ${visualAnalysis.stylingIssues.typographyIssues.inconsistentHeadings}`);
            
            console.log('\n🔘 FORM ELEMENTS:');
            Object.entries(visualAnalysis.componentAnalysis).forEach(([type, elements]) => {
                if (elements.length > 0) {
                    const styledCount = elements.filter(el => el.looksStyled).length;
                    console.log(`   ${type}: ${styledCount}/${elements.length} properly styled`);
                }
            });
            
            if (visualAnalysis.visualProblems.length > 0) {
                console.log('\n🚨 VISUAL PROBLEMS IDENTIFIED:');
                visualAnalysis.visualProblems.forEach((problem, i) => {
                    console.log(`   ${i + 1}. ${problem}`);
                });
            } else {
                console.log('\n✅ No major visual problems detected');
            }
            
            console.log(`\n${'🎨'.repeat(25)}\n`);
        });
    });
});