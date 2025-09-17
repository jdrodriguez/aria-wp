/**
 * Comprehensive Admin Pages Review
 * Evaluates all Aria admin pages for design consistency, component usage, and remaining issues
 */

const { test, expect } = require('@playwright/test');

// Admin pages to review
const adminPages = [
    { 
        name: 'Dashboard', 
        url: '/wp-admin/admin.php?page=aria',
        converted: true,
        description: 'Main overview page with metrics and recent activity'
    },
    { 
        name: 'Settings', 
        url: '/wp-admin/admin.php?page=aria-settings',
        converted: true,
        description: 'Configuration and behavior settings'
    },
    { 
        name: 'Design', 
        url: '/wp-admin/admin.php?page=aria-design',
        converted: true,
        description: 'Chat widget appearance customization'
    },
    { 
        name: 'AI Config', 
        url: '/wp-admin/admin.php?page=aria-ai-config',
        converted: false,
        description: 'AI provider and API configuration'
    },
    { 
        name: 'Conversations', 
        url: '/wp-admin/admin.php?page=aria-conversations',
        converted: false,
        description: 'Chat conversation history and management'
    },
    { 
        name: 'Knowledge Base', 
        url: '/wp-admin/admin.php?page=aria-knowledge',
        converted: false,
        description: 'Content and knowledge management'
    },
    { 
        name: 'Personality', 
        url: '/wp-admin/admin.php?page=aria-personality',
        converted: false,
        description: 'AI personality and behavior configuration'
    },
    { 
        name: 'Content Indexing', 
        url: '/wp-admin/admin.php?page=aria-content-indexing',
        converted: false,
        description: 'Content vectorization and indexing'
    }
];

test.describe('Comprehensive Admin Pages Review', () => {
    test.beforeEach(async ({ page }) => {
        // Set viewport for desktop review
        await page.setViewportSize({ width: 1440, height: 900 });
    });

    // Test each admin page individually
    for (const adminPage of adminPages) {
        test(`${adminPage.name} Page Analysis`, async ({ page }) => {
            console.log(`\\n=== ANALYZING ${adminPage.name.toUpperCase()} PAGE ===`);
            console.log(`Description: ${adminPage.description}`);
            console.log(`WordPress Components: ${adminPage.converted ? 'YES' : 'NO'}`);
            
            // Navigate to page
            try {
                await page.goto(`http://localhost:8080${adminPage.url}`, { 
                    waitUntil: 'networkidle',
                    timeout: 15000 
                });
            } catch (error) {
                console.log(`❌ Failed to load ${adminPage.name}: ${error.message}`);
                return;
            }

            // Wait for content to load
            await page.waitForTimeout(2000);

            // 1. Check if page loaded successfully
            const pageTitle = await page.locator('h1').first().textContent();
            console.log(`Page Title: "${pageTitle}"`);

            // 2. Component Analysis
            const componentAnalysis = await analyzeComponents(page, adminPage.converted);
            console.log('\\n📊 COMPONENT ANALYSIS:');
            console.log(`WordPress Components: ${componentAnalysis.hasWordPressComponents ? '✅' : '❌'}`);
            console.log(`Custom Cards: ${componentAnalysis.customCards}`);
            console.log(`React Elements: ${componentAnalysis.reactElements}`);
            console.log(`Form Controls: ${componentAnalysis.formControls}`);

            // 3. Design Quality Assessment
            const designAssessment = await assessDesignQuality(page);
            console.log('\\n🎨 DESIGN ASSESSMENT:');
            console.log(`Typography Issues: ${designAssessment.typographyIssues}`);
            console.log(`Button Issues: ${designAssessment.buttonIssues}`);
            console.log(`Layout Issues: ${designAssessment.layoutIssues}`);
            console.log(`Spacing Issues: ${designAssessment.spacingIssues}`);

            // 4. Page-specific Analysis
            const pageSpecificIssues = await analyzePageSpecificIssues(page, adminPage.name);
            if (pageSpecificIssues.length > 0) {
                console.log('\\n🔍 PAGE-SPECIFIC ISSUES:');
                pageSpecificIssues.forEach((issue, index) => {
                    console.log(`${index + 1}. ${issue}`);
                });
            }

            // 5. Conversion Priority Assessment
            const priority = assessConversionPriority(adminPage, componentAnalysis, designAssessment);
            console.log(`\\n⭐ CONVERSION PRIORITY: ${priority.level}`);
            console.log(`Reason: ${priority.reason}`);

            // 6. Take screenshot for visual reference
            await page.screenshot({ 
                path: `tests/visual/screenshots/admin-review-${adminPage.name.toLowerCase().replace(' ', '-')}.png`,
                fullPage: true 
            });

            console.log(`\\n📸 Screenshot saved for ${adminPage.name}`);
            console.log(`${'='.repeat(60)}`);
        });
    }

    // Summary test that provides overall recommendations
    test('Overall Conversion Strategy', async ({ page }) => {
        console.log('\\n🎯 OVERALL CONVERSION STRATEGY');
        console.log('='.repeat(60));
        
        console.log('\\n✅ COMPLETED CONVERSIONS:');
        adminPages.filter(p => p.converted).forEach(page => {
            console.log(`  • ${page.name}: ${page.description}`);
        });

        console.log('\\n🔄 PENDING CONVERSIONS (Priority Order):');
        const pendingPages = adminPages.filter(p => !p.converted);
        
        // Suggest conversion order based on importance and complexity
        const conversionOrder = [
            'AI Config',      // Critical for functionality
            'Knowledge Base', // Core feature
            'Personality',    // Core feature
            'Conversations',  // Important for management
            'Content Indexing' // Advanced feature
        ];

        conversionOrder.forEach((pageName, index) => {
            const page = pendingPages.find(p => p.name === pageName);
            if (page) {
                console.log(`  ${index + 1}. ${page.name}: ${page.description}`);
            }
        });

        console.log('\\n📋 RECOMMENDED NEXT STEPS:');
        console.log('  1. Convert AI Config page (most critical for functionality)');
        console.log('  2. Convert Knowledge Base page (core content management)');
        console.log('  3. Convert Personality page (core AI configuration)');
        console.log('  4. Optimize and test all converted pages');
        console.log('  5. Final Playwright review for consistency');

        console.log('\\n💡 BENEFITS OF COMPLETING CONVERSION:');
        console.log('  • Consistent WordPress native experience');
        console.log('  • Better accessibility and usability');
        console.log('  • Easier maintenance and future updates');
        console.log('  • Professional appearance matching WordPress standards');
    });
});

