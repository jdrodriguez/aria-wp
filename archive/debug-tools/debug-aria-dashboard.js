const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  
  // Enable console logging
  page.on('console', msg => console.log('BROWSER:', msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));
  
  console.log('=== ARIA Dashboard AJAX Debug ===');
  
  try {
    // Test 1: Check AJAX test page
    console.log('\n1. Testing AJAX endpoint...');
    await page.goto('http://localhost:8080/wp-content/plugins/aria/test-ajax-direct.php');
    await page.waitForTimeout(2000);
    
    // Check if we need to login
    const loginForm = await page.$('form[name="loginform"]');
    if (loginForm) {
      console.log('Logging in as admin...');
      await page.fill('input[name="log"]', 'admin');
      await page.fill('input[name="pwd"]', 'password');
      await page.click('input[type="submit"]');
      await page.waitForTimeout(2000);
      
      // Navigate back to test page
      await page.goto('http://localhost:8080/wp-content/plugins/aria/test-ajax-direct.php');
      await page.waitForTimeout(2000);
    }
    
    // Get the page content to see test results
    const content = await page.textContent('body');
    console.log('\nTest page results:');
    
    // Check class verification
    if (content.includes('✓ Aria_Ajax_Handler class exists')) {
      console.log('✅ Classes exist');
    } else {
      console.log('❌ Classes missing');
    }
    
    // Check AJAX response
    if (content.includes('Security check failed')) {
      console.log('❌ Security check failing');
    } else if (content.includes('"success":true')) {
      console.log('✅ AJAX working');
    }
    
    // Test JavaScript AJAX calls
    console.log('\n2. Testing JavaScript AJAX...');
    
    // Click the test button and capture results
    await page.click('button[onclick="testAjaxFromJS()"]');
    await page.waitForTimeout(3000);
    
    // Check for ariaAdmin object
    const ariaAdminExists = await page.evaluate(() => {
      return typeof window.ariaAdmin !== 'undefined';
    });
    
    console.log('ariaAdmin object exists:', ariaAdminExists);
    
    if (ariaAdminExists) {
      const ariaAdminContent = await page.evaluate(() => {
        return window.ariaAdmin;
      });
      console.log('ariaAdmin content:', ariaAdminContent);
    }
    
    // Test debug bypass
    console.log('\n3. Testing debug bypass...');
    await page.click('button[onclick="testDebugBypass()"]');
    await page.waitForTimeout(3000);
    
    const resultsDiv = await page.textContent('#js-results');
    console.log('JavaScript test results:', resultsDiv);
    
    // Test 2: Check actual dashboard
    console.log('\n4. Testing actual dashboard...');
    await page.goto('http://localhost:8080/wp-admin/admin.php?page=aria');
    await page.waitForTimeout(3000);
    
    // Check for React debugging messages
    const reactDebugFound = await page.evaluate(() => {
      return !!document.querySelector('#aria-dashboard-root');
    });
    
    console.log('Dashboard root element found:', reactDebugFound);
    
    // Check for any dashboard data
    const dashboardContent = await page.textContent('.aria-dashboard');
    if (dashboardContent.includes('0 CONVERSATIONS') || dashboardContent.includes('0 KNOWLEDGE')) {
      console.log('❌ Dashboard showing zeros');
    } else {
      console.log('✅ Dashboard has data');
    }
    
    console.log('\n=== Debug Complete ===');
    
  } catch (error) {
    console.error('Error during testing:', error);
  } finally {
    await browser.close();
  }
})();