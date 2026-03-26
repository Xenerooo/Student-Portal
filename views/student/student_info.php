<?php
// views/student/student_info.php
// Data provided by StudentController: $student
?>


<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 col-md-7 mx-auto">
            <h2 class="mb-4 ">Student Information</h2>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>1x1 Image:</strong></div>
                        <div class="col-md-3 ">
                            <div class="atio ratio-1x1" ">
                                 <?= '<img style="width: 100%; height: 100%;" src="data:image/jpeg;base64,'.base64_encode($student['img'] ?? '') .'" alt="assets/images/person.svg" />' ?>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Student Number:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Student Name:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['student_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Course/Program:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['course_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Birthday:</strong></div>
                        <div class="col-md-9">                                                                                                        
                            <?= htmlspecialchars(empty($student['birthday']) ? 'null' : date('F j, Y ', strtotime($student['birthday']))) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Place of Birth:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['place_of_birth'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Address:</strong></div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($student['address'] ?? 'N/A')) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>School Last Attended:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['last_school_attended'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Contact Number:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['contact_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Email:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 col-md-7 mx-auto mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Details</h5>
                </div>
                <div class="card-body">
                    <form id="changePasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">

                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Current Password</label>
                            <input type="password" name="old_password" id="oldPassword" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="newPassword" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mt-2">Change Password</button>
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
            const res = await fetch('/Student-Portal/student/api/password/change', {
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
