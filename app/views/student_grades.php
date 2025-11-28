<?php
// app/views/student_grades.php
// This file is loaded via AJAX by student_ajax_handler.php.
// The session is already started, and authorization is complete in student_ajax_handler.php.
// This view now fetches grades data via AJAX from process_get_student_grade.php
?>

<div class="container-fluid mt-4 mb-4 px-0">
    <h2 class="mb-4">My Grades</h2>
    <div class="mb-3">
        <label for="searchGrade" class="form-label">Search Subjects:</label>
        <input type="text" id="searchGrade" class="form-control" placeholder="Search by Subject name or subject code..." autocomplete="off">
    </div>
    <div class="mb-3">
        <button type="button" id="btnExportGradesPdf" class="btn btn-primary btn-sm">Download PDF</button>
    </div>
    
    <div id="gradesLoading" class="d-flex justify-content-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    <div id="gradesError" class="alert alert-danger" style="display: none;"></div>
    
    <div id="gradesContent" style="display: none;">
        <div id="gradesPdfArea"></div>
    </div>

    <!-- Hidden Templates -->
    <template id="templateYearSection">
        <div class="year-section">
            <h3 class="mt-4 year-title"></h3>
            <div class="row year-semesters"></div>
        </div>
    </template>

    <template id="templateSemesterSection">
        <div class="col-md-6 mb-4 semester-section">
            <h5 class="semester-title"></h5>
                        <div class="table-responsive grade-list-table">
                            <table class="table table-bordered table-striped table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Code</th>
                                        <th>Subject</th>
                                        <th>Units</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                    <tbody class="semester-subjects"></tbody>
                            </table>
            </div>
        </div>
    </template>

    <template id="templateSubjectRow">
        <tr class="subject-row">
            <td class="subject-code"></td>
            <td class="subject-name"></td>
            <td class="subject-units"></td>
            <td class="subject-grade"></td>
            <td class="subject-remarks"></td>
        </tr>
    </template>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
