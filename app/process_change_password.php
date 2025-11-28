<?php
// app/process_change_password.php - Handles student change password via AJAX (JSON)

session_start();
header('Content-Type: application/json');

// Authorization: must be logged in as a student
if (!isset($_SESSION['student_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Student login required.']);
    exit();
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// CSRF validation
$sentCsrf = $_POST['csrf'] ?? '';
$sessionCsrf = $_SESSION['csrf'] ?? '';
if (!$sentCsrf || !$sessionCsrf || !hash_equals($sessionCsrf, $sentCsrf)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

require_once 'includes/db_connect.php';
$conn = connect();

$studentId = $_SESSION['student_id'];
$oldPassword = (string)($_POST['old_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$enteredUsername = (string)($_POST['username'] ?? '');

// Basic validation
if ($oldPassword === '' || $newPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide old and new password.']);
    $conn->close();
    exit();
}
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    $conn->close();
    exit();
}



$sql = "CALL getUserAccountDetails(?);";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $studentId);  // 's' for string, 'i' for integer
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User account not found.']);
    $conn->close();
    exit();
}
// Verify  username
if ($enteredUsername != $row['username']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Username invalid.']);
    $conn->close();
    exit();
}

// Verify old password
if (!password_verify($oldPassword, $row['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Old password is incorrect.']);
    $conn->close();
    exit();
}

// Update to new hash
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $conn->prepare('CALL UserUpdatePassword(?, ?);');
$update->bind_param('si', $newHash, $row['user_id']);
$ok = $update->execute();
$update->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
?>


