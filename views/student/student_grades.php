<?php
/**
 * views/student/student_grades.php
 * Updated student grades view with term selection and curriculum tracking.
 */
?>
<div class="container-fluid p-0" id="grades-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Academic Grades</h1>
            <p class="text-muted">View your performance per term, curriculum progress, and full scholastic history.</p>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4" id="overall-summary">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body py-3">
                    <h6 class="text-muted small text-uppercase mb-2 fw-bold">Overall GWA</h6>
                    <div class="h4 mb-0 fw-bold" id="overall-gwa">--</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body py-3">
                    <h6 class="text-muted small text-uppercase mb-2 fw-bold">Units Earned</h6>
                    <div class="h4 mb-0 fw-bold" id="total-units">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-info border-4">
                <div class="card-body py-3">
                    <h6 class="text-muted small text-uppercase mb-2 fw-bold">Subjects Passed</h6>
                    <div class="h4 mb-0 fw-bold" id="subjects-passed">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-dark border-4">
                <div class="card-body py-3">
                    <h6 class="text-muted small text-uppercase mb-2 fw-bold">Academic Standing</h6>
                    <div class="h4 mb-0 fw-bold small" id="standing-text">Calculating...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 px-2" id="gradeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="tab-term" data-bs-toggle="tab" data-bs-target="#pane-term" type="button" role="tab">This Term</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-progress" data-bs-toggle="tab" data-bs-target="#pane-progress" type="button" role="tab">Curriculum Progress</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-history" data-bs-toggle="tab" data-bs-target="#pane-history" type="button" role="tab">Full History</button>
        </li>
    </ul>

    <div class="tab-content" id="gradeTabsContent">
        <!-- TAB 1: THIS TERM -->
        <div class="tab-pane fade show active" id="pane-term" role="tabpanel" aria-labelledby="tab-term">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold">This Term</h6>
                    <button id="exportPdfBtn" class="btn btn-primary btn-sm d-flex align-items-center">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
                    </button>
                </div>
                <div class="card-body bg-light border-bottom">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">School Year</label>
                            <select id="termYear" class="form-select border-0 shadow-none bg-white"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Semester</label>
                            <select id="termSem" class="form-select border-0 shadow-none bg-white"></select>
                        </div>
                        <div class="col-md-4 text-center text-md-end">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="term-context-bar" class="px-4 py-2 border-bottom small text-muted d-flex justify-content-between align-items-center" style="display: none !important;">
                        <span id="term-info"></span>
                        <span id="term-count-badge" class="badge bg-primary"></span>
                    </div>
                    
                    <div class="px-4 py-4" id="term-loading">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Loading enrollment data...</p>
                        </div>
                    </div>

                    <div id="term-content" style="display: none;">
                        <div id="term-alert-container" class="px-4 pt-4"></div>
                        
                    <div class="row g-3 px-4 pt-3 pb-4">
                        <div class="col-3">
                            <div class="bg-white border rounded p-2 text-center">
                                <div class="text-muted small">Term GWA</div>
                                <div class="h6 mb-0 fw-bold" id="term-gwa">--</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white border rounded p-2 text-center">
                                    <div class="text-muted small">Units</div>
                                    <div class="h6 mb-0 fw-bold" id="term-units">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white border rounded p-2 text-center">
                                    <div class="text-muted small">Graded</div>
                                    <div class="h6 mb-0 fw-bold" id="term-graded">0/0</div>
                                </div>
                            </div>
                        <div class="col-3">
                            <div class="bg-white border rounded p-2 text-center">
                                <div class="text-muted small">Status</div>
                                <div class="h6 mb-0 fw-bold" id="term-status-badge">--</div>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 pb-3">
                        <div class="mb-3 input-group">
                            <span class="input-group-text">
                                <svg class="bi bi-search" height="16px" width="16px" fill="current" role="img" aria-label="Search">
                                    <use xlink:href="/Student-Portal/assets/images/search.svg"/>
                                </svg>
                            </span>
                            <label for="termSearch" class="form-label"></label>
                            <input type="text" id="termSearch" class="form-control" placeholder="Filter this term..." autocomplete="off">
                        </div>
                    </div>

                        <div class="table-responsive mb-8">
                            <table class="table table-hover align-middle mb-0" id="term-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 15%;">Code</th>
                                        <th style="width: 45%;">Subject</th>
                                        <th class="text-center" style="width: 10%;">Units</th>
                                        <th class="text-center" style="width: 15%;">Average</th>
                                        <th class="text-center pe-4" style="width: 15%;">Grade</th>
                                    </tr>
                                </thead>
                                <tbody><!-- Loaded via JS --></tbody>
                            </table>
                        </div>
                        <div id="term-empty" class="p-5 text-center text-muted d-none">
                            <i class="bi bi-journal-x h1 d-block mb-3 opacity-25"></i>
                            <p class="mb-0">No enrollment records found for this term.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: PROGRESS -->
        <div class="tab-pane fade" id="pane-progress" role="tabpanel" aria-labelledby="tab-progress">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <h6 class="mb-1 fw-bold">Curriculum Road Map</h6>
                                <div class="text-muted small">Review your complete curriculum progress by year and semester.</div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button id="exportCurriculumPdfBtn" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                </button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="mb-3 input-group">
                                <span class="input-group-text">
                                    <svg class="bi bi-search" height="16px" width="16px" fill="current" role="img" aria-label="Search">
                                        <use xlink:href="/Student-Portal/assets/images/search.svg"/>
                                    </svg>
                                </span>
                                <label for="progressSearch" class="form-label"></label>
                                <input type="text" id="progressSearch" class="form-control" placeholder="Filter curriculum..." autocomplete="off">
                            </div>
                        </div>
                    </div>
                <div class="card-body p-0" id="progress-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: HISTORY -->
        <div class="tab-pane fade" id="pane-history" role="tabpanel" aria-labelledby="tab-history">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Scholastic Records</h6>
                    </div>
                    <div class="mt-3">
                        <div class="mb-3 input-group">
                            <span class="input-group-text">
                                <svg class="bi bi-search" height="16px" width="16px" fill="current" role="img" aria-label="Search">
                                    <use xlink:href="/Student-Portal/assets/images/search.svg"/>
                                </svg>
                            </span>
                            <label for="historySearch" class="form-label"></label>
                            <input type="text" id="historySearch" class="form-control" placeholder="Search records..." autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="history-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="table-responsive mb-8">
                        <table class="table table-hover align-middle mb-0" id="history-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 15%;">Term</th>
                                    <th style="width: 45%;">Subject</th>
                                    <th class="text-center" style="width: 10%;">Units</th>
                                    <th class="text-center pe-4" style="width: 30%;">Grade</th>
                                </tr>
                            </thead>
                            <tbody><!-- Loaded via JS --></tbody>
                        </table>
                    </div>
                    <div id="history-empty" class="p-5 text-center text-muted d-none">
                        <p>No academic records found.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let termsList = [];
    let currentTerm = null;
    let scholasticHistory = [];

    // Summary Elements
    const overallGwaEl = document.getElementById('overall-gwa');
    const totalUnitsEl = document.getElementById('total-units');
    const subjectsPassedEl = document.getElementById('subjects-passed');
    const standingTextEl = document.getElementById('standing-text');

    // Term Elements
    const termYearSelect = document.getElementById('termYear');
    const termSemSelect = document.getElementById('termSem');
    const termContent = document.getElementById('term-content');
    const termLoading = document.getElementById('term-loading');
    const termTableBody = document.querySelector('#term-table tbody');
    const termEmpty = document.getElementById('term-empty');
    const termAlertContainer = document.getElementById('term-alert-container');

    // Tab Lazy Loading
    let progressLoaded = false;
    let historyLoaded = false;

    // 1. Initial Data Fetch
    async function init() {
        try {
            // Fetch History immediately for summary calculations
            const histRes = await fetch('/Student-Portal/student/api/grades/history');
            const histData = await histRes.json();
            scholasticHistory = histData.data || [];
            updateOverallSummary();

            // Fetch Terms
            const termsRes = await fetch('/Student-Portal/student/api/grades/terms');
            const data = await termsRes.json();
            termsList = data.terms || [];

            if (termsList.length === 0) {
                termLoading.innerHTML = '<div class="p-5 text-center text-muted">You have no enrollment records yet.</div>';
                return;
            }

            // Group terms for selection dropdown
            const years = [...new Set(termsList.map(t => t.school_year))];
            termYearSelect.innerHTML = years.map(y => `<option value="${y}">${y}</option>`).join('');
            
            // Listen for changes
            termYearSelect.addEventListener('change', updateSemesterDropdown);
            termSemSelect.addEventListener('change', loadTermData);

            // Set initial state
            updateSemesterDropdown();
        } catch (err) {
            console.error(err);
        }
    }

    function updateSemesterDropdown() {
        const year = termYearSelect.value;
        const semesters = termsList.filter(t => t.school_year === year).map(t => t.semester);
        termSemSelect.innerHTML = semesters.map(s => `<option value="${s}">${s}</option>`).join('');
        loadTermData();
    }

    async function loadTermData() {
        const year = termYearSelect.value;
        const sem = termSemSelect.value;
        
        termLoading.classList.remove('d-none');
        termContent.style.opacity = '0.3';
        termContent.style.display = 'block';

        try {
            const res = await fetch(`/Student-Portal/student/api/grades/term?school_year=${encodeURIComponent(year)}&semester=${encodeURIComponent(sem)}`);
            const data = await res.json();
            renderTerm(data.data || [], year, sem);
        } catch (err) {
            alert('Failed to load term grades.');
        } finally {
            termLoading.classList.add('d-none');
            termContent.style.opacity = '1';
        }
    }

    function renderTerm(subjects, year, sem) {
        termAlertContainer.innerHTML = '';
        if (subjects.length === 0) {
            termTableBody.closest('table').classList.add('d-none');
            termEmpty.classList.remove('d-none');
            document.getElementById('term-units').textContent = '0';
            document.getElementById('term-gwa').textContent = '--';
            return;
        }

        termTableBody.closest('table').classList.remove('d-none');
        termEmpty.classList.add('d-none');

        let totalUnits = 0;
        let weightedSum = 0;
        let unitsForGwa = 0;
        let gradedCount = 0;
        let hasIncomplete = false;

        termTableBody.innerHTML = subjects.map(s => {
            const units = parseInt(s.units);
            totalUnits += units;
            
            const grade = s.grade ? parseFloat(s.grade) : null;
            const remarks = s.remarks || '';
            const status = s.status || 'enrolled';
            
            if (grade !== null) gradedCount++;
            if (remarks === 'Incomplete' || status === 'incomplete') hasIncomplete = true;

            if (grade !== null && !['Incomplete', 'Dropped'].includes(remarks) && !['incomplete', 'dropped'].includes(status)) {
                weightedSum += (grade * units);
                unitsForGwa += units;
            }

            // Grade cell logic
            let gradeBadge = '';
            if (remarks === 'Passed' || (grade !== null && grade <= 3.00)) {
                gradeBadge = `<span class="badge bg-success">${grade.toFixed(2)}</span>`;
            } else if (grade === 5.00 || remarks === 'Failed') {
                gradeBadge = `<span class="badge bg-danger">5.00</span>`;
            } else if (remarks === 'Incomplete' || status === 'incomplete') {
                gradeBadge = `<span class="badge bg-warning text-dark">INC</span>`;
            } else if (status === 'dropped') {
                gradeBadge = `<span class="text-muted text-decoration-line-through">Dropped</span>`;
            } else {
                gradeBadge = `<span class="text-muted small">Pending</span>`;
            }

            // Pips
            const pips = [s.prelim, s.midterm, s.prefinal, s.finals].map(p => 
                `<span class="${p !== null ? 'text-primary' : 'text-muted opacity-50'}" title="${p || ''}">●</span>`
            ).join(' ');

            return `
                <tr class="grade-row">
                    <td class="ps-4">
                        <code class="text-primary fw-bold">${s.subject_code}</code>
                        ${s.is_retake == 1 ? '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">Retake</span>' : ''}
                    </td>
                    <td>
                        <div>${s.subject_name}</div>
                        <div style="font-size: 0.75rem;">${pips}</div>
                    </td>
                    <td class="text-center">${units}</td>
                    <td class="text-center">${s.average_grade ? parseFloat(s.average_grade).toFixed(1) : '—'}</td>
                    <td class="text-center pe-4">${gradeBadge}</td>
                </tr>
            `;
        }).join('');

        // Update term metrics
        document.getElementById('term-units').textContent = totalUnits;
        document.getElementById('term-gwa').textContent = unitsForGwa > 0 ? (weightedSum / unitsForGwa).toFixed(2) : '--';
        document.getElementById('term-graded').textContent = `${gradedCount}/${subjects.length}`;
        
        const statusBadge = document.getElementById('term-status-badge');
        if (hasIncomplete) {
            statusBadge.innerHTML = '<span class="badge bg-warning text-dark">Has INC</span>';
            termAlertContainer.innerHTML = `<div class="alert alert-warning py-2 small mb-0"><i class="bi bi-exclamation-circle me-2"></i>You have an incomplete subject this term. Contact your instructor for resolution.</div>`;
        } else if (gradedCount > 0) {
            statusBadge.innerHTML = '<span class="badge bg-success">In Progress</span>';
        } else {
            statusBadge.innerHTML = '<span class="text-muted">No grades</span>';
        }
    }

    function updateOverallSummary() {
        if (scholasticHistory.length === 0) return;

        let totalUnitsPassed = 0;
        let weightedSum = 0;
        let unitsForGwa = 0;
        let subjectsPassed = 0;
        const subjectOutcomes = {}; // Latest outcome per subject code

        scholasticHistory.forEach(h => {
            const units = parseInt(h.units);
            const grade = h.grade ? parseFloat(h.grade) : null;
            const remarks = h.remarks || '';
            const status = h.status || '';
            const scode = h.subject_code;

            const isPassed = remarks === 'Passed' || (grade !== null && grade <= 3.00);
            if (isPassed) {
                totalUnitsPassed += units;
                subjectsPassed++;
            }

            if (grade !== null && !['Incomplete', 'Dropped'].includes(remarks) && !['incomplete', 'dropped'].includes(status)) {
                weightedSum += (grade * units);
                unitsForGwa += units;
            }

            // Track for standing: sort results by SY/Sem? 
            // The API returns most recent first, so the first encounter is the most recent.
            if (!subjectOutcomes[scode]) {
                subjectOutcomes[scode] = remarks;
            }
        });

        const gwaValue = unitsForGwa > 0 ? (weightedSum / unitsForGwa) : null;
        overallGwaEl.textContent = gwaValue !== null ? gwaValue.toFixed(2) : '--';
        totalUnitsEl.textContent = totalUnitsPassed;
        subjectsPassedEl.textContent = subjectsPassed;

        // Calculate failures and incompletes properly
        let failedCount = 0;
        let hasIncompleteStatus = false;
        scholasticHistory.forEach(h => {
             const grade = h.grade ? parseFloat(h.grade) : null;
             const remarks = (h.remarks || '').toLowerCase();
             if (remarks === 'incomplete') hasIncompleteStatus = true;
             if (remarks === 'failed' || grade === 5.00) failedCount++;
        });

        let standing = 'Good Standing';
        let colorClass = 'text-success';
        let icon = 'bi-check-circle';

        if (hasIncompleteStatus) {
            standing = 'Has Incomplete';
            colorClass = 'text-warning';
            icon = 'bi-exclamation-circle';
        } else if (failedCount >= 3 || (gwaValue !== null && gwaValue > 3.00)) {
            standing = 'Probation';
            colorClass = 'text-danger';
            icon = 'bi-exclamation-triangle-fill';
        } else if (failedCount > 0) {
            standing = 'Warning';
            colorClass = 'text-warning';
            icon = 'bi-exclamation-triangle';
        } else if (gwaValue !== null && gwaValue <= 1.50 && subjectsPassed > 0) {
            standing = 'Honor Roll';
            colorClass = 'text-primary';
            icon = 'bi-star-fill';
        }

        standingTextEl.innerHTML = `<span class="${colorClass}"><i class="bi ${icon}"></i> ${standing}</span>`;
    }

    // Lazy load Progress
    document.getElementById('tab-progress').addEventListener('click', async function() {
        if (progressLoaded) return;
        const container = document.getElementById('progress-container');
        
        try {
            const res = await fetch('/Student-Portal/student/api/grades/progress');
            const data = await res.json();
            renderProgress(data.data || {});
            progressLoaded = true;
        } catch (err) {
            container.innerHTML = '<div class="p-4 text-danger">Error loading progress.</div>';
        }
    });

    function renderProgress(groupedData) {
        const container = document.getElementById('progress-container');
        let html = '';

        for (const year in groupedData) {
            for (const sem in groupedData[year]) {
                const subjects = groupedData[year][sem];
                const yearLabels = {1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year'};
                const semLabels = {1:'1st Semester', 2:'2nd Semester'};

                html += `
                    <div class="px-3 py-2 bg-light small fw-bold text-primary border-bottom border-top mt-3 first:mt-0">
                        ${yearLabels[year] || year} — ${semLabels[sem] || sem}
                    </div>
                    <div class="table-responsive mb-8">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 15%;">Code</th>
                                    <th style="width: 45%;">Subject</th>
                                    <th class="text-center" style="width: 10%;">Units</th>
                                    <th class="text-center" style="width: 15%;">Average</th>
                                    <th class="text-center pe-4" style="width: 15%;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                subjects.forEach(s => {
                    const grade = s.grade ? parseFloat(s.grade) : null;
                    const remarks = s.remarks || '';
                    const status = s.enrollment_status;
                    const isRetake = parseInt(s.retake_count || 0) > 0;

                    let gradeCell = '';
                    if (remarks === 'Passed' || (grade !== null && grade <= 3.00)) {
                        gradeCell = `<span class="badge bg-success">${grade.toFixed(2)}${isRetake ? ' (retake)' : ''}</span>`;
                    } else if (grade === 5.00) {
                        if (isRetake) {
                            gradeCell = `<span class="badge bg-danger">5.00</span> <span class="badge bg-warning text-dark">Retake in progress</span>`;
                        } else {
                            gradeCell = `<span class="badge bg-danger">5.00</span> <span class="badge bg-danger">Needs retake</span>`;
                        }
                    } else if (status === 'incomplete') {
                        gradeCell = `<span class="badge bg-warning text-dark">INC</span>`;
                    } else if (status === 'enrolled' && grade === null) {
                        gradeCell = `<span class="text-muted small">Pending</span>`;
                    } else if (status === 'dropped') {
                        gradeCell = `<span class="text-muted text-decoration-line-through">Dropped</span>`;
                    } else {
                        gradeCell = `<span class="text-muted small">Not yet enrolled</span>`;
                    }

                    html += `
                        <tr class="curriculum-row">
                            <td class="ps-4"><code class="text-primary fw-bold">${s.subject_code}</code></td>
                            <td>${s.subject_name}</td>
                            <td class="text-center">${s.units}</td>
                            <td class="text-center">${s.average_grade ? parseFloat(s.average_grade).toFixed(1) : '—'}</td>
                            <td class="text-center pe-4">${gradeCell}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
            }
        }
        container.innerHTML = html || '<div class="p-5 text-center text-muted">No curriculum data found.</div>';
    }

    // Lazy load History
    document.getElementById('tab-history').addEventListener('click', function() {
        if (historyLoaded) return;
        renderHistory();
        historyLoaded = true;
    });

    function renderHistory() {
        const loading = document.getElementById('history-loading');
        const table = document.getElementById('history-table');
        const tbody = table.querySelector('tbody');
        const empty = document.getElementById('history-empty');

        loading.classList.add('d-none');
        if (scholasticHistory.length === 0) {
            table.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }

        table.classList.remove('d-none');
        tbody.innerHTML = scholasticHistory.map(h => {
            const grade = h.grade ? parseFloat(h.grade) : null;
            const remarks = h.remarks || '';
            const statusClass = remarks === 'Passed' ? 'success' : (remarks === 'Failed' ? 'danger' : (remarks === 'Incomplete' ? 'warning text-dark' : 'secondary'));

            return `
                <tr class="history-row">
                    <td class="ps-4">
                        <span class="badge bg-secondary opacity-75">${h.school_year}</span><br>
                        <span class="small text-muted">${h.semester}</span>
                    </td>
                    <td>
                        <div class="fw-bold">${h.subject_code}</div>
                        <div class="small text-muted">${h.subject_name}</div>
                    </td>
                    <td class="text-center">${h.units}</td>
                    <td class="text-center pe-4">
                        <div class="badge bg-${statusClass}">${grade ? grade.toFixed(2) : '--'}</div>
                        <div class="text-muted" style="font-size: 0.65rem;">
                            Avg: ${h.average_grade ? parseFloat(h.average_grade).toFixed(1) : '—'} | 
                            ${[h.prelim, h.midterm, h.prefinal, h.finals].map(p => p !== null ? parseFloat(p).toFixed(0) : '—').join('/')}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Global Search Filter
    function setupFilter(inputId, rowClass) {
        document.getElementById(inputId).addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.' + rowClass).forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
    setupFilter('termSearch', 'grade-row');
    setupFilter('progressSearch', 'curriculum-row');
    setupFilter('historySearch', 'history-row');

    // PDF Export
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const year = termYearSelect.value;
        const sem = termSemSelect.value;

        if (!year || !sem) {
            alert('Please wait for the term data to load before exporting.');
            return;
        }

        const exportUrl = new URL('/Student-Portal/student/printables/academic-record', window.location.origin);
        exportUrl.searchParams.set('school_year', year);
        exportUrl.searchParams.set('semester', sem);
        exportUrl.searchParams.set('return_to', '/Student-Portal/student/dashboard?view=get_student_grades');

        window.location.href = exportUrl.toString();
    });

    document.getElementById('exportCurriculumPdfBtn').addEventListener('click', function() {
        const exportUrl = new URL('/Student-Portal/student/printables/curriculum-progress', window.location.origin);
        exportUrl.searchParams.set('return_to', '/Student-Portal/student/dashboard?view=get_student_grades');
        window.location.href = exportUrl.toString();
    });

    init();
})();
</script>
<style>
    .first\:mt-0:first-of-type { margin-top: 0 !important; }
</style>
