# Escalation Report: Logout Button Visibility Issue on Welcome Screen

## Issue Summary
**Priority:** High  
**Status:** Critical - Multiple Fix Attempts Failed  
**Component:** Authentication & Session Management  
**Repository:** https://github.com/nedbollard/tw4-golf-management  

## Problem Description
The logout button is not appearing on the welcome screen (`/`) when users are logged in, despite successful authentication. The welcome screen continues to show the "Login" button instead of the expected "Logged In" state with logout functionality.

## Expected Behavior
- User logs in successfully
- Welcome screen should display "Logged In" state
- Show user information (username, role)
- Display appropriate menu button (Admin/Scorer)
- Show "Logout" button for user switching

## Actual Behavior
- User logs in successfully (HTTP 302 redirect)
- Welcome screen continues to show "Login" button
- No user information displayed
- No logout button visible
- Session data appears to be lost

## Environment Details
- **Platform:** Docker containerized application
- **PHP Version:** 8.3.30
- **Database:** MySQL 8.0
- **Framework:** Custom MVC architecture
- **Session Storage:** File-based (/tmp)

## Detailed Investigation

### Authentication Flow Analysis
1. **Login Process:** 
   - POST to `/login` returns HTTP 302 (success)
   - AuthService debugging shows successful login
   - Session data is properly stored during login

2. **Session State:**
   - Session data contains: `user_id`, `username`, `user_role`
   - Session ID is generated and stored
   - Session cookie is set properly

3. **Welcome Screen Issue:**
   - HomeController detects `isLoggedIn: false`
   - Session data appears empty when accessing welcome screen
   - Session ID keeps changing between requests

### Root Cause Analysis
The issue appears to be in session persistence between the login process and the welcome screen access. Despite successful authentication, the session state is not being maintained across requests.

## Attempted Solutions

### 1. Auth Middleware Database Table Fix
**Problem:** Auth middleware was using 'users' table instead of 'staff' table
**Solution:** Updated Auth middleware to use correct staff table queries
**Result:** Fixed authentication queries but logout button still not visible

### 2. Session Structure Consistency
**Problem:** AuthService and Auth middleware used different session structures
**Solution:** Aligned both to use `user_id`, `username`, `user_role`
**Result:** Improved consistency but issue persisted

### 3. Comprehensive Test Suite
**Problem:** No tests for Auth middleware functionality
**Solution:** Created unit and integration tests for authentication
**Result:** Tests catch issues but don't resolve the visibility problem

### 4. Session Management Enhancement
**Problem:** Session save path was not configured
**Solution:** Added session_save_path('/tmp') configuration
**Result:** Session configuration improved but issue persisted

### 5. Session Cookie Parameters
**Problem:** Session cookie parameters not properly set
**Solution:** Added session_set_cookie_params() with proper configuration
**Result:** Enhanced session handling but logout button still not visible

### 6. Multiple Logout Access Points
**Problem:** Only welcome screen had logout functionality
**Solution:** Added logout buttons to admin and scorer menus
**Result:** More access points but welcome screen issue persists

## Debugging Evidence

### Successful Login Debug Output
```
AuthService: Login successful - Session data: Array
(
    [config] => Array
        (
            [club_name] => TW4 Golf Club
            [competition_name] => Twilight
            [season_year] => 25_26
        )
    [config_checked] => 1
    [user_id] => 11
    [username] => testlogout2
    [user_role] => admin
)
```

### Welcome Screen Debug Output
```
HomeController - isLoggedIn: false
HomeController - User: null
HomeController - Session ID: [various IDs]
HomeController - Session data: Array
(
    [config] => Array
        (
            [club_name] => TW4 Golf Club
            [config_name] => TW4 Golf Club - Twilight
        )
    [config_checked] => 1
    [errors] => Array
        (
            [login] => Invalid username or password
        )
)
```

## Key Files Involved
- `/src/Controllers/HomeController.php` - Welcome screen logic
- `/src/Services/AuthService.php` - Authentication service
- `/src/Middleware/Auth.php` - Authentication middleware
- `/src/Views/home/index.php` - Welcome screen template
- `/src/Controllers/AuthController.php` - Login/logout endpoints

## Current State
- Test user (`testlogout2`) can login successfully
- Session data is stored correctly during login
- Welcome screen still shows "Login" button
- Session persistence issue between login and welcome screen
- Multiple access points for logout (admin/scorer menus) work correctly

## Impact Assessment
- **User Experience:** Users cannot see logout button on welcome screen
- **User Switching:** Cannot easily switch between admin/scorer roles
- **Workflow:** Breaks expected user flow for role switching
- **Professionalism:** Inconsistent user experience

## Next Steps Required
1. **Deep Session Analysis:** Investigate why session data is not persisting
2. **Environment Review:** Check Docker/session storage configuration
3. **Alternative Approaches:** Consider different session handling methods
4. **Expert Consultation:** May require PHP session management specialist

## Technical Notes
- Issue appears to be in session persistence, not authentication logic
- Session data is correctly stored during login process
- Problem occurs when accessing welcome screen after login
- Session ID changes suggest session regeneration or loss

## Contact Information
**Repository:** https://github.com/nedbollard/tw4-golf-management  
**Issue Type:** Bug - Session Management  
**Severity:** High - Core functionality affected  

---

**Report Generated:** 2026-04-14  
**Reporter:** AI Assistant (Cascade)  
**Escalation Reason:** Multiple fix attempts failed, requires expert intervention
