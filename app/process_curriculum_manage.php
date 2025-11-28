<?php
session_start();

header('Content-Type: application/json');

// Authorization Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admin login required.']);
    exit();
}

require_once "includes/db_connect.php";
$conn = connect();

$action = $_POST['action'] ?? 'add';

function refValues($arr) {
    if (strnatcmp(phpversion(), '5.3') >= 0) {
        $refs = array();
        foreach ($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

try {
    if ($action === 'add') {
        // Handle Add Curriculum Entry
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $subject_name = trim($_POST['subject_name'] ?? '');
        $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
        $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);

        if (!$course_id) {
            throw new Exception('Course is required.');
        }
        if (!$subject_id) {
            throw new Exception('Subject is required.');
        }
        if (empty($subject_name)) {
            throw new Exception('Subject name is required.');
        }
        if (!$year_level || $year_level < 1 || $year_level > 4) {
            throw new Exception('Year level must be between 1 and 4.');
        }
        if (!$semester || ($semester != 1 && $semester != 2)) {
            throw new Exception('Semester must be 1 or 2.');
        }

        // Insert new curriculum entry
        $stmt = $conn->prepare("INSERT INTO curriculum (course_id, subject_id, year_level, semester, subject_name) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement.');
        }
        $stmt->bind_param('iiiss', $course_id, $subject_id, $year_level, $semester, $subject_name);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new Exception('Failed to add curriculum entry.');
        }

        $curriculum_id = $conn->insert_id;
        $stmt->close();

        // Fetch the created entry with joined data
        $fetchStmt = $conn->prepare("
            SELECT c.curriculum_id, c.course_id, c.subject_id, c.year_level, c.semester, c.subject_name,
                   co.course_name, s.subject_code
            FROM curriculum c
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.curriculum_id = ?
        ");
        $fetchStmt->bind_param('i', $curriculum_id);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();
        $entry = $result->fetch_assoc();
        $fetchStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Curriculum entry added successfully!',
            'data' => $entry
        ]);

    } elseif ($action === 'update') {
        // Handle Update Curriculum Entry
        $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $subject_name = trim($_POST['subject_name'] ?? '');
        $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
        $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);

        if (!$curriculum_id) {
            throw new Exception('Curriculum ID is required.');
        }
        if (!$course_id) {
            throw new Exception('Course is required.');
        }
        if (!$subject_id) {
            throw new Exception('Subject is required.');
        }
        if (empty($subject_name)) {
            throw new Exception('Subject name is required.');
        }
        if (!$year_level || $year_level < 1 || $year_level > 4) {
            throw new Exception('Year level must be between 1 and 4.');
        }
        if (!$semester || ($semester != 1 && $semester != 2)) {
            throw new Exception('Semester must be 1 or 2.');
        }

        // Check if curriculum entry exists
        $checkStmt = $conn->prepare("SELECT curriculum_id FROM curriculum WHERE curriculum_id = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('i', $curriculum_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if (!$result || $result->num_rows === 0) {
            $checkStmt->close();
            throw new Exception('Curriculum entry not found.');
        }
        $checkStmt->close();

        // Update curriculum entry
        $stmt = $conn->prepare("UPDATE curriculum SET course_id = ?, subject_id = ?, year_level = ?, semester = ?, subject_name = ? WHERE curriculum_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement.');
        }
        $stmt->bind_param('iiissi', $course_id, $subject_id, $year_level, $semester, $subject_name, $curriculum_id);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new Exception('Failed to update curriculum entry.');
        }

        $stmt->close();

        // Fetch the updated entry with joined data
        $fetchStmt = $conn->prepare("
            SELECT c.curriculum_id, c.course_id, c.subject_id, c.year_level, c.semester, c.subject_name,
                   co.course_name, s.subject_code
            FROM curriculum c
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.curriculum_id = ?
        ");
        $fetchStmt->bind_param('i', $curriculum_id);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();
        $entry = $result->fetch_assoc();
        $fetchStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Curriculum entry updated successfully!',
            'data' => $entry
        ]);

    } elseif ($action === 'delete') {
        // Handle Delete Curriculum Entry
        $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);

        if (!$curriculum_id) {
            throw new Exception('Invalid curriculum ID.');
        }

        // Check if curriculum entry exists
        $checkStmt = $conn->prepare("SELECT curriculum_id FROM curriculum WHERE curriculum_id = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('i', $curriculum_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if (!$result || $result->num_rows === 0) {
            $checkStmt->close();
            throw new Exception('Curriculum entry not found.');
        }
        $checkStmt->close();

        // Delete curriculum entry
        $stmt = $conn->prepare("DELETE FROM curriculum WHERE curriculum_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare delete statement.');
        }
        $stmt->bind_param('i', $curriculum_id);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new Exception('Failed to delete curriculum entry.');
        }

        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Curriculum entry deleted successfully!'
        ]);

    } elseif ($action === 'bulk_save') {
        // Handle Bulk Save (from JSON upload or form submission)
        if (!isset($_POST['curriculum_data'])) {
            throw new Exception('No curriculum data provided.');
        }

        // Parse JSON if it's a string, otherwise use as array
        $curriculum_data = $_POST['curriculum_data'];
        if (is_string($curriculum_data)) {
            $curriculum_data = json_decode($curriculum_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
        }
        
        if (!is_array($curriculum_data)) {
            throw new Exception('Curriculum data must be an array.');
        }
        $subjects_to_insert = count($curriculum_data);
        
        if ($subjects_to_insert === 0) {
            throw new Exception('No subjects to insert.');
        }

        // Build the SQL Template for BULK INSERT
        $value_placeholder = "(?, ?, ?, ?, ?)";
        $placeholders = implode(", ", array_fill(0, $subjects_to_insert, $value_placeholder));
        
        $sql = "INSERT INTO curriculum (course_id, subject_id, year_level, semester, subject_name)
                VALUES {$placeholders}
                ON DUPLICATE KEY UPDATE
                    year_level = VALUES(year_level),
                    semester = VALUES(semester),
                    subject_name = VALUES(subject_name);";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare bulk insert statement.');
        }
        
        // Collect Data and Bind Types
        $types = str_repeat("iiiis", $subjects_to_insert);
        $params = array();
        
        foreach ($curriculum_data as $entry) {
            $params[] = (int)($entry['course_id'] ?? 0);
            $params[] = (int)($entry['subject_id'] ?? 0);
            $params[] = (int)($entry['year_level'] ?? 1);
            $params[] = (int)($entry['semester'] ?? 1);
            $params[] = $entry['subject_name'] ?? '';
        }
        
        // Dynamically Bind Parameters
        array_unshift($params, $types);
        call_user_func_array(array($stmt, 'bind_param'), refValues($params));

        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => "Curriculum for {$subjects_to_insert} entries saved successfully!",
            'affected_rows' => $affected_rows
        ]);

    } else {
        throw new Exception('Invalid action.');
    }

} catch (mysqli_sql_exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}

?>

