<?php
session_start();

header('Content-Type: application/json'); // Ensure JSON response

require_once 'includes/db_connect.php';
// Connect DB
try {
    $conn = connect();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Authorization Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admin login required.']);
    if (isset($conn)) { $conn->close(); }
    exit();
}

// --- 2. Handle Grade Submission (The 'U' in CRUD) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $current_semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT) ?: 1;
    $current_school_year_id = filter_input(INPUT_POST, 'school_year_id', FILTER_VALIDATE_INT) ?: 1;

    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
        $conn->close();
        exit();
    }

    if (!isset($_POST['grades']) || !is_array($_POST['grades'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No grades submitted.']);
        $conn->close();
        exit();
    }

    try {
        $conn->begin_transaction();

        foreach ($_POST['grades'] as $subject_id => $grade) {
            $grade = trim((string)$grade);
            if (!filter_var($subject_id, FILTER_VALIDATE_INT)) {
                continue;
            }

            if ($grade !== '' && is_numeric($grade)) {
                // Use stored procedure to insert/update grade
                $stmt = $conn->prepare("CALL upsertGrade(?, ?, ?, ?, ?);");
                $stmt->bind_param("iisii", $student_id, $subject_id, $grade, $current_semester_id, $current_school_year_id);
                $stmt->execute();
                $stmt->close();
            } else if ($grade === '') {
                // Use stored procedure to delete grade
                $stmt = $conn->prepare("CALL deleteGrade(?, ?, ?, ?);");
                $stmt->bind_param("iiii", $student_id, $subject_id, $current_semester_id, $current_school_year_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();
        $message = "Grades for student ID {$student_id} updated successfully!";
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        $message = 'Error updating grades.';
        echo json_encode(['success' => false, 'message' => $message]);
    } finally {
        $conn->close();
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
if (isset($conn)) { $conn->close(); }
exit();
?>