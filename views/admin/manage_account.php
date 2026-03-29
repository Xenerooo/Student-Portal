<?php
// views/admin/manage_account.php
// Data provided by AdminController: $admin
?>

<h1 class="">Manage My Account</h1>
<div id="account-submission-message" class="mb-4 col-12 col-md-8 mx-auto"></div>

<div class="card shadow ">
    <div class="card-header bg-primary text-white">
        Account Settings
    </div>
    <div class="card-body">
        <form id="manageAccountForm">
            <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['admin_id']) ?>">
            
            <div class="mb-3">
                <label for="admin_name" class="form-label">Admin Full Name</label>
                <input type="text" name="admin_name" id="admin_name" class="form-control" 
                       value="<?= htmlspecialchars($admin['admin_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Login Username</label>
                <input type="text" name="username" id="username" class="form-control" 
                       value="<?= htmlspecialchars($admin['username']) ?>" required>
            </div>
            
            <hr>
            
            <h5 class="mt-4 mb-3">Change Password</h5>
            <p class="text-muted small">Only fill these out if you want to change your password.</p>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="Enter new password" minlength="6">
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                       placeholder="Repeat new password" minlength="6">
            </div>
            
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const formElement = document.getElementById('manageAccountForm');
        const messageDiv = document.getElementById('account-submission-message');

        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const password = formData.get('password');
                const confirmPassword = formData.get('confirm_password');

                if (password && password !== confirmPassword) {
                    messageDiv.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
                    return;
                }

                messageDiv.innerHTML = '<div class="alert alert-info">Updating account...</div>';

                fetch('/admin/api/account/update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    window.scrollTo(0, 0);
                    if (data.success) {
                        messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        // Optional: Clear password fields after success
                        document.getElementById('password').value = '';
                        document.getElementById('confirm_password').value = '';
                    } else {
                        messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Update Error:', error);
                    messageDiv.innerHTML = '<div class="alert alert-danger">An unexpected error occurred.</div>';
                });
            });
        }
    })();
</script>
