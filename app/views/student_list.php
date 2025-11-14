<?php
// student_list_content.php
// This script is loaded via AJAX and only returns the HTML fragment.


require_once "includes/db_connect.php";

$conn = connect();

// Authorization Check (CRUCIAL)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $conn->close();
    // Return an error message to display in the content area
    die("<div class='alert alert-danger'>Access Denied. Please log in as an administrator.</div>");
}

// Get search parameter


$conn->close();
?>

<h1 class="mb-4">Student Lookup Table</h1>
<p class="lead">Select a student to view or edit their grades and academic record.</p>

<div class="mb-3 input-group">
    <span class="input-group-text">
        <svg class="bi bi-search" height="16px" width="16px" fill="current" role="img" aria-label="Tools" ">
            <use xlink:href="assets/images/search.svg"/>
        </svg>
    </span>

    <label for="searchStudent" class="form-label"></label>
    <input type="text" id="searchStudent" class="form-control" placeholder="Search by name, student number, or course..." autocomplete="off">
</div>


<div class="table-responsive shadow-sm">
    <table id="student-list-table" class="table table-hover table-sm align-middle">
        <thead class="table-primary">
            <tr>
                <th scope="col"  class="col-1">Student No.</th>
                <th scope="col"  class="col-3">Name</th>
                <th scope="col" class="col-5">Course/Program</th>
                <th scope="col" class="col-4">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="text-center py-4">Loading students...</td>
            </tr>
        </tbody>
    </table>

</div>

<script>
    (function() {
        // Server-side search functionality
        const searchInput = document.getElementById('searchStudent');
        const studentTable = document.getElementById('student-list-table');
        const tableBody = studentTable ? studentTable.querySelector('tbody') : null;
        let searchTimeout = null;
        
        // Function to load student list with search
        function loadStudentList(searchTerm = '') {
            if (!tableBody || !studentTable) return;
            
            // Show loading state in tbody
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Searching...
                    </td>
                </tr>
            `;
            
            // Build URL with search parameter
            let url = 'app/process_search_student.php';
            const params = new URLSearchParams();
            if (searchTerm.trim()) {
                params.set('search', searchTerm.trim());
            }
            if (params.toString()) {
                url += `?${params.toString()}`;
            }

            fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data || !data.success) {
                        throw new Error('Invalid response format.');
                    }
                    // console.log(data);
                    renderStudentRows(data.students || [], searchTerm);
                })
                .catch(error => {
                    tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">Error loading student list: ${error.message}</td></tr>`;
                    console.error('AJAX Error:', error);
                });
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
        }

        function renderStudentRows(students, searchTerm) {
            if (!Array.isArray(students) || students.length === 0) {
                const message = searchTerm ?
                    `No students found matching "${escapeHtml(searchTerm)}".` :
                    'No student records found in the system.';
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4">${message}</td></tr>`;
                return;
            }

            const rowsHtml = students.map(student => {
                const studentId = escapeHtml(student.student_id);
                return `
                    <tr>
                        <td>${escapeHtml(student.student_number)}</td>
                        <td>
                            <img src="assets/images/person.svg" alt="" class="mb-1">
                            ${escapeHtml(student.student_name)}
                        </td>
                        <td>${escapeHtml(student.course_name)}</td>
                        <td>
                            <div class="container">
                                <div class="row">
                                    <div class="col-sm">
                                        <button type="button" class="btn btn-outline-secondary w-100 edit-grades-btn m-1"
                                            data-student-id="${studentId}">
                                            <svg class="bi" height="16px" width="16px" fill="current" role="img" aria-label="Tools">
                                                <use xlink:href="assets/images/pencil-fill.svg"/>
                                            </svg>
                                            Edit Grades
                                        </button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" class="btn btn-outline-secondary w-100 edit-info-btn m-1"
                                            data-student-id="${studentId}">
                                            <svg class="bi" height="16px" width="16px" fill="current" role="img" aria-label="Tools">
                                                <use xlink:href="assets/images/pencil-fill.svg"/>
                                            </svg>
                                            Edit Info
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.innerHTML = rowsHtml;
            // console.log(rowsHtml);
            initializeEditButtons();
        }
        
        // Initialize search functionality (only once)
        if (searchInput) {
            // Add event listener with debouncing
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value;
                
                // Clear existing timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Debounce the search - wait 500ms after user stops typing
                searchTimeout = setTimeout(function() {
                    loadStudentList(searchTerm);
                }, 500);
            });
        }
        
        // Initialize edit buttons
        function initializeEditButtons() {
            // Get all edit info buttons
            const editInfoButtons = document.querySelectorAll('.edit-info-btn');
            // Get all edit grades buttons
            const editGradesButtons = document.querySelectorAll('.edit-grades-btn');
            
            // Attach event listeners to all edit info buttons
            editInfoButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    loadEditStudentForm(studentId);
                });
            });

            // Attach event listeners to all edit grades buttons
            editGradesButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    loadGradeEditor(studentId);
                });
            });
        }
        
        // Initialize edit buttons on page load
        initializeEditButtons();

        // Initial load of student list

        loadStudentList();

        
        
        // Function to load edit student form with student_id parameter
        function loadEditStudentForm(studentId) {
            const contentArea = document.getElementById('main-content-area');
            const ajaxHandlerPath = 'app/ajax_handler.php';
            
            // Show loading spinner
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            const url = `${ajaxHandlerPath}?action=get_edit_student_form&student_id=${studentId}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    // Parse and execute scripts from the response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.body;
                    const scripts = doc.querySelectorAll('script');

                    // Inject new content HTML
                    contentArea.innerHTML = newContent.innerHTML;

                    // Execute scripts
                    const existingExternalScripts = new Set(
                        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
                    );
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            if (existingExternalScripts.has(script.src)) {
                                return;
                            }
                            newScript.src = script.src;
                            existingExternalScripts.add(script.src);
                        } else {
                            // Wrap in IIFE to prevent global scope pollution
                            newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                        }
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                    
                    // Update URL
                    history.pushState(null, '', `admin_dashboard.php?view=get_edit_student_form&student_id=${studentId}`);
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        // Function to load grade editor into main content with student_id parameter
        function loadGradeEditor(studentId) {
            const contentArea = document.getElementById('main-content-area');

            // Show loading spinner
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            const url = `grade_editor.php?student_id=${studentId}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.body;
                    const scripts = doc.querySelectorAll('script');

                    contentArea.innerHTML = newContent.innerHTML;

                    const existingExternalScripts = new Set(
                        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
                    );
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            if (existingExternalScripts.has(script.src)) {
                                return;
                            }
                            newScript.src = script.src;
                            existingExternalScripts.add(script.src);
                        } else {
                            newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                        }
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });

                    history.pushState(null, '', `admin_dashboard.php?view=grade_editor&student_id=${studentId}`);
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }
        
    })();
</script>