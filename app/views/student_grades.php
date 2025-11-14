<?php
// app/views/student_grades.php
// This file is loaded via AJAX by student_ajax_handler.php.
// The session is already started, and authorization is complete in student_ajax_handler.php.

require_once 'includes/db_connect.php';
require_once 'includes/utilities.php';
$conn = connect();

$student_id = $_SESSION['student_id'];

// Get student's course_id
$stmt = $conn->prepare("CALL getStudentDetailsByStudentId(?);");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$course_id = $student['course_id'];
$stmt->close();

// Fetch subjects for that course from curriculum with grades
$stmt = $conn->prepare("CALL getSubjectsByStudentId(?);");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Group results by year & semester
$curriculum_data = [];
while ($row = $result->fetch_assoc()) {
    $curriculum_data[$row['year_level']][$row['semester']][] = $row;
}

$conn->close();
// print_r($curriculum_data);

function getRemarks($r) {
    if (empty( $r )) {
        return ["remarks" => "", "color" => "danger"];
    }

    if ($r <= 3.0) {
        return ["remarks" => "Passed", "color" => "success"];
    } else if ($r <= 4.0) {
        return ["remarks" => "Incomplete", "color" => "warning"];
    } else if ($r > 4.0){
        return ["remarks" => "Failed", "color" => "danger"];;
    } else {
        return ["remarks" => "", "color" => "danger"];
    }
}

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
    
    <?php if (empty($curriculum_data)): ?>
        <div class="alert alert-info">No curriculum data available for your course.</div>

        
    <?php else: ?>
        <div id="gradesPdfArea">
        <?php foreach ($curriculum_data as $year => $semesters): ?>
            <h3 class="mt-4"><?= getOrdinal($year) ?> Year</h3>
            <div class="row">
                <?php foreach ($semesters as $sem => $subjects): ?>
                    <div class="col-md-6 mb-4">
                        <h5><?= getOrdinal($sem) ?> Semester</h5>
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
                                <tbody >
                                    <?php foreach ($subjects as $sub): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sub['subject_code']) ?></td>
                                            <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                                            <td><?= $sub['units'] ?></td>
                                            <td><?= $sub['grade'] !== null ? $sub['grade'] : "—" ?></td>
                                            <td class="text-<?= getRemarks($sub['grade'])["color"] ?>""><?= getRemarks($sub['grade'])["remarks"] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
<script>
    (function(){
        const searchGradeInput = document.getElementById('searchGrade');

        const allTdElements = document.querySelectorAll('.grade-list-table td');
        // console.log('All td elements:', allTdElements);
        
        // OPTION 2: Get each table individually, then get td from each
        const gradeListTables = document.querySelectorAll('.grade-list-table');
        // console.log('Number of tables:', gradeListTables.length);
        
        // Loop through each table and get its td elements
        gradeListTables.forEach((table, index) => {
            // Get all td elements from this specific table
            const tdElements = table.querySelectorAll('td');
            // OR get only td from tbody (skips header)
            const tbodyTdElements = table.querySelectorAll('tbody td');
            // console.log(`Table ${index + 1} has ${tdElements.length} td elements`);
        });
        
        // OPTION 3: Get td from a specific table (if you know which one)
        // const firstTable = gradeListTables[0];
        // const firstTableTds = firstTable.querySelectorAll('td');
        // console.log(searchGradeInput);

        searchGradeInput.addEventListener('input', function(){
            displaySearch(this.value);
        });

        function displaySearch(input){
            const searchInput = input.toLowerCase().trim();
            // Get all table rows (tr) from all tables
            const allRows = document.querySelectorAll('.grade-list-table tbody tr');
            
            allRows.forEach(row => {
                // Get all td elements from this specific row
                const tds = row.querySelectorAll('td');
                
                // Check if any td in this row matches the search
                let rowText = '';
                tds.forEach(td => {
                    rowText += td.textContent.toLowerCase() + ' ';
                });
                
                // Show/hide row based on search match
                if (searchInput === '' || rowText.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };

        // function ensureHtml2PdfLoaded(callback){
        //     if (typeof window.html2pdf === 'function') { callback(); return; }
        //     const existing = document.querySelector('script[data-lib="html2pdf"]');
        //     if (existing) { existing.addEventListener('load', callback); return; }
        //     const s = document.createElement('script');
        //     s.src = 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js';
        //     s.async = true;
        //     s.setAttribute('data-lib', 'html2pdf');
        //     s.onload = callback;
        //     document.head.appendChild(s);
        // }

        // const exportBtn = document.getElementById('btnExportGradesPdf');
        // if (exportBtn) {
        //     exportBtn.addEventListener('click', function(){
        //         const runExport = function(){
        //             const content = document.getElementById('gradesPdfArea') || document.querySelector('.container-fluid');
        //             if (!content || typeof window.html2pdf !== 'function') return;

        //             const opt = {
        //                 margin:       0.35,
        //                 filename:     'My_Grades.pdf',
        //                 image:        { type: 'jpeg', quality: 0.98 },
        //                 html2canvas:  { scale: 2, useCORS: true },
        //                 jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
        //                 pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        //             };

        //             const tables = content.querySelectorAll('table');
        //             tables.forEach(t => t.classList.add('table-sm'));

        //             window.html2pdf().set(opt).from(content).save();
        //         };

        //         ensureHtml2PdfLoaded(runExport);
        //     });
        // }
    })();
</script>