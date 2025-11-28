<?php
// app/views/student_info.php
// This file is loaded via AJAX by student_ajax_handler.php.
// The session is already started, and authorization is complete in student_ajax_handler.php.

require_once 'includes/db_connect.php';
require_once 'includes/utilities.php';
$conn = connect();

$student_id = $_SESSION['student_id'];

// Get student info + course details
// $stmt = $conn->prepare("
//     SELECT 
//         st.img,
//         st.student_id,
//         st.student_number,
//         st.student_name, 
//         st.course_id, 
//         st.birthday,
//         c.course_name
//     FROM students st
//     JOIN courses c ON st.course_id = c.course_id
//     WHERE st.student_id = ?
// ");

$stmt = $conn->prepare("CALL getStudentDetailsByStudentId(?);");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?;");
$stmt->bind_param("i", $student['course_id']);
$stmt->execute();
$student['course_name'] = $stmt->get_result()->fetch_assoc()['course_name'];
$stmt->close();



// $stmt->bind_param("i", $student_id);
// $stmt->execute();
// $student = $stmt->get_result()->fetch_assoc();

$conn->close();
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
                    <?php
                    if (empty($_SESSION['csrf'])) {
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    }
                    $csrf = $_SESSION['csrf'];
                    ?>
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Old Password</label>
                            <input type="password" name="old_password" id="oldPassword" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="newPassword" class="form-control" required>
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
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

    document.getElementById('changePasswordForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = new FormData(form);
    const feedback = document.getElementById('changePasswordFeedback');
    feedback.innerHTML = '';
    try {
        const res = await fetch('app/process_change_password.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        });
        // await console.log(res.clone().text());
        const json = await res.json();
        const cls = json.success ? 'alert-success' : 'alert-danger';
        feedback.innerHTML = `<div class="alert ${cls}">${json.message || (json.success ? 'Password updated.' : 'Failed to update password.')}</div>`;
        if (json.success) {
            form.reset();
        }
    } catch (err) {
        feedback.innerHTML = '<div class="alert alert-danger">Network error. Please try again. Error: ' + err + '</div>';
    }
});
</script>
