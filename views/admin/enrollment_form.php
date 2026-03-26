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
<style>
    .enrollment-section-card {
        border: 1px solid #e9ecef;
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
    }
    .enrollment-section-card + .enrollment-section-card {
        margin-top: 1rem;
    }
    .retake-section-card {
        border-color: #f5c2c7;
        background: linear-gradient(180deg, #fff8f8 0%, #fffdfd 100%);
        box-shadow: 0 0.5rem 1.25rem rgba(220, 53, 69, 0.08);
    }
    .section-title-row {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 1rem;
    }
    .section-helper {
        font-size: 0.8rem;
        color: #6c757d;
    }
    .workspace-nav {
        margin-bottom: 1rem;
    }
    .workspace-nav .workspace-tab {
        border: 1px solid transparent !important;
        border-top-left-radius: 0.375rem !important;
        border-top-right-radius: 0.375rem !important;
        color: #212529 !important;
        font-weight: 700 !important;
        box-shadow: none !important;
        background-color: transparent !important;
    }
    .workspace-nav .workspace-tab:not(.active),
    .workspace-nav .workspace-tab:not(.active):hover,
    .workspace-nav .workspace-tab:not(.active):focus,
    .workspace-nav .workspace-tab.nav-link:not(.active),
    .workspace-nav .workspace-tab.nav-link:not(.active):hover,
    .workspace-nav .workspace-tab.nav-link:not(.active):focus {
        color: #212529 !important;
    }
    .workspace-nav .workspace-tab:hover,
    .workspace-nav .workspace-tab:focus {
        color: #212529 !important;
        background-color: #f8f9fa !important;
        border-color: transparent !important;
        box-shadow: none !important;
    }
    .workspace-nav .workspace-tab.active {
        color: #212529 !important;
        background-color: #ffffff !important;
        border-color: #dee2e6 #dee2e6 #ffffff !important;
        box-shadow: none !important;
    }
    .workspace-count {
        margin-left: 0.35rem;
        font-weight: 700;
    }
    .workspace-count-retake {
        color: #dc3545;
    }
    .workspace-count-curriculum {
        color: #0d6efd;
    }
    .workspace-count-other {
        color: #6c757d;
    }
    .workspace-nav .workspace-tab.active .workspace-count {
        font-weight: 800;
    }
    .workspace-pane {
        display: none;
    }
    .workspace-pane.active {
        display: block;
    }
    .subject-workspace-loading {
        opacity: 0.65;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
</style>
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
    <ul class="nav nav-tabs" id="enrollmentTabs" role="tablist">
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

                        <div id="subjectChecklistArea" style="display: none;">
                            <div id="regularChecklist">
                                <ul class="nav nav-tabs workspace-nav" id="regularWorkspaceNav" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link workspace-tab" data-workspace-group="regular" data-workspace-target="regularRetakePane">Retakes <span class="workspace-count workspace-count-retake" id="retakeCandidateCount">0</span></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link workspace-tab" data-workspace-group="regular" data-workspace-target="regularCurriculumPane">Curriculum <span class="workspace-count workspace-count-curriculum" id="curriculumSubjectCount">0</span></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link workspace-tab" data-workspace-group="regular" data-workspace-target="regularOthersPane">Other Subjects <span class="workspace-count workspace-count-other" id="otherSubjectCount">0</span></button>
                                    </li>
                                </ul>
                                <div class="m-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="text-muted" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.398 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.866-3.834zM12 6.5a5.5 5.5 0 1 1-11 0a5.5 5.5 0 0 1 11 0"/>
                                            </svg>
                                        </span>
                                        <input type="text" id="subjectSearch" class="form-control border-start-0 ps-0" placeholder="Search subjects by code or name...">
                                    </div>
                                    <div id="search-feedback" class="small text-muted mt-1" style="display:none;"></div>
                                    <div id="subject-loading-feedback" class="small text-primary mt-1" style="display:none;"></div>
                                </div>

                                <div class="workspace-pane" id="regularRetakePane" hidden aria-hidden="true" style="display:none;">
                                    <div class="enrollment-section-card retake-section-card p-3 mb-3">
                                        <div class="section-title-row mb-2">
                                        <div>
                                            <h6 class="small fw-bold text-uppercase text-danger mb-1">Failed Subjects (Retake Candidates)</h6>
                                            <div class="section-helper">Latest failed takes are highlighted here and selected first so they are easy to review.</div>
                                        </div>
                                    </div>
                                        <div id="regularRetakeList" class="list-group list-group-flush mb-0">
                                            <!-- Regular retake candidates injected here -->
                                        </div>
                                    </div>
                                </div>

                                <div class="workspace-pane" id="regularCurriculumPane" hidden aria-hidden="true" style="display:none;">
                                    <div class="enrollment-section-card p-3 mb-3">
                                        <div class="section-title-row mb-2">
                                        <div>
                                            <h6 class="small fw-bold text-uppercase text-primary mb-1">Recommended Curriculum Subjects</h6>
                                            <div class="section-helper">Core subjects for the selected year level and semester.</div>
                                        </div>
                                    </div>
                                        <div id="curriculumList" class="list-group list-group-flush mb-0">
                                            <!-- Curriculum subjects injected here -->
                                        </div>
                                    </div>
                                </div>

                                <div class="workspace-pane" id="regularOthersPane" hidden aria-hidden="true" style="display:none;">
                                    <div class="enrollment-section-card p-3 mb-4">
                                        <div class="section-title-row mb-2">
                                        <div>
                                            <h6 class="small fw-bold text-uppercase text-muted mb-1">Other Subjects</h6>
                                            <div class="section-helper">Search by code or name to add electives or non-curriculum subjects.</div>
                                        </div>
                                    </div>
                                        <div id="othersList" class="list-group list-group-flush pt-2">
                                            <!-- Other subjects injected here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="summerChecklist" style="display: none;">
                                <ul class="nav nav-tabs workspace-nav" id="summerWorkspaceNav" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link workspace-tab" data-workspace-group="summer" data-workspace-target="summerRetakePane">Retakes <span class="workspace-count workspace-count-retake" id="summerRetakeCount">0</span></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link workspace-tab" data-workspace-group="summer" data-workspace-target="summerAvailablePane">Available <span class="workspace-count workspace-count-other" id="summerAvailableCount">0</span></button>
                                    </li>
                                </ul>

                                <div class="workspace-pane" id="summerRetakePane" hidden aria-hidden="true" style="display:none;">
                                    <div class="enrollment-section-card retake-section-card p-3 mb-3">
                                        <div class="section-title-row mb-2">
                                        <div>
                                            <h6 class="small fw-bold text-uppercase text-danger mb-1">Failed Subjects (Retake Candidates)</h6>
                                            <div class="section-helper">These are the subjects whose latest recorded take is still failed.</div>
                                        </div>
                                    </div>
                                        <div id="retakeList" class="list-group list-group-flush mb-0">
                                            <!-- Retake candidates injected here -->
                                        </div>
                                    </div>
                                </div>

                                <div class="workspace-pane" id="summerAvailablePane" hidden aria-hidden="true" style="display:none;">
                                    <div class="enrollment-section-card p-3 mb-4">
                                        <div class="section-title-row mb-2">
                                        <div>
                                            <h6 class="small fw-bold text-uppercase text-primary mb-1">Other Available Subjects</h6>
                                            <div class="section-helper">Use summer for additional load only after reviewing retake needs above.</div>
                                        </div>
                                    </div>
                                        <div id="summerAllList" class="list-group list-group-flush">
                                            <!-- All subjects for summer injected here -->
                                        </div>
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

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success w-100" id="submitEnrollment" disabled>
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
    const checklistArea = document.getElementById('subjectChecklistArea');
    const curriculumList = document.getElementById('curriculumList');
    const othersList = document.getElementById('othersList');
    const regularRetakeList = document.getElementById('regularRetakeList');
    const retakeList = document.getElementById('retakeList');
    const summerAllList = document.getElementById('summerAllList');
    const unitTotalEl = document.getElementById('unitTotal');
    const unitStatusEl = document.getElementById('unitStatus');
    const submitBtn = document.getElementById('submitEnrollment');
    const historyContainer = document.getElementById('historyContainer');
    const schoolYearSelect = document.getElementById('schoolYear');
    const semesterSelect = document.getElementById('semester');
    const yearLevelContainer = document.getElementById('yearLevelContainer');
    const yearLevelSelect = document.getElementById('yearLevel');
    const subjectSearch = document.getElementById('subjectSearch');
    const subjectLoadingFeedback = document.getElementById('subject-loading-feedback');
    const retakeCandidateCountEl = document.getElementById('retakeCandidateCount');
    const curriculumSubjectCountEl = document.getElementById('curriculumSubjectCount');
    const otherSubjectCountEl = document.getElementById('otherSubjectCount');
    const summerRetakeCountEl = document.getElementById('summerRetakeCount');
    const summerAvailableCountEl = document.getElementById('summerAvailableCount');

    let allSubjects = [];
    let curriculumData = [];
    let allOtherSubjects = []; // For optimized loading
    let selectedSubjects = new Map(); // id -> units (for persistent tracking)
    let enrollmentHistory = [];
    let retakeCandidates = [];
    let latestEnrollmentBySubject = new Map();
    let isAutoLoadingSubjects = false;
    let latestEnrollmentLoaded = false;
    let subjectCatalogLoaded = false;
    let lastSelectionKey = '';

    // --- Search Functionality ---
    function filterSubjects(searchTerm) {
        const query = searchTerm.toLowerCase().trim();
        const items = checklistArea.querySelectorAll('.list-group-item');
        let visibleCount = 0;

        // 1. Filter existing items (Curriculum, Retakes)
        items.forEach(item => {
            if (item.closest('#curriculumList') || item.closest('#regularRetakeList') || item.closest('#retakeList') || item.closest('#summerAllList')) {
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

            if (matches.length > 0) {
                if (semesterSelect.value === 'Summer') {
                    setWorkspace('summer', 'summerAvailablePane');
                } else {
                    setWorkspace('regular', 'regularOthersPane');
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
            const latestTake = latestEnrollmentBySubject.get(parseInt(s.subject_id));
            const isPassed = latestTake && latestTake.status === 'passed';
            const isChecked = selectedSubjects.has(s.subject_id.toString());
            return `
                <label class="list-group-item d-flex align-items-center ${isPassed ? 'bg-light text-muted' : ''}">
                    <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${s.subject_id}" 
                           data-units="${s.units}" ${isChecked && !isPassed ? 'checked' : ''} ${isPassed ? 'disabled' : ''}>
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
        }).join('') + (subjects.length > 50 ? `<div class="p-2 text-center text-muted small">Showing 50 of ${subjects.length} results. Refine search for more.</div>` : '');
        
        attachCbHandlers();
    }

    function setText(el, value) {
        if (el) el.textContent = value;
    }

    async function loadLatestEnrollmentMap() {
        if (latestEnrollmentLoaded) return;

        const histRes = await fetch(`/Student-Portal/admin/api/students/enrollment-history?student_id=${studentId}`);
        const histData = await histRes.json();
        if (!histData.success) throw new Error(histData.message);

        latestEnrollmentBySubject = new Map();
        (histData.history || []).forEach(h => {
            const sid = parseInt(h.subject_id);
            if (!latestEnrollmentBySubject.has(sid)) {
                latestEnrollmentBySubject.set(sid, h);
            }
        });
        latestEnrollmentLoaded = true;
    }

    async function loadAllSubjectsCatalog() {
        if (subjectCatalogLoaded) return;

        const subRes = await fetch('/Student-Portal/admin/api/subjects/list');
        const subData = await subRes.json();
        if (!subData.success) throw new Error(subData.message || 'Failed to load subjects.');
        allSubjects = subData.subjects || [];
        subjectCatalogLoaded = true;
    }

    function setSubjectLoadingState(isLoading, message = 'Updating subject recommendations...') {
        checklistArea.classList.toggle('subject-workspace-loading', isLoading);
        if (subjectLoadingFeedback) {
            subjectLoadingFeedback.style.display = isLoading ? 'block' : 'none';
            subjectLoadingFeedback.innerHTML = isLoading
                ? '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + message
                : '';
        }
    }

    function setWorkspace(group, targetId) {
        document.querySelectorAll(`[data-workspace-group="${group}"]`).forEach(btn => {
            const isActiveButton = btn.getAttribute('data-workspace-target') === targetId;
            btn.classList.toggle('active', isActiveButton);
            btn.setAttribute('aria-selected', isActiveButton ? 'true' : 'false');
            btn.style.color = '#212529';
            btn.style.backgroundColor = isActiveButton ? '#ffffff' : 'transparent';
            btn.style.borderColor = isActiveButton ? '#dee2e6 #dee2e6 #ffffff' : 'transparent';
            btn.style.boxShadow = 'none';
        });
        const prefix = group === 'regular' ? 'regular' : 'summer';
        document.querySelectorAll(`[id^="${prefix}"][id$="Pane"]`).forEach(pane => {
            const isActive = pane.id === targetId;
            pane.classList.toggle('active', isActive);
            pane.hidden = !isActive;
            pane.style.display = isActive ? 'block' : 'none';
            pane.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    function updateSubjectSummary({ retakes = 0, curriculum = 0, others = 0 }) {
        setText(retakeCandidateCountEl, `(${retakes})`);
        setText(curriculumSubjectCountEl, `(${curriculum})`);
        setText(otherSubjectCountEl, `(${others})`);
        setText(summerRetakeCountEl, `(${retakes})`);
        setText(summerAvailableCountEl, `(${others})`);

        if (retakeCandidateCountEl) retakeCandidateCountEl.style.color = '#dc3545';
        if (summerRetakeCountEl) summerRetakeCountEl.style.color = '#dc3545';
        if (curriculumSubjectCountEl) curriculumSubjectCountEl.style.color = '#0d6efd';
        if (otherSubjectCountEl) otherSubjectCountEl.style.color = '#6c757d';
        if (summerAvailableCountEl) summerAvailableCountEl.style.color = '#6c757d';
    }

    if (subjectSearch) {
        subjectSearch.addEventListener('input', (e) => filterSubjects(e.target.value));
    }

    document.querySelectorAll('[data-workspace-group][data-workspace-target]').forEach(btn => {
        btn.addEventListener('click', function() {
            setWorkspace(this.getAttribute('data-workspace-group'), this.getAttribute('data-workspace-target'));
        });
    });

    async function loadSubjectsForCurrentSelection() {
        if (isAutoLoadingSubjects) return;

        const semester = semesterSelect.value;
        const schoolYear = schoolYearSelect.value;
        const yearLevel = yearLevelSelect.value;
        const selectionKey = `${schoolYear}|${semester}|${yearLevel}`;
        if (selectionKey === lastSelectionKey && checklistArea.style.display !== 'none') return;
        isAutoLoadingSubjects = true;
        selectedSubjects.clear();

        if (subjectSearch) {
            subjectSearch.value = '';
        }

        try {
            await loadLatestEnrollmentMap();
            checklistArea.style.display = 'block';
            setSubjectLoadingState(true);

            if (semester === 'Summer') {
                await loadAllSubjectsCatalog();
                const retakeRes = await fetch(`/Student-Portal/admin/api/students/retake-candidates?student_id=${studentId}&school_year=${encodeURIComponent(schoolYear)}&semester=${encodeURIComponent(semester)}`);
                const retakeData = await retakeRes.json();
                if (!retakeData.success) throw new Error(retakeData.message);
                retakeCandidates = retakeData.retake_candidates || [];
                await renderSummerChecklist();
            } else {
                await renderRegularChecklist(yearLevel, semester === '1st Semester' ? 1 : 2, schoolYear, semester);
            }

            lastSelectionKey = selectionKey;
            updateUnitCount();
        } catch (err) {
            alert('Error loading subjects: ' + err.message);
        } finally {
            setSubjectLoadingState(false);
            isAutoLoadingSubjects = false;
        }
    }

    // Toggle year level based on semester
    semesterSelect.addEventListener('change', async function() {
        if (this.value === 'Summer') {
            yearLevelContainer.style.display = 'none';
        } else {
            yearLevelContainer.style.display = 'block';
        }
        await loadSubjectsForCurrentSelection();
    });

    yearLevelSelect.addEventListener('change', async function() {
        if (semesterSelect.value !== 'Summer') {
            await loadSubjectsForCurrentSelection();
        }
    });

    schoolYearSelect.addEventListener('change', async function() {
        await loadSubjectsForCurrentSelection();
    });

    async function renderRegularChecklist(yearLevel, semInt, schoolYear, semesterLabel) {
        document.getElementById('regularChecklist').style.display = 'block';
        document.getElementById('summerChecklist').style.display = 'none';
        
        const enrollSubRes = await fetch(`/Student-Portal/admin/api/students/enroll-form-subjects?student_id=${studentId}&year_level=${yearLevel}&semester_int=${semInt}&school_year=${encodeURIComponent(schoolYear)}&semester=${encodeURIComponent(semesterLabel)}`);
        const enrollSubData = await enrollSubRes.json();
        
        if (!enrollSubData.success) throw new Error(enrollSubData.message);
        
        const { curriculum, others, retake_candidates } = enrollSubData.data;
        retakeCandidates = retake_candidates || [];
        const retakeIds = new Set(retakeCandidates.map(r => parseInt(r.subject_id)));
        allOtherSubjects = (others || []).filter(s => !retakeIds.has(parseInt(s.subject_id)));
        const regularCurriculum = (curriculum || []).filter(e => !retakeIds.has(parseInt(e.subject_id)));

        retakeCandidates.forEach(r => {
            selectedSubjects.set(r.subject_id.toString(), parseInt(r.units));
        });

        regularRetakeList.innerHTML = retakeCandidates.map(r => `
            <label class="list-group-item d-flex align-items-center">
                <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${r.subject_id}" 
                       data-units="${r.units}" data-retake="1" checked>
                <input type="hidden" class="retake-flag" name="retake_subject_ids[]" value="${r.subject_id}">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div style="white-space: normal; word-break: break-word;">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-bold">${r.subject_code}</span>
                                <span class="badge text-bg-danger">Latest take: Failed</span>
                                <span class="badge text-bg-warning text-dark">Recommended</span>
                            </div>
                            <div class="small">${r.subject_name}</div>
                            <div class="small text-danger">Latest take failed in ${r.school_year} ${r.semester}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-secondary rounded-pill">${r.units} Units</span>
                        </div>
                    </div>
                </div>
            </label>
        `).join('') || '<div class="p-3 text-center text-muted small">No failed subjects for retake.</div>';

        // Render Curriculum
        curriculumList.innerHTML = regularCurriculum.map(e => {
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

        updateSubjectSummary({
            retakes: retakeCandidates.length,
            curriculum: regularCurriculum.length,
            others: allOtherSubjects.length
        });

        if (retakeCandidates.length > 0) {
            setWorkspace('regular', 'regularRetakePane');
        } else {
            setWorkspace('regular', 'regularCurriculumPane');
        }

        // Clear Others render
        othersList.innerHTML = '<div class="p-3 text-center text-muted small">Type to search other subjects...</div>';

        attachCbHandlers();
    }

    async function renderSummerChecklist() {
        document.getElementById('regularChecklist').style.display = 'none';
        document.getElementById('summerChecklist').style.display = 'block';

        const retakes = retakeCandidates || [];
        const retakeIds = new Set(retakes.map(r => parseInt(r.subject_id)));

        retakes.forEach(r => {
            selectedSubjects.set(r.subject_id.toString(), parseInt(r.units));
        });

        retakeList.innerHTML = retakes.map(r => `
            <label class="list-group-item d-flex align-items-center">
                <input class="form-check-input me-3 subject-cb" type="checkbox" name="subject_ids[]" value="${r.subject_id}" 
                       data-units="${r.units}" data-retake="1" checked>
                <input type="hidden" class="retake-flag" name="retake_subject_ids[]" value="${r.subject_id}">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div style="white-space: normal; word-break: break-word;">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-bold">${r.subject_code}</span>
                                <span class="badge text-bg-danger">Latest take: Failed</span>
                                <span class="badge text-bg-warning text-dark">Recommended</span>
                            </div>
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
        const summerAvailableSubjects = allSubjects.filter(s => !retakeIds.has(parseInt(s.subject_id)));
        summerAllList.innerHTML = summerAvailableSubjects.map(s => {
            const sid = parseInt(s.subject_id);
            const latestTake = latestEnrollmentBySubject.get(sid);
            const isPassed = latestTake && latestTake.status === 'passed';
            
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
        }).join('') || '<div class="p-3 text-center text-muted small">No additional summer subjects available.</div>';

        updateSubjectSummary({
            retakes: retakes.length,
            curriculum: 0,
            others: summerAvailableSubjects.length
        });

        if (retakes.length > 0) {
            setWorkspace('summer', 'summerRetakePane');
        } else {
            setWorkspace('summer', 'summerAvailablePane');
        }

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
    loadSubjectsForCurrentSelection();


})();
</script>
