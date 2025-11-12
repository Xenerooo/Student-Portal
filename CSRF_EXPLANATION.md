# CSRF Token Validation Explained

## What is CSRF?
**Cross-Site Request Forgery (CSRF)** is an attack where a malicious website tricks a logged-in user into performing actions on your website without their knowledge.

### Example Attack Scenario:
1. User is logged into your student portal (student_portal.com)
2. User visits a malicious website (evil-site.com)
3. Evil-site.com has a hidden form that submits to your change password endpoint
4. Since the user is logged in, the browser sends the request WITH their session cookies
5. **Without CSRF protection:** Your server thinks it's a legitimate request and changes the password!
6. **With CSRF protection:** The attack fails because the malicious site can't get the CSRF token

---

## How CSRF Tokens Work

### Step 1: Generate Token (in student_info.php)
```php
// Generate a random secret token (64-character hex string)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
```

**What happens:**
- Server creates a random secret token
- Stores it in `$_SESSION['csrf']` (server-side only)
- This token is unique per user session

### Step 2: Include Token in Form (in student_info.php)
```html
<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
```

**What happens:**
- Token is embedded as a hidden field in the form
- When user submits, the token is sent along with other form data

### Step 3: Validate Token (in process_change_password.php)
```php
$sentCsrf = $_POST['csrf'] ?? '';
$sessionCsrf = $_SESSION['csrf'] ?? '';
if (!$sentCsrf || !$sessionCsrf || !hash_equals($sessionCsrf, $sentCsrf)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}
```

---

## Line-by-Line Breakdown

### Line 22: Get Token from Form
```php
$sentCsrf = $_POST['csrf'] ?? '';
```
- Gets the CSRF token that was sent in the form submission
- `?? ''` means "use empty string if not set"
- This is what the client (browser) sent

### Line 23: Get Token from Session
```php
$sessionCsrf = $_SESSION['csrf'] ?? '';
```
- Gets the CSRF token stored in the server session
- This is the "real" token that the server knows about
- Only the legitimate user's browser should have this

### Line 24: Compare Tokens
```php
if (!$sentCsrf || !$sessionCsrf || !hash_equals($sessionCsrf, $sentCsrf)) {
```

**Three checks:**

1. **`!$sentCsrf`** - Is the token missing from the form?
   - If yes → REJECT (attack or broken form)

2. **`!$sessionCsrf`** - Is the token missing from the session?
   - If yes → REJECT (session expired or not logged in properly)

3. **`!hash_equals($sessionCsrf, $sentCsrf)`** - Do the tokens match?
   - Uses `hash_equals()` instead of `==` for security (prevents timing attacks)
   - If they don't match → REJECT (tampered token or attack)

**If ANY of these are true → REJECT the request**

### Line 25-27: Reject Invalid Request
```php
http_response_code(419);
echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
exit();
```
- HTTP 419 is "Page Expired" (commonly used for CSRF failures)
- Returns JSON error message
- Stops execution immediately

---

## Why `hash_equals()` Instead of `==`?

**Regular comparison (`==`):**
```php
if ($sessionCsrf == $sentCsrf) { // ❌ Vulnerable to timing attacks
```

**Secure comparison (`hash_equals()`):**
```php
if (hash_equals($sessionCsrf, $sentCsrf)) { // ✅ Safe
```

**Why?** `hash_equals()` takes the same amount of time regardless of where the strings differ. Regular `==` can leak information through timing differences, allowing attackers to guess tokens character by character.

---

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. User visits student_info.php                             │
│    Server generates: $_SESSION['csrf'] = "abc123..."        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Form is displayed with hidden field:                     │
│    <input name="csrf" value="abc123...">                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. User submits form                                        │
│    Browser sends: POST['csrf'] = "abc123..."                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. process_change_password.php validates:                   │
│    • $_POST['csrf'] exists? ✓                              │
│    • $_SESSION['csrf'] exists? ✓                           │
│    • Do they match? ✓                                       │
│    → ALLOW request                                          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ATTACK SCENARIO: Malicious site tries to submit             │
│    • $_POST['csrf'] = "fake_token" (or missing)            │
│    • $_SESSION['csrf'] = "abc123..." (real token)          │
│    • Do they match? ✗                                       │
│    → REJECT request (419 error)                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Real-World Example

### Without CSRF Protection (VULNERABLE):
```html
<!-- Evil website (evil-site.com) -->
<form action="https://student-portal.com/app/process_change_password.php" method="POST">
    <input name="old_password" value="hack123">
    <input name="new_password" value="hacker_wins">
    <input name="username" value="victim">
    <!-- No CSRF token needed! -->
</form>
<script>document.forms[0].submit();</script>
```
**Result:** If user is logged in, password gets changed! 😱

### With CSRF Protection (SECURE):
```html
<!-- Evil website (evil-site.com) -->
<form action="https://student-portal.com/app/process_change_password.php" method="POST">
    <input name="old_password" value="hack123">
    <input name="new_password" value="hacker_wins">
    <input name="username" value="victim">
    <input name="csrf" value="fake_token"> <!-- ❌ Wrong token! -->
</form>
<script>document.forms[0].submit();</script>
```
**Result:** Server checks token, sees it doesn't match session, REJECTS request! ✅

---

## Key Takeaways

1. **CSRF tokens prevent unauthorized actions** from malicious websites
2. **Token is generated server-side** and stored in session
3. **Token is included in forms** as a hidden field
4. **Server validates token** on every sensitive request
5. **`hash_equals()` prevents timing attacks** when comparing tokens
6. **If tokens don't match → request is rejected** (HTTP 419)

---

## Summary of Your Code

```php
// Get token from form submission
$sentCsrf = $_POST['csrf'] ?? '';

// Get token from server session
$sessionCsrf = $_SESSION['csrf'] ?? '';

// Validate: both must exist AND match
if (!$sentCsrf || !$sessionCsrf || !hash_equals($sessionCsrf, $sentCsrf)) {
    // Reject if: missing from form OR missing from session OR don't match
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// If we get here, tokens are valid - proceed with password change
```

This is a **security best practice** for protecting state-changing operations (like changing passwords, deleting data, etc.).

