<?php
// app/views/edit_student.php
// This file is loaded via AJAX by ajax_handler.php for editing student information.

// The session is already started, and authorization is complete in ajax_handler.php.
require_once "includes/db_connect.php";
$conn = connect();

// Get student_id from GET parameter
$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);

if (!$student_id) {
    die("<div class='alert alert-danger'>Invalid student ID.</div>");
}

// Fetch student data with user info
$stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.student_name,
        s.student_number,
        s.course_id,
        s.birthday,
        s.img,
        u.user_id,
        u.username
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.student_id = ?
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $conn->close();
    die("<div class='alert alert-danger'>Student not found.</div>");
}

// Load courses for dropdown
$courses = [];
try {
    $courses_result = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name");
    if ($courses_result) {
        $courses = $courses_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // If error, courses array remains empty
}

$conn->close();
?>



<h1 class="mb-4 col-12 col-md-9 mx-auto">Edit Student Information</h1>
<div id="form-submission-message" class="mb-4 col-12 col-md-9 mx-auto"></div>

<div class="card shadow col-12 col-md-9 mx-auto " id="editStudentCard">
    
    <!-- <div class="row"> -->
    <!-- <div class="col-12 col-md-9 mx-auto"> -->
    <div class="card-header bg-primary text-white ">
        Edit Student Details
    </div>
    <div class="card-body">
        <form id="editStudentForm" enctype="multipart/form-data">
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($student['user_id']) ?>">
            
            <div class="mb-3">
                <label for="student_image" class="form-label">Profile Image (1x1)</label>
                <div class="mb-2">
                    <?php if (!empty($student['img'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($student['img']) ?>" 
                             alt="Current Image" 
                             style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                        <p class="text-muted small mt-1">Current image</p>
                    <?php else: ?>
                        <p class="text-muted">No image uploaded</p>
                    <?php endif; ?>
                </div>
                <input type="file" name="student_image" id="student_image" class="form-control" accept="image/*">
                <small class="form-text text-muted">Leave empty to keep current image</small>
            </div>

            <div class="mb-3">
                <label for="student_name" class="form-label">Full Name</label>
                <input type="text" name="student_name" id="student_name" class="form-control" 
                       value="<?= htmlspecialchars($student['student_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="student_number" class="form-label">Student ID/Number</label>
                <input type="text" name="student_number" id="student_number" class="form-control" 
                       value="<?= htmlspecialchars($student['student_number']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="birthday" class="form-label">Birthday</label>
                <input type="date" name="birthday" id="birthday" class="form-control" 
                       value="<?= htmlspecialchars($student['birthday'] ?? '') ?>" 
                       max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="mb-3">
                <label for="course_id" class="form-label">Course / Program</label>
                <select name="course_id" id="course_id" class="form-select" required>
                    <option value="" disabled>Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= htmlspecialchars($course['course_id']) ?>" 
                                <?= $course['course_id'] == $student['course_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <hr>
            
            <h5 class="mt-4 mb-3">Account Information</h5>
            
            <div class="mb-3">
                <label for="username" class="form-label">Login Username</label>
                <input type="text" name="username" id="username" class="form-control" 
                       value="<?= htmlspecialchars($student['username']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="Leave empty to keep current password" minlength="6">
                <small class="form-text text-muted">Minimum 6 characters. Leave empty to keep current password.</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success flex-fill">Update Student</button>
                <button type="button" class="btn btn-danger flex-fill" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Delete Student</button>
                <button type="button" class="btn btn-secondary cancel-edit-btn"> Cancel</button>
            </div>
        </form>
    </div>
    <!-- </div> -->
    <!-- </div> -->
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="confirmDeleteLabel">Confirm delete</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        This action cannot be undone. Are you sure you want to delete this student?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>

    (function() {
        // Handle cancel button click
        const cancelBtn = document.querySelector('.cancel-edit-btn');

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(){
                // Use the global loadContent function from admin_dashboard.php
                const action = typeof defaultContentAction !== 'undefined' ? defaultContentAction : 'get_student_list';
                const targetLink = document.querySelector(`[data-content="${action}"]`);
                
                if (typeof window.loadContent === 'function') {
                    window.loadContent(action, targetLink);
                } else {
                    // Fallback if loadContent is not available
                    console.error('loadContent function not available');
                    window.location.href = 'admin_dashboard.php';
                }
            });
        }

        

        function handleEditStudentSubmit(e) {
            e.preventDefault(); 
            console.log("Edit Student Form Submission Intercepted.");
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('form-submission-message');
            
            messageDiv.innerHTML = '<div class="alert alert-info">Processing...</div>';

            fetch('app/process_student_edit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Server Status Error:', response.status, text);
                            messageDiv.innerHTML = `<div class="alert alert-danger">Server Error (${response.status}): Check Console.</div>`;
                            throw new Error('Server returned error status.');
                        });
                    }
                    return response.json();
            })
            .then(data => {
                console.log("Server Response:", data);
                window.scrollTo(0, 0);
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    // Redirect to student list after success using the global loadContent function
                    setTimeout(() => {
                        const action = 'get_student_list';
                        const targetLink = document.querySelector(`[data-content="${action}"]`);
                        
                        if (typeof window.loadContent === 'function') {
                            window.loadContent(action, targetLink);
                        } else {
                            window.location.href = 'admin_dashboard.php';
                        }
                    }, 1500);
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Fetch/JSON Error:', error);
                if (!messageDiv.innerHTML.includes('Server Error')) {
                    messageDiv.innerHTML = `<div class="alert alert-danger">Network or JSON Parsing Error. See console.</div>`;
                }
            });
        }

        const formElement = document.getElementById('editStudentForm');
        if (formElement) {
            formElement.addEventListener('submit', handleEditStudentSubmit);
        } else {
            console.error("Form element 'editStudentForm' not found!");
        }

        // Confirm deletion via modal
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function(){
                const studentIdInput = document.querySelector('input[name="student_id"]');
                if (!studentIdInput) {
                    console.error('Hidden student_id input not found');
                    return;
                }
                const studentId = studentIdInput.value;
                if (!studentId) {
                    console.error('Student ID missing');
                    return;
                }

                const messageDiv = document.getElementById('form-submission-message');
                messageDiv.innerHTML = '<div class="alert alert-info">Deleting student...</div>';

                const formData = new FormData();
                formData.append('student_id', studentId);

                fetch('app/process_student_delete.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Server Status Error:', response.status, text);
                            messageDiv.innerHTML = `<div class="alert alert-danger">Server Error (${response.status}): Check Console.</div>`;
                            throw new Error('Server returned error status.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete Response:', data);
                    window.scrollTo(0, 0);
                    if (data.success) {
                        messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        // Hide modal and fade out card before navigating
                        try {
                            const modalEl = document.getElementById('confirmDeleteModal');
                            if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                                const instance = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                                instance.hide();
                            }
                        } catch (e) {}

                        const card = document.getElementById('editStudentCard');
                        if (card) {
                            card.style.transition = 'opacity 0.5s ease';
                            card.style.opacity = '0';
                        }

                        setTimeout(() => {
                            const action = 'get_student_list';
                            const targetLink = document.querySelector(`[data-content="${action}"]`);
                            if (typeof window.loadContent === 'function') {
                                window.loadContent(action, targetLink);
                            } else {
                                window.location.href = 'admin_dashboard.php';
                            }
                        }, 1000);
                    } else {
                        messageDiv.innerHTML = `<div class=\"alert alert-danger\">${data.message || 'Failed to delete student.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Delete Fetch/JSON Error:', error);
                    if (!messageDiv.innerHTML.includes('Server Error')) {
                        messageDiv.innerHTML = `<div class="alert alert-danger">Network or JSON Parsing Error. See console.</div>`;
                    }
                });
            });
        }
    })();
</script>
