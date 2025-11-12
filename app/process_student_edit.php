<?php
// app/process_student_edit.php - Handles the student edit form submission via AJAX

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

// --- 1. Collect and sanitize input ---
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$name = trim($_POST['student_name'] ?? '');
$number = trim($_POST['student_number'] ?? '');
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? ''; // Optional - only update if provided
$birthday = trim($_POST['birthday'] ?? '');

// --- 2. Basic Validation ---
if (!$student_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student or user ID.']);
    $conn->close();
    exit();
}

if (empty($name) || empty($number) || !$course_id || empty($username) || empty($birthday)) {
    echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
    $conn->close();
    exit();
}

// Validate password if provided
if (!empty($password) && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    $conn->close();
    exit();
}

// Validate birthday format (YYYY-MM-DD) and ensure it is not in the future
$birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
$birthdayValid = $birthdayDate && $birthdayDate->format('Y-m-d') === $birthday;
if (!$birthdayValid) {
    echo json_encode(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.']);
    $conn->close();
    exit();
}
if ($birthdayDate > new DateTime('today')) {
    echo json_encode(['success' => false, 'message' => 'Birthday cannot be in the future.']);
    $conn->close();
    exit();
}

// --- 3. Handle Image Upload ---
$image_data = null;
if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['student_image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image file type. Only JPEG, PNG, and GIF are allowed.']);
        $conn->close();
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image file is too large. Maximum size is 5MB.']);
        $conn->close();
        exit();
    }
    
    // Read image file into binary data
    $image_data = file_get_contents($file['tmp_name']);
}

// --- 4. Database Update (Transaction) ---
try {
    // Start Transaction
    $conn->begin_transaction();
    
    // --- UPDATE 1: Update User Account (Username and optionally Password) ---
    if (!empty($password)) {
        // Update both username and password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("
            UPDATE users 
            SET username = ?, password_hash = ?
            WHERE user_id = ?
        ");
        $stmt_user->bind_param("ssi", $username, $hashed_password, $user_id);
    } else {
        // Update only username
        $stmt_user = $conn->prepare("
            UPDATE users 
            SET username = ?
            WHERE user_id = ?
        ");
        $stmt_user->bind_param("si", $username, $user_id);
    }
    
    if (!$stmt_user->execute()) {
        $conn->rollback();
        if ($conn->errno === 1062) {
             echo json_encode(['success' => false, 'message' => "Error: Username '{$username}' already exists. Please choose a different one."]);
        } else {
             echo json_encode(['success' => false, 'message' => "User update failed: " . htmlspecialchars($stmt_user->error)]);
        }
        $stmt_user->close();
        $conn->close();
        exit();
    }
    $stmt_user->close();

    // --- UPDATE 2: Update Student Profile ---
    if ($image_data !== null) {
        // Update with image
        $stmt_student = $conn->prepare("
            UPDATE students 
            SET student_name = ?, student_number = ?, course_id = ?, birthday = ?, img = ?
            WHERE student_id = ?
        ");
        // For binary data, bind NULL first then send the data
        $null = null;
        $stmt_student->bind_param("ssisbi", $name, $number, $course_id, $birthday, $null, $student_id);
        // Send binary data (parameter index 4, which is the 5th parameter - 0-indexed)
        $stmt_student->send_long_data(4, $image_data);
    } else {
        // Update without changing image
        $stmt_student = $conn->prepare("
            UPDATE students 
            SET student_name = ?, student_number = ?, course_id = ?, birthday = ?
            WHERE student_id = ?
        ");
        $stmt_student->bind_param("ssisi", $name, $number, $course_id, $birthday, $student_id);
    }

    if (!$stmt_student->execute()) {
        $conn->rollback();
        if ($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => "Error: Student number '{$number}' already exists."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Student profile update failed: " . htmlspecialchars($stmt_student->error)]);
        }
        $stmt_student->close();
        $conn->close();
        exit();
    }
    
    $stmt_student->close();
    
    // --- Success: Commit Transaction ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Student **{$name}** updated successfully!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => "FATAL Error: " . htmlspecialchars($e->getMessage())]);
}

$conn->close();
?>
