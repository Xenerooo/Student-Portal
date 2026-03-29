<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= APP_URL ?>/assets/images/icon.png">
    
    <title>Portal Login</title>
    <link href="<?= APP_URL ?>/assets/css/bootstrap.css" rel="stylesheet" >
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet" >
    <style>
        body {
            background-color: #3a522e; /* Hunter Green */
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
            background: radial-gradient(circle at top right, #585f56 0%, #3a522e 100%);
            padding: 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 10;
        }
        .login-logo {
            width: 88px;
            height: 88px;
            border-radius: 1.25rem;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }
        .login-logo img {
            border-radius: 0.5rem;
        }
        .login-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .form-floating > label {
            color: #64748b;
        }
        .form-control {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            background-color: #f8fafc;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: #3a522e;
            box-shadow: 0 0 0 4px rgba(58, 82, 46, 0.15);
        }
        .btn-login {
            background-color: #3a522e;
            color: white;
            border: none;
            border-radius: 9999px;
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            margin-top: 1rem;
        }
        .btn-login:hover {
            background-color: #0c0c0c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(12, 12, 12, 0.3);
        }
        
        /* Decorative circle behind card */
        .decor-circle {
            position: absolute;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            z-index: 1;
        }
        .circle-1 {
            width: 500px;
            height: 500px;
            top: -150px;
            right: -150px;
        }
        .circle-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -100px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper position-relative overflow-hidden">
        <div class="decor-circle circle-1"></div>
        <div class="decor-circle circle-2"></div>
        
        <div class="login-card">
            <div class="login-logo">
                <img src="<?= APP_URL ?>/assets/images/icon.png" alt="" width="64" height="64">
            </div>
            
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your account to continue</p>

            <form method="POST" action="<?= APP_URL ?>/login" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <button type="submit" class="btn btn-login w-100">Sign In</button>
            </form>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 1.25rem;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="errorModalLabel" style="color: #0f172a;">Login Failed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-danger pt-3 pb-4" id="errorModalMessage">
                    <!-- Error message will be inserted here -->
                </div>
            </div>
        </div>
    </div>

<script src="<?= APP_URL ?>/assets/js/bootstrap.bundle.js"></script>
<script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing in...';
        btn.disabled = true;

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
                
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('errorModalMessage').textContent = 'An error occurred. Please try again.';
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            
            // Reset button
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
</script>
</body>
</html>
