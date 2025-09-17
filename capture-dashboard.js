const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // Set viewport for desktop view
  await page.setViewportSize({ width: 1920, height: 1080 });
  
  try {
    // Navigate to login page
    await page.goto('http://localhost:8080/wp-login.php', { waitUntil: 'networkidle' });
    
    // Check if login form exists
    const loginForm = await page.locator('#loginform').isVisible().catch(() => false);
    
    if (loginForm) {
      console.log('Logging in with admin credentials...');
      
      // Fill login form
      await page.fill('#user_login', 'admin');
      await page.fill('#user_pass', 'admin123');
      await page.click('#wp-submit');
      
      // Wait for login to complete
      await page.waitForNavigation({ waitUntil: 'networkidle' });
      
      console.log('Login successful! Navigating to Aria dashboard...');
    }
    
    // Navigate to Aria dashboard
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria', { 
      waitUntil: 'networkidle',
      timeout: 15000 
    });
    
    // Wait for dashboard elements to load
    await page.waitForSelector('.aria-dashboard', { timeout: 10000 });
    
    // Clear browser cache and refresh
    await page.evaluate(() => {
      // Clear localStorage and sessionStorage
      localStorage.clear();
      sessionStorage.clear();
      
      // Force refresh
      location.reload(true);
    });
    
    // Wait for page to reload
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.aria-dashboard', { timeout: 10000 });
    
    // Take full page screenshot after cache clear
    await page.screenshot({ 
      path: 'dashboard-after-cache-clear.png', 
      fullPage: true 
    });
    
    console.log('✅ Dashboard screenshot (cache cleared): dashboard-after-cache-clear.png');
    
    // Check what CSS is actually loaded
    const cssInfo = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.map(link => ({
        href: link.href,
        loaded: link.sheet !== null
      }));
    });
    
    console.log('CSS files loaded:', cssInfo);
    
    // Check if our new classes exist
    const hasNewClasses = await page.evaluate(() => {
      return {
        hasNewContainer: !!document.querySelector('.aria-dashboard-container'),
        hasOldPageHeader: !!document.querySelector('.aria-page-header'),
        hasNewHeader: !!document.querySelector('.aria-dashboard-header'),
        hasNewMetrics: !!document.querySelector('.aria-metrics-grid'),
        ariaClass: document.querySelector('.aria-dashboard')?.className
      };
    });
    
    console.log('Dashboard structure:', hasNewClasses);
  } catch (error) {
    console.log('Error accessing dashboard:', error.message);
    
    // Try to capture whatever page we're on
    await page.screenshot({ path: 'dashboard-error.png', fullPage: true });
    console.log('Error screenshot saved as dashboard-error.png');
  }
  
  await browser.close();
})();