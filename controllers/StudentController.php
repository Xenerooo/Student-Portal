<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Grade;
use App\Models\User;
use App\Models\Enrollment;
use Throwable;

class StudentController extends BaseController {

    public function dashboard() {
        if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
            header("Location: /Student-Portal/");
            exit();
        }

        $student_id = $_SESSION['student_id'];
        $studentModel = new \App\Models\Student($this->conn);
        $student = $studentModel->getStudentDashboardData($student_id);

        $this->generateCsrfToken();
        $this->render('student/dashboard', [
            'pageTitle' => "Student Dashboard | SIS",
            'student' => $student
        ]);
    }

    public function getStudentInfo() {
        $this->checkStudent();
        $student_id = $_SESSION['student_id'];

        $studentModel = new \App\Models\Student($this->conn);
        $student = $studentModel->getStudentById($student_id);

        if (!$student) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                $this->json(['success' => false, 'message' => 'Student info not found.'], 404);
            }
            die("<div class='alert alert-danger'>Student info not found.</div>");
        }

        $this->render('student/student_info', [
            'student' => $student
        ]);
    }

    public function getStudentGrades() {
        $this->checkStudent();
        $this->render('student/student_grades');
    }

    public function changePassword() {
        $this->checkStudent();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $this->verifyCsrfToken();

        $student_id = $_SESSION['student_id'];
        $oldPassword = (string)($_POST['old_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $enteredUsername = (string)($_POST['username'] ?? '');

        if ($oldPassword === '' || $newPassword === '') {
            $this->json(['success' => false, 'message' => 'Please provide old and new password.'], 400);
        }
        if (strlen($newPassword) < 6) {
            $this->json(['success' => false, 'message' => 'New password must be at least 6 characters.'], 400);
        }

        $userModel = new User($this->conn);
        $row = $userModel->getUserAccountDetails($student_id);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'User account not found.'], 404);
        }

        if ($enteredUsername != $row['username']) {
            $this->json(['success' => false, 'message' => 'Username invalid.'], 401);
        }

        if (!password_verify($oldPassword, $row['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Old password is incorrect.'], 401);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($userModel->updatePassword($row['user_id'], $newHash)) {
            $this->json(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update password.'], 500);
        }
    }

    public function getGradesProgress() {
        $this->checkStudent();
        header('Content-Type: application/json');

        $student_id = $_SESSION['student_id'];
        $gradeModel = new Grade($this->conn);
        
        try {
            $data = $gradeModel->getCurriculumProgress($student_id);
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch progress: ' . $e->getMessage()], 500);
        }
    }

    public function getScholasticHistory() {
        $this->checkStudent();
        header('Content-Type: application/json');

        $student_id = $_SESSION['student_id'];
        $gradeModel = new Grade($this->conn);
        
        try {
            $data = $gradeModel->getScholasticHistory($student_id);
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch history: ' . $e->getMessage()], 500);
        }
    }
    
    public function getGradesByTerm() {
        $this->checkStudent();
        header('Content-Type: application/json');
        $school_year = trim($_GET['school_year'] ?? '');
        $semester    = trim($_GET['semester'] ?? '');
        if (!$school_year || !$semester)
            $this->json(['success'=>false,'message'=>'school_year and semester are required.'],400);

        $student_id = $_SESSION['student_id'];
        try {
            $enrollModel = new Enrollment($this->conn);
            $data = $enrollModel->getEnrollmentsByTerm($student_id, $school_year, $semester);
            $this->json(['success'=>true,'data'=>$data]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function getEnrolledTerms() {
        $this->checkStudent();
        header('Content-Type: application/json');
        $student_id = $_SESSION['student_id'];
        try {
            $enrollModel = new Enrollment($this->conn);
            $terms = $enrollModel->getTermsWithEnrollment($student_id);
            $this->json(['success'=>true,'terms'=>$terms]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    private function checkStudent() {
        if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                $this->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or not logged in as student.</div>");
        }
    }
}
?>
