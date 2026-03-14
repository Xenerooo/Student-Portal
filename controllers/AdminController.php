<?php
class AdminController {
    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Student-Portal/");
            exit();
        }

        $pageTitle = "Admin Dashboard | SIS";
        require 'views/admin/dashboard.php';
    }

    public function getStudentList() {
        $this->checkAdmin();
        include 'views/student_list.php';
    }

    public function getManageSubjects() {
        $this->checkAdmin();
        include 'views/manage_subjects.php';
    }

    public function getManageCurriculum() {
        $this->checkAdmin();
        include 'views/manage_curriculum.php';
    }

    public function getCreateStudentForm() {
        $this->checkAdmin();
        include 'views/create_student.php';
    }

    public function getEditStudentForm() {
        $this->checkAdmin();
        // The view expects $_GET['student_id'] to be present
        include 'views/edit_student.php';
    }

    public function getGradeEditor() {
        $this->checkAdmin();
        // The view expects $_GET['student_id'] to be present
        include 'views/grade_editor.php';
    }

    public function saveGrade() {
        $this->checkAdmin();
        include 'app/process_edit_grade.php';
    }

    public function searchStudents() {
        $this->checkAdmin();
        // This used to be app/process_search_student.php
        include 'app/process_search_student.php';
    }

    public function manageSubject() {
        $this->checkAdmin();
        include 'app/process_subject_manage.php';
    }

    public function manageCurriculum() {
        $this->checkAdmin();
        include 'app/process_curriculum_manage.php';
    }

    public function editStudent() {
        $this->checkAdmin();
        include 'app/process_student_edit.php';
    }

    public function deleteStudent() {
        $this->checkAdmin();
        include 'app/process_student_delete.php';
    }

    public function createStudent() {
        $this->checkAdmin();
        include 'app/process_student_create.php';
    }

    public function getCurriculumData() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        require_once 'core/db_connect.php';
        $conn = connect();
        
        // AltoRouter doesn't auto-parse GET params if they aren't in the route sometimes, but we can still grab $_GET or modify our route later if needed. For now $_GET works fine.
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        if ($course_id > 0) {
            $stmt = $conn->prepare("
                SELECT c.curriculum_id, c.course_id, c.subject_id, c.year_level, c.semester, c.subject_name,
                       co.course_name, s.subject_code
                FROM curriculum c
                LEFT JOIN courses co ON c.course_id = co.course_id
                LEFT JOIN subjects s ON c.subject_id = s.subject_id
                WHERE c.course_id = ?
                ORDER BY c.year_level, c.semester, c.subject_name
            ");
            $stmt->bind_param('i', $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $entries = [];
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'entries' => $entries]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
        }
        $conn->close();
    }

    private function checkAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or role.</div>");
        }
    }
}
?>
