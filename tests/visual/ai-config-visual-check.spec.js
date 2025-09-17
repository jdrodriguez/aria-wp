/**
 * AI Config Page Visual Check
 * Compares AI Config page with Dashboard for design consistency
 */

const { test, expect } = require('@playwright/test');

test.describe('AI Config Page Visual Consistency', () => {
  // Helper function to login
  async function login(page) {
    await page.goto('/wp-login.php');
    
    // Check if already logged in
    if (page.url().includes('wp-admin')) {
      return;
    }
    
    // Login if needed
    const loginForm = await page.$('#loginform');
    if (loginForm) {
      await page.fill('#user_login', 'admin');
      await page.fill('#user_pass', 'admin123');
      await page.click('#wp-submit');
      await page.waitForNavigation({ waitUntil: 'networkidle' });
    }
  }

  test('Compare AI Config with Dashboard styling', async ({ page }) => {
    // Login first
    await login(page);
    
    // First take Dashboard screenshot for reference
    await page.goto('/wp-admin/admin.php?page=aria');
    await page.waitForTimeout(2000); // Wait for React render
    
    // Take full page screenshot
    await page.screenshot({ 
      path: 'screenshots/dashboard-reference.png',
      fullPage: true 
    });
    
    // Take specific element screenshots
    const dashboardHeader = await page.$('.aria-dashboard-header');
    if (dashboardHeader) {
      await dashboardHeader.screenshot({ 
        path: 'screenshots/dashboard-header-element.png' 
      });
    }
    
    const dashboardContent = await page.$('.aria-dashboard-react');
    if (dashboardContent) {
      await dashboardContent.screenshot({ 
        path: 'screenshots/dashboard-content-structure.png' 
      });
    }
    
    // Now check AI Config page
    await page.goto('/wp-admin/admin.php?page=aria-ai-config');
    await page.waitForTimeout(2000); // Wait for React render
    
    // Take full page screenshot
    await page.screenshot({ 
      path: 'screenshots/ai-config-current.png',
      fullPage: true 
    });
    
    // Check if content is visible
    const aiConfigRoot = await page.$('#aria-ai-config-root');
    expect(aiConfigRoot).toBeTruthy();
    
    // Check if React component mounted
    const hasContent = await page.evaluate(() => {
      const root = document.getElementById('aria-ai-config-root');
      return root && root.children.length > 0;
    });
    expect(hasContent).toBeTruthy();
    
    // Take specific element screenshots for comparison
    const logoHeader = await page.$('.aria-logo-header');
    if (logoHeader) {
      await logoHeader.screenshot({ 
        path: 'screenshots/ai-config-logo-header.png' 
      });
    }
    
    const pageContent = await page.$('.aria-page-content');
    if (pageContent) {
      await pageContent.screenshot({ 
        path: 'screenshots/ai-config-page-content.png' 
      });
    }
    
    // Log what elements are present
    const elements = await page.evaluate(() => {
      const results = {
        hasLogo: !!document.querySelector('.aria-logo-header'),
        hasPageContent: !!document.querySelector('.aria-page-content'),
        hasMetricsGrid: !!document.querySelector('.aria-metrics-grid'),
        hasPageHeader: !!document.querySelector('.aria-page-header'),
        rootClasses: document.querySelector('.wrap')?.className || 'not found',
        rootChildren: document.querySelector('#aria-ai-config-root')?.children.length || 0
      };
      return results;
    });
    
    console.log('AI Config Page Elements:', elements);
    
    // Also check Personality page for comparison (good React example)
    await page.goto('/wp-admin/admin.php?page=aria-personality');
    await page.waitForTimeout(2000);
    
    await page.screenshot({ 
      path: 'screenshots/personality-reference.png',
      fullPage: true 
    });
    
    // Log personality page structure
    const personalityElements = await page.evaluate(() => {
      const results = {
        hasHeader: !!document.querySelector('.aria-personality-header'),
        hasReactContainer: !!document.querySelector('.aria-personality-react'),
        headerText: document.querySelector('h1')?.textContent || 'not found',
        rootClasses: document.querySelector('.wrap')?.className || 'not found'
      };
      return results;
    });
    
    console.log('Personality Page Elements:', personalityElements);
  });

  test('Check AI Config CSS classes and structure', async ({ page }) => {
    // Login first
    await login(page);
    
    await page.goto('/wp-admin/admin.php?page=aria-ai-config');
    await page.waitForTimeout(2000);
    
    // Check CSS classes match expected pattern
    const cssAnalysis = await page.evaluate(() => {
      const analysis = {
        wrapClasses: [],
        headerClasses: [],
        contentClasses: [],
        hasReactContainer: false,
        computedStyles: {}
      };
      
      // Check wrap element
      const wrap = document.querySelector('.wrap');
      if (wrap) {
        analysis.wrapClasses = Array.from(wrap.classList);
      }
      
      // Check for React container class pattern
      const reactContainers = document.querySelectorAll('[class*="-react"]');
      analysis.hasReactContainer = reactContainers.length > 0;
      
      // Get computed styles of key elements
      const pageContent = document.querySelector('.aria-page-content');
      if (pageContent) {
        const styles = window.getComputedStyle(pageContent);
        analysis.computedStyles.pageContent = {
          padding: styles.padding,
          margin: styles.margin,
          display: styles.display
        };
      }
      
      // Check heading styles
      const h1 = document.querySelector('h1');
      if (h1) {
        const styles = window.getComputedStyle(h1);
        analysis.computedStyles.heading = {
          fontSize: styles.fontSize,
          fontWeight: styles.fontWeight,
          color: styles.color,
          margin: styles.margin
        };
      }
      
      return analysis;
    });
    
    console.log('CSS Analysis:', JSON.stringify(cssAnalysis, null, 2));
    
    // Take specific screenshots of problem areas
    await page.screenshot({ 
      path: 'screenshots/ai-config-detailed-view.png',
      fullPage: false,
      clip: { x: 0, y: 0, width: 1400, height: 800 }
    });
  });
});