// Helper function to analyze component usage
async function analyzeComponents(page, isConverted) {
    const hasWordPressComponents = await page.locator('.components-panel, .components-card, .components-tab-panel').count() > 0;
    const customCards = await page.locator('.aria-metric-card').count();
    const reactElements = await page.locator('[id$="-root"]').count();
    const formControls = await page.locator('input, select, textarea, button').count();

    return {
        hasWordPressComponents,
        customCards,
        reactElements,
        formControls
    };
}

// Helper function to assess design quality
async function assessDesignQuality(page) {
    // Typography issues (elements with small fonts or poor line height)
    const smallTextElements = await page.locator('*').evaluateAll(elements => {
        return elements.filter(el => {
            const styles = window.getComputedStyle(el);
            const fontSize = parseFloat(styles.fontSize);
            return fontSize < 12 && el.textContent.trim().length > 0;
        }).length;
    });

    // Button issues (hidden or undersized buttons)
    const buttonIssues = await page.locator('button, .button, input[type="submit"]').evaluateAll(buttons => {
        return buttons.filter(btn => {
            const rect = btn.getBoundingClientRect();
            const styles = window.getComputedStyle(btn);
            return rect.height < 32 || styles.visibility === 'hidden' || styles.opacity === '0';
        }).length;
    });

    // Layout issues (elements with insufficient spacing)
    const layoutElements = await page.locator('.aria-metric-card, .components-panel, section').count();
    
    // Spacing issues (elements too close together)
    const spacingIssues = await page.locator('*').evaluateAll(elements => {
        let issues = 0;
        elements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.height > 50) { // Only check larger elements
                const siblings = [...el.parentElement?.children || []];
                const index = siblings.indexOf(el);
                const nextSibling = siblings[index + 1];
                if (nextSibling) {
                    const nextRect = nextSibling.getBoundingClientRect();
                    const gap = nextRect.top - rect.bottom;
                    if (gap < 8 && gap > -10) { // Too close or overlapping
                        issues++;
                    }
                }
            }
        });
        return issues;
    });

    return {
        typographyIssues: smallTextElements,
        buttonIssues,
        layoutIssues: Math.max(0, 5 - layoutElements), // Ideal is 5+ structured sections
        spacingIssues
    };
}

// Helper function for page-specific issue analysis
async function analyzePageSpecificIssues(page, pageName) {
    const issues = [];

    switch (pageName) {
        case 'Dashboard':
            const metricsCards = await page.locator('.aria-metric-card, .components-card').count();
            if (metricsCards < 4) {
                issues.push(`Expected 4+ metric cards, found ${metricsCards}`);
            }
            break;

        case 'Settings':
            const tabPanels = await page.locator('.components-tab-panel').count();
            if (tabPanels === 0) {
                issues.push('Settings should use TabPanel for organization');
            }
            break;

        case 'Design':
            const colorPickers = await page.locator('.components-color-picker, input[type="color"]').count();
            if (colorPickers === 0) {
                issues.push('Design page should have color picker controls');
            }
            break;

        case 'AI Config':
            const apiKeyField = await page.locator('input[type="password"], input[name*="api"]').count();
            if (apiKeyField === 0) {
                issues.push('AI Config should have API key input field');
            }
            break;

        case 'Knowledge Base':
            const knowledgeEntries = await page.locator('.knowledge-entry, .aria-metric-card').count();
            if (knowledgeEntries === 0) {
                issues.push('Knowledge Base should show entries or empty state');
            }
            break;
    }

    return issues;
}

// Helper function to assess conversion priority
function assessConversionPriority(adminPage, componentAnalysis, designAssessment) {
    if (adminPage.converted) {
        return { level: 'COMPLETED', reason: 'Already using WordPress Components' };
    }

    const totalIssues = designAssessment.typographyIssues + 
                       designAssessment.buttonIssues + 
                       designAssessment.layoutIssues + 
                       designAssessment.spacingIssues;

    const functionalImportance = {
        'AI Config': 10,      // Critical for basic functionality
        'Knowledge Base': 9,  // Core feature
        'Personality': 8,     // Core feature
        'Conversations': 7,   // Important for management
        'Content Indexing': 6 // Advanced feature
    };

    const importance = functionalImportance[adminPage.name] || 5;
    const designScore = Math.max(1, 10 - totalIssues);
    const priority = (importance + designScore) / 2;

    if (priority >= 8) {
        return { level: 'HIGH', reason: 'Critical functionality with design issues' };
    } else if (priority >= 6) {
        return { level: 'MEDIUM', reason: 'Important feature needing improvement' };
    } else {
        return { level: 'LOW', reason: 'Secondary feature or minor issues' };
    }
}