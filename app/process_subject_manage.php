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

try {
    if ($action === 'add') {
        // Handle Add Subject
        $subject_code = trim($_POST['subject_code'] ?? '');
        $units = filter_input(INPUT_POST, 'units', FILTER_VALIDATE_INT);

        if (empty($subject_code)) {
            throw new Exception('Subject code is required.');
        }

        if (!$units || $units < 1 || $units > 10) {
            throw new Exception('Units must be between 1 and 10.');
        }

        // Check if subject code already exists
        $checkStmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('s', $subject_code);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result && $result->num_rows > 0) {
            $checkStmt->close();
            throw new Exception('Subject code already exists.');
        }
        $checkStmt->close();

        // Insert new subject
        $stmt = $conn->prepare("INSERT INTO subjects (subject_code, units) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement.');
        }
        $stmt->bind_param('si', $subject_code, $units);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new Exception('Failed to add subject.');
        }

        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Subject added successfully!'
        ]);

    } elseif ($action === 'delete') {
        // Handle Delete Subject
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

        if (!$subject_id) {
            throw new Exception('Invalid subject ID.');
        }

        // Check if subject exists
        $checkStmt = $conn->prepare("SELECT subject_code FROM subjects WHERE subject_id = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('i', $subject_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if (!$result || $result->num_rows === 0) {
            $checkStmt->close();
            throw new Exception('Subject not found.');
        }
        $checkStmt->close();

        // Start transaction for safe deletion
        $conn->begin_transaction();

        // Delete from curriculum_subjects first (if it exists and has foreign key)
        $delCurriculum = $conn->prepare("DELETE FROM curriculum WHERE subject_id = ?");
        if ($delCurriculum) {
            $delCurriculum->bind_param('i', $subject_id);
            $delCurriculum->execute();
            $delCurriculum->close();
        }

        // Delete from subjects
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare delete statement.');
        }
        $stmt->bind_param('i', $subject_id);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            $conn->rollback();
            throw new Exception('Failed to delete subject. subject input: ' . $subject_id);
        }

        $stmt->close();
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Subject deleted successfully!'
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

