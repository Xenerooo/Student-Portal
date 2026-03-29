<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Grade;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Event;
use Throwable;

class StudentController extends BaseController {

    public function dashboard() {
        if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
            header("Location: " . APP_URL . "/");
            exit();
        }

        if (!empty($_SESSION['must_change_password'])) {
            header("Location: " . APP_URL . "/student/change-password");
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

    public function getOverview() {
        $this->checkStudent();
        $student_id = $_SESSION['student_id'];
        
        $studentModel = new \App\Models\Student($this->conn);
        $student = $studentModel->getStudentDashboardData($student_id);
        
        $this->render('student/overview', [
            'student' => $student
        ]);
    }

    public function showChangePasswordForm() {
        $this->checkStudent();

        $userModel = new User($this->conn);
        $account = $userModel->getUserAccountDetails($_SESSION['student_id']);

        $this->generateCsrfToken();
        $this->render('student/change_password', [
            'pageTitle' => 'Change Password | SIS',
            'account' => $account,
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
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->json(['success' => false, 'message' => 'Please provide your current password, new password, and confirmation.'], 400);
        }
        if (strlen($newPassword) < 6) {
            $this->json(['success' => false, 'message' => 'New password must be at least 6 characters.'], 400);
        }
        if ($newPassword !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'New password and confirmation do not match.'], 400);
        }

        $userModel = new User($this->conn);
        $row = $userModel->getUserAccountDetails($student_id);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'User account not found.'], 404);
        }

        if (!password_verify($oldPassword, $row['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Old password is incorrect.'], 401);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($userModel->updatePassword($row['user_id'], $newHash)) {
            $_SESSION['must_change_password'] = 0;
            $this->json([
                'success' => true,
                'message' => 'Password updated successfully.',
                'redirect' => APP_URL . '/student/dashboard'
            ]);
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

    public function exportAcademicRecord() {
        $this->checkStudent();

        $student_id = $_SESSION['student_id'];
        $studentModel = new \App\Models\Student($this->conn);
        $gradeModel = new Grade($this->conn);
        $enrollModel = new Enrollment($this->conn);

        $student = $studentModel->getStudentById($student_id);
        if (!$student) {
            http_response_code(404);
            die("<div class='alert alert-danger'>Student info not found.</div>");
        }

        $terms = $enrollModel->getTermsWithEnrollment($student_id);
        $latestTerm = $terms[0] ?? null;

        $currMonth = (int)date('m');
        $currYear = (int)date('Y');
        $defaultSchoolYear = ($currMonth >= 6) ? "$currYear-" . ($currYear + 1) : ($currYear - 1) . "-$currYear";
        $defaultSemester = "1st Semester";

        $schoolYear = trim($_GET['school_year'] ?? ($latestTerm['school_year'] ?? $defaultSchoolYear));
        $semester = trim($_GET['semester'] ?? ($latestTerm['semester'] ?? $defaultSemester));

        $termGrades = [];
        try {
            $termGrades = $enrollModel->getEnrollmentsByTerm($student_id, $schoolYear, $semester);
        } catch (\Throwable $e) {
            $termGrades = [];
        }

        $scholasticHistory = $gradeModel->getScholasticHistory($student_id);
        $curriculumProgress = $gradeModel->getCurriculumProgress($student_id);

        $termSummary = $this->buildGradeSummary($termGrades, true);
        $overallSummary = $this->buildGradeSummary($scholasticHistory, false);
        $groupedHistory = $this->groupScholasticHistory($scholasticHistory);

        $this->render('student/academic_record_print', [
            'pageTitle' => 'Academic Record | SIS',
            'student' => $student,
            'selectedSchoolYear' => $schoolYear,
            'selectedSemester' => $semester,
            'termGrades' => $termGrades,
            'termSummary' => $termSummary,
            'overallSummary' => $overallSummary,
            'scholasticHistory' => $scholasticHistory,
            'groupedHistory' => $groupedHistory,
            'curriculumProgress' => $curriculumProgress,
            'generatedAt' => date('F j, Y g:i A'),
        ]);
    }

    public function exportCurriculumProgress() {
        $this->checkStudent();

        $student_id = $_SESSION['student_id'];
        $studentModel = new \App\Models\Student($this->conn);
        $gradeModel = new Grade($this->conn);

        $student = $studentModel->getStudentById($student_id);
        if (!$student) {
            http_response_code(404);
            die("<div class='alert alert-danger'>Student info not found.</div>");
        }

        $curriculumProgress = $gradeModel->getCurriculumProgress($student_id);
        $summary = $this->buildCurriculumSummary($curriculumProgress);
        $returnTo = trim($_GET['return_to'] ?? '') ?: APP_URL . '/student/dashboard?view=get_student_grades';

        $this->render('student/curriculum_progress_print', [
            'pageTitle' => 'Curriculum Progress | SIS',
            'student' => $student,
            'curriculumProgress' => $curriculumProgress,
            'summary' => $summary,
            'generatedAt' => date('F j, Y g:i A'),
            'returnTo' => $returnTo,
        ]);
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

    public function getEventsApi() {
        $this->checkStudent();
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        
        error_log("Calendar Get API Call (Student): start=$start, end=$end");

        try {
            $eventModel = new Event($this->conn);
            $events = $eventModel->getExpandedEvents($start, $end);
            error_log("Calendar Get API Response (Student): " . count($events) . " events found.");
            $this->json(['success' => true, 'events' => $events]);
        } catch (\Throwable $e) {
            error_log("Calendar Get API Error (Student): " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function checkStudent() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'admin'])) {
            $this->json(['success' => false, 'message' => 'Access Denied.'], 403);
            exit;
        }
    }

    private function buildGradeSummary(array $records, bool $isTerm): array {
        $totalUnits = 0;
        $weightedSum = 0.0;
        $unitsForGwa = 0;
        $gradedCount = 0;
        $hasIncomplete = false;
        $passedCount = 0;
        $failedCount = 0;

        foreach ($records as $row) {
            $units = (int)($row['units'] ?? 0);
            $grade = isset($row['grade']) && $row['grade'] !== '' ? (float)$row['grade'] : null;
            $remarks = (string)($row['remarks'] ?? '');
            $status = (string)($row['status'] ?? '');

            $totalUnits += $units;

            if ($grade !== null) {
                $gradedCount++;
            }
            if ($remarks === 'Incomplete' || $status === 'incomplete') {
                $hasIncomplete = true;
            }

            if ($grade !== null && !in_array($remarks, ['Incomplete', 'Dropped']) && !in_array($status, ['incomplete', 'dropped'])) {
                $weightedSum += ($grade * $units);
                $unitsForGwa += $units;
                if ($remarks === 'Passed' || $grade <= 3.00) {
                    $passedCount++;
                } else if ($remarks === 'Failed' || $grade === 5.00) {
                    $failedCount++;
                }
            }
        }

        $gwa = $unitsForGwa > 0 ? round($weightedSum / $unitsForGwa, 2) : null;
        $standing = 'Good Standing';
        if ($hasIncomplete) {
            $standing = 'Has Incomplete';
        } else if ($failedCount >= 3 || ($gwa !== null && $gwa > 3.00)) {
            $standing = 'Probation';
        } else if ($failedCount > 0) {
            $standing = 'Warning';
        } else if ($gwa !== null && $gwa <= 1.50 && $passedCount > 0) {
            $standing = 'Honor Roll';
        }

        return [
            'total_units' => $totalUnits,
            'gwa' => $gwa,
            'graded_count' => $gradedCount,
            'has_incomplete' => $hasIncomplete,
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'standing' => $standing,
            'label' => $isTerm ? 'Term' : 'Overall',
        ];
    }

    private function groupScholasticHistory(array $history): array {
        $grouped = [];
        foreach ($history as $row) {
            $key = ($row['school_year'] ?? 'Unknown') . '|' . ($row['semester'] ?? 'Unknown');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'school_year' => $row['school_year'] ?? 'Unknown',
                    'semester' => $row['semester'] ?? 'Unknown',
                    'items' => [],
                ];
            }
            $grouped[$key]['items'][] = $row;
        }

        return array_values($grouped);
    }

    private function buildCurriculumSummary(array $curriculumProgress): array {
        $yearGroups = 0;
        $semesterGroups = 0;
        $subjectsTotal = 0;
        $subjectsPassed = 0;
        $subjectsIncomplete = 0;
        $subjectsFailed = 0;
        $subjectsPending = 0;
        $totalUnits = 0;
        $passedUnits = 0;

        foreach ($curriculumProgress as $year => $semesters) {
            $yearGroups++;
            foreach ($semesters as $semester => $subjects) {
                $semesterGroups++;
                foreach ($subjects as $subject) {
                    $subjectsTotal++;
                    $units = (int)($subject['units'] ?? 0);
                    $totalUnits += $units;
                    $grade = isset($subject['grade']) && $subject['grade'] !== '' ? (float)$subject['grade'] : null;
                    $remarks = (string)($subject['remarks'] ?? '');
                    $status = (string)($subject['enrollment_status'] ?? '');

                    if ($remarks === 'Passed' || ($grade !== null && $grade <= 3.00)) {
                        $subjectsPassed++;
                        $passedUnits += $units;
                    } elseif ($remarks === 'Incomplete' || $status === 'incomplete') {
                        $subjectsIncomplete++;
                    } elseif ($remarks === 'Failed' || $status === 'failed' || $grade === 5.00) {
                        $subjectsFailed++;
                    } else {
                        $subjectsPending++;
                    }
                }
            }
        }

        return [
            'year_groups' => $yearGroups,
            'semester_groups' => $semesterGroups,
            'subjects_total' => $subjectsTotal,
            'subjects_passed' => $subjectsPassed,
            'subjects_incomplete' => $subjectsIncomplete,
            'subjects_failed' => $subjectsFailed,
            'subjects_pending' => $subjectsPending,
            'total_units' => $totalUnits,
            'passed_units' => $passedUnits,
        ];
    }
}
?>
