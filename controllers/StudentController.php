<?php
class StudentController {
    public function dashboard() {
        if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
            header("Location: /Student-Portal/");
            exit();
        }

        require_once 'core/db_connect.php';
        $conn = connect();
        
        $student_id = $_SESSION['student_id'];
        
        $stmt = $conn->prepare("CALL getStudentDetailsByStudentId(?);");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        while ($conn->more_results()) { $conn->next_result(); }

        $stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?;");
        $stmt->bind_param("i", $student['course_id']);
        $stmt->execute();
        $student['course_name'] = $stmt->get_result()->fetch_assoc()['course_name'];
        $stmt->close();
        
        $conn->close();

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
        include 'app/process_change_password.php';
    }

    public function getGradesData() {
        $this->checkStudent();
        include 'app/process_get_student_grade.php';
    }

    private function checkStudent() {
        if (!isset($_SESSION['student_id'])) {
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or not logged in as student.</div>");
        }
    }
}
?>
