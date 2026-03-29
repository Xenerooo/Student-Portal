<?php
// views/admin/manage_subjects.php
// Data provided by AdminController: $subjects
?>

<h1 class="mb-4">Manage Subjects</h1>
<div id="form-submission-message"></div>

<!-- Subjects List -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Current Subjects</h5>
        <div class="d-flex align-items-center gap-2">
            <span id="subject-count-badge" class="badge bg-light text-dark"><?php echo (is_array($subjects) || $subjects instanceof Countable) ? count($subjects) : 0; ?> subjects</span>
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
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-plus-lg"></i> New Subject
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
                            <th>Subject Name</th>
                            <th>Units</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subjects-table-body">
                        <?php foreach ($subjects as $subject): ?>
                            <tr class="subject-row" 
                                id="subject-row-<?php echo h($subject['subject_id']); ?>"
                                data-subject-code="<?php echo h(strtolower($subject['subject_code'])); ?>"
                                data-subject-name="<?php echo h(strtolower($subject['subject_name'] ?? '')); ?>"
                                data-units="<?php echo h($subject['units']); ?>">
                                <td class="subject-code-cell"><?php echo h($subject['subject_code']); ?></td>
                                <td class="subject-name-cell"><?php echo h($subject['subject_name'] ?? ''); ?></td>
                                <td class="units-cell"><?php echo h($subject['units']); ?></td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-subject-btn text-dark" 
                                            data-subject-id="<?php echo h($subject['subject_id']); ?>"
                                            data-subject-code="<?php echo h($subject['subject_code']); ?>"
                                            data-subject-name="<?php echo h($subject['subject_name'] ?? ''); ?>"
                                            data-units="<?php echo h($subject['units']); ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>
                                    <button type="button" 
                                            class="btn btn-info btn-sm requisites-btn text-white" 
                                            data-subject-id="<?php echo h($subject['subject_id']); ?>"
                                            data-subject-code="<?php echo h($subject['subject_code']); ?>">
                                        <i class="bi bi-diagram-3-fill"></i> Requisites
                                    </button>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm delete-subject-btn" 
                                            data-subject-id="<?php echo h($subject['subject_id']); ?>"
                                            data-subject-code="<?php echo h($subject['subject_code']); ?>">
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

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSubjectForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="subject_code" class="form-control" 
                               placeholder="e.g., CS101, MATH102" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" id="subject_name" class="form-control" 
                               placeholder="e.g., Introduction to Computing" required>
                    </div>
                    <div class="mb-3">
                        <label for="units" class="form-label">Units <span class="text-danger">*</span></label>
                        <input type="number" name="units" id="units" class="form-control" 
                               placeholder="e.g., 3" min="1" max="10" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSubjectForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="subject_id" id="edit_subject_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_units" class="form-label">Units <span class="text-danger">*</span></label>
                        <input type="number" name="units" id="edit_units" class="form-control" min="1" max="10" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Subject</button>
                </div>
            </form>
        </div>
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

