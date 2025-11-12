<?php
session_start();

require_once "includes/db_connect.php";
$conn = connect();

// --- Handle Login Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Raw password from form

    // 1. Authenticate against the central 'users' table
    $stmt = $conn->prepare("CALL getUserDetailByUsername(?);");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $result->close(); // Close the result set
    $stmt->close(); // Close the statement before making another call

    // 2. Securely verify the password
    if ($user && password_verify($password, $user['password_hash'])) {
            
            // Set fundamental session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role']; // 'student' or 'admin'
            
            $redirect_page = '../index.php'; // Fallback to index.php if no data found

            // 3. Handle role-based session and redirection
            if ($user['role'] === 'student') {
                
                // Get the specific student_id from the students table
                $stmt_s = $conn->prepare("CALL getStudentByUserId(?);");
                $stmt_s->bind_param("i", $user['user_id']);
                $stmt_s->execute();
                $result_s = $stmt_s->get_result();
                $student_data = $result_s->fetch_assoc();
                $result_s->close(); // Close the result set
                $stmt_s->close(); // Close the statement
                
                if ($student_data) {
                    $_SESSION['student_id'] = $student_data['student_id'];
                    $redirect_page = '../student_dashboard.php';
                }
                
            } elseif ($user['role'] === 'admin') {
                
                // Get the specific admin_id from the admins table
                $stmt_a = $conn->prepare("CALL getAdminByUserId(?);");
                $stmt_a->bind_param("i", $user['user_id']);
                $stmt_a->execute();
                $result_a = $stmt_a->get_result();
                $admin_data = $result_a->fetch_assoc();
                $result_a->close(); // Close the result set
                $stmt_a->close(); // Close the statement
                
                if ($admin_data) {
                    $_SESSION['admin_id'] = $admin_data['admin_id'];
                    $redirect_page = '../admin_dashboard.php';
                }
            }

            $conn->close(); // Close connection after successful DB operations
            
            // 4. Redirect the user
            header("Location: " . $redirect_page);
            exit();
            
    } else {
        // Invalid username or password (user not found or password doesn't match)
        $error_message = 'Invalid username or password.';
    }
    
    // Display error message using JavaScript alert (Only runs if login failed)
    if (isset($error_message)) {
        echo "<script>alert('$error_message');</script>";
    }
    
    $conn->close();
}
?>