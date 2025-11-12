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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

$defaultPerPage = 25;
$maxPerPage = 100;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : $defaultPerPage;
$perPage = max(5, min($perPage, $maxPerPage));

$offset = ($page - 1) * $perPage;

$students = [];
$totalRecords = 0;
$totalPages = 1;
$recordStart = 0;
$recordEnd = 0;

$countSql = "SELECT COUNT(*) AS total FROM students";
$countResult = $conn->query($countSql);

if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    $totalRecords = isset($countRow['total']) ? (int)$countRow['total'] : 0;
    $countResult->free();
}

if ($totalRecords > 0) {
    $totalPages = (int)ceil($totalRecords / $perPage);
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT 
            s.student_id, 
            s.student_number, 
            s.student_name, 
            c.course_name
        FROM students s
        JOIN courses c ON s.course_id = c.course_id
        ORDER BY s.student_number ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }

    $recordStart = $offset + 1;
    $recordEnd = min($offset + $perPage, $totalRecords);
} else {
    $students = [];
    $totalPages = 1;
}

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
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                        
                        <td>
                            <img src="assets/images/person.svg" alt="" class="mb-1">
                            <?php echo htmlspecialchars($student['student_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                        <td>
                            <div class="container">
                                <div class="row">
                                    <div class="col-sm">
                                        
                                        <button type="button" class="btn btn-outline-secondary w-100 edit-grades-btn m-1" 
                                                data-student-id="<?php echo $student['student_id']; ?>">
                                                <svg class="bi" height="16px" width="16px" fill="current" role="img" aria-label="Tools" >
                                                    <use xlink:href="assets/images/pencil-fill.svg"/>
                                                </svg>
                                                <!-- <img src="assets/images/pencil-fill.svg" alt="" class="mb-1"> -->
                                                 Edit Grades   
                                        </button>
                                    </div>
                                    <div class="col-sm">
                                        <button type="button" class="btn btn-outline-secondary w-100 edit-info-btn m-1" 
                                                data-student-id="<?php echo $student['student_id']; ?>">
                                                <svg class="bi" height="16px" width="16px" fill="current" role="img" aria-label="Tools" >
                                                    <use xlink:href="assets/images/pencil-fill.svg"/>
                                                </svg>
                                                 Edit Info 
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-center py-4">No student records found in the system.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalRecords > 0): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
            <div class="text-muted small">
                Showing <?php echo htmlspecialchars((string)$recordStart); ?>-<?php echo htmlspecialchars((string)$recordEnd); ?> of <?php echo htmlspecialchars((string)$totalRecords); ?> students
            </div>
            <?php if ($totalPages > 1): ?>
                <?php
                    $visibleLinkCount = 5;
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + $visibleLinkCount - 1);

                    if ($endPage - $startPage + 1 < $visibleLinkCount) {
                        $startPage = max(1, $endPage - $visibleLinkCount + 1);
                    }

                    $buildHref = static function (int $targetPage, int $perPageValue): string {
                        $query = http_build_query([
                            'view' => 'student_list',
                            'page' => $targetPage,
                            'per_page' => $perPageValue,
                        ]);

                        return "admin_dashboard.php?{$query}";
                    };
                ?>
                <nav aria-label="Student pagination" data-pagination="students" data-base-url="app/views/student_list.php">
                    <ul class="pagination pagination-sm mb-0 student-pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a 
                                class="page-link pagination-link" 
                                href="<?php echo $page <= 1 ? '#' : $buildHref($page - 1, $perPage); ?>" 
                                data-page="<?php echo $page - 1; ?>" 
                                data-per-page="<?php echo $perPage; ?>" 
                                aria-label="Previous"
                            >
                                &laquo;
                            </a>
                        </li>

                        <?php if ($startPage > 1): ?>
                            <li class="page-item">
                                <a 
                                    class="page-link pagination-link" 
                                    href="<?php echo $buildHref(1, $perPage); ?>" 
                                    data-page="1" 
                                    data-per-page="<?php echo $perPage; ?>"
                                >
                                    1
                                </a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a 
                                    class="page-link pagination-link" 
                                    href="<?php echo $p === $page ? '#' : $buildHref($p, $perPage); ?>" 
                                    data-page="<?php echo $p; ?>" 
                                    data-per-page="<?php echo $perPage; ?>"
                                >
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a 
                                    class="page-link pagination-link" 
                                    href="<?php echo $buildHref($totalPages, $perPage); ?>" 
                                    data-page="<?php echo $totalPages; ?>" 
                                    data-per-page="<?php echo $perPage; ?>"
                                >
                                    <?php echo $totalPages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a 
                                class="page-link pagination-link" 
                                href="<?php echo $page >= $totalPages ? '#' : $buildHref($page + 1, $perPage); ?>" 
                                data-page="<?php echo $page + 1; ?>" 
                                data-per-page="<?php echo $perPage; ?>" 
                                aria-label="Next"
                            >
                                &raquo;
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-muted small mt-3">No students to display.</div>
    <?php endif; ?>

</div>

<script>
    (function() {
        // Search functionality
        const searchInput = document.getElementById('searchStudent');
        const studentTable = document.getElementById('student-list-table');
        const tableBody = studentTable ? studentTable.querySelector('tbody') : null;
        
        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = tableBody.querySelectorAll('tr');
                
                // Track original "no records" row if it exists
                const originalNoRecordsRow = Array.from(rows).find(row => 
                    row.querySelector('td[colspan]') && !row.classList.contains('no-results-row')
                );
                
                rows.forEach(row => {
                    // Skip the original "No records found" row and our custom "no results" row
                    if (row.querySelector('td[colspan]')) {
                        // Hide original no records row when searching
                        if (searchTerm && row === originalNoRecordsRow) {
                            row.style.display = 'none';
                        } else if (!searchTerm && row === originalNoRecordsRow) {
                            row.style.display = '';
                        }
                        return;
                    }
                    
                    // Get text from all columns (student number, name, course)
                    const cells = row.querySelectorAll('td');
                    let rowText = '';
                    cells.forEach(cell => {
                        rowText += cell.textContent.toLowerCase() + ' ';
                    });
                    
                    // Show/hide row based on search match
                    if (!searchTerm || rowText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show "No results" message if all rows are hidden
                const visibleRows = Array.from(rows).filter(row => 
                    row.style.display !== 'none' && 
                    !row.querySelector('td[colspan]') &&
                    !row.classList.contains('no-results-row')
                );
                
                // Remove existing "no results" message if any
                const existingNoResults = tableBody.querySelector('tr.no-results-row');
                if (existingNoResults) {
                    existingNoResults.remove();
                }
                
                // Add "no results" message if search term exists but no matches
                if (searchTerm && visibleRows.length === 0) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = '<td colspan="4" class="text-center py-4">No students found matching your search.</td>';
                    tableBody.appendChild(noResultsRow);
                }
            });
        }
        
        // Get all edit info buttons
        const editInfoButtons = document.querySelectorAll('.edit-info-btn');
        // Get all edit grades buttons
        const editGradesButtons = document.querySelectorAll('.edit-grades-btn');
        
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

        const paginationNav = document.querySelector('[data-pagination="students"]');

        if (paginationNav) {
            paginationNav.addEventListener('click', function(event) {
                const link = event.target.closest('a.pagination-link');

                if (!link) {
                    return;
                }

                const targetPage = parseInt(link.getAttribute('data-page'), 10);

                if (!targetPage || targetPage < 1 || link.parentElement.classList.contains('disabled') || link.parentElement.classList.contains('active')) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();

                const perPage = link.getAttribute('data-per-page') || '';
                const contentArea = document.getElementById('main-content-area');

                if (!contentArea) {
                    const fallbackHref = link.getAttribute('href');
                    if (fallbackHref && fallbackHref !== '#') {
                        window.location.href = fallbackHref;
                    }
                    return;
                }

                contentArea.innerHTML = `
                    <div class="d-flex justify-content-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;

                const baseUrl = paginationNav.getAttribute('data-base-url') || 'app/views/student_list.php';
                const params = new URLSearchParams();
                params.set('page', targetPage);

                if (perPage) {
                    params.set('per_page', perPage);
                }

                const requestUrl = `${baseUrl}?${params.toString()}`;

                fetch(requestUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok. Status: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        contentArea.innerHTML = doc.body.innerHTML;

                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.set('view', 'student_list');
                        newUrl.searchParams.set('page', targetPage);
                        if (perPage) {
                            newUrl.searchParams.set('per_page', perPage);
                        } else {
                            newUrl.searchParams.delete('per_page');
                        }
                        history.pushState(null, '', newUrl.toString());
                    })
                    .catch(error => {
                        contentArea.innerHTML = `<div class="alert alert-danger">Error loading page: ${error.message}</div>`;
                        console.error('Pagination AJAX Error:', error);
                    });
            });
        }
    })();
</script>