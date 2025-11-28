<?php
session_start();

header('Content-Type: application/json');

// Security Check: Must be a student
if (!isset($_SESSION['student_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. Invalid session or not logged in as student.']);
    exit();
}

require_once 'includes/db_connect.php';

try {
    $conn = connect();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$student_id = $_SESSION['student_id'];

// Get student's course_id
$stmt = $conn->prepare("CALL getStudentDetailsByStudentId(?);");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$course_id = $student['course_id'] ?? null;
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

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $curriculum_data,
    'course_id' => $course_id
]);
?>