<!-- Requisites Modal -->
<div class="modal fade" id="requisitesModal" tabindex="-1" aria-labelledby="requisitesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="requisitesModalLabel">Manage Requisites for <span id="req-subject-code"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="requisites-message-container"></div>
                
                <h6>Existing Requisites</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Type</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="requisites-list-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>

                <hr>

                <h6>Add New Requisite</h6>
                <form id="addRequisiteForm" class="row g-2 align-items-end">
                    <input type="hidden" id="req-target-subject-id" name="subject_id">
                    <div class="col-md-6">
                        <label class="form-label small">Select Subject</label>
                        <select class="form-select form-select-sm" id="req-required-subject-id" name="required_id" required>
                            <option value="">Choose a subject...</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo h($s['subject_id']); ?>"><?php echo h($s['subject_code'] . " - " . ($s['subject_name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Type</label>
                        <select class="form-select form-select-sm" name="type" required>
                            <option value="prerequisite">Prerequisite</option>
                            <option value="corequisite">Corequisite</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                    </div>
                </form>
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
        
        fetch('/Student-Portal/admin/api/subjects/manage', {
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
                
                // Hide modal
                const modalEl = document.getElementById('addSubjectModal');
                if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    const instance = window.bootstrap.Modal.getInstance(modalEl);
                    if (instance) {
                        instance.hide();
                    }
                }

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
                const subjectName = row.getAttribute('data-subject-name') || '';
                const units = row.getAttribute('data-units') || '';
                
                if (subjectCode.includes(searchLower) || subjectName.includes(searchLower) || units.includes(searchLower)) {
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

                fetch('/Student-Portal/admin/api/subjects/manage', {
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

        /* --- Edit Subject Logic --- */
        const editSubjectForm = document.getElementById('editSubjectForm');
        if (editSubjectForm) {
            editSubjectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const messageDiv = document.getElementById('form-submission-message');
                messageDiv.innerHTML = '<div class="alert alert-info">Updating subject...</div>';

                fetch('/Student-Portal/admin/api/subjects/manage', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        const modalEl = document.getElementById('editSubjectModal');
                        if (modalEl && window.bootstrap) {
                            const instance = window.bootstrap.Modal.getInstance(modalEl);
                            if (instance) instance.hide();
                        }
                        // Refresh content
                        setTimeout(() => {
                            if (typeof window.loadContent === 'function') {
                                window.loadContent('get_manage_subjects', document.querySelector('[data-content="get_manage_subjects"]'));
                            } else {
                                location.reload();
                            }
                        }, 1000);
                    } else {
                        messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    messageDiv.innerHTML = `<div class="alert alert-danger">An error occurred during update.</div>`;
                });
            });
        }

        // Delegate Edit Button Click
        if (subjectsTableBody) {
            subjectsTableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.edit-subject-btn');
                if (btn) {
                    const sid = btn.getAttribute('data-subject-id');
                    const scode = btn.getAttribute('data-subject-code');
                    const sname = btn.getAttribute('data-subject-name');
                    const sunits = btn.getAttribute('data-units');

                    document.getElementById('edit_subject_id').value = sid;
                    document.getElementById('edit_subject_code').value = scode;
                    document.getElementById('edit_subject_name').value = sname;
                    document.getElementById('edit_units').value = sunits;

                    const modalEl = document.getElementById('editSubjectModal');
                    if (modalEl && window.bootstrap) {
                        const modal = new window.bootstrap.Modal(modalEl);
                        modal.show();
                    }
                }
            });
        }

        /* --- Requisites Management --- */

        let currentSubjectIdForReq = null;

        function fetchRequisites(subjectId) {
            const listBody = document.getElementById('requisites-list-body');
            listBody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

            fetch(`/Student-Portal/admin/api/subjects/requisites?subject_id=${subjectId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderRequisites(data.requisites);
                    } else {
                        listBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${data.message}</td></tr>`;
                    }
                });
        }

        function openRequisitesModal(subjectId, subjectCode) {
            currentSubjectIdForReq = subjectId;
            document.getElementById('req-subject-code').textContent = subjectCode;
            document.getElementById('req-target-subject-id').value = subjectId;
            
            fetchRequisites(subjectId);

            const modalEl = document.getElementById('requisitesModal');
            if (modalEl && window.bootstrap) {
                // Check if already opened to prevent backdrop stacking
                let modal = window.bootstrap.Modal.getInstance(modalEl);
                if (!modal) {
                    modal = new window.bootstrap.Modal(modalEl);
                }
                modal.show();
            }
        }

        function renderRequisites(requisites) {
            const listBody = document.getElementById('requisites-list-body');
            if (requisites.length === 0) {
                listBody.innerHTML = '<tr><td colspan="4" class="text-center">No requisites defined.</td></tr>';
                return;
            }

            listBody.innerHTML = requisites.map(req => `
                <tr>
                    <td>${req.subject_code}</td>
                    <td>${req.subject_name}</td>
                    <td><span class="badge ${req.type === 'prerequisite' ? 'bg-primary' : 'bg-info'} text-capitalize">${req.type}</span></td>
                    <td class="text-center">
                        <button class="btn btn-outline-danger btn-sm py-0 delete-req-btn" data-id="${req.prerequisite_id}">
                            &times;
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Delegate Requisites Click
        if (subjectsTableBody) {
            subjectsTableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.requisites-btn');
                if (btn) {
                    const sid = btn.getAttribute('data-subject-id');
                    const scode = btn.getAttribute('data-subject-code');
                    openRequisitesModal(sid, scode);
                }
            });
        }

        // Add Requisite Form
        const addReqForm = document.getElementById('addRequisiteForm');
        if (addReqForm) {
            addReqForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add');
                
                const msgContainer = document.getElementById('requisites-message-container');
                msgContainer.innerHTML = '';

                fetch('/Student-Portal/admin/api/subjects/requisites/manage', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        msgContainer.innerHTML = `<div class="alert alert-success py-1 small">${data.message}</div>`;
                        addReqForm.reset();
                        document.getElementById('req-target-subject-id').value = currentSubjectIdForReq; 
                        fetchRequisites(currentSubjectIdForReq);
                    } else {
                        msgContainer.innerHTML = `<div class="alert alert-danger py-1 small">${data.message}</div>`;
                    }
                });
            });
        }

        // Delete Requisite Delegation
        const reqListBody = document.getElementById('requisites-list-body');
        if (reqListBody) {
            reqListBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.delete-req-btn');
                if (btn) {
                    const reqId = btn.getAttribute('data-id');
                    if (!confirm("Remove this requisite?")) return;

                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('prerequisite_id', reqId);
                    formData.append('subject_id', currentSubjectIdForReq);

                    fetch('/Student-Portal/admin/api/subjects/requisites/manage', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            fetchRequisites(currentSubjectIdForReq);
                        } else {
                            alert(data.message);
                        }
                    });
                }
            });
        }
    })();
</script>
