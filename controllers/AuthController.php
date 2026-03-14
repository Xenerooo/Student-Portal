<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;

class AuthController extends BaseController {
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
        
        $this->render('auth/login');
    }

    public function login() {
        header('Content-Type: application/json');
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new User($this->conn);
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

            $this->json([
                'success' => true,
                'redirect' => $redirect_page
            ]);
            
        } else {
            $this->json([
                'success' => false,
                'message' => 'Invalid username or password.'
            ], 401);
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /Student-Portal/");
        exit();
    }
}
?>
