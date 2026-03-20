<?php
// student_grades.php - View for student to see their own grades
?>

<div class="container-fluid mt-4 mb-4 px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Academic Records</h2>
        <button type="button" id="btnExportGradesPdf" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Export as PDF
        </button>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="gradeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="progress-tab" data-bs-toggle="tab" data-bs-target="#progress-pane" type="button" role="tab">
                Curriculum Progress
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
                Scholastic History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="gradeTabsContent">
        <!-- Progress Pane -->
        <div class="tab-pane fade show active" id="progress-pane" role="tabpanel" tabindex="0">
            <div class="mb-3">
                <input type="text" id="searchProgress" class="form-control" placeholder="Search subjects in curriculum..." autocomplete="off">
            </div>
            
            <div id="progressLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading curriculum...</div>
            </div>
            
            <div id="progressError" class="alert alert-danger" style="display: none;"></div>
            <div id="progressContent" style="display: none;">
                <!-- PDF Area uses this container -->
                <div id="gradesPdfArea"></div>
            </div>
        </div>

        <!-- History Pane -->
        <div class="tab-pane fade" id="history-pane" role="tabpanel" tabindex="0">
            <div class="mb-3">
                <input type="text" id="searchHistory" class="form-control" placeholder="Search entire history..." autocomplete="off">
            </div>

            <div id="historyLoading" class="text-center py-5">
                <div class="spinner-border text-secondary" role="status"></div>
                <div class="mt-2 text-muted">Loading history...</div>
            </div>

            <div id="historyError" class="alert alert-danger" style="display: none;"></div>
            <div id="historyContent" style="display: none;">
                <div class="table-responsive shadow-sm rounded">
                    <table class="table table-hover align-middle mb-0" id="table-history">
                        <thead class="table-dark">
                            <tr>
                                <th>Term</th>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Units</th>
                                <th class="text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody id="history-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Templates for Progress -->
    <template id="templateYearSection">
        <div class="year-section mb-5">
            <h4 class="border-bottom pb-2 text-primary year-title"></h4>
            <div class="row year-semesters"></div>
        </div>
    </template>

    <template id="templateSemesterSection">
        <div class="col-lg-6 mb-4 semester-section">
            <h6 class="text-muted fw-bold semester-title"></h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Subject</th>
                            <th class="text-center">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="semester-subjects"></tbody>
                </table>
            </div>
        </div>
    </template>

    <template id="templateSubjectRow">
        <tr>
            <td class="subject-code fw-bold small"></td>
            <td class="subject-name small"></td>
            <td class="text-center subject-grade fw-bold"></td>
        </tr>
    </template>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
<script>
(function(){
    const getOrdinal = (n) => {
        const s = ["th", "st", "nd", "rd"], v = n % 100;
        return n + (s[(v - 20) % 10] || s[v] || s[0]);
    };

    const getRemarksColor = (grade) => {
        if (!grade || isNaN(grade)) return 'text-muted';
        const g = parseFloat(grade);
        if (g <= 3.0) return 'text-success';
        if (g <= 4.0) return 'text-warning';
        return 'text-danger';
    };

    function renderProgress(data) {
        const container = document.getElementById('gradesPdfArea');
        const yearTempl = document.getElementById('templateYearSection');
        const semTempl = document.getElementById('templateSemesterSection');
        const rowTempl = document.getElementById('templateSubjectRow');
        
        container.innerHTML = '';
        
        Object.keys(data).sort().forEach(year => {
            const yearNode = yearTempl.content.cloneNode(true);
            yearNode.querySelector('.year-title').textContent = getOrdinal(year) + ' Year';
            const semestersContainer = yearNode.querySelector('.year-semesters');
            
            Object.keys(data[year]).sort().forEach(sem => {
                const semNode = semTempl.content.cloneNode(true);
                semNode.querySelector('.semester-title').textContent = getOrdinal(sem) + ' Semester';
                const tbody = semNode.querySelector('.semester-subjects');
                
                data[year][sem].forEach(sub => {
                    const row = rowTempl.content.cloneNode(true);
                    row.querySelector('.subject-code').textContent = sub.subject_code;
                    row.querySelector('.subject-name').textContent = sub.subject_name;
                    const gradeEl = row.querySelector('.subject-grade');
                    const gradeVal = sub.grade ? parseFloat(sub.grade).toFixed(2) : '--';
                    const breakdown = (sub.prelim || sub.midterm || sub.prefinal || sub.finals) 
                        ? `<div class="text-muted small fw-normal" style="font-size: 0.65rem">
                             ${sub.prelim || '-'}/${sub.midterm || '-'}/${sub.prefinal || '-'}/${sub.finals || '-'}
                           </div>` 
                        : '';
                    
                    gradeEl.innerHTML = `<div>${gradeVal}</div>${breakdown}`;
                    gradeEl.className += ' ' + getRemarksColor(sub.grade);
                    tbody.appendChild(row);
                });
                semestersContainer.appendChild(semNode);
            });
            container.appendChild(yearNode);
        });
    }

    function renderHistory(data) {
        const tbody = document.getElementById('history-list');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No records found.</td></tr>';
            return;
        }

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="small">
                    <div class="fw-bold">${row.school_year}</div>
                    <div class="text-muted" style="font-size: 0.75rem">${row.semester}</div>
                </td>
                <td class="fw-bold small">${row.subject_code}</td>
                <td class="small">${row.subject_name || 'N/A'}</td>
                <td class="text-center">${row.units}</td>
                <td class="text-center fw-bold ${getRemarksColor(row.grade)}">
                    <div>${row.grade ? parseFloat(row.grade).toFixed(2) : '--'}</div>
                    <div class="text-muted small fw-normal" style="font-size: 0.65rem">
                        ${row.prelim || '-'}/${row.midterm || '-'}/${row.prefinal || '-'}/${row.finals || '-'}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function loadData(url, loadingId, contentId, errorId, renderFn) {
        const loading = document.getElementById(loadingId);
        const content = document.getElementById(contentId);
        const error = document.getElementById(errorId);
        
        try {
            const res = await fetch(url);
            const json = await res.json();
            
            loading.style.display = 'none';
            if (json.success) {
                renderFn(json.data);
                content.style.display = 'block';
            } else {
                error.textContent = json.message;
                error.style.display = 'block';
            }
        } catch (e) {
            loading.style.display = 'none';
            error.textContent = "Failed to fetch data: " + e.message;
            error.style.display = 'block';
        }
    }

    // Initialize
    loadData('/Student-Portal/student/api/grades/progress', 'progressLoading', 'progressContent', 'progressError', renderProgress);
    loadData('/Student-Portal/student/api/grades/history', 'historyLoading', 'historyContent', 'historyError', renderHistory);

    // Search filters
    const setupSearch = (inputId, tableId) => {
        document.getElementById(inputId).addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll(`${tableId} tbody tr`);
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    };
    setupSearch('searchProgress', '#gradesPdfArea');
    setupSearch('searchHistory', '#table-history');

    // PDF Export
    document.getElementById('btnExportGradesPdf').addEventListener('click', () => {
        const element = document.getElementById('gradesPdfArea');
        const opt = {
            margin: 10,
            filename: 'My_Grades.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });

})();
</script>
