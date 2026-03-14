<?php
require_once 'models/Grade.php';

class StudentController {
    private $conn;

    public function __construct($dbConnection = null) {
        $this->conn = $dbConnection;
    }

    public function dashboard() {
        if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
            header("Location: /Student-Portal/");
            exit();
        }

        $student_id = $_SESSION['student_id'];
        
        $stmt = $this->conn->prepare("CALL getStudentDetailsByStudentId(?);");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }

        $stmt = $this->conn->prepare("SELECT course_name FROM courses WHERE course_id = ?;");
        $stmt->bind_param("i", $student['course_id']);
        $stmt->execute();
        $student['course_name'] = $stmt->get_result()->fetch_assoc()['course_name'];
        $stmt->close();

        $pageTitle = "Student Dashboard | SIS";
        require 'views/student/dashboard.php';
    }

    public function getStudentInfo() {
        $this->checkStudent();
        include 'views/student_info.php';
    }

    public function getStudentGrades() {
        $this->checkStudent();
        include 'views/student_grades.php';
    }

    public function changePassword() {
        $this->checkStudent();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        $sentCsrf = $_POST['csrf'] ?? '';
        $sessionCsrf = $_SESSION['csrf'] ?? '';
        if (!$sentCsrf || !$sessionCsrf || !hash_equals($sessionCsrf, $sentCsrf)) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit();
        }

        $student_id = $_SESSION['student_id'];
        $oldPassword = (string)($_POST['old_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $enteredUsername = (string)($_POST['username'] ?? '');

        if ($oldPassword === '' || $newPassword === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide old and new password.']);
            exit();
        }
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
            exit();
        }

        $userModel = new User($this->conn);
        $row = $userModel->getUserAccountDetails($student_id);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
            exit();
        }

        if ($enteredUsername != $row['username']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Username invalid.']);
            exit();
        }

        if (!password_verify($oldPassword, $row['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Old password is incorrect.']);
            exit();
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($userModel->updatePassword($row['user_id'], $newHash)) {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
    }

    public function getGradesData() {
        $this->checkStudent();
        header('Content-Type: application/json');

        $student_id = $_SESSION['student_id'];
        $gradeModel = new Grade($this->conn);
        
        try {
            $data = $gradeModel->getStudentGrades($student_id);
            echo json_encode([
                'success' => true,
                'data' => $data['grades'],
                'course_id' => $data['course_id']
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch grades: ' . $e->getMessage()]);
        }
    }

    private function checkStudent() {
        if (!isset($_SESSION['student_id'])) {
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or not logged in as student.</div>");
        }
    }
}
?>
