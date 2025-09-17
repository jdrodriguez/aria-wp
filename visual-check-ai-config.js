const { chromium } = require('playwright');

(async () => {
  // Launch browser
  const browser = await chromium.launch({ 
    headless: false,
    slowMo: 100 
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  
  const page = await context.newPage();
  
  try {
    // Navigate to WordPress admin
    console.log('Navigating to WordPress admin...');
    await page.goto('http://localhost:8080/wp-admin/');
    
    // Login if needed
    const loginForm = await page.$('#loginform');
    if (loginForm) {
      console.log('Logging in...');
      await page.fill('#user_login', 'admin');
      await page.fill('#user_pass', 'admin');
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('#wp-submit')
      ]);
    }
    
    // Take screenshot of Dashboard for comparison
    console.log('Taking Dashboard screenshot...');
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria');
    await page.waitForTimeout(2000); // Wait for React to render
    await page.screenshot({ 
      path: 'screenshots/dashboard-page.png',
      fullPage: true 
    });
    
    // Take screenshot of AI Config page
    console.log('Taking AI Config screenshot...');
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-ai-config');
    await page.waitForTimeout(2000); // Wait for React to render
    await page.screenshot({ 
      path: 'screenshots/ai-config-page.png',
      fullPage: true 
    });
    
    // Also take a screenshot of Personality page for comparison
    console.log('Taking Personality screenshot...');
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-personality');
    await page.waitForTimeout(2000);
    await page.screenshot({ 
      path: 'screenshots/personality-page.png',
      fullPage: true 
    });
    
    // Get specific element screenshots for detailed comparison
    console.log('Taking detailed element screenshots...');
    
    // AI Config page elements
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria-ai-config');
    await page.waitForTimeout(2000);
    
    const aiConfigHeader = await page.$('.aria-logo-header');
    if (aiConfigHeader) {
      await aiConfigHeader.screenshot({ path: 'screenshots/ai-config-header.png' });
    }
    
    const aiConfigContent = await page.$('.aria-page-content');
    if (aiConfigContent) {
      await aiConfigContent.screenshot({ path: 'screenshots/ai-config-content.png' });
    }
    
    // Dashboard elements for comparison
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria');
    await page.waitForTimeout(2000);
    
    const dashboardHeader = await page.$('.aria-dashboard-header');
    if (dashboardHeader) {
      await dashboardHeader.screenshot({ path: 'screenshots/dashboard-header.png' });
    }
    
    console.log('Screenshots saved successfully!');
    
  } catch (error) {
    console.error('Error:', error);
  } finally {
    await browser.close();
  }
})();