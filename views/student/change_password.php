<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="container py-5" style="max-width: 720px;">
    <div class="text-center mb-4">
        <img src="/assets/images/icon.png" alt="School Logo" height="72" class="mb-3">
        <h1 class="h3 mb-2">Change Your Password</h1>
        <p class="text-muted mb-0">
            <?php if (!empty($account['username'])): ?>
                Account: <strong><?= htmlspecialchars($account['username']) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <?php if (!empty($_SESSION['must_change_password'])): ?>
                <div class="alert alert-warning">
                    You must change your temporary password before continuing to the portal.
                </div>
            <?php endif; ?>

            <form id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

                <div class="mb-3">
                    <label for="old_password" class="form-label">Current Password</label>
                    <input type="password" name="old_password" id="old_password" class="form-control" required>
                    <div class="form-text">Use the temporary password sent to your email.</div>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Password</button>
            </form>

            <div id="changePasswordFeedback" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
    document.getElementById('changePasswordForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = e.currentTarget;
        const feedback = document.getElementById('changePasswordFeedback');
        const formData = new FormData(form);

        feedback.innerHTML = '<div class="alert alert-info">Updating password...</div>';

        try {
            const res = await fetch('/student/api/password/change', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': formData.get('csrf_token') || ''
                }
            });

            const json = await res.json();
            const cls = json.success ? 'alert-success' : 'alert-danger';
            feedback.innerHTML = `<div class="alert ${cls}">${json.message || (json.success ? 'Password updated.' : 'Failed to update password.')}</div>`;

            if (json.success) {
                form.reset();
                window.location.href = json.redirect || '/student/dashboard';
            }
        } catch (err) {
            feedback.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
        }
    });
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
