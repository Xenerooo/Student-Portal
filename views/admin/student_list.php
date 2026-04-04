<?php
// student_list_content.php
// This script is loaded via AJAX and only returns the HTML fragment.
// Authorization and Data fetching handled by AdminController
?>

<h1 class="mb-4">Student Lookup Table</h1>
<p class="lead">Select a student to view or edit their grades and academic record.</p>

<div class="row g-3 mb-4">
    <div class="col-md-8 text-dark">
        <label for="searchStudent" class="form-label small fw-bold text-uppercase tracking-wider">Search Students</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <svg class="bi bi-search text-muted" height="16px" width="16px" fill="currentColor">
                    <use xlink:href="<?= APP_URL ?>/assets/images/search.svg"/>
                </svg>
            </span>
            <input type="text" id="searchStudent" class="form-control border-start-0 ps-0" placeholder="Search by name, ID, or course..." autocomplete="off">
        </div>
    </div>
    <div class="col-md-4">
        <label for="filterCourse" class="form-label small fw-bold text-uppercase tracking-wider text-dark">Filter by Course</label>
        <select id="filterCourse" class="form-select">
            <option value="">All Courses</option>
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['acronym']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>


<div class="table-responsive shadow-sm">
    <table id="student-list-table" class="table table-hover table-sm align-middle">
        <thead class="table-primary">
            <tr>
                <th scope="col" class="col-1">Student No.</th>
                <th scope="col" class="col-3">Name</th>
                <th scope="col" class="col-2 text-center">Year</th>
                <th scope="col" class="col-2 text-center">Course</th>
                <th scope="col" class="col-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="text-center py-4">Loading students...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Pagination Container -->
<div id="pagination-container" class="d-flex justify-content-between align-items-center mt-3">
    <div id="pagination-info" class="text-muted small"></div>
    <nav aria-label="Student list pagination">
        <ul class="pagination pagination-sm mb-0" id="pagination-controls"></ul>
    </nav>
</div>

