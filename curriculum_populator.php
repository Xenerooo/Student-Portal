<?php
require_once 'app/includes/db_connect.php';

$conn = connect();

/**
 * Helper function required for mysqli_stmt::bind_param using call_user_func_array in PHP versions >= 5.3.
 * It ensures all elements in the array are passed by reference.
 */
function refValues($arr) {
    if (strnatcmp(phpversion(), '5.3') >= 0) { // Reference is required for PHP 5.3+
        $refs = array();
        foreach ($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

// --- OPTIMIZED PHP Submission Handler (Using Multi-Insert) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['subject_id']) && is_array($_POST['subject_id'])) {
        
        $subjects_to_insert = count($_POST['subject_id']);
        if ($subjects_to_insert > 0) {

            // 1. Build the SQL Template for BULK INSERT
            // 5 columns per row: course_id, subject_id, year_level, semester, subject_name (iiiis)
            $value_placeholder = "(?, ?, ?, ?, ?)";
            $placeholders = implode(", ", array_fill(0, $subjects_to_insert, $value_placeholder));
            
            $sql = "INSERT INTO curriculum (course_id, subject_id, year_level, semester, subject_name)
                    VALUES {$placeholders}
                    ON DUPLICATE KEY UPDATE
                        year_level = VALUES(year_level),
                        semester = VALUES(semester),
                        subject_name = VALUES(subject_name);";

            $stmt = null; 
            try {
                $stmt = $conn->prepare($sql);
                
                // 2. Collect Data and Bind Types
                $types = str_repeat("iiiis", $subjects_to_insert); // e.g., "iiiisiiiis..."
                $params = array();
                
                // Collect all data into a flat array in the correct order for binding
                foreach ($_POST['subject_id'] as $key => $subject) {
                    if (isset($_POST['course_id'][$key], $_POST['year_level'][$key], $_POST['semester'][$key], $_POST['subject_name'][$key])) {
                        $params[] = (int)$_POST['course_id'][$key];
                        $params[] = (int)$subject; 
                        $params[] = (int)$_POST['year_level'][$key];
                        $params[] = (int)$_POST['semester'][$key];
                        $params[] = $_POST['subject_name'][$key];
                    }
                }
                
                // 3. Dynamically Bind Parameters
                array_unshift($params, $types);
                
                // call_user_func_array requires references (handled by refValues)
                call_user_func_array(array($stmt, 'bind_param'), refValues($params));

                // 4. Execute the single, massive insert (FAST!)
                $stmt->execute();
                
                echo "<div class='alert alert-success mt-3'>Curriculum for {$subjects_to_insert} entries saved successfully! (Bulk Insert)</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger mt-3'>Error saving curriculum: " . $e->getMessage() . "</div>";
            } finally {
                if ($stmt) {
                    $stmt->close();
                }
            }
        } else {
             echo "<div class='alert alert-warning mt-3'>No subjects submitted.</div>";
        }
    }
}

// Get courses and subjects for dropdowns
$courses = $conn->query("SELECT course_id, course_name FROM courses");
$subjects = $conn->query("SELECT subject_id, subject_code FROM subjects");

// Close the connection after queries
$conn->close();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Curriculum Management</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Curriculum Management</h2>
        
        <div class="card p-3 mb-4">
            <h4>Bulk Upload via JSON</h4>
            <div class="mb-3">
                <label for="jsonFile" class="form-label">Upload Curriculum JSON</label>
                <input type="file" class="form-control" id="jsonFile" accept=".json">
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="flex-fill">
                    <label for="bulkCourse" class="form-label">Course for Bulk Upload</label>
                    <select id="bulkCourse" class="form-control">
                        <option value="">Select Course</option>
                        <?php
                        if ($courses) {
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()):
                                ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo $course['course_name']; ?>
                                </option>
                            <?php endwhile; 
                        }?>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-primary" id="processJson">Process JSON and Populate Table</button>
        </div>
        
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-bordered" id="curriculumTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="course_id[]" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php 
                                    if ($courses) {
                                        $courses->data_seek(0);
                                        while ($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['course_id']; ?>">
                                                <?php echo $course['course_name']; ?>
                                            </option>
                                        <?php endwhile; 
                                    } ?>
                                </select>
                            </td>
                            <td>
                                <select name="subject_id[]" class="form-control" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    if ($subjects) {
                                        $subjects->data_seek(0);
                                        while ($subject = $subjects->fetch_assoc()): ?>
                                            <option value="<?php echo $subject['subject_id']; ?>">
                                                <?php echo $subject['subject_code']; ?>
                                            </option>
                                            <?php endwhile; 
                                    }?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="subject_name[]" class="form-control" required>
                            </td>
                            <td>
                                <select name="year_level[]" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td>
                                <select name="semester[]" class="form-control" required>
                                    <option value="">Select Semester</option>
                                    <option value="1">1st</option>
                                    <option value="2">2nd</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-info" id="addRow">Add Row</button>
            <button type="submit" class="btn btn-success">Save Curriculum</button>
        </form>
    </div>

    <script>
        // 1. Cache the template row and tbody on page load (DOM optimization)
        const templateRow = document.querySelector('#curriculumTable tbody tr:first-child').cloneNode(true);
        const tbody = document.querySelector('#curriculumTable tbody');
        
        // Helper to clear input values
        function clearRow(row) {
            row.querySelectorAll('select, input[type="text"]').forEach(el => el.value = '');
        }

        // --- Add Row Handler (Fast Cloning) ---
        document.getElementById('addRow').addEventListener('click', function () {
            var newRow = templateRow.cloneNode(true);
            clearRow(newRow); 
            tbody.appendChild(newRow);
        });

        // --- Remove Row Handler ---
        tbody.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                if (tbody.children.length > 1) {
                    e.target.closest('tr').remove();
                } else {
                    // If removing the last row, clear it instead of removing
                    clearRow(e.target.closest('tr'));
                }
            }
        });

        // --- Process JSON Handler (Performance optimized with Document Fragment) ---
        document.getElementById('processJson').addEventListener('click', function () {
            const fileInput = document.getElementById('jsonFile');
            const courseId = document.getElementById('bulkCourse').value;

            if (!fileInput.files[0] || !courseId) {
                alert('Please select a file and a course for bulk upload.');
                return;
            }
            
            // 2. Create a subject lookup map (Code -> ID)
            const subjectMap = {};
            const subjectOptions = templateRow.querySelector('select[name="subject_id[]"]').options;

            for (let i = 0; i < subjectOptions.length; i++) {
                if (subjectOptions[i].text !== 'Select Subject' && subjectOptions[i].value) {
                    subjectMap[subjectOptions[i].text.trim()] = subjectOptions[i].value;
                }
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                try {
                    const json = JSON.parse(e.target.result);

                    // 3. Clear the table before repopulating
                    tbody.innerHTML = ''; 
                    
                    // 4. Use Document Fragment for single DOM insertion (PERFORMANCE BOOST)
                    const fragment = document.createDocumentFragment(); 
                    let rowCounter = 0;

                    for (const year in json) {
                        for (const semester in json[year]) {
                            const semesterValue = semester.toString();
                            const yearValue = year.toString();
                            
                            if (Array.isArray(json[year][semester])) {
                                json[year][semester].forEach(subject => {
                                    const newRow = templateRow.cloneNode(true); 
                                    
                                    const subjectCode = subject.subject_code ? subject.subject_code.trim() : '';
                                    const subjectName = subject.subject_name || '';
                                    
                                    const subjectId = subjectMap[subjectCode] || '';
                                    
                                    // Set values on the cloned row
                                    newRow.querySelector('select[name="course_id[]"]').value = courseId;
                                    newRow.querySelector('select[name="subject_id[]"]').value = subjectId;
                                    newRow.querySelector('input[name="subject_name[]"]').value = subjectName;
                                    newRow.querySelector('select[name="year_level[]"]').value = yearValue;
                                    newRow.querySelector('select[name="semester[]"]').value = semesterValue;

                                    fragment.appendChild(newRow);
                                    rowCounter++;
                                });
                            }
                        }
                    }
                    
                    // 5. ATTACH THE FRAGMENT TO THE DOM IN ONE OPERATION
                    tbody.appendChild(fragment);

                    // If no subjects were processed, re-add one clean empty row
                    if (rowCounter === 0) {
                        const emptyRow = templateRow.cloneNode(true);
                        clearRow(emptyRow);
                        tbody.appendChild(emptyRow);
                    }

                } catch (error) {
                    console.error("JSON Processing Error:", error);
                    alert("Error processing JSON. Check console for details.");
                }
            };
            reader.readAsText(fileInput.files[0]);
        });
            
    </script>
</body>

</html>