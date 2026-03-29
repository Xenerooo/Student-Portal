<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Curriculum;
use App\Models\Grade;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use Exception;
use DateTime;
use mysqli_sql_exception;
use Throwable;

class AdminController extends BaseController {

    public function dashboard() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: " . APP_URL . "/");
            exit();
        }

        $this->generateCsrfToken();
        $this->render('admin/dashboard', [
            'pageTitle' => "Admin Dashboard | SIS"
        ]);
    }

    public function getOverview() {
        $this->checkAdmin();
        
        $studentModel = new Student($this->conn);
        $subjectModel = new Subject($this->conn);

        $totalStudents = $studentModel->getTotalStudentsCount();
        $totalSubjects = $subjectModel->getTotalSubjectsCount();
        $recentStudents = $studentModel->getRecentStudents(5);

        $totalEnrolled = 0;
        $enrollRes = $this->conn->query("SELECT COUNT(DISTINCT student_id) as total FROM enrollments");
        if ($enrollRes) {
            $row = $enrollRes->fetch_assoc();
            $totalEnrolled = (int)$row['total'];
        }

        $this->render('admin/overview', [
            'totalStudents' => $totalStudents,
            'totalSubjects' => $totalSubjects,
            'totalEnrolled' => $totalEnrolled,
            'recentStudents' => $recentStudents
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
            header("Location: " . APP_URL . "/admin/dashboard");
            exit();
        }

        // Fetch Student Details
        $studentModel = new Student($this->conn);
        $student_details = $studentModel->getStudentById($student_id);

        if (!$student_details) {
            die("<div class='alert alert-danger'>Student not found.</div>");
        }

        // Fetch enrolled terms to populate filters
        $enrollModel = new \App\Models\Enrollment($this->conn);
        $enrolled_terms = $enrollModel->getTermsWithEnrollment($student_id);

        // Defaults for term selection if not provided in GET
        if (!empty($enrolled_terms)) {
            $latest = $enrolled_terms[0];
            $default_sy = $latest['school_year'];
            $default_sem = $latest['semester'];
        } else {
            $currMonth = (int)date('m');
            $currYear = (int)date('Y');
            $default_sy = ($currMonth >= 6) ? "$currYear-" . ($currYear + 1) : ($currYear - 1) . "-$currYear";
            $default_sem = "1st Semester";
        }
        
        $current_school_year = trim($_GET['school_year'] ?? $default_sy);
        $current_semester = trim($_GET['semester'] ?? $default_sem);

        // Fetch enrolled subjects for the selected term
        $grades_data = $enrollModel->getEnrollmentsByTerm($student_id, $current_school_year, $current_semester);

        $this->render('admin/grade_editor', [
            'student_id' => $student_id,
            'student_details' => $student_details,
            'grades_data' => $grades_data,
            'current_school_year' => $current_school_year,
            'current_semester' => $current_semester,
            'enrolled_terms' => $enrolled_terms
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
        $currMonth = (int)date('m');
        $currYear = (int)date('Y');
        $default_sy = ($currMonth >= 6) ? "$currYear-" . ($currYear + 1) : ($currYear - 1) . "-$currYear";
        
        $semester = trim($_POST['semester'] ?? '1st Semester');
        $school_year = trim($_POST['school_year'] ?? $default_sy);

        if (!$student_id || !isset($_POST['grades']) || !is_array($_POST['grades'])) {
            $this->json(['success' => false, 'message' => 'Invalid student ID or missing grades.'], 400);
        }

        // The 'grades' array from POST might contain objects {grade, prelim, midterm, prefinal, finals}
        $grades = $_POST['grades'];

        $gradeModel = new Grade($this->conn);
        try {
            $gradeModel->upsertGrades($student_id, $grades, $semester, $school_year);
            $this->json(['success' => true, 'message' => "Grades updated successfully!"]);
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
                $subject_name = trim($_POST['subject_name'] ?? '');
                $units = filter_input(INPUT_POST, 'units', FILTER_VALIDATE_INT);

                if (empty($subject_code) || empty($subject_name)) {
                    throw new Exception('Subject code and name are required.');
                }
                if (!$units || $units < 1 || $units > 10) {
                    throw new Exception('Units must be between 1 and 10.');
                }

                if ($subjectModel->subjectExists($subject_code)) {
                    throw new Exception('Subject code already exists.');
                }

                if ($subjectModel->addSubject($subject_code, $subject_name, $units)) {
                    $this->json(['success' => true, 'message' => 'Subject added successfully!']);
                } else {
                    throw new Exception('Failed to add subject.');
                }

            } elseif ($action === 'edit') {
                $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
                $subject_code = trim($_POST['subject_code'] ?? '');
                $subject_name = trim($_POST['subject_name'] ?? '');
                $units = filter_input(INPUT_POST, 'units', FILTER_VALIDATE_INT);

                if (!$subject_id) throw new Exception('Invalid subject ID.');
                if (empty($subject_code) || empty($subject_name)) throw new Exception('Subject code and name are required.');
                if (!$units || $units < 1 || $units > 10) throw new Exception('Units must be between 1 and 10.');

                if ($subjectModel->updateSubject($subject_id, $subject_code, $subject_name, $units)) {
                    $this->json(['success' => true, 'message' => 'Subject updated successfully!']);
                } else {
                    throw new Exception('Failed to update subject.');
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

                if (!$course_id || !$subject_id) {
                    throw new Exception('Course and subject are required.');
                }
                if (!$year_level || $year_level < 1 || $year_level > 4) {
                    throw new Exception('Year level must be between 1 and 4.');
                }
                if (!$semester || ($semester != 1 && $semester != 2)) {
                    throw new Exception('Semester must be 1 or 2.');
                }

                $entry = $curriculumModel->addEntry($course_id, $subject_id, $year_level, $semester);
                $this->json(['success' => true, 'message' => 'Curriculum entry added successfully!', 'data' => $entry]);

            } elseif ($action === 'update') {
                $curriculum_id = filter_input(INPUT_POST, 'curriculum_id', FILTER_VALIDATE_INT);
                $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
                $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
                $subject_name = trim($_POST['subject_name'] ?? '');
                $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
                $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);

                if (!$curriculum_id || !$course_id || !$subject_id) {
                    throw new Exception('All fields are required.');
                }

                if (!$curriculumModel->exists($curriculum_id)) {
                    throw new Exception('Curriculum entry not found.');
                }

                $entry = $curriculumModel->updateEntry($curriculum_id, $course_id, $subject_id, $year_level, $semester);
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
        $address = trim($_POST['address'] ?? '');
        $last_school_attended = trim($_POST['last_school_attended'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $place_of_birth = trim($_POST['place_of_birth'] ?? '');

        if (!$student_id || !$user_id) {
            $this->json(['success' => false, 'message' => 'Invalid student or user ID.'], 400);
        }

        if (
            empty($name) || empty($number) || !$course_id || empty($username) || empty($birthday) ||
            empty($address) || empty($last_school_attended) || empty($contact_number) ||
            empty($email) || empty($place_of_birth)
        ) {
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
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
            $result = $studentModel->editStudent(
                $student_id,
                $user_id,
                $username,
                $password,
                $name,
                $number,
                $course_id,
                $birthday,
                $image_data,
                $address,
                $last_school_attended,
                $contact_number,
                $email,
                $place_of_birth
            );
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
        $birthday = trim($_POST['birthday'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $last_school_attended = trim($_POST['last_school_attended'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $place_of_birth = trim($_POST['place_of_birth'] ?? '');

        if (
            empty($name) || empty($number) || !$course_id || empty($username) ||
            empty($birthday) || empty($address) || empty($last_school_attended) ||
            empty($contact_number) || empty($email) || empty($place_of_birth)
        ) {
            $this->json(['success' => false, 'message' => 'Please fill out all required fields.'], 400);
        }

        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
            $this->json(['success' => false, 'message' => 'Invalid birthday format. Use YYYY-MM-DD.'], 400);
        }
        if ($birthdayDate > new DateTime('today')) {
            $this->json(['success' => false, 'message' => 'Birthday cannot be in the future.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
        }

        $temporary_password = \generateTemporaryPassword();
        $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);
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
            $result = $studentModel->createStudent(
                $username,
                $hashed_password,
                $name,
                $number,
                $course_id,
                $birthday,
                $image_data,
                $address,
                $last_school_attended,
                $contact_number,
                $email,
                $place_of_birth
            );

            $emailResult = \sendWelcomeEmail($email, $name, $username, $temporary_password);
            $message = $result['message'] ?? 'Student created successfully!';
            if (!empty($emailResult['success'])) {
                $message .= ' Welcome email sent to the student.';
            } else {
                $message .= ' Student account was created, but the welcome email could not be sent: ' . ($emailResult['message'] ?? 'Unknown email error.');
            }

            $this->json([
                'success' => true,
                'message' => $message,
                'user_id' => $result['user_id'] ?? null,
                'email_sent' => !empty($emailResult['success'])
            ]);
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

    public function getEnrollmentForm() {
        $this->checkAdmin();
        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
        if (!$student_id) die("<div class='alert alert-danger'>Invalid student ID.</div>");

        $studentModel = new Student($this->conn);
        $student = $studentModel->getStudentById($student_id);
        if (!$student) die("<div class='alert alert-danger'>Student not found.</div>");

        // Generate school years array
        $currYear = (int)date('Y');
        $years = [];
        for ($i = $currYear + 1; $i >= 2000; $i--) {
            $years[] = "$i-" . ($i + 1);
        }

        $this->render('admin/enrollment_form', [
            'student' => $student,
            'student_id' => $student_id,
            'years' => $years
        ]);
    }

    public function enrollStudent() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['success'=>false,'message'=>'Method not allowed.'],405);
        $this->verifyCsrfToken();

        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $school_year = trim($_POST['school_year'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $subject_ids = array_filter(array_map('intval', (array)($_POST['subject_ids'] ?? [])));
        $retake_ids  = array_filter(array_map('intval', (array)($_POST['retake_subject_ids'] ?? [])));
        $force_enroll = (isset($_POST['force_enroll']) && ($_POST['force_enroll'] === 'true' || $_POST['force_enroll'] === '1'));

        if (!$student_id) $this->json(['success'=>false,'message'=>'Invalid student ID.'],400);
        if (empty($school_year)) $this->json(['success'=>false,'message'=>'School year required.'],400);
        if (!in_array($semester, ['1st Semester','2nd Semester','Summer']))
            $this->json(['success'=>false,'message'=>'Invalid semester.'],400);
        if (empty($subject_ids)) $this->json(['success'=>false,'message'=>'No subjects selected.'],400);

        try {
            $enrollModel = new Enrollment($this->conn);

            // Server-side requisite check (unless forced by Admin)
            if (!$force_enroll) {
                foreach ($subject_ids as $sid) {
                    $missing = $enrollModel->getMissingRequisites($student_id, $sid, $subject_ids);
                    if (!empty($missing)) {
                        $this->json([
                            'success' => false, 
                            'type' => 'requisite_violation',
                            'message' => 'Requisite violation detected.',
                            'subject_id' => $sid,
                            'missing_requisites' => $missing
                        ], 400);
                        return;
                    }
                }
            }

            $allowedRetakeIds = array_map(
                'intval',
                array_column($enrollModel->getLatestFailedRetakeCandidates($student_id, $school_year, $semester), 'subject_id')
            );
            $invalidRetakes = array_diff($retake_ids, $allowedRetakeIds);
            if (!empty($invalidRetakes)) {
                $this->json(['success'=>false,'message'=>'Some retake subjects are not valid for the selected term.'],400);
            }
            $count = $enrollModel->bulkEnroll($student_id, array_values($subject_ids), $school_year, $semester, array_values($retake_ids));
            $this->json(['success'=>true,'message'=>"Enrolled in $count subject(s) successfully."]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>'Enrollment failed: '.$e->getMessage()],500);
        }
    }

    public function dropSubject() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['success'=>false,'message'=>'Method not allowed.'],405);
        $this->verifyCsrfToken();

        $enrollment_id = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);
        if (!$enrollment_id) $this->json(['success'=>false,'message'=>'Invalid enrollment ID.'],400);

        try {
            $enrollModel = new Enrollment($this->conn);
            if ($enrollModel->dropSubject($enrollment_id))
                $this->json(['success'=>true,'message'=>'Subject dropped.']);
            else
                $this->json(['success'=>false,'message'=>'Enrollment not found.'],404);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function getEnrollmentHistory() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
        if (!$student_id) $this->json(['success'=>false,'message'=>'Invalid student ID.'],400);

        try {
            $enrollModel = new Enrollment($this->conn);
            $history = $enrollModel->getEnrollmentHistory($student_id);
            $this->json(['success'=>true,'history'=>$history]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function getRetakeCandidates() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
        $school_year = trim($_GET['school_year'] ?? '');
        $semester = trim($_GET['semester'] ?? '');
        if (!$student_id) $this->json(['success'=>false,'message'=>'Invalid student ID.'],400);

        try {
            $enrollModel = new Enrollment($this->conn);
            $candidates = $enrollModel->getLatestFailedRetakeCandidates(
                $student_id,
                $school_year !== '' ? $school_year : null,
                $semester !== '' ? $semester : null
            );
            $this->json(['success'=>true,'retake_candidates'=>$candidates]);
        } catch (\Throwable $e) {
            $this->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function getEnrollFormSubjects() {
        $this->checkAdmin();
        header('Content-Type: application/json');

        $student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
        $year_level = filter_input(INPUT_GET, 'year_level', FILTER_VALIDATE_INT);
        $semester_int = filter_input(INPUT_GET, 'semester_int', FILTER_VALIDATE_INT);
        $school_year = trim($_GET['school_year'] ?? '');
        $semester = trim($_GET['semester'] ?? '');

        if (!$student_id || !$year_level || !$semester_int) {
            $this->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
            return;
        }

        $enrollModel = new Enrollment($this->conn);
        try {
            $data = $enrollModel->getSubjectsForEnrollment(
                $student_id,
                $year_level,
                $semester_int,
                $school_year !== '' ? $school_year : null,
                $semester !== '' ? $semester : null
            );
            $this->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSubjectsList() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        $subjectModel = new Subject($this->conn);
        $subjects = $subjectModel->getAllSubjects();
        $this->json(['success' => true, 'subjects' => $subjects]);
    }

    public function getCalendar() {
        $this->checkAdmin();
        $this->render('admin/calendar', [
            'pageTitle' => "School Calendar | SIS"
        ]);
    }

    public function getEventsApi() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        
        error_log("Calendar Get API Call (Admin): start=$start, end=$end");
        
        try {
            $eventModel = new Event($this->conn);
            $events = $eventModel->getExpandedEvents($start, $end);
            error_log("Calendar Get API Response (Admin): " . count($events) . " events found.");
            $this->json(['success' => true, 'events' => $events]);
        } catch (\Throwable $e) {
            error_log("Calendar Get API Error (Admin): " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveEventApi() {
        $this->checkAdmin();
        $this->verifyCsrfToken();
        header('Content-Type: application/json');
        
        // Handle JSON or POST data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $data = [
            'id' => $input['id'] ?? null,
            'title' => trim($input['title'] ?? ''),
            'description' => trim($input['description'] ?? ''),
            'location' => trim($input['location'] ?? ''),
            'start_date' => str_replace('T', ' ', $input['start_date'] ?? ''),
            'end_date' => str_replace('T', ' ', $input['end_date'] ?? ''),
            'color' => $input['color'] ?? '#3788d8',
            'all_day' => (isset($input['all_day']) && ($input['all_day'] === 'true' || $input['all_day'] === 1 || $input['all_day'] === true)) ? 1 : 0,
            'rrule' => trim($input['rrule'] ?? ''),
            'created_by' => $_SESSION['user_id'] ?? null
        ];

        error_log("Calendar Save API Call: " . json_encode($data));

        if (empty($data['title']) || empty($data['start_date']) || empty($data['end_date'])) {
            $this->json(['success' => false, 'message' => 'Title and dates are required.'], 400);
        }

        $eventModel = new Event($this->conn);
        $result = false;
        if (!empty($data['id'])) {
            $result = $eventModel->updateEvent($data);
        } else {
            $result = $eventModel->createEvent($data);
        }

        if ($result) {
            $this->json(['success' => true, 'message' => 'Event saved successfully!']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to save event.'], 500);
        }
    }

    public function deleteEventApi() {
        $this->checkAdmin();
        $this->verifyCsrfToken();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_POST['id'] ?? null;
        $id = filter_var($id, FILTER_VALIDATE_INT);

        if (!$id) {
            $this->json(['success' => false, 'message' => 'Invalid event ID.'], 400);
        }
        $eventModel = new Event($this->conn);
        if ($eventModel->deleteEvent($id)) {
            $this->json(['success' => true, 'message' => 'Event deleted successfully!']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to delete event.'], 500);
        }
    }

    public function getSubjectRequisitesApi() {
        $this->checkAdmin();
        header('Content-Type: application/json');
        $subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
        if (!$subject_id) $this->json(['success' => false, 'message' => 'Invalid subject ID.'], 400);

        try {
            $subjectModel = new Subject($this->conn);
            $requisites = $subjectModel->getRequisites($subject_id);
            $this->json(['success' => true, 'requisites' => $requisites]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function manageRequisites() {
        $this->checkAdmin();
        $this->verifyCsrfToken();
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? '';
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $subjectModel = new Subject($this->conn);

        try {
            if ($action === 'add') {
                $required_id = filter_input(INPUT_POST, 'required_id', FILTER_VALIDATE_INT);
                $type = $_POST['type'] ?? 'prerequisite';
                if (!$subject_id || !$required_id) throw new \Exception("Invalid parameters.");
                if ($subject_id === $required_id) throw new \Exception("A subject cannot require itself.");
                
                if ($subjectModel->addRequisite($subject_id, $required_id, $type)) {
                    $this->json(['success' => true, 'message' => 'Requisite added successfully!']);
                } else {
                    throw new \Exception("Failed to add requisite.");
                }
            } elseif ($action === 'delete') {
                $prerequisite_id = filter_input(INPUT_POST, 'prerequisite_id', FILTER_VALIDATE_INT);
                if (!$prerequisite_id) throw new \Exception("Invalid requisite ID.");
                
                if ($subjectModel->deleteRequisite($prerequisite_id)) {
                    $this->json(['success' => true, 'message' => 'Requisite deleted successfully!']);
                } else {
                    throw new \Exception("Failed to delete requisite.");
                }
            } else {
                throw new \Exception("Invalid action.");
            }
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
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
