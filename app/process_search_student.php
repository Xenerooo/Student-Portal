<?php 
session_start();
require_once "includes/db_connect.php";
$conn = connect();

header('Content-Type: application/json'); // Ensure JSON response

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$students = [];

// Query to fetch student data for the lookup table with optional search
// echo json_encode(['success' => true, 'students' => $students]);
if (!empty($search)) {
    // Use prepared statement to prevent SQL injection
    $searchParam = "%{$search}%";
    $stmt = $conn->prepare("CALL getStudentBySearch(?);");
    if ($stmt) {
        $stmt->bind_param("s", $searchParam);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $students = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $students = [];
            error_log("Search query failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $students = [];
        error_log("Prepare failed: " . $conn->error);
    }
} else {
    // No search term, fetch all students
    $sql = "CALL getAllStudents();";
    $result = $conn->query($sql);
    if ($result) {
        $students = ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        $students = [];
        error_log("Query failed: " . $conn->error);
        
    }
}

echo json_encode(['success' => true, 'students' => $students]);
$conn->close();
?>