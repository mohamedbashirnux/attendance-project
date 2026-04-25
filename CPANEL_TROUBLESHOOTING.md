# cPanel Troubleshooting Guide for Subject Assignment

## Problem
The subject assignment feature works on localhost (XAMPP) but not on cPanel hosting.

## Common Issues and Solutions

### 1. Run the Debug Test First
Upload all files to cPanel, then access:
```
https://yourdomain.com/Database_users/subject_class/debug_test.php
```

This will show you exactly what's failing:
- Session status
- Database connection
- Faculty session
- Database tables
- File paths

### 2. Common cPanel Issues

#### Issue A: Session Not Working
**Symptoms:** "Session error - please login again"

**Solutions:**
1. Check if session directory has write permissions:
   - In cPanel File Manager, check `/tmp` or session.save_path
   - Set permissions to 755 or 777

2. Add to the top of `session_faculty.php`:
```php
ini_set('session.save_path', '/home/yourusername/tmp');
session_start();
```

#### Issue B: Database Connection
**Symptoms:** "Database connection failed"

**Solutions:**
1. Update `connection/connect.php` with cPanel credentials:
```php
define('DB_HOST', 'localhost'); // or your cPanel DB host
define('DB_USER', 'cpanel_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'cpanel_dbname');
```

2. Make sure the database user has ALL PRIVILEGES on the database

#### Issue C: File Paths
**Symptoms:** "Failed to open stream" or "No such file or directory"

**Solutions:**
1. Check that all files are uploaded to the correct directories
2. Verify file permissions (644 for files, 755 for directories)
3. Check that file names match exactly (case-sensitive on Linux)

#### Issue D: JSON Parsing Error
**Symptoms:** "Unexpected token" or "JSON parse error" in console

**Solutions:**
1. Check for BOM (Byte Order Mark) in PHP files
2. Ensure no whitespace before `<?php` or after `?>`
3. Check PHP error logs in cPanel

### 3. Browser Console Debugging

Open your browser's Developer Tools (F12) and check the Console tab when:
1. Opening the "Add Subject to Class" modal
2. Selecting subjects
3. Clicking "Assign Selected Subjects"

Look for:
- Red error messages
- AJAX request details
- Response data

### 4. Check PHP Error Logs

In cPanel:
1. Go to "Errors" or "Error Log"
2. Look for recent errors related to your files
3. Common errors:
   - Parse errors (syntax issues)
   - Fatal errors (missing files)
   - Warning messages (deprecated functions)

### 5. Database Issues

Check if the `subject_class` table exists and has the correct structure:

```sql
CREATE TABLE IF NOT EXISTS `subject_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`subject_id`, `class_id`, `faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6. File Permissions

Set correct permissions in cPanel File Manager:
- PHP files: 644
- Directories: 755
- No files should be 777 (security risk)

### 7. PHP Version

Ensure cPanel is using PHP 7.4 or higher:
1. In cPanel, go to "Select PHP Version"
2. Choose PHP 7.4 or 8.x
3. Enable required extensions:
   - pdo
   - pdo_mysql
   - json
   - session

## Testing Steps

1. **Test database connection:**
   - Access `debug_test.php`
   - Verify database connection works

2. **Test session:**
   - Login to your system
   - Access `debug_test.php`
   - Verify faculty session shows your info

3. **Test subject loading:**
   - Open browser console (F12)
   - Go to subject assignment page
   - Click "Add Subject to Class"
   - Check console for errors

4. **Test assignment:**
   - Select a subject
   - Click "Assign Selected Subjects"
   - Check console for request/response
   - Check if subject appears in the list

## Quick Fixes Applied

The following improvements were made to your code:

1. **Better error handling** - All AJAX calls now log detailed errors
2. **JSON output protection** - Headers set before any output
3. **Console logging** - All AJAX requests/responses logged
4. **Debug information** - Error responses include file/line info
5. **Output buffering** - Prevents whitespace issues

#### Issue E: Redirect Loop / ERR_TOO_MANY_REDIRECTS
**Symptoms:** 
- "ERR_TOO_MANY_REDIRECTS" in console
- Status Code: 0 in AJAX error
- Response Text: undefined
- URL shows `/app/interval/interval/interval/...`

**Cause:** 
Your server has authentication middleware that's redirecting API requests, creating an infinite loop.

**Solutions:**

1. **Upload the app/.htaccess file** (already created in your project):
   - This file prevents authentication redirects for API endpoints
   - Upload `app/.htaccess` to your server's `app` directory

2. **Check for root .htaccess file on server:**
   - In cPanel File Manager, check if there's a `.htaccess` file in your root directory
   - Look for RewriteRules that redirect to `/interval/` or authentication pages
   - Add this exception BEFORE any authentication rules:
   ```apache
   # Allow API access without authentication
   RewriteCond %{REQUEST_URI} ^/app/
   RewriteRule ^ - [L]
   ```

3. **Alternative: Add authentication bypass in PHP:**
   - If .htaccess doesn't work, you can check the request source in your authentication files
   - In `interval/auth_faculty.php` or similar, add:
   ```php
   // Skip authentication for API endpoints
   if (strpos($_SERVER['REQUEST_URI'], '/app/') !== false) {
       return;
   }
   ```

4. **Check cPanel "Hotlink Protection":**
   - In cPanel, check if "Hotlink Protection" is enabled
   - If yes, add your domain to allowed URLs
   - Or disable it for the `/app/` directory

5. **Verify API files don't include session files:**
   - API files in `/app/` should NOT include `session_faculty.php`
   - They should handle authentication independently
   - Check `send_class_notification.php` - it should NOT have session includes

## Still Not Working?

If issues persist after trying the above:

1. Check the browser console for the exact error message
2. Check cPanel error logs for PHP errors
3. Verify all files uploaded correctly
4. Test with a simple subject first
5. Contact your hosting provider about:
   - Session configuration
   - PHP version and extensions
   - File permission issues
   - .htaccess RewriteRule conflicts