<script>
    (function(){
        // Helper function to get ordinal suffix
        function getOrdinal(number) {
            if (!isNumeric(number)) {
                return number;
            }
            const num = parseInt(number);
            if ([11, 12, 13].includes(num % 100)) {
                return num + 'th';
            }
            switch (num % 10) {
                case 1: return num + 'st';
                case 2: return num + 'nd';
                case 3: return num + 'rd';
                default: return num + 'th';
            }
        }

        function isNumeric(n) {
            return !isNaN(parseFloat(n)) && isFinite(n);
        }

        // Helper function to get remarks
        function getRemarks(grade) {
            if (grade === null || grade === undefined || grade === '') {
                return { remarks: '', color: 'danger' };
            }
            const g = parseFloat(grade);
            if (isNaN(g)) {
                return { remarks: '', color: 'danger' };
            }
            if (g <= 3.0) {
                return { remarks: 'Passed', color: 'success' };
            } else if (g <= 4.0) {
                return { remarks: 'Incomplete', color: 'warning' };
            } else if (g > 4.0) {
                return { remarks: 'Failed', color: 'danger' };
            }
            return { remarks: '', color: 'danger' };
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Render grades using templates
        function renderGrades(curriculumData) {
            const pdfArea = document.getElementById('gradesPdfArea');
            if (!pdfArea) return;

            const yearTemplate = document.getElementById('templateYearSection');
            const semesterTemplate = document.getElementById('templateSemesterSection');
            const subjectRowTemplate = document.getElementById('templateSubjectRow');

            if (!yearTemplate || !semesterTemplate || !subjectRowTemplate) {
                console.error('Templates not found');
                return;
            }

            pdfArea.innerHTML = ''; // Clear existing content

            // Sort years numerically
            const sortedYears = Object.keys(curriculumData).sort((a, b) => parseInt(a) - parseInt(b));

            for (const year of sortedYears) {
                const semesters = curriculumData[year];
                
                // Clone year section template
                const yearSection = yearTemplate.content.cloneNode(true);
                const yearTitle = yearSection.querySelector('.year-title');
                const yearSemesters = yearSection.querySelector('.year-semesters');
                
                if (yearTitle) {
                    yearTitle.textContent = getOrdinal(year) + ' Year';
                }

                // Sort semesters numerically
                const sortedSemesters = Object.keys(semesters).sort((a, b) => parseInt(a) - parseInt(b));

                for (const sem of sortedSemesters) {
                    const subjects = semesters[sem];
                    
                    // Clone semester section template
                    const semesterSection = semesterTemplate.content.cloneNode(true);
                    const semesterTitle = semesterSection.querySelector('.semester-title');
                    const semesterSubjects = semesterSection.querySelector('.semester-subjects');
                    
                    if (semesterTitle) {
                        semesterTitle.textContent = getOrdinal(sem) + ' Semester';
                    }

                    // Add subject rows
                    subjects.forEach(sub => {
                        const subjectRow = subjectRowTemplate.content.cloneNode(true);
                        const codeCell = subjectRow.querySelector('.subject-code');
                        const nameCell = subjectRow.querySelector('.subject-name');
                        const unitsCell = subjectRow.querySelector('.subject-units');
                        const gradeCell = subjectRow.querySelector('.subject-grade');
                        const remarksCell = subjectRow.querySelector('.subject-remarks');
                        
                        if (codeCell) codeCell.textContent = sub.subject_code || '';
                        if (nameCell) nameCell.textContent = sub.subject_name || '';
                        if (unitsCell) unitsCell.textContent = sub.units || '';
                        if (gradeCell) {
                            gradeCell.textContent = sub.grade !== null ? sub.grade : '—';
                        }
                        
                        if (remarksCell) {
                            const remarks = getRemarks(sub.grade);
                            remarksCell.textContent = remarks.remarks;
                            remarksCell.className = 'subject-remarks text-' + remarks.color;
                        }
                        
                        if (semesterSubjects) {
                            semesterSubjects.appendChild(subjectRow);
                        }
                    });

                    if (yearSemesters) {
                        yearSemesters.appendChild(semesterSection);
                    }
                }

                pdfArea.appendChild(yearSection);
            }
        }

        // Fetch grades data
        function fetchGrades() {
            const loadingEl = document.getElementById('gradesLoading');
            const errorEl = document.getElementById('gradesError');
            const contentEl = document.getElementById('gradesContent');
            const pdfArea = document.getElementById('gradesPdfArea');

            if (!loadingEl || !errorEl || !contentEl || !pdfArea) {
                console.error('Required elements not found');
                return;
            }

            fetch('app/process_get_student_grade.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loader first - remove d-flex and add d-none, plus inline style
                    if (loadingEl) {
                        loadingEl.classList.remove('d-flex');
                        loadingEl.classList.add('d-none');
                        loadingEl.style.display = 'none';
                    }
                    
                    if (!data.success) {
                        if (errorEl) {
                            errorEl.textContent = data.message || 'Failed to load grades.';
                            errorEl.style.display = 'block';
                        }
                        return;
                    }

                    const curriculumData = data.data || {};
                    
                    if (Object.keys(curriculumData).length === 0) {
                        if (pdfArea) {
                            pdfArea.innerHTML = '<div class="alert alert-info">No curriculum data available for your course.</div>';
                        }
                        if (contentEl) {
                            contentEl.style.display = 'block';
                        }
                        return;
                    }

                    // Render the grades using templates
                    renderGrades(curriculumData);
                    
                    if (contentEl) {
                        contentEl.style.display = 'block';
                    }
                    
                    // Setup search functionality after content is loaded
                    setupSearch();
                })
                .catch(error => {
                    if (loadingEl) {
                        loadingEl.classList.remove('d-flex');
                        loadingEl.classList.add('d-none');
                        loadingEl.style.display = 'none';
                    }
                    if (errorEl) {
                        errorEl.textContent = 'Error loading grades: ' + error.message;
                        errorEl.style.display = 'block';
                    }
                    console.error('Error fetching grades:', error);
                });
        }

        // Setup search functionality
        function setupSearch() {
            const searchGradeInput = document.getElementById('searchGrade');
            if (!searchGradeInput) return;

        searchGradeInput.addEventListener('input', function(){
            displaySearch(this.value);
        });
        }

        function displaySearch(input){
            const searchInput = input.toLowerCase().trim();
            const allRows = document.querySelectorAll('.grade-list-table tbody tr');
            
            allRows.forEach(row => {
                const tds = row.querySelectorAll('td');
                let rowText = '';
                tds.forEach(td => {
                    rowText += td.textContent.toLowerCase() + ' ';
                });
                
                if (searchInput === '' || rowText.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Load grades on page load
        // Use a small delay to ensure DOM is ready when loaded via AJAX
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fetchGrades);
        } else {
            // DOM is already ready
            setTimeout(fetchGrades, 0);
        }
    })();
</script>