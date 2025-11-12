<?php
// app/student_ajax_handler.php
session_start();

// Security Check: Must be a student for ANY student AJAX action
if (!isset($_SESSION['student_id'])) {
    http_response_code(403); // HTTP 403 Forbidden status
    die("<div class='alert alert-danger'>Access Denied. Invalid session or not logged in as student.</div>");
}

// Determine the requested action (e.g., 'get_student_info', 'get_student_grades')
$action = $_GET['action'] ?? null;

// Route the request to the correct view file inside the views folder
switch ($action) {
    case 'get_student_info':
        include 'views/student_info.php';
        break;

    case 'get_student_grades':
        include 'views/student_grades.php';
        break;
        
    default:
        echo "<div class='alert alert-danger'>Invalid student action requested.</div>";
        break;
}
?>

