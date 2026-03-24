<?php
/**
 * views/admin/enrollment_form.php
 * AJAX fragment for student enrollment.
 * Variables: $student, $student_id, $years
 */
$student_name = htmlspecialchars($student['student_name'] ?? '');
$student_number = htmlspecialchars($student['student_number'] ?? '');
$course_name = htmlspecialchars($student['course_name'] ?? '');
$course_id = (int)($student['course_id'] ?? 0);
?>
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Enroll Student</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#" onclick="document.querySelector('[data-content=\'get_student_list\']')?.click(); return false;">Students</a></li>
                    <li class="breadcrumb-item active">Enrollment</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-outline-secondary" onclick="document.querySelector('[data-content=\'get_student_list\']')?.click()">
            <i class="bi bi-arrow-left"></i> Back to Student List
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px;">
                        <?= substr($student_name, 0, 1) ?>
                    </div>
                </div>
                <div class="col">
                    <h5 class="mb-0"><?= $student_name ?></h5>
                    <p class="text-muted mb-0"><?= $student_number ?> | <?= $course_name ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="enrollmentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="enroll-tab" data-bs-toggle="tab" data-bs-target="#enroll-panel" type="button" role="tab">
                <i class="bi bi-plus-circle me-1"></i> New Enrollment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> Enrollment History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="enrollmentTabsContent">
        <!-- New Enrollment Panel -->
        <div class="tab-pane fade show active" id="enroll-panel" role="tabpanel" aria-labelledby="enroll-tab">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Select Term and Subjects</h6>
                </div>
                <div class="card-body">
                    <form id="enrollmentForm">
                        <input type="hidden" name="student_id" value="<?= $student_id ?>">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">School Year</label>
                                <select class="form-select" name="school_year" id="schoolYear" required>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Semester</label>
                                <select class="form-select" name="semester" id="semester" required>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="yearLevelContainer">
                                <label class="form-label small fw-bold">Year Level</label>
                                <select class="form-select" id="yearLevel">
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="button" class="btn btn-primary" id="loadSubjectsBtn">
                                <i class="bi bi-arrow-repeat me-1"></i> Load Available Subjects
                            </button>
                        </div>

                        <div id="subjectChecklistArea" style="display: none;">
                            <!-- Subject Search Box -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input type="text" id="subjectSearch" class="form-control border-start-0 ps-0" placeholder="Search subjects by code or name...">
                                </div>
                                <div id="search-feedback" class="small text-muted mt-1" style="display:none;"></div>
                            </div>

                            <div id="regularChecklist">
                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2">Curriculum Subjects</h6>
                                    <div id="curriculumList" class="list-group list-group-flush mb-3">
                                        <!-- Curriculum subjects injected here -->
                                    </div>
                                </div>
                                
                                <div class="accordion mb-4" id="othersAccordion">
                                    <div class="accordion-item border-0">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed px-3 py-2 bg-light rounded shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOthers">
                                                <span class="small fw-bold text-uppercase text-muted">Add Other Subjects</span>
                                            </button>
                                        </h2>
                                        <div id="collapseOthers" class="accordion-collapse collapse" data-bs-parent="#othersAccordion">
                                            <div id="othersList" class="list-group list-group-flush pt-2">
                                                <!-- Other subjects injected here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="summerChecklist" style="display: none;">
                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2">Failed Subjects (Retake Candidates)</h6>
                                    <div id="retakeList" class="list-group list-group-flush mb-3">
                                        <!-- Retake candidates injected here -->
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2">Available Subjects</h6>
                                    <div id="summerAllList" class="list-group list-group-flush">
                                        <!-- All subjects for summer injected here -->
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4 p-3 rounded-3 sticky-bottom bg-white shadow-sm" style="bottom: 1rem; z-index: 100;">
                                <div>
                                    <span class="text-muted small">Total Units:</span>
                                    <span id="unitTotal" class="h5 mb-0 ms-2 fw-bold">0</span>
                                </div>
                                <div id="unitStatus" class="small"></div>
                            </div>

                            <div class="d-grid shadow-sm rounded-pill overflow-hidden">
                                <button type="submit" class="btn btn-success btn-lg py-3 fw-bold" id="submitEnrollment" disabled>
                                    Confirm Enrollment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enrollment History Panel -->
        <div class="tab-pane fade" id="history-panel" role="tabpanel" aria-labelledby="history-tab">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Enrollment History</h6>
                    <button class="btn btn-outline-primary btn-sm" onclick="reloadHistory()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="card-body p-0" id="historyContainer">
                    <div class="p-5 text-center text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Loading history...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Enrollment Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Confirm enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary mb-0">Are you sure you want to proceed with the enrollment for this student? This action cannot be undone once confirmed.</p>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary px-4 py-2 border-0" data-bs-dismiss="modal" style="background-color: #6c757d;">Cancel</button>
                    <button type="button" id="btnActualSubmit" class="btn btn-success px-4 py-2 border-0" style="background-color: #28a745;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Notification Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="statusModalTitleHeader">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p id="statusMsg" class="text-secondary mb-0"></p>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary px-4 py-2 border-0" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const studentId = <?= $student_id ?>;
    const courseId = <?= $course_id ?>;
    const form = document.getElementById('enrollmentForm');
    const loadBtn = document.getElementById('loadSubjectsBtn');
    const checklistArea = document.getElementById('subjectChecklistArea');
    const curriculumList = document.getElementById('curriculumList');
    const othersList = document.getElementById('othersList');
    const retakeList = document.getElementById('retakeList');
    const summerAllList = document.getElementById('summerAllList');
    const unitTotalEl = document.getElementById('unitTotal');
    const unitStatusEl = document.getElementById('unitStatus');
    const submitBtn = document.getElementById('submitEnrollment');
    const historyContainer = document.getElementById('historyContainer');
    const semesterSelect = document.getElementById('semester');
    const yearLevelContainer = document.getElementById('yearLevelContainer');
    const subjectSearch = document.getElementById('subjectSearch');

    let allSubjects = [];
    let curriculumData = [];
    let allOtherSubjects = []; // For optimized loading
    let selectedSubjects = new Map(); // id -> units (for persistent tracking)
    let enrollmentHistory = [];

    // --- Search Functionality ---
    function filterSubjects(searchTerm) {
        const query = searchTerm.toLowerCase().trim();
        const items = checklistArea.querySelectorAll('.list-group-item');
        let visibleCount = 0;

        // 1. Filter existing items (Curriculum, Retakes)
        items.forEach(item => {
            if (item.closest('#curriculumList') || item.closest('#retakeList') || item.closest('#summerAllList')) {
                const code = item.querySelector('.fw-bold')?.textContent.toLowerCase() || '';
                const name = item.querySelector('.small')?.textContent.toLowerCase() || '';
                
                if (code.includes(query) || name.includes(query)) {
                    item.classList.remove('d-none');
                    item.classList.add('d-flex');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                    item.classList.remove('d-flex');
                }
            }
        });

        // 2. Perform Optimized Rendering for "Others"
        if (query.length >= 2) {
            const matches = allOtherSubjects.filter(s => 
                s.subject_code.toLowerCase().includes(query) || 
                s.subject_name.toLowerCase().includes(query)
            );
            
            visibleCount += matches.length;
            renderOptimizedOthers(matches);
            
            // Auto-expand "Others" if matches found
            const othersCollapse = document.getElementById('collapseOthers');
            if (othersCollapse && matches.length > 0 && !othersCollapse.classList.contains('show')) {
                if (window.bootstrap && bootstrap.Collapse) {
                    const bsCollapse = bootstrap.Collapse.getInstance(othersCollapse) || new bootstrap.Collapse(othersCollapse);
                    bsCollapse.show();
                } else {
                    othersCollapse.classList.add('show');
                }
            }
        } else {
            othersList.innerHTML = '<div class="p-3 text-center text-muted small">Type at least 2 characters to search other subjects...</div>';
        }

        // Show feedback if searching
        const feedback = document.getElementById('search-feedback');
        if (query.length > 0) {
            feedback.style.display = 'block';
            feedback.textContent = `Found ${visibleCount} matches`;
        } else {
            feedback.style.display = 'none';
        }
    }

    function renderOptimizedOthers(subjects) {
        if (subjects.length === 0) {
            othersList.innerHTML = '<div class="p-3 text-center text-muted small">No other subjects found.</div>';
            return;
        }
        
        // Only render first 50 to keep it snappy
        const toRender = subjects.slice(0, 50);
        othersList.innerHTML = toRender.map(s => {
            const isChecked = selectedSubjects.has(s.subject_id.toString());
            return `
                <label class="list-group-item d-flex align-items-center">
                    <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${s.subject_id}" 
                           data-units="${s.units}" ${isChecked ? 'checked' : ''}>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="white-space: normal; word-break: break-word;">
                                <span class="fw-bold">${s.subject_code}</span>
                                <div class="small">${s.subject_name}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary rounded-pill">${s.units} Units</span>
                            </div>
                        </div>
                    </div>
                </label>
            `;
        }).join('') + (subjects.length > 50 ? `<div class="p-2 text-center text-muted small">Showing 50 of ${subjects.length} results. Refine search for more.</div>` : '');
        
        attachCbHandlers();
    }

    if (subjectSearch) {
        subjectSearch.addEventListener('input', (e) => filterSubjects(e.target.value));
    }

    // Toggle year level based on semester
    semesterSelect.addEventListener('change', function() {
        if (this.value === 'Summer') {
            yearLevelContainer.style.display = 'none';
        } else {
            yearLevelContainer.style.display = 'block';
        }
        checklistArea.style.display = 'none';
    });

    // Load Subjects Action
    loadBtn.addEventListener('click', async function() {
        const semester = semesterSelect.value;
        const yearLevel = document.getElementById('yearLevel').value;
        
        loadBtn.disabled = true;
        loadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        
        selectedSubjects.clear(); // Reset selection on reload
        
        // Reset search
        if (subjectSearch) {
            subjectSearch.value = '';
        }
        
        try {
            if (semester === 'Summer') {
                const subRes = await fetch('/Student-Portal/admin/api/subjects/list');
                const subData = await subRes.json();
                allSubjects = subData.subjects || [];
                await renderSummerChecklist();
            } else {
                await renderRegularChecklist(yearLevel, semester === '1st Semester' ? 1 : 2);
            }
            
            checklistArea.style.display = 'block';
            updateUnitCount();
        } catch (err) {
            alert('Error loading subjects: ' + err.message);
        } finally {
            loadBtn.disabled = false;
            loadBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Load Available Subjects';
        }
    });

    async function renderRegularChecklist(yearLevel, semInt) {
        document.getElementById('regularChecklist').style.display = 'block';
        document.getElementById('summerChecklist').style.display = 'none';
        
        const enrollSubRes = await fetch(`/Student-Portal/admin/api/students/enroll-form-subjects?student_id=${studentId}&year_level=${yearLevel}&semester_int=${semInt}`);
        const enrollSubData = await enrollSubRes.json();
        
        if (!enrollSubData.success) throw new Error(enrollSubData.message);
        
        const { curriculum, others } = enrollSubData.data;
        allOtherSubjects = others; // Store for optimized searching

        // Render Curriculum
        curriculumList.innerHTML = curriculum.map(e => {
            const isPassed = parseInt(e.already_passed) > 0;
            if (!isPassed) {
                // Pre-select curriculum subjects if not passed
                selectedSubjects.set(e.subject_id.toString(), parseInt(e.units));
            }
            return `
                <label class="list-group-item d-flex align-items-center ${isPassed ? 'bg-light text-muted' : ''}">
                    <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${e.subject_id}" 
                           data-units="${e.units}" ${isPassed ? 'disabled' : 'checked'}>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="white-space: normal; word-break: break-word;">
                                <span class="fw-bold">${e.subject_code}</span>
                                <div class="small">${e.subject_name}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary rounded-pill">${e.units} Units</span>
                                ${isPassed ? '<span class="d-block small text-success">Already passed</span>' : ''}
                            </div>
                        </div>
                    </div>
                </label>
            `;
        }).join('') || '<div class="p-3 text-center text-muted small">No curriculum subjects for this term.</div>';

        // Clear Others render
        othersList.innerHTML = '<div class="p-3 text-center text-muted small">Type to search other subjects...</div>';

        attachCbHandlers();
    }

    async function renderSummerChecklist() {
        document.getElementById('regularChecklist').style.display = 'none';
        document.getElementById('summerChecklist').style.display = 'block';

        // 1. Fetch history for retakes
        const histRes = await fetch(`/Student-Portal/admin/api/students/enrollment-history?student_id=${studentId}`);
        const histData = await histRes.json();
        
        // Failed subjects without subsequent pass
        const passedIds = (histData.history || []).filter(h => h.status === 'passed').map(h => parseInt(h.subject_id));
        const failedMap = {};
        (histData.history || []).filter(h => h.status === 'failed').forEach(h => {
             const sid = parseInt(h.subject_id);
             if (!passedIds.includes(sid)) {
                 failedMap[sid] = h;
             }
        });

        const retakes = Object.values(failedMap);
        retakeList.innerHTML = retakes.map(r => `
            <label class="list-group-item d-flex align-items-center">
                <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${r.subject_id}" 
                       data-units="${r.units}" data-retake="1">
                <input type="hidden" class="retake-flag" name="retake_subject_ids[]" value="${r.subject_id}" disabled>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div style="white-space: normal; word-break: break-word;">
                            <span class="fw-bold">${r.subject_code}</span>
                            <div class="small">${r.subject_name}</div>
                            <div class="small text-danger">Failed in ${r.school_year} ${r.semester}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-secondary rounded-pill">${r.units} Units</span>
                        </div>
                    </div>
                </div>
            </label>
        `).join('') || '<div class="p-3 text-center text-muted small">No failed subjects for retake.</div>';

        // 2. All available
        summerAllList.innerHTML = allSubjects.map(s => {
            const isPassed = passedIds.includes(parseInt(s.subject_id));
            const isFailed = failedMap[parseInt(s.subject_id)];
            if (isFailed) return ''; // Skip if in retake list above
            
            return `
                <label class="list-group-item d-flex align-items-center ${isPassed ? 'bg-light text-muted' : ''}">
                    <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${s.subject_id}" 
                           data-units="${s.units}" ${isPassed ? 'disabled' : ''}>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="white-space: normal; word-break: break-word;">
                                <span class="fw-bold">${s.subject_code}</span>
                                <div class="small">${s.subject_name}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary rounded-pill">${s.units} Units</span>
                                ${isPassed ? '<span class="d-block small text-success">Already passed</span>' : ''}
                            </div>
                        </div>
                    </div>
                </label>
            `;
        }).join('');

        attachCbHandlers();
    }

    function attachCbHandlers() {
        document.querySelectorAll('.subject-cb').forEach(cb => {
            cb.onclick = function() {
                const sid = this.value;
                const units = parseInt(this.getAttribute('data-units') || 0);
                
                if (this.checked) {
                    selectedSubjects.set(sid, units);
                } else {
                    selectedSubjects.delete(sid);
                }

                // Handle retake hidden input sync
                const retakeFlag = this.parentElement.querySelector('.retake-flag');
                if (retakeFlag) {
                    retakeFlag.disabled = !this.checked;
                }
                updateUnitCount();
            };
        });
    }

    function updateUnitCount() {
        let total = 0;
        selectedSubjects.forEach(units => {
            total += units;
        });

        unitTotalEl.textContent = total;

        if (total > 0) {
            unitTotalEl.className = 'h5 mb-0 ms-2 fw-bold text-success';
            unitStatusEl.innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle"></i> ${total} units selected</span>`;
            submitBtn.disabled = false;
        } else {
            unitTotalEl.className = 'h5 mb-0 ms-2 fw-bold';
            unitStatusEl.textContent = '';
            submitBtn.disabled = true;
        }
    }

    // Submit Enrollment Initiation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ensure all selected subjects are included, even those not currently rendered!
        // We'll append hidden inputs for subjects in selectedSubjects that are NOT already in the form.
        const existingInputs = new Set([...form.querySelectorAll('input[name="subject_ids[]"]')].map(i => i.value));
        
        selectedSubjects.forEach((units, sid) => {
            if (!existingInputs.has(sid)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'subject_ids[]';
                input.value = sid;
                form.appendChild(input);
            }
        });

        // Show Bootstrap Modal
        const modalEl = document.getElementById('confirmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Handle Confirmation
        document.getElementById('btnActualSubmit').onclick = function() {
            modal.hide();
            processEnrollment();
        };
    });

    async function processEnrollment() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enrolling...';
        
        const formData = new FormData(form);
        
        try {
            const r = await fetch('/Student-Portal/admin/api/students/enroll', {
                method: 'POST',
                body: formData
            });
            const res = await r.json();
            
            if (res.success) {
                showStatus(res.message, true);
                reloadHistory();
                checklistArea.style.display = 'none';
                
                // Remove the hidden inputs we added
                form.querySelectorAll('input[type="hidden"][name="subject_ids[]"]').forEach(i => i.remove());
            } else {
                showStatus('Enrollment failed: ' + res.message, false);
            }
        } catch (err) {
            showStatus('Network error: ' + err.message, false);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Confirm Enrollment';
            updateUnitCount();
        }
    }

    function showStatus(message, isSuccess = true) {
        const modalEl = document.getElementById('statusModal');
        const titleHeader = document.getElementById('statusModalTitleHeader');
        const msg = document.getElementById('statusMsg');

        titleHeader.textContent = isSuccess ? 'Success' : 'Error';
        msg.textContent = message;
        new bootstrap.Modal(modalEl).show();
    }

    // History Logic
    window.reloadHistory = function() {
        historyContainer.innerHTML = '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</div>';
        
        fetch(`/Student-Portal/admin/api/students/enrollment-history?student_id=${studentId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            renderHistory(data.history);
        })
        .catch(err => {
            historyContainer.innerHTML = `<div class="p-4 text-danger small">Error: ${err.message}</div>`;
        });
    };

    function renderHistory(history) {
        if (!history || history.length === 0) {
            historyContainer.innerHTML = '<div class="p-4 text-center text-muted small">No enrollment history found.</div>';
            return;
        }

        // Group by SY + Semester
        const groups = {};
        history.forEach(h => {
            const key = `${h.school_year} - ${h.semester}`;
            if (!groups[key]) groups[key] = [];
            groups[key].push(h);
        });

        let html = '';
        for (const [key, items] of Object.entries(groups)) {
            html += `
                <div class="border-bottom">
                    <div class="bg-light p-2 px-3 small fw-bold text-primary d-flex justify-content-between align-items-center">
                        ${key}
                        <span class="badge bg-primary rounded-pill">${items.length} Subjects</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle" style="font-size: 0.85rem;">
                            <thead class="bg-white">
                                <tr class="text-muted small">
                                    <th class="ps-3 border-0" style="width: 50%;">Subject</th>
                                    <th class="text-center border-0" style="width: 16.6%;">Units</th>
                                    <th class="text-center border-0" style="width: 16.6%;">Status</th>
                                    <th class="text-center border-0" style="width: 16.6%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            items.forEach(h => {
                const statusClass = {
                    'enrolled': 'bg-primary',
                    'passed': 'bg-success',
                    'failed': 'bg-danger',
                    'incomplete': 'bg-warning text-dark',
                    'dropped': 'bg-secondary'
                }[h.status];

                html += `
                    <tr>
                        <td class="ps-3" style="white-space: normal; word-break: break-word;">
                            <div class="fw-bold">${h.subject_code}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${h.subject_name}</div>
                            ${h.is_retake == 1 ? '<span class="badge bg-warning text-dark" style="font-size: 0.6rem;">Retake</span>' : ''}
                        </td>
                        <td class="text-center">${h.units}</td>
                        <td class="text-center">
                            <span class="badge ${statusClass}" style="font-size: 0.7rem;">${h.status}</span>
                        </td>
                        <td class="text-center pe-2">
                            ${h.status === 'enrolled' ? `
                                <button class="btn btn-link btn-sm text-danger p-0 drop-btn" 
                                        data-enroll-id="${h.enrollment_id}" title="Drop Subject">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            ` : '—'}
                        </td>
                    </tr>
                `;
            });

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        historyContainer.innerHTML = html;

        // Attach Drop Event
        document.querySelectorAll('.drop-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-enroll-id');
                if (!confirm('Are you sure you want to drop this subject?')) return;
                
                fetch('/Student-Portal/admin/api/students/drop-subject', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `enrollment_id=${id}`
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        reloadHistory();
                    } else {
                        alert(res.message);
                    }
                })
                .catch(err => alert('Network error: ' + err.message));
            });
        });
    }

    // Initialize
    reloadHistory();


})();
</script>
