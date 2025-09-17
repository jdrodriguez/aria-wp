# Archive Directory - Aria Plugin Cleanup

**Date Created:** July 24, 2025  
**Purpose:** Preserve legacy and duplicate files during React migration cleanup  

## Directory Structure

### duplicate-files/
Contains duplicate JavaScript and CSS files that were causing conflicts:
- Legacy admin.js versions
- Duplicate chat.js files  
- Duplicate CSS files

### debug-tools/
Development and debugging files moved from production:
- debug-* files
- test-* files
- diagnostic tools
- development utilities

### legacy-assets/
Screenshots, images, and assets from development:
- Dashboard screenshots
- Development images
- Temporary assets

### concept-files/
HTML concept files and design mockups:
- Admin design concepts
- Branding prototypes
- Test pages

### backup-css/
Legacy CSS and SCSS files:
- Old SCSS versions
- Backup CSS files
- Legacy styling approaches

## Restoration Instructions

If you need to restore any archived files:

1. **Identify the file type** and check the appropriate subdirectory
2. **Copy (don't move)** the file back to its original location
3. **Test thoroughly** before making permanent
4. **Update build system** if needed for JavaScript/CSS files

## Files Safe to Delete

After successful React migration completion and thorough testing:
- All files in `duplicate-files/` (superseded by compiled versions)
- Files in `debug-tools/` (development only)
- Files in `concept-files/` (design reference only)

## Files to Keep Permanently

- `backup-css/` - May contain useful styling patterns
- `legacy-assets/` - May contain important images/assets

---

**Important**: Do not delete this archive until React migration is completely tested and stable in production.