# ARIA Dashboard Fake Data Troubleshooting Guide

## Quick Steps to Debug Dashboard Issues

### Step 1: Force Browser Cache Refresh
1. Go to WordPress admin dashboard: `http://localhost:8080/wp-admin`
2. Navigate to ARIA dashboard
3. **Hard refresh the page**: 
   - **Chrome/Firefox**: `Ctrl+Shift+R` (Windows) or `Cmd+Shift+R` (Mac)
   - **Safari**: `Cmd+Option+R`
4. Or open Developer Tools (F12) → right-click refresh button → "Empty Cache and Hard Reload"

### Step 2: Check Browser Console
1. Open Developer Tools (F12)
2. Go to **Console** tab
3. Look for:
   - ✅ `ariaAdmin object:` - Should show WordPress variables
   - ✅ `Making AJAX request to:` - Should show AJAX calls
   - ✅ `Dashboard data loaded successfully:` - Should show real data
   - ❌ Any error messages about AJAX failures

### Step 3: Run Debug Script
1. Copy the entire content of `debug-dashboard.js`
2. Paste into browser console and press Enter
3. Check the output for errors and data

### Step 4: Check WordPress Error Log
**Location**: Check these files for debug output:
- `/wp-content/debug.log` (if debug logging enabled)
- Web server error logs
- Docker container logs if using Docker

**Look for lines containing**:
- `Aria Dashboard Data Retrieved:`
- `Aria Conversation Counts:`
- `Aria Database Query:`
- `Aria Sample Conversations in DB:`

### Step 5: Verify Database Tables
If you have database access, check if there are any conversations:
```sql
SELECT COUNT(*) FROM wp_aria_conversations;
SELECT * FROM wp_aria_conversations ORDER BY created_at DESC LIMIT 5;
```

## Common Issues and Solutions

### Issue 1: "ariaAdmin object not found"
**Cause**: WordPress script localization failed
**Solution**: 
- Clear browser cache
- Check if you're on the correct ARIA dashboard page
- Verify WordPress admin is loading properly

### Issue 2: AJAX calls failing
**Cause**: Nonce issues or AJAX handler not registered
**Solution**:
- Check WordPress error logs
- Verify you're logged in as admin
- Try logging out and back in

### Issue 3: Same numbers for "Today" and "Total" conversations
**Cause**: Date filtering not working (this was the main issue we fixed)
**Solution**: 
- This should be fixed with our database updates
- Check WordPress error logs for "Aria Database Query" entries

### Issue 4: Still seeing fake conversations
**Cause**: Actual test data in database
**Solution**:
- Check WordPress error logs for "Aria Sample Conversations in DB"
- If there's test data, it needs to be manually removed

## Expected Behavior (Empty Database)
For a fresh installation with no real conversations:
- **Today's Activity**: 0 conversations
- **Total Activity**: 0 conversations  
- **Knowledge Base**: 0 entries (unless you added some)
- **Recent Conversations**: "No conversations yet" message
- **License Status**: Trial status

## Files That Were Fixed
- `includes/class-aria-database.php` - Added date filtering support
- `includes/class-aria-ajax-handler.php` - Removed mock values, added debugging
- `admin/class-aria-admin.php` - Fixed script localization
- `src/js/admin-react.jsx` - Fixed hardcoded values, added error handling

## Getting Help
1. Run the debug script and copy console output
2. Check WordPress error logs for "Aria" entries
3. Take a screenshot of the dashboard showing the fake data
4. Note what specific numbers/data appears fake