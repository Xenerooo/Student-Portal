<?php
// views/student/student_info.php
// Data provided by StudentController: $student

$hasPhoto = !empty($student['img']);
$photoSrc = $hasPhoto ? 'data:image/jpeg;base64,' . base64_encode($student['img']) : '';

$studentName = htmlspecialchars($student['student_name'] ?? 'N/A');
$studentNumber = htmlspecialchars($student['student_number'] ?? 'N/A');
$courseName = htmlspecialchars($student['course_name'] ?? 'N/A');
$birthday = !empty($student['birthday']) ? date('F j, Y', strtotime($student['birthday'])) : 'N/A';
$placeOfBirth = htmlspecialchars($student['place_of_birth'] ?? 'N/A');
$address = nl2br(htmlspecialchars($student['address'] ?? 'N/A'));
$lastSchool = htmlspecialchars($student['last_school_attended'] ?? 'N/A');
$contactNumber = htmlspecialchars($student['contact_number'] ?? 'N/A');
$email = htmlspecialchars($student['email'] ?? 'N/A');
$initials = strtoupper(substr(trim((string)($student['student_name'] ?? 'S')), 0, 1));
?>

<div class="container-fluid py-4 profile-shell">
    <div class="profile-hero p-4 p-md-5 mb-4">
        <div class="row align-items-center g-4 position-relative">
            <div class="col-12 col-md-auto text-center text-md-start">
                <div class="d-inline-flex position-relative">
                    <?php if ($hasPhoto): ?>
                        <img src="<?= $photoSrc ?>" alt="Student photo" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-fallback" aria-hidden="true">
                            <?= htmlspecialchars($initials ?: 'S') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge text-bg-light text-primary">Student Profile</span>
                    <span class="badge text-bg-light text-dark">ID: <?= $studentNumber ?></span>
                </div>
                <h2 class="display-6 fw-bold mb-2"><?= $studentName ?></h2>
                <p class="mb-3 fs-5 opacity-75"><?= $courseName ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">Birthday: <?= htmlspecialchars($birthday) ?></span>
                    <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">Email: <?= $email ?></span>
                    <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">Contact: <?= $contactNumber ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card profile-card h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <div class="section-label">Personal Details</div>
                    <h3 class="h4 mb-0 mt-1">Student Information</h3>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Student Number</div>
                                <div class="info-value"><?= $studentNumber ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= $studentName ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Course / Program</div>
                                <div class="info-value"><?= $courseName ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Birthday</div>
                                <div class="info-value"><?= htmlspecialchars($birthday) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Place of Birth</div>
                                <div class="info-value"><?= $placeOfBirth ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?= $contactNumber ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?= $address ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">School Last Attended</div>
                                <div class="info-value"><?= $lastSchool ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?= $email ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card profile-card security-panel h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="section-label">Account Security</div>
                    <h3 class="h4 mb-0 mt-1">Change Password</h3>
                    <p class="text-muted mb-0 mt-2">Use your current password, then choose a new one to keep your account secure.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!empty($_SESSION['must_change_password'])): ?>
                        <div class="alert alert-warning border-0">
                            You must change your temporary password before continuing to the portal.
                        </div>
                    <?php endif; ?>

                    <div class="password-badge mb-3">
                        <span>Secure access</span>
                    </div>

                    <form id="changePasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

                        <div class="mb-3">
                            <label for="oldPassword" class="form-label fw-semibold">Current Password</label>
                            <input type="password" name="old_password" id="oldPassword" class="form-control form-control-lg" required>
                        </div>

                        <div class="mb-3">
                            <label for="newPassword" class="form-label fw-semibold">New Password</label>
                            <input type="password" name="new_password" id="newPassword" class="form-control form-control-lg" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Update Password</button>
                    </form>
                    <div id="changePasswordFeedback" class="mt-3"></div>
                </div>
            </div>
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
            const res = await fetch('<?= APP_URL ?>/student/api/password/change', {
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
            }
        } catch (err) {
            feedback.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
        }
    });
</script>
