<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Curriculum;
use App\Models\Grade;
use App\Models\Course;
use Exception;
use DateTime;
use mysqli_sql_exception;
use Throwable;

class AdminController extends BaseController {

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: /Student-Portal/");
            exit();
        }

        $this->generateCsrfToken();
        $this->render('admin/dashboard', [
            'pageTitle' => "Admin Dashboard | SIS"
        ]);
    }

    public function getStudentList() {
        $this->checkAdmin();
        $this->render('admin/student_list');
    }

    public function getManageSubjects() {
        $this->checkAdmin();
        $subjectModel = new Subject($this->conn);
        $subjects = $subjectModel->getAllSubjects();
        $this->render('admin/manage_subjects', ['subjects' => $subjects]);
    }

    public function getManageCurriculum() {
        $this->checkAdmin();
        $courseModel = new \App\Models\Course($this->conn);
        $subjectModel = new Subject($this->conn);
        
        $courses = $courseModel->getAllCourses();
        $subjects = $subjectModel->getAllSubjects();
        
        $this->render('admin/manage_curriculum', [
            'courses' => $courses,
            'subjects' => $subjects
        ]);
    }

    public function getCreateStudentForm() {
        $this->checkAdmin();
        $courseModel = new Course($this->conn);
        $courses = $courseModel->getAllCourses();
        $this->render('admin/create_student', ['courses' => $courses]);
    }

    public function getEditStudentForm() {
        $this->checkAdmin();
        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);

        if (!$student_id) {
            die("<div class='alert alert-danger'>Invalid student ID.</div>");
        }

        $studentModel = new Student($this->conn);
        $student = $studentModel->getStudentById($student_id);

        if (!$student) {
            die("<div class='alert alert-danger'>Student not found.</div>");
        }

        $courseModel = new Course($this->conn);
        $courses = $courseModel->getAllCourses();

        $this->render('admin/edit_student', [
            'student' => $student,
            'courses' => $courses
        ]);
    }

    public function getGradeEditor() {
        $this->checkAdmin();
        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);

        if (!$student_id) {
            header("Location: /Student-Portal/admin/dashboard");
            exit();
        }

        // Fetch Student Details
        $studentModel = new Student($this->conn);
        $student_details = $studentModel->getStudentById($student_id);

        if (!$student_details) {
            die("<div class='alert alert-danger'>Student not found.</div>");
        }

        // Defaults for term selection if not provided in GET
        $current_school_year = trim($_GET['school_year'] ?? "2024-2025");
        $current_semester = trim($_GET['semester'] ?? "1st Semester");

        // Fetch curriculum progress filtered by term
        $gradeModel = new Grade($this->conn);
        $res = $gradeModel->getCurriculumProgress($student_id, $current_semester, $current_school_year);
        
        // Flatten for the current simple view compatibility
        $flattened_grades = [];
        foreach ($res as $year => $semesters) {
            foreach ($semesters as $sem => $subjects) {
                foreach ($subjects as $subject) {
                    $flattened_grades[] = $subject;
                }
            }
        }

        $this->render('admin/grade_editor', [
            'student_id' => $student_id,
            'student_details' => $student_details,
            'grades_data' => $flattened_grades,
            'current_school_year' => $current_school_year,
            'current_semester' => $current_semester
        ]);
    }

    public function getSubjectHistoryApi() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
        $subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);

        if (!$student_id || !$subject_id) {
            $this->json(['success' => false, 'message' => 'Invalid student or subject ID.'], 400);
        }

        try {
            $gradeModel = new Grade($this->conn);
            $history = $gradeModel->getSubjectHistory($student_id, $subject_id);
            $this->json(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function saveGrade() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        $this->verifyCsrfToken();

        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $semester = trim($_POST['semester'] ?? '1st Semester');
        $school_year = trim($_POST['school_year'] ?? '2024-2025');

        if (!$student_id || !isset($_POST['grades']) || !is_array($_POST['grades'])) {
            $this->json(['success' => false, 'message' => 'Invalid student ID or missing grades.'], 400);
        }

        $gradeModel = new Grade($this->conn);
        try {
            $gradeModel->upsertGrades($student_id, $_POST['grades'], $semester, $school_year);
            $this->json(['success' => true, 'message' => "Grades for {$semester}, SY {$school_year} updated successfully!"]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Error updating grades: ' . $e->getMessage()], 500);
        }
    }

    public function searchStudents() {
        $this->checkAdmin();
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $studentModel = new Student($this->conn);
        $students = $studentModel->searchStudents($search);
        
        $this->json(['success' => true, 'students' => $students]);
    }

    public function manageSubject() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $this->verifyCsrfToken();

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
                    $this->json(['success' => true, 'message' => 'Subject added successfully!']);
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
                    $this->json(['success' => true, 'message' => 'Subject deleted successfully!']);
                }

            } else {
                throw new Exception('Invalid action.');
            }

        } catch (mysqli_sql_exception $e) {
            $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function manageCurriculum() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $this->verifyCsrfToken();

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
                $this->json(['success' => true, 'message' => 'Curriculum entry added successfully!', 'data' => $entry]);

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
                $this->json(['success' => true, 'message' => 'Curriculum entry updated successfully!', 'data' => $entry]);

            } elseif ($action === 'delete') {
                $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);
                if (!$curriculum_id) throw new Exception('Invalid curriculum ID.');
                
                if (!$curriculumModel->exists($curriculum_id)) {
                    throw new Exception('Curriculum entry not found.');
                }

                if ($curriculumModel->deleteEntry($curriculum_id)) {
                    $this->json(['success' => true, 'message' => 'Curriculum entry deleted successfully!']);
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
                $this->json(['success' => true, 'message' => "Curriculum saved successfully!", 'affected_rows' => $affected_rows]);

            } else {
                throw new Exception('Invalid action.');
            }

        } catch (mysqli_sql_exception $e) {
            $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function editStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $this->verifyCsrfToken();

        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['student_name'] ?? '');
        $number = trim($_POST['student_number'] ?? '');
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; 
        $birthday = trim($_POST['birthday'] ?? '');

        if (!$student_id || !$user_id) {
            $this->json(['success' => false, 'message' => 'Invalid student or user ID.'], 400);
        }

        if (empty($name) || empty($number) || !$course_id || empty($username) || empty($birthday)) {
            $this->json(['success' => false, 'message' => 'Please fill out all required fields.'], 400);
        }

        if (!empty($password) && strlen($password) < 6) {
            $this->json(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
        }

        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
            $this->json(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.'], 400);
        }
        if ($birthdayDate > new DateTime('today')) {
            $this->json(['success' => false, 'message' => 'Birthday cannot be in the future.'], 400);
        }

        $image_data = null;
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['student_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $this->json(['success' => false, 'message' => 'Invalid image file type.'], 400);
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                $this->json(['success' => false, 'message' => 'Image file is too large.'], 400);
            }
            $image_data = file_get_contents($file['tmp_name']);
        }

        $studentModel = new Student($this->conn);
        try {
            $result = $studentModel->editStudent($student_id, $user_id, $username, $password, $name, $number, $course_id, $birthday, $image_data);
            $this->json($result);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function deleteStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $this->verifyCsrfToken();

        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        if (!$studentId) {
            $this->json(['success' => false, 'message' => 'Invalid or missing student ID.'], 400);
        }
        
        $studentModel = new Student($this->conn);
        try {
            $studentModel->deleteStudent($studentId);
            $this->json(['success' => true, 'message' => 'Student deleted successfully.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function createStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $this->verifyCsrfToken();

        $name = trim($_POST['student_name'] ?? '');
        $number = trim($_POST['student_number'] ?? '');
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $birthday = trim($_POST['birthday'] ?? '');

        if (empty($name) || empty($number) || !$course_id || empty($username) || empty($password) || empty($birthday)) {
            $this->json(['success' => false, 'message' => 'Please fill out all required fields.'], 400);
        }
        if (strlen($password) < 6) {
            $this->json(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
        }

        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
            $this->json(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.'], 400);
        }
        if ($birthdayDate > new DateTime('today')) {
            $this->json(['success' => false, 'message' => 'Birthday cannot be in the future.'], 400);
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
                $this->json(['success' => false, 'message' => 'Invalid image file type.'], 400);
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                $this->json(['success' => false, 'message' => 'Image file is too large. Maximum size is 5MB.'], 400);
            }
            $image_data = file_get_contents($file['tmp_name']);
        }

        $studentModel = new Student($this->conn);
        try {
            $result = $studentModel->createStudent($username, $hashed_password, $name, $number, $course_id, $birthday, $image_data);
            $this->json($result);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getCurriculumData() {
        $this->checkAdmin();
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        if ($course_id > 0) {
            $curriculumModel = new Curriculum($this->conn);
            $entries = $curriculumModel->getEntriesByCourse($course_id);
            $this->json(['success' => true, 'entries' => $entries]);
        } else {
            $this->json(['success' => false, 'message' => 'Invalid course ID'], 400);
        }
    }

    public function getManageAccount() {
        $this->checkAdmin();
        $userModel = new \App\Models\User($this->conn);
        $admin = $userModel->getAdminDetailsByUserId($_SESSION['user_id']);
        
        if (!$admin) {
            die("<div class='alert alert-danger'>Admin details not found.</div>");
        }

        $this->render('admin/manage_account', [
            'admin' => $admin,
            'pageTitle' => "Manage Account | SIS"
        ]);
    }

    public function updateAccountProfile() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        $this->verifyCsrfToken();

        $user_id = $_SESSION['user_id'];
        $admin_id = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
        $admin_name = trim($_POST['admin_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($admin_name) || empty($username)) {
            $this->json(['success' => false, 'message' => 'Admin Name and Username are required.'], 400);
        }

        $new_password_hash = null;
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $this->json(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
            }
            if ($password !== $confirm_password) {
                $this->json(['success' => false, 'message' => 'Passwords do not match.'], 400);
            }
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
        }

        $userModel = new \App\Models\User($this->conn);
        try {
            $success = $userModel->updateAdminProfile($user_id, $admin_id, $username, $admin_name, $new_password_hash);
            if ($success) {
                // Update session if username changed? 
                // Usually session stores user_id and role, maybe username if displayed
                $this->json(['success' => true, 'message' => 'Account updated successfully!']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to update account.'], 500);
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->json(['success' => false, 'message' => 'Username already exists.'], 400);
            } else {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
        }
    }

    private function checkAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                $this->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
            http_response_code(403);
            die("<div class='alert alert-danger'>Access Denied. Invalid session or role.</div>");
        }
    }
}
?>
