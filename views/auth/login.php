<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/Student-Portal/assets/images/icon.png">
    
    <title>Portal Login</title>
    <link href="/Student-Portal/assets/css/bootstrap.css" rel="stylesheet" >
    <link href="/Student-Portal/assets/css/app.css" rel="stylesheet">

</head>
<body>
    <div class="login-shell">
        <div class="login-panel">
            <section class="login-brand">
                <img src="/Student-Portal/assets/images/icon.png" alt="Portal icon">
                <div class="text-uppercase small fw-semibold opacity-75 mb-2">Student Information System</div>
                <h1>Clearer access to student and admin workflows.</h1>
                <p class="mt-3">Sign in to review grades, manage records, and handle enrollment work from one streamlined portal.</p>
                <div class="mt-4">
                    <span class="login-feature">Secure sign-in</span>
                    <span class="login-feature">Student records</span>
                    <span class="login-feature">Enrollment tools</span>
                </div>
            </section>

            <section class="login-form-panel">
                <h2>Welcome back</h2>
                <p class="text-muted mb-4">Use your portal credentials to continue.</p>

                <form method="POST" action="/Student-Portal/login" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control" id="username" placeholder="25-001" required>
                        <label for="username">Username</label>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </section>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Login Failed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="errorModalMessage">
                    <!-- Error message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="/Student-Portal/assets/js/bootstrap.bundle.js"></script>
<script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const actionUrl = event.target.getAttribute('action');
        
        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]')?.value || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect on success
                window.location.href = data.redirect;
            } else {
                // Show modal and clear form on failure
                document.getElementById('errorModalMessage').textContent = data.message;
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
                
                // Clear form
                event.target.reset();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('errorModalMessage').textContent = 'An error occurred. Please try again.';
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        });
    });
</script>
</body>
</html>
