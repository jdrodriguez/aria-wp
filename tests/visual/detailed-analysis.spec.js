/**
 * Detailed Visual Analysis of Aria Admin Pages
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

test.describe('Detailed Visual Analysis', () => {
    ARIA_PAGES.forEach(({ name, url }) => {
        test(`${name} - Detailed Layout Analysis`, async ({ page }) => {
            console.log(`\n${'='.repeat(60)}`);
            console.log(`🔍 DETAILED ANALYSIS: ${name.toUpperCase()}`);
            console.log(`${'='.repeat(60)}`);
            
            // Login and navigate
            await page.goto('http://localhost:8080/wp-login.php');
            await page.fill('#user_login', 'admin');
            await page.fill('#user_pass', 'admin123');
            await page.click('#wp-submit');
            await page.waitForSelector('#wpadminbar');
            
            await page.goto(url);
            await page.waitForTimeout(3000);
            
            // Comprehensive layout analysis
            const layoutAnalysis = await page.evaluate(() => {
                const analysis = {
                    pageWidth: window.innerWidth,
                    contentWidth: 0,
                    headerInfo: {},
                    gridInfo: {},
                    cardInfo: [],
                    spacingIssues: [],
                    alignmentIssues: [],
                    responsiveIssues: []
                };
                
                // Analyze page content container
                const pageContent = document.querySelector('.aria-page-content');
                if (pageContent) {
                    const contentRect = pageContent.getBoundingClientRect();
                    const contentStyles = window.getComputedStyle(pageContent);
                    
                    analysis.contentWidth = contentRect.width;
                    analysis.pageContentInfo = {
                        width: contentRect.width,
                        maxWidth: contentStyles.maxWidth,
                        margin: contentStyles.margin,
                        padding: contentStyles.padding,
                        marginTop: contentStyles.marginTop
                    };
                }
                
                // Analyze page header
                const pageHeader = document.querySelector('.aria-page-header');
                if (pageHeader) {
                    const headerRect = pageHeader.getBoundingClientRect();
                    const headerStyles = window.getComputedStyle(pageHeader);
                    
                    analysis.headerInfo = {
                        height: headerRect.height,
                        display: headerStyles.display,
                        alignItems: headerStyles.alignItems,
                        marginBottom: headerStyles.marginBottom,
                        padding: headerStyles.padding
                    };
                    
                    // Check header text alignment
                    const pageTitle = document.querySelector('.aria-page-title');
                    const pageDesc = document.querySelector('.aria-page-description');
                    
                    if (pageTitle) {
                        const titleStyles = window.getComputedStyle(pageTitle);
                        analysis.headerInfo.titleAlignment = titleStyles.textAlign;
                    }
                    
                    if (pageDesc) {
                        const descStyles = window.getComputedStyle(pageDesc);
                        analysis.headerInfo.descAlignment = descStyles.textAlign;
                    }
                }
                
                // Analyze metrics grid
                const metricsGrids = document.querySelectorAll('.aria-metrics-grid');
                metricsGrids.forEach((grid, index) => {
                    const gridRect = grid.getBoundingClientRect();
                    const gridStyles = window.getComputedStyle(grid);
                    
                    analysis.gridInfo[`grid_${index}`] = {
                        width: gridRect.width,
                        display: gridStyles.display,
                        gridTemplateColumns: gridStyles.gridTemplateColumns,
                        gap: gridStyles.gap,
                        marginBottom: gridStyles.marginBottom
                    };
                });
                
                // Analyze metric cards
                const cards = document.querySelectorAll('.aria-metric-card');
                cards.forEach((card, index) => {
                    const cardRect = card.getBoundingClientRect();
                    const cardStyles = window.getComputedStyle(card);
                    
                    const cardInfo = {
                        index: index + 1,
                        width: cardRect.width,
                        height: cardRect.height,
                        background: cardStyles.background,
                        border: cardStyles.border,
                        borderRadius: cardStyles.borderRadius,
                        padding: cardStyles.padding,
                        boxShadow: cardStyles.boxShadow
                    };
                    
                    // Check for styling issues
                    if (!cardStyles.background || cardStyles.background === 'rgba(0, 0, 0, 0)') {
                        cardInfo.issues = cardInfo.issues || [];
                        cardInfo.issues.push('No background');
                    }
                    
                    if (!cardStyles.border || cardStyles.border.includes('0px')) {
                        cardInfo.issues = cardInfo.issues || [];
                        cardInfo.issues.push('No border');
                    }
                    
                    if (!cardStyles.borderRadius || cardStyles.borderRadius === '0px') {
                        cardInfo.issues = cardInfo.issues || [];
                        cardInfo.issues.push('No border radius');
                    }
                    
                    if (!cardStyles.padding || cardStyles.padding === '0px') {
                        cardInfo.issues = cardInfo.issues || [];
                        cardInfo.issues.push('No padding');
                    }
                    
                    analysis.cardInfo.push(cardInfo);
                });
                
                // Check for height consistency in grid rows
                if (cards.length >= 2) {
                    const firstRowCards = Array.from(cards).slice(0, 2);
                    const heights = firstRowCards.map(card => card.getBoundingClientRect().height);
                    
                    if (heights.length === 2) {
                        const heightDiff = Math.abs(heights[0] - heights[1]);
                        if (heightDiff > 10) {
                            analysis.spacingIssues.push(`Card height inconsistency: ${heightDiff}px difference between first two cards`);
                        }
                    }
                }
                
                // Check for alignment issues
                const centerAlignedElements = document.querySelectorAll('*').forEach(el => {
                    const styles = window.getComputedStyle(el);
                    if (styles.textAlign === 'center' && el.classList.contains('aria-page-')) {
                        analysis.alignmentIssues.push(`${el.className} has center alignment`);
                    }
                });
                
                return analysis;
            });
            
            // Display analysis results
            console.log('\n📏 PAGE DIMENSIONS:');
            console.log(`   Viewport width: ${layoutAnalysis.pageWidth}px`);
            console.log(`   Content width: ${layoutAnalysis.contentWidth}px`);
            
            if (layoutAnalysis.pageContentInfo) {
                console.log('\n📦 PAGE CONTENT CONTAINER:');
                Object.entries(layoutAnalysis.pageContentInfo).forEach(([key, value]) => {
                    console.log(`   ${key}: ${value}`);
                });
            }
            
            if (Object.keys(layoutAnalysis.headerInfo).length > 0) {
                console.log('\n📋 HEADER ANALYSIS:');
                Object.entries(layoutAnalysis.headerInfo).forEach(([key, value]) => {
                    console.log(`   ${key}: ${value}`);
                });
            }
            
            if (Object.keys(layoutAnalysis.gridInfo).length > 0) {
                console.log('\n🗂️  GRID ANALYSIS:');
                Object.entries(layoutAnalysis.gridInfo).forEach(([gridName, gridData]) => {
                    console.log(`   ${gridName}:`);
                    Object.entries(gridData).forEach(([key, value]) => {
                        console.log(`     ${key}: ${value}`);
                    });
                });
            }
            
            if (layoutAnalysis.cardInfo.length > 0) {
                console.log('\n🎴 CARD ANALYSIS:');
                layoutAnalysis.cardInfo.forEach(card => {
                    console.log(`   Card ${card.index}: ${card.width}x${card.height}px`);
                    if (card.issues && card.issues.length > 0) {
                        console.log(`     🚨 Issues: ${card.issues.join(', ')}`);
                    }
                });
            }
            
            if (layoutAnalysis.spacingIssues.length > 0) {
                console.log('\n📐 SPACING ISSUES:');
                layoutAnalysis.spacingIssues.forEach(issue => {
                    console.log(`   🚨 ${issue}`);
                });
            }
            
            if (layoutAnalysis.alignmentIssues.length > 0) {
                console.log('\n↔️  ALIGNMENT ISSUES:');
                layoutAnalysis.alignmentIssues.forEach(issue => {
                    console.log(`   🚨 ${issue}`);
                });
            }
            
            // Check for responsive behavior
            await page.setViewportSize({ width: 768, height: 1024 });
            await page.waitForTimeout(1000);
            
            const mobileAnalysis = await page.evaluate(() => {
                const grid = document.querySelector('.aria-metrics-grid');
                if (grid) {
                    const styles = window.getComputedStyle(grid);
                    return {
                        gridColumns: styles.gridTemplateColumns,
                        isSingleColumn: styles.gridTemplateColumns === '1fr'
                    };
                }
                return null;
            });
            
            if (mobileAnalysis) {
                console.log('\n📱 MOBILE RESPONSIVENESS:');
                console.log(`   Grid columns at 768px: ${mobileAnalysis.gridColumns}`);
                console.log(`   Single column layout: ${mobileAnalysis.isSingleColumn ? '✅ Yes' : '❌ No'}`);
            }
            
            // Reset viewport
            await page.setViewportSize({ width: 1400, height: 900 });
            
            console.log(`\n${'='.repeat(60)}\n`);
        });
    });
});