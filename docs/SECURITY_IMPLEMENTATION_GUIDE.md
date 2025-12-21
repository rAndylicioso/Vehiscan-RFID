# Implementation Guide: Security Improvements

This guide shows how to implement the InputSanitizer in your API endpoints.

## Quick Start

### 1. Include the InputSanitizer

Add this at the top of your PHP files:

```php
<?php
require_once __DIR__ . '/../includes/input_sanitizer.php';
// or adjust path based on file location
```

### 2. Replace Direct $_POST Usage

**Before (Vulnerable):**
```php
$userId = $_POST['user_id'] ?? null;
$email = $_POST['email'] ?? '';
$age = $_POST['age'] ?? 0;
```

**After (Secure):**
```php
$userId = InputSanitizer::post('user_id', 'int');
$email = InputSanitizer::post('email', 'email');
$age = InputSanitizer::post('age', 'int', 0);
```

### 3. Add CSRF Protection

**Generate Token (in forms):**
```php
<input type="hidden" name="csrf_token" value="<?= InputSanitizer::generateCsrf() ?>">
```

**Validate Token (in API):**
```php
$csrfToken = InputSanitizer::post('csrf_token');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}
```

## Examples by File Type

### Example 1: Admin API Endpoint

**File:** `admin/api/approve_user_account.php`

**Before:**
```php
<?php
session_start();
require_once '../../db.php';

$userId = $_POST['user_id'] ?? null;
$role = $_POST['role'] ?? null;

if (!$userId || !$role) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}
```

**After:**
```php
<?php
session_start();
require_once '../../db.php';
require_once '../../includes/input_sanitizer.php';

// Validate CSRF token first
$csrfToken = InputSanitizer::post('csrf_token');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Sanitize inputs
$userId = InputSanitizer::post('user_id', 'int');
$role = InputSanitizer::post('role', 'string');

if (!$userId || !$role) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Whitelist validation for role
$allowedRoles = ['homeowner', 'guard', 'admin'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}
```

### Example 2: Employee Save API

**File:** `admin/api/employee_save.php`

**Before:**
```php
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$contact = $_POST['contact_number'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? '';
```

**After:**
```php
// CSRF validation
$csrfToken = InputSanitizer::post('csrf_token');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Sanitize all inputs
$firstName = InputSanitizer::post('first_name', 'string');
$lastName = InputSanitizer::post('last_name', 'string');
$contact = InputSanitizer::post('contact_number', 'string');
$email = InputSanitizer::post('email', 'email');
$role = InputSanitizer::post('role', 'string');

// Additional validation
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}
```

### Example 3: Visitor Pass Creation

**File:** `admin/api/create_visitor_pass.php`

**Before:**
```php
$homeownerId = $_POST['homeowner_id'] ?? null;
$visitorName = $_POST['visitor_name'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$validFrom = $_POST['valid_from'] ?? null;
```

**After:**
```php
// CSRF validation
$csrfToken = InputSanitizer::post('csrf_token');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Sanitize inputs
$homeownerId = InputSanitizer::post('homeowner_id', 'int');
$visitorName = InputSanitizer::post('visitor_name', 'string');
$purpose = InputSanitizer::post('purpose', 'string');
$validFrom = InputSanitizer::post('valid_from', 'string'); // Date string

// Validate required fields
if (!$homeownerId || !$visitorName || !$validFrom) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Additional date validation
if (!strtotime($validFrom)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}
```

## Complete List of Files to Update

### High Priority (Admin API - 10 files)
1. `admin/api/approve_user_account.php`
2. `admin/api/approve_visitor_pass.php`
3. `admin/api/cancel_visitor_pass.php`
4. `admin/api/create_visitor_pass.php`
5. `admin/api/employee_delete.php`
6. `admin/api/employee_save.php`
7. `admin/api/reject_visitor_pass.php`
8. `admin/api/visitor_pass_form.php`
9. `admin/api/employee_form.php`
10. `admin/api/get_pending_accounts.php`

### Medium Priority (Other API - 4 files)
11. `api/homeowner_save.php`
12. `api/homeowners_get.php`
13. `admin/employee_registration.php`
14. `admin/employee_edit.php`

### All Input Types Reference

```php
// String (default)
$name = InputSanitizer::post('name', 'string');
$name = InputSanitizer::post('name'); // same as above

// Integer
$id = InputSanitizer::post('id', 'int');
$count = InputSanitizer::post('count', 'integer');

// Email
$email = InputSanitizer::post('email', 'email');

// Boolean
$isActive = InputSanitizer::post('is_active', 'bool');
$remember = InputSanitizer::post('remember_me', 'boolean');

// Float
$price = InputSanitizer::post('price', 'float');
$rating = InputSanitizer::post('rating', 'double');

// Array
$tags = InputSanitizer::post('tags', 'array');

// URL
$website = InputSanitizer::post('website', 'url');

// With default value
$page = InputSanitizer::post('page', 'int', 1);
$sortBy = InputSanitizer::post('sort_by', 'string', 'created_at');
```

## Testing Your Changes

After updating files, test each endpoint:

1. **Test Normal Flow:** Submit valid data and verify it works
2. **Test XSS Protection:** Try `<script>alert('xss')</script>` in text fields
3. **Test SQL Injection:** Try `'; DROP TABLE users; --` in text fields
4. **Test Type Validation:** Submit wrong types (string for int, etc.)
5. **Test CSRF:** Submit form without CSRF token

## Checklist

- [ ] Include `input_sanitizer.php` in all API files
- [ ] Replace all `$_POST['key']` with `InputSanitizer::post('key', 'type')`
- [ ] Replace all `$_GET['key']` with `InputSanitizer::get('key', 'type')`
- [ ] Add CSRF token generation in forms
- [ ] Add CSRF token validation in API handlers
- [ ] Add whitelist validation for enums (role, status, etc.)
- [ ] Test all endpoints with malicious input
- [ ] Update forms to include CSRF token field

## Common Patterns

### Pattern 1: Simple Form Handler
```php
<?php
session_start();
require_once '../db.php';
require_once '../includes/input_sanitizer.php';

// CSRF check
if (!InputSanitizer::validateCsrf(InputSanitizer::post('csrf_token'))) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

// Get and sanitize data
$data = [
    'name' => InputSanitizer::post('name', 'string'),
    'email' => InputSanitizer::post('email', 'email'),
    'age' => InputSanitizer::post('age', 'int', 0)
];

// Validate
if (!$data['email']) {
    die(json_encode(['success' => false, 'message' => 'Invalid email']));
}

// Process...
```

### Pattern 2: File Upload Handler
```php
// Validate file
$validation = InputSanitizer::validateFileUpload(
    $_FILES['photo'],
    ['image/jpeg', 'image/png'],
    5242880 // 5MB
);

if (!$validation['valid']) {
    die(json_encode(['success' => false, 'message' => $validation['error']]));
}

// Sanitize filename
$filename = InputSanitizer::sanitizeFilename($_FILES['photo']['name']);
```

## Need Help?

If you encounter issues:
1. Check the `InputSanitizer` class documentation in `/includes/input_sanitizer.php`
2. Run `php _diagnostics/code_quality_checker.php` to find remaining issues
3. Test with the comprehensive test suite: `php _diagnostics/comprehensive_system_test.php`
