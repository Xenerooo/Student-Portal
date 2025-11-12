<?php
// app/views/create_student.php
// This file is loaded via AJAX by ajax_handler.php.

// The session is already started, and authorization is complete in ajax_handler.php.
require_once "includes/db_connect.php";
$conn = connect();

// 1. Data Retrieval: Load Courses for Dropdown
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

// NOTE: No POST handling here! The form submits to a different endpoint.
?>

<h1 class="mb-4">Create New Student Account</h1>
<div id="form-submission-message"></div>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        Student Registration Details
    </div>
    <div class="card-body">
        <form id="createStudentForm" enctype="multipart/form-data"> 
            
            <div class="mb-3">
                <label for="student_image" class="form-label">Profile Image (1x1)</label>
                <input type="file" name="student_image" id="student_image" class="form-control" accept="image/*">
                <small class="form-text text-muted">Optional. Upload a square image for best results.</small>
            </div>
            
            
            <div class="mb-3">
                <label for="student_name" class="form-label">Full Name</label>
                <input type="text" name="student_name" id="student_name" class="form-control" placeholder="e.g., Jane D. Doe" required>
            </div>

            <div class="mb-3">
                <label for="student_number" class="form-label">Student ID/Number</label>
                <input type="text" name="student_number" id="student_number" class="form-control" placeholder="e.g., 2024-00123" required>
            </div>

            <div class="mb-3">
                <label for="birthday" class="form-label">Birthday</label>
                <input type="date" name="birthday" id="birthday" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="course_id" class="form-label">Course / Program</label>
                <select name="course_id" id="course_id" class="form-select" required>
                    <option value="" disabled selected>Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <label for="username" class="form-label">Login Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="e.g., jdoe2024" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Initial Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-4">Create Student</button>
            <button type="button" class="btn btn-secondary w-100 mt-2" onclick="loadContent('get_student_list', document.querySelector('[data-content=\"get_student_list\"]'))">Cancel</button>
        </form>
    </div>
</div>

<script>
    function handleCreateStudentSubmit(e) {
        e.preventDefault(); 
        console.log("Native JS Submission Intercepted.");
        
        const form = e.target;
        const formData = new FormData(form);
        const messageDiv = document.getElementById('form-submission-message');
        
        messageDiv.innerHTML = '<div class="alert alert-info">Processing...</div>';

        fetch('app/process_student_create.php', {
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
            if (data.success) {
                messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                form.reset();
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


    const student_number_input = document.getElementById('student_number');
    const student_username_input = document.getElementById('username');
    student_number_input.addEventListener('input', function() {
        student_username_input.value = student_number_input.value
    });

    const formElement = document.getElementById('createStudentForm');
    if (formElement) {
        formElement.addEventListener('submit', handleCreateStudentSubmit);
    } else {
        console.error("Form element 'createStudentForm' not found!");
    }
</script>