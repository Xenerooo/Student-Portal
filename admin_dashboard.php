<?php
// admin_dashboard.php - The main shell that loads content via AJAX
session_start();
// NOTE: db_connect is included here but the connection is closed immediately.
// You must move db_connect.php to the app/includes/ directory as planned.
require_once "app/includes/db_connect.php"; 
$conn = connect();

// Authorization Check (Crucial)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $conn->close();
    header("Location: index.php");
    exit();
}

$conn->close(); // Close connection early since the shell does not query the DB
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SIS</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet"> 
    <script defer src="assets/js/bootstrap.bundle.js"></script>
    <link rel="icon" href="assets/images/icon.png">
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
                    <a class="nav-link" href="#" data-content="get_student_list">Student List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_manage_subjects">Manage Subjects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_manage_curriculum">Manage Curriculum</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_create_student_form">Create Student</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="none">VIP Student</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="btn btn-outline-light" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['role']); ?>)</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div id="main-content-area">
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>



<script>
    const defaultContentAction = 'get_student_list';
    const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);

    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.nav-link[data-content]');
        const ajaxHandlerPath = 'app/ajax_handler.php';

        async function loadContent(action, targetLink) {
            // 1. Show loading spinner
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            const url = `${ajaxHandlerPath}?action=${action}`;
            
            //await sleep(10000);
            

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
                    const newContent = doc.body; // Use doc.body to include top-level elements
                    const scripts = doc.querySelectorAll('script');

                    // 3. Inject new content HTML (the structure)
                    contentArea.innerHTML = newContent.innerHTML;

                    // 4. Execute scripts after the DOM elements are present
                    //    - Avoid re-inserting external scripts already on the page
                    //    - Wrap inline scripts in an IIFE to prevent global re-declarations
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
                            // mark as added to avoid future duplicates in this run
                            existingExternalScripts.add(script.src);
                        } else {
                            // Inline scripts: wrap in IIFE to isolate scope and avoid
                            // "Identifier has already been declared" errors on re-loads
                            newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                        }
                        // Append the script element to a live part of the DOM to force execution
                        // This is necessary to attach the form's event listener.
                        document.body.appendChild(newScript);
                        // Immediately remove it to keep the DOM clean
                        newScript.remove();
                    });
                    // console.log(document.body);
                    // --------------------------------------------------------
                    
                    // 5. Update the 'active' class on the navigation bar
                    navLinks.forEach(link => link.classList.remove('active'));
                    if (targetLink) {
                        targetLink.classList.add('active');
                    }
                    
                    // 6. Update the URL in the browser bar
                    history.pushState(null, '', `admin_dashboard.php?view=${action}`);
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

        // Initial Load: Load the student list when the dashboard first opens
        // const defaultContentAction = 'get_student_list';
        const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);
        
        // Mark the default link active and load content
        if(defaultLink) {
             defaultLink.classList.add('active');
        }
        loadContent(defaultContentAction, defaultLink);
    });

    async function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
</script>

</body>
</html>