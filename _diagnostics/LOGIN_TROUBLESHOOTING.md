# Login & Session Troubleshooting Guide

## Issue Summary
User reported that login is not working properly for admin role, and errors still persist in the system.

## Root Cause Analysis

### Session Architecture
The system uses **role-specific session names**:
- **Super Admin:** `vehiscan_superadmin`
- **Admin:** `vehiscan_admin`  
- **Guard:** `vehiscan_guard`
- **Homeowner:** (uses default session)

### The Problem
When a regular admin user (like ydnAr, Admin1, Admin0) logs in:
1. Login.php creates session with name `vehiscan_admin`
2. Sets `$_SESSION['role'] = 'admin'`
3. Redirects to admin_panel.php

However, some AJAX endpoints were not properly checking the `vehiscan_admin` session.

## Fixes Applied

### 1. Updated fetch_approvals.php
**File:** [admin/fetch/fetch_approvals.php](admin/fetch/fetch_approvals.php)

**Old Logic:**
- Only checked `vehiscan_superadmin` first
- Then fell back to `vehiscan_admin`
- Didn't check for session cookies properly

**New Logic:**
```php
// Check for session cookie first (more reliable)
if (isset($_COOKIE['vehiscan_admin'])) {
    session_name('vehiscan_admin');
    @session_start();
    // Verify it's a valid admin session
}

// Then try super admin
if (!$sessionFound && isset($_COOKIE['vehiscan_superadmin'])) {
    session_name('vehiscan_superadmin');
    @session_start();
    // Verify it's a valid super_admin session
}
```

**Why This Works:**
- Checks browser cookies FIRST to know which session type exists
- More reliable than blindly trying session names
- Prevents session conflicts

## Testing Tools Created

### 1. Cookie Inspector
**URL:** `http://localhost/Vehiscan-RFID/_diagnostics/cookie_inspector.html`
**Purpose:** View all browser cookies and active sessions
**Features:**
- Shows which session cookies exist
- Displays active session types
- Real-time session status

### 2. Session Debug Enhanced
**URL:** `http://localhost/Vehiscan-RFID/_diagnostics/session_debug_enhanced.php`
**Purpose:** Server-side session inspection
**Features:**
- Checks all 3 session types
- Shows session data
- Identifies which role is logged in

### 3. Login Test Page
**URL:** `http://localhost/Vehiscan-RFID/_diagnostics/login_test.html`
**Purpose:** Test login flow and approvals access
**Features:**
- Check current login status
- Test approvals endpoint
- Links to admin panel

## Users in Database

### Super Admin Table
- **Username:** Administrator
- **Email:** itstotallynotrandy@gmail.com
- **Role:** super_admin

### Regular Users Table (Admin)
- **ID 15:** ydnAr (admin)
- **ID 19:** Admin1 (admin)
- **ID 20:** Admin0 (admin)

### Regular Users Table (Guard)
- **ID 11:** jojo (guard)
- **ID 14:** jaja (guard)
- **ID 16:** Bato (guard)
- **ID 18:** Guard1 (guard)

## Troubleshooting Steps

### Step 1: Check if you're logged in
1. Open: `http://localhost/Vehiscan-RFID/_diagnostics/cookie_inspector.html`
2. Look for "Login Status" section
3. Should show "✓ LOGGED IN as Admin" or "✓ LOGGED IN as Super Admin"

### Step 2: If NOT logged in
1. Go to: `http://localhost/Vehiscan-RFID/auth/login.php`
2. Login with one of these accounts:
   - **Super Admin:** Username: `Administrator` 
   - **Admin:** Username: `Admin1` or `ydnAr` or `Admin0`
3. After login, check cookie inspector again

### Step 3: Test Approvals Access
1. Open: `http://localhost/Vehiscan-RFID/_diagnostics/login_test.html`
2. Click "Test fetch_approvals.php"
3. Should show "SUCCESS: Approvals page loaded correctly!"

### Step 4: Access Admin Panel
1. Go to: `http://localhost/Vehiscan-RFID/admin/admin_panel.php`
2. Should load successfully
3. Navigate to "Account Approvals"
4. Should load without "Unauthorized" error

## Common Issues & Solutions

### Issue 1: "Unauthorized" error on Approvals
**Symptoms:** Click Account Approvals → Error message
**Cause:** Session not detected by fetch_approvals.php
**Solution:** 
- Logout completely
- Clear browser cookies
- Login again
- Session cookie should be created properly

### Issue 2: Can't login at all
**Symptoms:** Login form redirects back with error
**Check:**
1. Verify username exists in database (use check_users.php)
2. Check password is correct
3. Look at login.php URL for error parameter: `?error=invalid_credentials`

### Issue 3: Login works but admin panel says unauthorized
**Symptoms:** Login successful but admin_panel.php kicks you out
**Cause:** Session name mismatch
**Debug:**
```php
// Add to admin_panel.php temporarily
echo "Session Name: " . session_name() . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NONE') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NONE');
exit();
```

### Issue 4: Multiple tabs/windows cause logout
**Cause:** Session strict mode conflict
**Solution:** Already handled in session configuration:
```php
ini_set('session.use_strict_mode', 0); // Allows multiple tabs
```

## Verification Checklist

- [ ] Can login as Administrator (super_admin)
- [ ] Can login as Admin1 (admin)
- [ ] Cookie inspector shows active session
- [ ] Admin panel loads successfully
- [ ] Account Approvals page loads without error
- [ ] Can approve/reject accounts
- [ ] Employee creation works
- [ ] Guard visitor passes load

## Files Modified This Session

1. **admin/fetch/fetch_approvals.php**
   - Added cookie-based session detection
   - Improved error messages with debug info
   - Better session fallback logic

2. **_diagnostics/cookie_inspector.html** *(NEW)*
   - Cookie and session viewer
   - Real-time status checking

3. **_diagnostics/session_debug_enhanced.php** *(NEW)*
   - Server-side session checker
   - Tests all 3 session types

4. **_diagnostics/login_test.html** *(NEW)*
   - Login flow tester
   - Approvals endpoint tester

5. **_diagnostics/check_users.php** *(NEW)*
   - Shows all users and roles in database

## Next Steps

1. **Test the login:**
   - Try logging in as Admin1
   - Check cookie inspector
   - Verify session is active

2. **Test admin functions:**
   - Account Approvals
   - Employee Management
   - All other admin features

3. **If still not working:**
   - Share screenshot of cookie inspector
   - Share screenshot of login page with error
   - Check browser console for JavaScript errors

## Important Notes

- **Session names are case-sensitive**
- **Cookies expire after browser closes** (unless remember me)
- **30-minute timeout** for admin sessions
- **AJAX calls must include credentials** (credentials: 'include')
- **Session cookies are httpOnly** (can't access via JavaScript)

---

**Last Updated:** December 16, 2025  
**Status:** Session detection improved, cookie-based checking added  
**Testing Required:** Login as regular admin and test all features
