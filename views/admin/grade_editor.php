<?php
// edit_grades.php - Page for updating a student's grades
// Data provided by AdminController: $student_id, $student_details, $grades_data, $current_semester_id, $current_school_year_id
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grades for <?php echo htmlspecialchars($student_details['student_name']); ?></title>
    <link href="/Student-Portal/assets/css/bootstrap.min.css" rel="stylesheet"> 
    <script defer src="/Student-Portal/assets/js/bootstrap.bundle.js"></script>

</head>
<body>
    <?php // You should include your admin_navbar here if it's a separate file ?>
    <?php // include 'includes/admin_navbar.php'; ?> 

    <div class="container mt-5">
        <h1 class="mb-3">Edit Grades</h1>
        <h3 class="mb-4 text-primary">
            Student: <?php echo htmlspecialchars($student_details['student_name']); ?> 
            <small class="text-muted">(<?php echo htmlspecialchars($student_details['student_number']); ?>)</small>
        </h3>
        



        <div class="container-fluid" id="saveFeedback"></div>

        <div class="mb-3">
            <label for="searchGradeEdit" class="form-label">Search Subject:</label>
            <input type="text" id="searchGradeEdit" class="form-control" placeholder="Search by Subject code or Subject name" autocomplete="off">
        </div>
        <div class="container-fluid mt-4 fade-in">
    <div class="row w-100 max-w-4xl mx-auto">
        <form method="POST" action="/Student-Portal/admin/api/grades/save" class="shadow p-4 rounded bg-light">
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
            <input type="hidden" name="semester_id" value="<?php echo $current_semester_id; ?>">
            <input type="hidden" name="school_year_id" value="<?php echo $current_school_year_id; ?>">
            
            <!-- <p class="lead text-info">
                Editing Grades for **Semester ID <?php echo $current_semester_id; ?>** and **School Year ID <?php echo $current_school_year_id; ?>**.
            </p> -->

            <div class="container-fluid table-responsive px-0">
                <table class="table table-striped table-sm align-middle" id="table-subject-list">
                    <thead class="table-dark">
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Grade Input (decimal)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($grades_data)): ?>
                            <?php foreach ($grades_data as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td>
                                        <input type="text" 
                                               name="grades[<?php echo $subject['subject_id']; ?>]" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($subject['grade'] ?? ''); ?>" 
                                               placeholder="e.g., 1.0"
                                               maxlength="5"
                                               pattern="[0-9]{1,3}\.?[0-9]{0,2}"
                                               style="width: 150px;"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4">No subjects found for this student's current course.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="btn btn-success mt-3">Save All Grades</button>
            <a href="/Student-Portal/" class="btn btn-secondary mt-3">Back to Student List</a>
        </form>
    </div>
</div>
</body>
<script>
(function() {
    // Form submission via AJAX
    const form = document.querySelector("form[action^='/Student-Portal/admin/api/grades/save']");
    if (!form) return;

    const feedback = document.getElementById('saveFeedback');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const prevText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; }
        if (feedback) feedback.innerHTML = '';

        const processUrl = form.getAttribute('action');
        const data = new FormData(form);

        fetch(processUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
        .then(res => { if (!res.ok) throw new Error('Status: ' + res.status); return res.json(); })
        .then(json => {
            const cls = json.success ? 'alert-success' : 'alert-danger';
            const msg = (json.success ? 'Grades saved successfully! now redirecting to student list...' : 'Failed to save grades.');

            if (feedback) feedback.innerHTML = `
                <div class="alert ${cls} d-flex align-items-center">
                    <div class="flex-grow-1 me-3">${msg}</div>
                    <div class="spinner-border ms-auto" role="status" aria-hidden="true"></div>
                </div>
                `;
            if (json.success) {
                window.scrollTo(0, 0);
                setTimeout(() => {
                    window.location.href = '/Student-Portal/';
                }, 3000);
            }
        })
        .catch(err => {
            if (feedback) feedback.innerHTML = `<div class='alert alert-danger'>Error saving grades: ${err.message}</div>`;
            console.error('AJAX Save Error:', err);
        })
        .finally(() => {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = prevText; }
            
        });
    });
    
    const searchSubjectInput = document.getElementById('searchGradeEdit');
    const studentTable = document.getElementById('table-subject-list');

    const tableBody = studentTable ? studentTable.querySelector('tbody') : null;

    searchSubjectInput.addEventListener('input', function(){ searchSubject(this.value)});
    
    function searchSubject(value){

        const searchTerm = value.toLowerCase().trim();
        
        const rows = tableBody.querySelectorAll("tr");

        rows.forEach(row => {
            let rowText = ''
            var cells = row.querySelectorAll('td')

            cells.forEach(cell => {
                rowText += cell.textContent.toLowerCase() + ' ';
            });

            if (searchTerm === '' || rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
        });
    };

})();
</script>
</html>