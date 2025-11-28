<?php
session_start();
require_once "app/includes/db_connect.php";
$conn = connect();

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    $conn->close();
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Get student info + course for the welcome message
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


$conn->close();
?>


<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet"> 
    <link rel="icon" href="assets/images/icon.png">

    <script defer src="assets/js/bootstrap.bundle.js"></script>
</head>
    
<body class="vh-100">
    <nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="assets/images/icon.png" alt="School Logo" height=32">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                 <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-content="get_student_info">Student Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-content="get_student_grades">Grades</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    
    <div class="container-fluid mt-4">
        <div class="mb-3">
            <h2>Welcome, <?= htmlspecialchars($student['student_name']) ?></h2>
            <p class="text-muted">Course: <?= htmlspecialchars($student['course_name']) ?></p>
        </div>
        
        <div id="main-content-area"">
            <div class="d-flex justify-content-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const contentArea = document.getElementById('main-content-area');
            const navLinks = document.querySelectorAll('.nav-link[data-content]');
            const ajaxHandlerPath = 'app/student_ajax_handler.php';

            async function loadContent(action, targetLink) {
                // 1. Show loading spinner
                contentArea.innerHTML = `
                    <div class="d-flex justify-content-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                // await sleep(5000);

                const url = `${ajaxHandlerPath}?action=${action}`;

                // 2. Fetch content via AJAX
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok. Status: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        // --- CRITICAL FIX: Manually Parse and Execute Scripts ---
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.body;
                        const scripts = doc.querySelectorAll('script');

                        // 3. Inject new content HTML
                        contentArea.innerHTML = newContent.innerHTML;

                        // 4. Execute scripts after the DOM elements are present
                        const existingExternalScripts = new Set(
                            Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
                        );
                        scripts.forEach(script => {
                            const newScript = document.createElement('script');
                            if (script.src) {
                                if (existingExternalScripts.has(script.src)) {
                                    return; // skip duplicate external script
                                }
                                newScript.src = script.src;
                                existingExternalScripts.add(script.src);
                            } else {
                                // Inline scripts: wrap in IIFE to isolate scope
                                newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                            }
                            document.body.appendChild(newScript);
                            newScript.remove();
                        });
                        // --------------------------------------------------------
                        
                        // 5. Update the 'active' class on the navigation bar
                        navLinks.forEach(link => link.classList.remove('active'));
                        if (targetLink) {
                            targetLink.classList.add('active');
                        }
                        
                        // 6. Update the URL in the browser bar
                        history.pushState(null, '', `student_dashboard.php?view=${action}`);
                    })
                    .catch(error => {
                        contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                        console.error('AJAX Error:', error);
                    });
            }

            // Set up navigation click handlers
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    const actionName = this.getAttribute('data-content'); 
                    loadContent(actionName, this);
                });
            });

            // Initial Load: Load student grades when the dashboard first opens
            const defaultContentAction = 'get_student_grades';
            const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);
            
            // Mark the default link active and load content
            if(defaultLink) {
                defaultLink.classList.add('active');
            }
            loadContent(defaultContentAction, defaultLink);
        });
    </script>

</html>
