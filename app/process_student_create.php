<?php
// app/process_student_create.php - Handles the form submission via AJAX

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
$name = trim($_POST['student_name'] ?? '');
$number = trim($_POST['student_number'] ?? ''); // Maps to student_number (unique)
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username'] ?? ''); // Maps to users.username (unique)
$password = $_POST['password'] ?? ''; // Maps to users.password_hash
$birthday = trim($_POST['birthday'] ?? ''); // YYYY-MM-DD

// --- 2. Basic Validation ---
if (empty($name) || empty($number) || !$course_id || empty($username) || empty($password) || empty($birthday)) {
    echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
    $conn->close();
    exit();
} 
if (strlen($password) < 6) {
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

// --- 3. Securely Hash Password ---
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- 4. Handle Image Upload (optional) ---
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

// --- 5. Database Insertion (Two Inserts in a Transaction) ---
try {
    // Start Transaction
    $conn->begin_transaction();
    
    // --- INSERT 1: Create the User Account (Authentication/Login) ---
    $stmt_user = $conn->prepare(
        "CALL createUser(?, ?, 'student');"
    );
    $stmt_user->bind_param("ss", $username, $hashed_password);
    
    if (!$stmt_user->execute()) {
        $conn->rollback();
        if ($conn->errno === 1062) {
             echo json_encode(['success' => false, 'message' => "Error: Username '{$username}' already exists. Please choose a different one."]);
        } else {
             echo json_encode(['success' => false, 'message' => "User creation failed: " . htmlspecialchars($stmt_user->error)]);
        }
        $stmt_user->close();
        $conn->close();
        exit();
    }
    
    // Get the new user_id to link the student record
    // Stored procedures don't set insert_id, so we query by username instead
    $stmt_user->close();
    
    // Query the user_id by username (since username is unique and we just created it)
    $stmt_get_id = $conn->prepare("CALL getUserDetailByUsername(?);");
    $stmt_get_id->bind_param("s", $username);
    if (!$stmt_get_id->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => "Error: Could not retrieve user_id after user creation."]);
        $stmt_get_id->close();
        $conn->close();
        exit();
    }
    $id_result = $stmt_get_id->get_result();
    if ($id_row = $id_result->fetch_assoc()) {
        $new_user_id = (int)$id_row['user_id'];
        if ($new_user_id <= 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Error: Invalid user_id retrieved after user creation."]);
            $stmt_get_id->close();
            $conn->close();
            exit();
        }
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => "Error: User was created but user_id could not be retrieved."]);
        $stmt_get_id->close();
        $conn->close();
        exit();
    }
    $stmt_get_id->close();


    // --- INSERT 2: Create the Student Profile (Profile/Academic Data) ---
    $stmt_student = $conn->prepare(
        "CALL createStudent(?, ?, ?, ?, ?, ?)"
    );
    // Initialize img_param for binding (NULL if no image, otherwise will be set via send_long_data)
    $img_param = null;
    $stmt_student->bind_param("issisb", $new_user_id, $name, $number, $course_id, $birthday, $img_param);
    
    // Send long data only if image is provided
    if ($image_data !== null) {
        $stmt_student->send_long_data(5, $image_data); // index 5 corresponds to 'img'
    }

    if (!$stmt_student->execute()) {
        $conn->rollback(); // Rollback user creation as well!
        if ($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => "Error: Student number '{$number}' already exists."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Student profile creation failed: " . htmlspecialchars($stmt_student->error)]);
        }
        $stmt_student->close();
        $conn->close();
        exit();
    }
    
    $stmt_student->close();
    
    // --- Success: Commit Transaction ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Student **{$name}** created successfully! User ID: {$new_user_id}"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => "FATAL Error: " . htmlspecialchars($e->getMessage())]);
}

$conn->close();
?>
