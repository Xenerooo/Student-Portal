<?php
require_once 'models/Subject.php';
require_once 'models/Student.php';
require_once 'models/Curriculum.php';
require_once 'models/Grade.php';

class AdminController {
    private $conn;

    public function __construct($dbConnection = null) {
        $this->conn = $dbConnection;
    }

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
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_grades'])) {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit();
        }

        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT) ?: 1;
        $school_year_id = filter_input(INPUT_POST, 'school_year_id', FILTER_VALIDATE_INT) ?: 1;

        if (!$student_id || !isset($_POST['grades']) || !is_array($_POST['grades'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid student ID or missing grades.']);
            exit();
        }

        $gradeModel = new Grade($this->conn);
        try {
            $gradeModel->upsertGrades($student_id, $_POST['grades'], $semester_id, $school_year_id);
            echo json_encode(['success' => true, 'message' => "Grades for student ID {$student_id} updated successfully!"]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating grades: ' . $e->getMessage()]);
        }
    }

    public function searchStudents() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $studentModel = new Student($this->conn);
        $students = $studentModel->searchStudents($search);
        
        echo json_encode(['success' => true, 'students' => $students]);
    }

    public function manageSubject() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? 'add';
        $subjectModel = new Subject($this->conn);

        try {
            if ($action === 'add') {
                $subject_code = trim($_POST['subject_code'] ?? '');
                $units = filter_input(INPUT_POST, 'units', FILTER_VALIDATE_INT);

                if (empty($subject_code)) {
                    throw new Exception('Subject code is required.');
                }
                if (!$units || $units < 1 || $units > 10) {
                    throw new Exception('Units must be between 1 and 10.');
                }

                if ($subjectModel->subjectExists($subject_code)) {
                    throw new Exception('Subject code already exists.');
                }

                if ($subjectModel->addSubject($subject_code, $units)) {
                    echo json_encode(['success' => true, 'message' => 'Subject added successfully!']);
                } else {
                    throw new Exception('Failed to add subject.');
                }

            } elseif ($action === 'delete') {
                $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

                if (!$subject_id) {
                    throw new Exception('Invalid subject ID.');
                }

                if (!$subjectModel->subjectExistsById($subject_id)) {
                    throw new Exception('Subject not found.');
                }

                if ($subjectModel->deleteSubject($subject_id)) {
                    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully!']);
                }

            } else {
                throw new Exception('Invalid action.');
            }

        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function manageCurriculum() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? 'add';
        $curriculumModel = new Curriculum($this->conn);

        try {
            if ($action === 'add') {
                $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
                $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
                $subject_name = trim($_POST['subject_name'] ?? '');
                $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
                $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);

                if (!$course_id || !$subject_id || empty($subject_name)) {
                    throw new Exception('Course, subject, and subject name are required.');
                }
                if (!$year_level || $year_level < 1 || $year_level > 4) {
                    throw new Exception('Year level must be between 1 and 4.');
                }
                if (!$semester || ($semester != 1 && $semester != 2)) {
                    throw new Exception('Semester must be 1 or 2.');
                }

                $entry = $curriculumModel->addEntry($course_id, $subject_id, $year_level, $semester, $subject_name);
                echo json_encode(['success' => true, 'message' => 'Curriculum entry added successfully!', 'data' => $entry]);

            } elseif ($action === 'update') {
                $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);
                $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
                $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
                $subject_name = trim($_POST['subject_name'] ?? '');
                $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
                $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);

                if (!$curriculum_id || !$course_id || !$subject_id || empty($subject_name)) {
                    throw new Exception('All fields are required.');
                }

                if (!$curriculumModel->exists($curriculum_id)) {
                    throw new Exception('Curriculum entry not found.');
                }

                $entry = $curriculumModel->updateEntry($curriculum_id, $course_id, $subject_id, $year_level, $semester, $subject_name);
                echo json_encode(['success' => true, 'message' => 'Curriculum entry updated successfully!', 'data' => $entry]);

            } elseif ($action === 'delete') {
                $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);
                if (!$curriculum_id) throw new Exception('Invalid curriculum ID.');
                
                if (!$curriculumModel->exists($curriculum_id)) {
                    throw new Exception('Curriculum entry not found.');
                }

                if ($curriculumModel->deleteEntry($curriculum_id)) {
                    echo json_encode(['success' => true, 'message' => 'Curriculum entry deleted successfully!']);
                } else {
                    throw new Exception('Failed to delete curriculum entry.');
                }

            } elseif ($action === 'bulk_save') {
                if (!isset($_POST['curriculum_data'])) throw new Exception('No curriculum data provided.');
                
                $curriculum_data = $_POST['curriculum_data'];
                if (is_string($curriculum_data)) {
                    $curriculum_data = json_decode($curriculum_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
                    }
                }
                
                if (!is_array($curriculum_data)) throw new Exception('Curriculum data must be an array.');

                $affected_rows = $curriculumModel->bulkSave($curriculum_data);
                echo json_encode(['success' => true, 'message' => "Curriculum saved successfully!", 'affected_rows' => $affected_rows]);

            } else {
                throw new Exception('Invalid action.');
            }

        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function editStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['student_name'] ?? '');
        $number = trim($_POST['student_number'] ?? '');
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; 
        $birthday = trim($_POST['birthday'] ?? '');

        if (!$student_id || !$user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid student or user ID.']);
            exit();
        }

        if (empty($name) || empty($number) || !$course_id || empty($username) || empty($birthday)) {
            echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
            exit();
        }

        if (!empty($password) && strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
            exit();
        }

        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
            echo json_encode(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.']);
            exit();
        }
        if ($birthdayDate > new DateTime('today')) {
            echo json_encode(['success' => false, 'message' => 'Birthday cannot be in the future.']);
            exit();
        }

        $image_data = null;
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['student_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image file type.']);
                exit();
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Image file is too large.']);
                exit();
            }
            $image_data = file_get_contents($file['tmp_name']);
        }

        $studentModel = new Student($this->conn);
        try {
            $result = $studentModel->editStudent($student_id, $user_id, $username, $password, $name, $number, $course_id, $birthday, $image_data);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        if (!$studentId) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing student ID.']);
            exit();
        }
        
        $studentModel = new Student($this->conn);
        try {
            $studentModel->deleteStudent($studentId);
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $name = trim($_POST['student_name'] ?? '');
        $number = trim($_POST['student_number'] ?? '');
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $birthday = trim($_POST['birthday'] ?? '');

        if (empty($name) || empty($number) || !$course_id || empty($username) || empty($password) || empty($birthday)) {
            echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
            exit();
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
            exit();
        }

        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
            echo json_encode(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.']);
            exit();
        }
        if ($birthdayDate > new DateTime('today')) {
            echo json_encode(['success' => false, 'message' => 'Birthday cannot be in the future.']);
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $image_data = null;

        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['student_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image file type.']);
                exit();
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Image file is too large. Maximum size is 5MB.']);
                exit();
            }
            $image_data = file_get_contents($file['tmp_name']);
        }

        $studentModel = new Student($this->conn);
        try {
            $result = $studentModel->createStudent($username, $hashed_password, $name, $number, $course_id, $birthday, $image_data);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCurriculumData() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        if ($course_id > 0) {
            $stmt = $this->conn->prepare("
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
    }

    private function checkAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or role.</div>");
        }
    }
}
?>
