<?php
require_once 'models/User.php';

class AuthController {
    public function showLoginForm() {
        // If already logged in, redirect
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'student') {
                header("Location: /Student-Portal/student/dashboard");
                exit();
            } elseif ($_SESSION['role'] === 'admin') {
                header("Location: /Student-Portal/admin/dashboard");
                exit();
            }
        }
        
        require 'views/auth/login.php';
    }

    public function login() {
        header('Content-Type: application/json');
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            $redirect_page = '';

            if ($user['role'] === 'student') {
                $student_id = $userModel->getStudentIdByUserId($user['user_id']);
                if ($student_id) {
                    $_SESSION['student_id'] = $student_id;
                    $redirect_page = '/Student-Portal/student/dashboard';
                }
            } elseif ($user['role'] === 'admin') {
                $admin_id = $userModel->getAdminIdByUserId($user['user_id']);
                if ($admin_id) {
                    $_SESSION['admin_id'] = $admin_id;
                    $redirect_page = '/Student-Portal/admin/dashboard';
                }
            }

            echo json_encode([
                'success' => true,
                'redirect' => $redirect_page
            ]);
            exit();
            
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password.'
            ]);
            exit();
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /Student-Portal/");
        exit();
    }
}
?>