<script>
    (function() {
        // State management
        let currentPage = 1;
        const itemsPerPage = 10;
        let lastSearchTerm = '';

        // Server-side search functionality
        const searchInput = document.getElementById('searchStudent');
        const courseFilter = document.getElementById('filterCourse');
        const studentTable = document.getElementById('student-list-table');
        const tableBody = studentTable ? studentTable.querySelector('tbody') : null;
        const paginationControls = document.getElementById('pagination-controls');
        const paginationInfo = document.getElementById('pagination-info');
        let searchTimeout = null;
        let currentCourseId = '';
        
        // Function to load student list with search and pagination
        function loadStudentList(searchTerm = '', page = 1, courseId = '') {
            if (!tableBody || !studentTable) return;
            
            currentPage = page;
            lastSearchTerm = searchTerm;
            currentCourseId = courseId;

            // Show loading state in tbody
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Searching...
                    </td>
                </tr>
            `;
            
            // Build URL with search and pagination parameters
            let url = '<?= APP_URL ?>/admin/api/students/search';
            const params = new URLSearchParams();
            if (searchTerm.trim()) {
                params.set('search', searchTerm.trim());
            }
            if (courseId) {
                params.set('course_id', courseId);
            }
            params.set('page', page);
            params.set('limit', itemsPerPage);
            
            url += `?${params.toString()}`;

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
                    renderStudentRows(data.students || [], searchTerm);
                    renderPagination(data.pagination);
                })
                .catch(error => {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Error loading student list: ${error.message}</td></tr>`;
                    console.error('AJAX Error:', error);
                });
        }

        function renderPagination(pagination) {
            if (!paginationControls || !paginationInfo) return;
            
            const { total_count, total_pages, current_page } = pagination;
            
            // Update info text
            const start = total_count > 0 ? (current_page - 1) * itemsPerPage + 1 : 0;
            const end = Math.min(current_page * itemsPerPage, total_count);
            paginationInfo.textContent = `Showing ${start} to ${end} of ${total_count} students`;

            if (total_pages <= 1) {
                paginationControls.innerHTML = '';
                return;
            }

            let html = '';
            
            // Previous Button
            html += `
                <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page - 1}">&laquo;</a>
                </li>
            `;

            // Page numbers (simplified version: show all if few, or around current)
            const maxVisible = 5;
            let startPage = Math.max(1, current_page - 2);
            let endPage = Math.min(total_pages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            // Next Button
            html += `
                <li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page + 1}">&raquo;</a>
                </li>
            `;

            paginationControls.innerHTML = html;

            // Add click listeners to pagination links
            paginationControls.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.parentElement.classList.contains('disabled') || this.parentElement.classList.contains('active')) return;
                    
                    const nextPage = parseInt(this.getAttribute('data-page'));
                    loadStudentList(lastSearchTerm, nextPage, currentCourseId);
                });
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

        function formatYearLevel(year) {
            if (!year) return '-';
            const y = parseInt(year);
            const suffixes = ['th', 'st', 'nd', 'rd'];
            const val = y % 100;
            const suffix = suffixes[(val - 20) % 10] || suffixes[val] || suffixes[0];
            return `${y}${suffix} Year`;
        }

        function renderStudentRows(students, searchTerm) {
            if (!Array.isArray(students) || students.length === 0) {
                const message = searchTerm ?
                    `No students found matching "${escapeHtml(searchTerm)}".` :
                    'No student records found in the system.';
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4">${message}</td></tr>`;
                return;
            }

            const rowsHtml = students.map(student => {
                const studentId = escapeHtml(student.student_id);
                return `
                    <tr>
                        <td class="small fw-bold">${escapeHtml(student.student_number)}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= APP_URL ?>/assets/images/person.svg" alt="" width="20" class="rounded-circle bg-light p-1">
                                <span>${escapeHtml(student.student_name)}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge fw-semibold px-3 py-2" style="background-color: rgba(92, 64, 51, 0.08); color: #5c4033; border: 1px solid rgba(92, 64, 51, 0.2); min-width: 100px; border-radius: 6px;">
                                <i class="bi bi-mortarboard-fill me-1"></i>
                                ${formatYearLevel(student.year_level)}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge fw-semibold px-3 py-2" style="background-color: rgba(212, 160, 23, 0.08); color: #a67b0a; border: 1px solid rgba(212, 160, 23, 0.3); min-width: 100px; border-radius: 6px;">
                                <i class="bi bi-journal-bookmark-fill me-1"></i>
                                ${escapeHtml(student.acronym)}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 edit-grades-btn"
                                    title="Edit Grades" data-student-id="${studentId}" style="min-width: 85px; justify-content: center;">
                                    <i class="bi bi-pencil-square"></i> Grades
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 edit-info-btn"
                                    title="Edit Info" data-student-id="${studentId}" style="min-width: 85px; justify-content: center;">
                                    <i class="bi bi-person-gear"></i> Info
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 enroll-btn"
                                    title="Enrollment" data-student-id="${studentId}" style="min-width: 85px; justify-content: center;">
                                    <i class="bi bi-journal-plus"></i> Enroll
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.innerHTML = rowsHtml;
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
                    // Reset to page 1 on new search
                    loadStudentList(searchTerm, 1, currentCourseId);
                }, 500);
            });
        }

        if (courseFilter) {
            courseFilter.addEventListener('change', function() {
                loadStudentList(lastSearchTerm, 1, this.value);
            });
        }
        
        function initializeEditButtons() {
            const editInfoButtons = document.querySelectorAll('.edit-info-btn');
            const editGradesButtons = document.querySelectorAll('.edit-grades-btn');
            const enrollButtons = document.querySelectorAll('.enroll-btn');
            
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

            // Attach event listeners to all enroll buttons
            enrollButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    loadEnrollmentForm(studentId);
                });
            });
        }
        
        // Initialize edit buttons on page load
        initializeEditButtons();

        // Initial load of student list

        loadStudentList();

        
        
        function loadEditStudentForm(studentId) {
            const contentArea = document.getElementById('main-content-area');
            
            const url = `<?= APP_URL ?>/admin/api/students/edit?student_id=${studentId}`;
            
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
                    history.pushState(null, '', `<?= APP_URL ?>/admin/dashboard?view=get_edit_student_form&student_id=${studentId}`);

                    // Scroll to top
                    const scrollContainer = document.querySelector('.app-content-scrollable');
                    if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        // Function to load grade editor into main content with student_id parameter
        window.loadGradeEditor = function(studentId, schoolYear = '', semester = '') {
            const contentArea = document.getElementById('main-content-area');

            // Show loading spinner
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            let url = `<?= APP_URL ?>/admin/api/grades/edit?student_id=${studentId}`;
            if (schoolYear) url += `&school_year=${encodeURIComponent(schoolYear)}`;
            if (semester) url += `&semester=${encodeURIComponent(semester)}`;

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

                    let pushUrl = `<?= APP_URL ?>/admin/dashboard?view=grade_editor&student_id=${studentId}`;
                    if (schoolYear) pushUrl += `&school_year=${encodeURIComponent(schoolYear)}`;
                    if (semester) pushUrl += `&semester=${encodeURIComponent(semester)}`;
                    history.pushState(null, '', pushUrl);

                    // Scroll to top
                    const scrollContainer = document.querySelector('.app-content-scrollable');
                    if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        function loadEnrollmentForm(studentId) {
            const contentArea = document.getElementById('main-content-area');
            
            const url = `<?= APP_URL ?>/admin/api/students/enroll-form?student_id=${studentId}`;
            
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
                    
                    history.pushState(null, '', `<?= APP_URL ?>/admin/dashboard?view=enroll_form&student_id=${studentId}`);

                    // Scroll to top
                    const scrollContainer = document.querySelector('.app-content-scrollable');
                    if (scrollContainer) scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }
        
    })();
</script>
