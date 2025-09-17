/**
 * Quick Width Test for Personality Page
 */

const { test } = require('@playwright/test');

test('Verify Page Width Increase', async ({ page }) => {
    console.log('\n📏 PAGE WIDTH VERIFICATION');
    console.log('='.repeat(40));

    const testHtml = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Width Test</title>
    <link rel="stylesheet" href="http://localhost:8080/wp-content/plugins/aria/dist/admin-style.css">
    <style>body { margin: 0; padding: 20px; background: #f0f0f1; }</style>
</head>
<body>
    <div class="wrap aria-personality">
        <div class="aria-page-content">
            <div id="aria-personality-root"></div>
        </div>
    </div>
    <script src="http://localhost:8080/wp-content/plugins/aria/dist/admin-react.js"></script>
</body>
</html>`;

    const dataUrl = `data:text/html;charset=utf-8,${encodeURIComponent(testHtml)}`;
    
    await page.goto(dataUrl, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    // Set a wide viewport to test the new width
    await page.setViewportSize({ width: 1800, height: 1000 });
    await page.waitForTimeout(1000);

    // Check page content width
    const pageContentWidth = await page.locator('.aria-page-content').evaluate(el => {
        const styles = window.getComputedStyle(el);
        return {
            maxWidth: styles.maxWidth,
            actualWidth: el.getBoundingClientRect().width
        };
    });

    console.log(`Max Width Setting: ${pageContentWidth.maxWidth}`);
    console.log(`Actual Content Width: ${Math.round(pageContentWidth.actualWidth)}px`);

    // Check if grids are using the wider layout
    const businessGrid = await page.locator('.aria-business-type-grid').evaluate(el => {
        if (!el) return null;
        const styles = window.getComputedStyle(el);
        return {
            gridTemplateColumns: styles.gridTemplateColumns,
            columnCount: styles.gridTemplateColumns.split(' ').length
        };
    });

    if (businessGrid) {
        console.log(`Business Grid Columns: ${businessGrid.columnCount}`);
        console.log(`Business Grid Layout: ${businessGrid.gridTemplateColumns}`);
    }

    const toneGrid = await page.locator('.aria-tone-grid').evaluate(el => {
        if (!el) return null;
        const styles = window.getComputedStyle(el);
        return {
            gridTemplateColumns: styles.gridTemplateColumns,
            columnCount: styles.gridTemplateColumns.split(' ').length
        };
    });

    if (toneGrid) {
        console.log(`Tone Grid Columns: ${toneGrid.columnCount}`);
        console.log(`Tone Grid Layout: ${toneGrid.gridTemplateColumns}`);
    }

    // Take screenshot to show the wider layout
    await page.screenshot({ 
        path: 'tests/visual/screenshots/personality-page-wide.png',
        fullPage: true 
    });

    console.log('\n✅ Width verification complete');
    console.log('📸 Wide layout screenshot saved');
    
    // Check if we're getting the expected wider layout
    const isWiderLayout = pageContentWidth.maxWidth === '1600px';
    console.log(`\n🎯 Result: ${isWiderLayout ? '✅ Successfully increased to 1600px' : '❌ Width not updated'}`);
});