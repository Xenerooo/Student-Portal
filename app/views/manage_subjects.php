<?php
// app/views/manage_subjects.php

// Basic Authorization Check (Essential!)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger'>Access Denied. Please log in as an administrator.</div>");
}

require_once "includes/db_connect.php";
$conn = connect();

// Fetch existing subjects
$subjects = [];
try {
    $result = $conn->query("SELECT subject_id, subject_code, units FROM subjects ORDER BY subject_code");
    if ($result) {
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // If error, subjects array remains empty
}

$conn->close();
?>

<h1 class="mb-4">Manage Subjects</h1>
<div id="form-submission-message"></div>

<!-- Add Subject Form -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Add New Subject</h5>
    </div>
    <div class="card-body">
        <form id="addSubjectForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                    <input type="text" name="subject_code" id="subject_code" class="form-control" 
                           placeholder="e.g., CS101, MATH102" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="units" class="form-label">Units <span class="text-danger">*</span></label>
                    <input type="number" name="units" id="units" class="form-control" 
                           placeholder="e.g., 3" min="1" max="10" required>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Add Subject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Subjects List -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Current Subjects</h5>
        <div class="d-flex align-items-center gap-2">
            <span id="subject-count-badge" class="badge bg-light text-dark"><?php echo count($subjects); ?> subjects</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($subjects)): ?>
            <div class="alert alert-info">No subjects found. Add your first subject above.</div>
        <?php else: ?>
            <!-- Search Bar -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" 
                           class="form-control" 
                           id="subject-search" 
                           placeholder="Search by subject code or units...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" style="display: none;">
                        Clear
                    </button>
                </div>
            </div>
            
            <div id="no-results-message" class="alert alert-warning" style="display: none;">
                No subjects found matching your search.
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Units</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subjects-table-body">
                        <?php foreach ($subjects as $subject): ?>
                            <tr class="subject-row" 
                                id="subject-row-<?php echo htmlspecialchars($subject['subject_id']); ?>"
                                data-subject-code="<?php echo htmlspecialchars(strtolower($subject['subject_code'])); ?>"
                                data-units="<?php echo htmlspecialchars($subject['units']); ?>">
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm delete-subject-btn" 
                                            data-subject-id="<?php echo htmlspecialchars($subject['subject_id']); ?>"
                                            data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSubjectModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete subject <strong id="delete-subject-code"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone. All related curriculum entries will be affected.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Subject</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle Add Subject Form Submission
    function handleAddSubjectSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const messageDiv = document.getElementById('form-submission-message');
        
        messageDiv.innerHTML = '<div class="alert alert-info">Adding subject...</div>';
        
        fetch('app/process_subject_manage.php', {
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
                form.reset();
                // Reload the page content to show new subject
                setTimeout(() => {
                    const action = 'get_manage_subjects';
                    const targetLink = document.querySelector(`[data-content="${action}"]`);
                    if (typeof window.loadContent === 'function') {
                        window.loadContent(action, targetLink);
                    } else {
                        location.reload();
                    }
                }, 1000);
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

    // Search functionality
    function filterSubjects(searchTerm) {
        const searchLower = searchTerm.toLowerCase().trim();
        const rows = document.querySelectorAll('.subject-row');
        const noResultsMsg = document.getElementById('no-results-message');
        const clearBtn = document.getElementById('clear-search');
        const countBadge = document.getElementById('subject-count-badge');
        let visibleCount = 0;

        if (!searchTerm || searchTerm.trim() === '') {
            // Show all rows
            rows.forEach(row => {
                row.style.display = '';
                visibleCount++;
            });
            if (noResultsMsg) noResultsMsg.style.display = 'none';
            if (clearBtn) clearBtn.style.display = 'none';
        } else {
            // Filter rows
            rows.forEach(row => {
                const subjectCode = row.getAttribute('data-subject-code') || '';
                const units = row.getAttribute('data-units') || '';
                
                if (subjectCode.includes(searchLower) || units.includes(searchLower)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            if (noResultsMsg) {
                noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
            if (clearBtn) clearBtn.style.display = 'inline-block';
        }
        
        // Update count badge
        if (countBadge) {
            const totalCount = rows.length;
            if (searchTerm && searchTerm.trim() !== '') {
                countBadge.textContent = `${visibleCount} of ${totalCount} subjects`;
            } else {
                countBadge.textContent = `${totalCount} subjects`;
            }
        }
    }

    // Initialize handlers (works for both initial load and AJAX-loaded content)
    (function() {
        // Handle Delete Subject - all inside IIFE scope
        let pendingDeleteSubjectId = null;
        
        function deleteSubject(subjectId, subjectCode) {
            pendingDeleteSubjectId = subjectId;
            const deleteCodeEl = document.getElementById('delete-subject-code');
            if (deleteCodeEl) {
                deleteCodeEl.textContent = subjectCode;
            }
            
            const modalEl = document.getElementById('deleteSubjectModal');
            if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                const modal = new window.bootstrap.Modal(modalEl);
                modal.show();
            }
        }

        const formElement = document.getElementById('addSubjectForm');
        if (formElement) {
            formElement.addEventListener('submit', handleAddSubjectSubmit);
        }

        // Search input handler
        const searchInput = document.getElementById('subject-search');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                filterSubjects(e.target.value);
            });
        }

        // Clear search button handler
        const clearBtn = document.getElementById('clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    filterSubjects('');
                }
            });
        }

        // Event delegation for delete buttons (works even if buttons are added dynamically)
        const subjectsTableBody = document.getElementById('subjects-table-body');
        if (subjectsTableBody) {
            subjectsTableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-subject-btn') || e.target.closest('.delete-subject-btn')) {
                    const btn = e.target.classList.contains('delete-subject-btn') ? e.target : e.target.closest('.delete-subject-btn');
                    const subjectId = btn.getAttribute('data-subject-id');
                    const subjectCode = btn.getAttribute('data-subject-code');
                    if (subjectId && subjectCode) {
                        deleteSubject(parseInt(subjectId), subjectCode);
                    }
                }
            });
        }

        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                if (!pendingDeleteSubjectId) {
                    return;
                }

                const messageDiv = document.getElementById('form-submission-message');
                messageDiv.innerHTML = '<div class="alert alert-info">Deleting subject...</div>';

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('subject_id', pendingDeleteSubjectId);

                fetch('app/process_subject_manage.php', {
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
                        
                        // Hide modal
                        const modalEl = document.getElementById('deleteSubjectModal');
                        if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                            const instance = window.bootstrap.Modal.getInstance(modalEl);
                            if (instance) {
                                instance.hide();
                            }
                        }

                        // Remove row with fade effect
                        const row = document.getElementById(`subject-row-${pendingDeleteSubjectId}`);
                        if (row) {
                            row.style.transition = 'opacity 0.5s ease';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                // Update search filter count
                                const searchInput = document.getElementById('subject-search');
                                if (searchInput) {
                                    filterSubjects(searchInput.value);
                                }
                                // Check if table is empty and reload if needed
                                const tbody = row.closest('tbody');
                                if (tbody && tbody.children.length === 0) {
                                    const action = 'get_manage_subjects';
                                    const targetLink = document.querySelector(`[data-content="${action}"]`);
                                    if (typeof window.loadContent === 'function') {
                                        window.loadContent(action, targetLink);
                                    } else {
                                        location.reload();
                                    }
                                }
                            }, 500);
                        } else {
                            // Reload if row not found
                            const action = 'get_manage_subjects';
                            const targetLink = document.querySelector(`[data-content="${action}"]`);
                            if (typeof window.loadContent === 'function') {
                                window.loadContent(action, targetLink);
                            } else {
                                location.reload();
                            }
                        }
                    } else {
                        messageDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to delete subject.'}</div>`;
                    }
                    pendingDeleteSubjectId = null;
                })
                .catch(error => {
                    console.error('Delete Fetch/JSON Error:', error);
                    if (!messageDiv.innerHTML.includes('Server Error')) {
                        messageDiv.innerHTML = `<div class="alert alert-danger">Network or JSON Parsing Error. See console.</div>`;
                    }
                    pendingDeleteSubjectId = null;
                });
            });
        }
    })();
</script>