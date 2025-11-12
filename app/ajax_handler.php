<?php
// app/ajax_handler.php
session_start();

// Security Check: Must be admin for ANY administrative AJAX action
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // HTTP 403 Forbidden status
    die("<div class='alert alert-danger'>Access Denied. Invalid session or role.</div>");
}

// Determine the requested action (e.g., 'get_student_list')
$action = $_GET['action'] ?? null;

// Route the request to the correct view file inside the views folder
switch ($action) {
    case 'get_student_list':
        include 'views/student_list.php';
        break;

    case 'get_manage_subjects':
        include 'views/manage_subjects.php';
        break;
        
    case 'get_manage_curriculum':
        include 'views/manage_curriculum.php';
        break;
    case 'get_create_student_form': 
        include 'views/create_student.php'; 
        break;
    case 'get_edit_student_form':
        include 'views/edit_student.php';
        break;
    case 'test_content':
        include 'views/create_students.php';  //Sample content that's suppose to crash since directory doesnt exist
        break;
    case 'get_grade_editor':
        include 'views/grade_editor.php';
        break;
    default:
        echo "<div class='alert alert-danger'>Invalid administrative action requested.</div>";
        break;
}
?>