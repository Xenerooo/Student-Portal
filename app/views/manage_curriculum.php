<?php
// manage_curriculum.php

// Basic Authorization Check (Essential!)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger'>Access Denied. Please log in as an administrator.</div>");
}

require_once "includes/db_connect.php";
$conn = connect();

// Fetch courses and subjects for dropdowns
$courses = [];
$subjects = [];
$curriculum_entries = [];

try {
    $courses_result = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name");
    if ($courses_result) {
        $courses = $courses_result->fetch_all(MYSQLI_ASSOC);
    }
    
    $subjects_result = $conn->query("SELECT subject_id, subject_code FROM subjects ORDER BY subject_code");
    if ($subjects_result) {
        $subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Handle error silently, arrays remain empty
}

$conn->close();
?>

<h1 class="mb-4">Manage Curriculum and Programs</h1>
<div id="form-submission-message"></div>

<!-- Course Selection and Bulk Upload -->
<div class="card shadow mb-4" style="display: none;">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Course Selection & Bulk Upload</h5>
    </div>
    <div class="card-body">

        
        <hr>
        
        <!-- <h6>Bulk Upload via JSON</h6> -->
        <div class="row" style="display: none;">
            <div class="col-md-4 mb-3">
                <label for="jsonFile" class="form-label">Upload Curriculum JSON</label>
                <input type="file" class="form-control" id="jsonFile" accept=".json">
            </div>
            <div class="col-md-4 mb-3">
                <label for="bulkCourse" class="form-label">Course for Bulk Upload</label>
                <select id="bulkCourse" class="form-control">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" id="processJson">Process JSON and Populate</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Curriculum Entry Form -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">Add New Curriculum Entry</h5>
    </div>
    <div class="card-body">
        
        <form id="addCurriculumForm">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="add_course_id" class="form-label">Course <span class="text-danger">*</span></label>
                    <select name="course_id" id="add_course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="add_subject_id" class="form-label">Subject Code <span class="text-danger">*</span></label>
                    <select name="subject_id" id="add_subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="add_subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                    <input type="text" name="subject_name" id="add_subject_name" class="form-control" required>
                </div>
                <div class="col-md-1 mb-3">
                    <label for="add_year_level" class="form-label">Year <span class="text-danger">*</span></label>
                    <select name="year_level" id="add_year_level" class="form-control" required>
                        <option value="">--</option>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-1 mb-3">
                    <label for="add_semester" class="form-label">Sem <span class="text-danger">*</span></label>
                    <select name="semester" id="add_semester" class="form-control" required>
                        <option value="">--</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Curriculum Entries List -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Curriculum Entries</h5>
        <span id="curriculum-count-badge" class="badge bg-light text-dark">0 entries</span>
    </div>
    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-9">
                <label for="filterCourse" class="form-label">Select Course to Manage</label>
                <select id="filterCourse" class="form-control">
                    <option value="">-- Select a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-success" id="loadCurriculum">Load Curriculum</button>
                    <button type="button" class="btn btn-danger" id="clearFilter">Clear</button>
                </div>
            </div>
        </div>

        <div id="curriculum-empty-message" class="alert alert-info">
            Select a course and click "Load Curriculum" to view entries, or add a new entry above.
        </div>
        
        <div id="curriculum-table-container" style="display: none;">
            <!-- Search Bar -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" class="form-control" id="curriculum-search" placeholder="Search by subject code, name, year, or semester...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-curriculum-search" style="display: none;">Clear</button>
                </div>
            </div>
            
            <div id="no-curriculum-results" class="alert alert-warning" style="display: none;">
                No entries found matching your search.
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="curriculum-table-body">
                        <!-- Curriculum entries will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCurriculumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Curriculum Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCurriculumForm">
                    <input type="hidden" name="curriculum_id" id="edit_curriculum_id">
                    <div class="mb-3">
                        <label for="edit_course_id" class="form-label">Course <span class="text-danger">*</span></label>
                        <select name="course_id" id="edit_course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_id" class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <select name="subject_id" id="edit_subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="edit_year_level" class="form-control" required>
                                <option value="">Select Year</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select name="semester" id="edit_semester" class="form-control" required>
                                <option value="">Select Semester</option>
                                <option value="1">1st</option>
                                <option value="2">2nd</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditCurriculum">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Cache DOM elements
    const filterCourse = document.getElementById('filterCourse');
    const loadCurriculumBtn = document.getElementById('loadCurriculum');
    const clearFilterBtn = document.getElementById('clearFilter');
    const addCurriculumForm = document.getElementById('addCurriculumForm');
    const curriculumTableBody = document.getElementById('curriculum-table-body');
    const curriculumTableContainer = document.getElementById('curriculum-table-container');
    const curriculumEmptyMessage = document.getElementById('curriculum-empty-message');
    const curriculumCountBadge = document.getElementById('curriculum-count-badge');
    const curriculumSearch = document.getElementById('curriculum-search');
    const clearCurriculumSearch = document.getElementById('clear-curriculum-search');
    const noCurriculumResults = document.getElementById('no-curriculum-results');
    const messageDiv = document.getElementById('form-submission-message');
    
    // Template row for bulk upload
    let templateRow = null;
    
    // Store current curriculum data
    let currentCurriculumData = [];
    let currentCourseId = null;
    
    // Initialize template row (will be set after first load)
    function initializeTemplateRow() {
        if (!templateRow && curriculumTableBody.children.length > 0) {
            templateRow = curriculumTableBody.children[0].cloneNode(true);
        }
    }
    
    // Show message
    function showMessage(message, type = 'success') {
        messageDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
        setTimeout(() => {
            const alert = messageDiv.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => messageDiv.innerHTML = '', 300);
            }
        }, 5000);
    }
    
    // Load curriculum for selected course
    function loadCurriculum(courseId) {
        if (!courseId) {
            showMessage('Please select a course first.', 'warning');
            return;
        }
        
        fetch(`app/ajax_handler.php?action=get_curriculum_data&course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentCurriculumData = data.entries || [];
                    currentCourseId = courseId;
                    renderCurriculumTable(currentCurriculumData);
                    curriculumTableContainer.style.display = 'block';
                    curriculumEmptyMessage.style.display = 'none';
                    updateCountBadge(currentCurriculumData.length);
                } else {
                    showMessage(data.message || 'Failed to load curriculum.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading curriculum:', error);
                showMessage('Error loading curriculum. Please try again.', 'danger');
            });
    }
    
    // Render curriculum table
    function renderCurriculumTable(data) {
        if (!data || data.length === 0) {
            curriculumTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No curriculum entries found.</td></tr>';
            updateCountBadge(0);
            return;
        }
        
        curriculumTableBody.innerHTML = data.map(entry => `
            <tr class="curriculum-row" data-id="${entry.curriculum_id}">
                <td>${escapeHtml(entry.course_name || 'N/A')}</td>
                <td>${escapeHtml(entry.subject_code || 'N/A')}</td>
                <td>${escapeHtml(entry.subject_name || '')}</td>
                <td>${entry.year_level || ''}</td>
                <td>${entry.semester || ''}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary edit-curriculum" data-id="${entry.curriculum_id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-curriculum" data-id="${entry.curriculum_id}">Delete</button>
                </td>
            </tr>
        `).join('');
        
        updateCountBadge(data.length);
        attachEventListeners();
    }
    
    // Update count badge
    function updateCountBadge(count) {
        curriculumCountBadge.textContent = `${count} ${count === 1 ? 'entry' : 'entries'}`;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Attach event listeners to table rows
    function attachEventListeners() {
        // Edit buttons
        document.querySelectorAll('.edit-curriculum').forEach(btn => {
            btn.addEventListener('click', function() {
                const curriculumId = this.getAttribute('data-id');
                const entry = currentCurriculumData.find(e => e.curriculum_id == curriculumId);
                if (entry) {
                    openEditModal(entry);
                }
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.delete-curriculum').forEach(btn => {
            btn.addEventListener('click', function() {
                const curriculumId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this curriculum entry?')) {
                    deleteCurriculum(curriculumId);
                }
            });
        });
    }
    
    // Add curriculum entry
    addCurriculumForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add');
        
        fetch('app/process_curriculum_manage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                this.reset();
                // Reload curriculum if a course is selected
                if (currentCourseId) {
                    loadCurriculum(currentCourseId);
                }
            } else {
                showMessage(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error adding curriculum entry. Please try again.', 'danger');
        });
    });
    
    // Open edit modal
    function openEditModal(entry) {
        document.getElementById('edit_curriculum_id').value = entry.curriculum_id;
        document.getElementById('edit_course_id').value = entry.course_id;
        document.getElementById('edit_subject_id').value = entry.subject_id;
        document.getElementById('edit_subject_name').value = entry.subject_name || '';
        document.getElementById('edit_year_level').value = entry.year_level;
        document.getElementById('edit_semester').value = entry.semester;
        
        const modal = new bootstrap.Modal(document.getElementById('editCurriculumModal'));
        modal.show();
    }
    
    // Save edit
    document.getElementById('saveEditCurriculum').addEventListener('click', function() {
        const form = document.getElementById('editCurriculumForm');
        const formData = new FormData(form);
        formData.append('action', 'update');
        
        fetch('app/process_curriculum_manage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editCurriculumModal')).hide();
                if (currentCourseId) {
                    loadCurriculum(currentCourseId);
                }
            } else {
                showMessage(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error updating curriculum entry. Please try again.', 'danger');
        });
    });
    
    // Delete curriculum entry
    function deleteCurriculum(curriculumId) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('curriculum_id', curriculumId);
        
        fetch('app/process_curriculum_manage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                if (currentCourseId) {
                    loadCurriculum(currentCourseId);
                }
            } else {
                showMessage(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error deleting curriculum entry. Please try again.', 'danger');
        });
    }
    
    // Load curriculum button
    loadCurriculumBtn.addEventListener('click', function() {
        const courseId = filterCourse.value;
        loadCurriculum(courseId);
    });
    
    // Clear filter
    clearFilterBtn.addEventListener('click', function() {
        filterCourse.value = '';
        currentCourseId = null;
        currentCurriculumData = [];
        curriculumTableContainer.style.display = 'none';
        curriculumEmptyMessage.style.display = 'block';
        updateCountBadge(0);
    });
    
    // Search functionality
    curriculumSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        clearCurriculumSearch.style.display = searchTerm ? 'block' : 'none';
        
        if (!searchTerm) {
            renderCurriculumTable(currentCurriculumData);
            noCurriculumResults.style.display = 'none';
            return;
        }
        
        const filtered = currentCurriculumData.filter(entry => {
            return (entry.subject_code && entry.subject_code.toLowerCase().includes(searchTerm)) ||
                   (entry.subject_name && entry.subject_name.toLowerCase().includes(searchTerm)) ||
                   (entry.course_name && entry.course_name.toLowerCase().includes(searchTerm)) ||
                   (entry.year_level && entry.year_level.toString().includes(searchTerm)) ||
                   (entry.semester && entry.semester.toString().includes(searchTerm));
        });
        
        if (filtered.length === 0) {
            noCurriculumResults.style.display = 'block';
            curriculumTableBody.innerHTML = '';
        } else {
            noCurriculumResults.style.display = 'none';
            renderCurriculumTable(filtered);
        }
    });
    
    clearCurriculumSearch.addEventListener('click', function() {
        curriculumSearch.value = '';
        this.style.display = 'none';
        renderCurriculumTable(currentCurriculumData);
        noCurriculumResults.style.display = 'none';
    });
    
    // Process JSON upload (from curriculum_populator.php)
    document.getElementById('processJson').addEventListener('click', function() {
        const fileInput = document.getElementById('jsonFile');
        const courseId = document.getElementById('bulkCourse').value;
        
        if (!fileInput.files[0] || !courseId) {
            showMessage('Please select a JSON file and a course for bulk upload.', 'warning');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const json = JSON.parse(e.target.result);
                const curriculumData = [];
                
                // Build subject map
                const subjectMap = {};
                <?php foreach ($subjects as $subject): ?>
                subjectMap['<?php echo htmlspecialchars($subject['subject_code'], ENT_QUOTES); ?>'] = <?php echo $subject['subject_id']; ?>;
                <?php endforeach; ?>
                
                // Parse JSON structure (year -> semester -> subjects)
                for (const year in json) {
                    for (const semester in json[year]) {
                        if (Array.isArray(json[year][semester])) {
                            json[year][semester].forEach(subject => {
                                const subjectCode = subject.subject_code ? subject.subject_code.trim() : '';
                                const subjectId = subjectMap[subjectCode] || '';
                                
                                if (subjectId && subject.subject_name) {
                                    curriculumData.push({
                                        course_id: courseId,
                                        subject_id: subjectId,
                                        subject_name: subject.subject_name,
                                        year_level: parseInt(year),
                                        semester: parseInt(semester)
                                    });
                                }
                            });
                        }
                    }
                }
                
                if (curriculumData.length === 0) {
                    showMessage('No valid curriculum entries found in JSON file.', 'warning');
                    return;
                }
                
                // Send bulk save request
                const formData = new FormData();
                formData.append('action', 'bulk_save');
                formData.append('curriculum_data', JSON.stringify(curriculumData));
                
                fetch('app/process_curriculum_manage.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        fileInput.value = '';
                        // Reload curriculum if the same course is selected
                        if (currentCourseId == courseId) {
                            loadCurriculum(courseId);
                        }
                    } else {
                        showMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Error processing JSON file. Please try again.', 'danger');
                });
                
            } catch (error) {
                console.error('JSON Processing Error:', error);
                showMessage('Error processing JSON file. Please check the file format.', 'danger');
            }
        };
        reader.readAsText(fileInput.files[0]);
    });
})();
</script>
