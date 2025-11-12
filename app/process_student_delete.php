<?php
    session_start();

    header('Content-Type: application/json'); // Ensure JSON response

    // Authorization Check
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access Denied. Admin login required.']);
        exit();
    }

    require_once "includes/db_connect.php";
    $conn = connect();

    // Validate input
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    
    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing student ID.']);
        $conn->close();
        exit();
    }

    // Use a transaction to keep data consistent
    $conn->begin_transaction();
    try {
        // Fetch related user_id for cleanup after deleting the student record
        $getStmt = $conn->prepare("SELECT user_id FROM students WHERE student_id = ?");
        if (!$getStmt) {
            throw new Exception('Failed to prepare fetch statement.');
        }
        $getStmt->bind_param('i', $studentId);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $getStmt->close();

        if (!$row) {
            throw new Exception('Student not found.');
        }

        $userId = (int)$row['user_id'];

        // Delete dependent rows that reference this student to satisfy FK constraints
        // 1) grades -> student_id FK
        $delGrades = $conn->prepare("DELETE FROM grades WHERE student_id = ?");
        if (!$delGrades) {
            throw new Exception('Failed to prepare grades delete statement.');
        }
        $delGrades->bind_param('i', $studentId);
        $delGrades->execute();
        $delGrades->close();

        // Delete from students (now safe after dependent rows removed)
        $delStudent = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        if (!$delStudent) {
            throw new Exception('Failed to prepare student delete statement.');
        }
        $delStudent->bind_param('i', $studentId);
        $delStudent->execute();
        if ($delStudent->affected_rows < 1) {
            throw new Exception('Failed to delete student record.');
        }
        $delStudent->close();

        // Optionally delete the linked user account if it exists
        if ($userId > 0) {
            $delUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            if ($delUser) {
                $delUser->bind_param('i', $userId);
                $delUser->execute();
                // It's okay if no user row is deleted (defensive)
                $delUser->close();
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Student deleted successfully.'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } finally {
        $conn->close();
    }
?>