<?php
// edit_grades.php - Page for updating a student's grades
// Data provided by AdminController: $student_id, $student_details, $grades_data, $current_school_year, $current_semester
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Editor - <?php echo htmlspecialchars($student_details['student_name']); ?></title>
    <link href="/Student-Portal/assets/css/bootstrap.min.css" rel="stylesheet"> 
    <script defer src="/Student-Portal/assets/js/bootstrap.bundle.js"></script>
    <style>
        .grade-card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 1rem;
        }
        .table-container {
            max-height: 600px;
            overflow-y: auto;
        }
        .subject-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .subject-row:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
        }
        .grade-badge {
            font-size: 1rem;
            padding: 0.5em 0.8em;
            border-radius: 0.5rem;
            min-width: 60px;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Grade Management</h1>
                <p class="text-muted">Recording grades for: <strong><?php echo htmlspecialchars($student_details['student_name']); ?></strong></p>
            </div>
            <a href="/Student-Portal/admin/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="container-fluid mb-4" id="saveFeedback"></div>

        <div class="card grade-card">
            <div class="card-body p-4">
                <div class="row g-3 mb-4 border-bottom pb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Academic Year Filter</label>
                        <select id="filter_school_year" name="school_year" class="form-select form-select-lg shadow-sm">
                            <?php
                            $years = ["2023-2024", "2024-2025", "2025-2026", "2026-2027"];
                            foreach ($years as $year) {
                                $selected = ($year === $current_school_year) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Semester Filter</label>
                        <select id="filter_semester" name="semester" class="form-select form-select-lg shadow-sm">
                            <?php
                            $sems = ["1st Semester", "2nd Semester", "Summer"];
                            foreach ($sems as $sem) {
                                $selected = ($sem === $current_semester) ? 'selected' : '';
                                echo "<option value=\"$sem\" $selected>$sem</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="input-group">
                        <input type="text" id="searchGradeEdit" class="form-control form-control-lg" placeholder="Search subjects..." autocomplete="off">
                    </div>
                </div>

                <div class="table-container rounded border">
                    <table class="table table-hover align-middle mb-0" id="table-subject-list">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Code</th>
                                <th>Subject Name</th>
                                <th class="text-center">Units</th>
                                <th class="text-end pe-4">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($grades_data)): ?>
                                <?php foreach ($grades_data as $subject): ?>
                                    <tr class="subject-row" 
                                        data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>"
                                        data-subject-code="<?= htmlspecialchars($subject['subject_code']) ?>"
                                        data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>"
                                        data-current-grade="<?= htmlspecialchars($subject['grade'] ?? '') ?>">
                                        <td class="ps-4">
                                            <code class="text-primary fw-bold"><?= htmlspecialchars($subject['subject_code']) ?></code>
                                        </td>
                                        <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($subject['units'] ?? '3.0') ?></td>
                                        <td class="text-end pe-4">
                                            <?php if ($subject['grade']): ?>
                                                <span class="grade-badge text-primary fw-bold">
                                                    <?= number_format($subject['grade'], 1) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="grade-badge bg-light text-muted">
                                                    --
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No subjects found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Grade Modal -->
    <div class="modal fade" id="editSubjectGradeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title">Subject Grade Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Left side: Form -->
                        <div class="col-md-5">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Recording for current term</h6>
                            <form id="modalGradeForm">
                                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                                <input type="hidden" name="subject_id" id="modal_subject_id">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Academic Year</label>
                                    <select id="modal_school_year" name="school_year" class="form-select">
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>"><?= $year ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Semester</label>
                                    <select id="modal_semester" name="semester" class="form-select">
                                        <?php foreach ($sems as $sem): ?>
                                            <option value="<?= $sem ?>"><?= $sem ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Final Grade (1.0 - 5.0)</label>
                                    <input type="number" step="0.1" name="grade" id="modal_grade_input" 
                                           class="form-control form-control-lg border-primary text-center fw-bold" 
                                           placeholder="0.0" min="1.0" max="5.0">
                                    <div class="form-text text-muted">Use 5.0 for Failure. Leave empty to remove entry.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-3 shadow">
                                    Update Grade
                                </button>
                            </form>
                        </div>
                        
                        <!-- Right side: History -->
                        <div class="col-md-7 border-start ps-md-4">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Scholastic History (This Subject)</h6>
                            <div id="subjectHistoryContainer" class="table-responsive" style="max-height: 350px;">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const studentId = <?= json_encode($student_id, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const yearFilter = document.getElementById('filter_school_year');
        const semFilter = document.getElementById('filter_semester');
        const searchInput = document.getElementById('searchGradeEdit');
        const subjectRows = document.querySelectorAll('.subject-row');
        
        // Modal elements
        const gradeModal = new bootstrap.Modal(document.getElementById('editSubjectGradeModal'));
        const modalForm = document.getElementById('modalGradeForm');
        const historyContainer = document.getElementById('subjectHistoryContainer');
        const modalSubjId = document.getElementById('modal_subject_id');
        const modalYear = document.getElementById('modal_school_year');
        const modalSem = document.getElementById('modal_semester');
        const modalGradeInput = document.getElementById('modal_grade_input');

        // Filter Handlers
        [yearFilter, semFilter].forEach(el => {
            el.addEventListener('change', () => {
                if (window.loadGradeEditor) {
                    window.loadGradeEditor(studentId, yearFilter.value, semFilter.value);
                }
            });
        });

        // Search Filter
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            subjectRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });

        // Row Click Handler
        subjectRows.forEach(row => {
            row.addEventListener('click', function() {
                const subjId = this.dataset.subjectId;
                const subjCode = this.dataset.subjectCode;
                const subjName = this.dataset.subjectName;
                const currentGrade = this.dataset.currentGrade;

                // Setup modal form
                modalSubjId.value = subjId;
                modalYear.value = yearFilter.value;
                modalSem.value = semFilter.value;
                modalGradeInput.value = currentGrade;
                
                document.querySelector('#editSubjectGradeModal .modal-title').textContent = `${subjCode}: ${subjName}`;
                
                // Show modal
                gradeModal.show();
                
                // Fetch History
                fetchSubjectHistory(subjId);
            });
        });

        function fetchSubjectHistory(subjId) {
            historyContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
            
            fetch(`/Student-Portal/admin/api/subject/history?student_id=${studentId}&subject_id=${subjId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        let html = '<table class="table table-sm table-striped small"><thead><tr class="table-light"><th>Term</th><th class="text-center">Grade</th></tr></thead><tbody>';
                        data.history.forEach(h => {
                            html += `<tr>
                                        <td>${h.school_year} ${h.semester}</td>
                                        <td class="text-center fw-bold">${h.grade}</td>
                                     </tr>`;
                        });
                        html += '</tbody></table>';
                        historyContainer.innerHTML = html;
                    } else {
                        historyContainer.innerHTML = '<div class="alert alert-light text-center py-4">No previous history found.</div>';
                    }
                })
                .catch(err => {
                    historyContainer.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
                });
        }

        // Modal Form Submission
        modalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            const formData = new FormData(modalForm);
            
            // Note: The backend expects 'grades[subject_id]' format for the grade value
            const gradeVal = modalGradeInput.value;
            const subjectId = modalSubjId.value;
            formData.append(`grades[${subjectId}]`, gradeVal);

            fetch('/Student-Portal/admin/api/grades/save', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    gradeModal.hide();
                    // Reload the editor to show updated data
                    if (window.loadGradeEditor) {
                        window.loadGradeEditor(studentId, yearFilter.value, semFilter.value);
                    }
                } else {
                    alert('Error: ' + (json.message || 'Failed to update grade.'));
                }
            })
            .catch(err => {
                alert('Network error: ' + err.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    })();
    </script>
</body>
</html>
