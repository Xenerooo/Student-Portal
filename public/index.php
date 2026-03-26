<?php
// Secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Use secure cookies if HTTPS is on
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
ob_start();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

define('ROOT_PATH', __DIR__ . '/..');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Core/db_connect.php';
require_once __DIR__ . '/../Core/utilities.php';

function portal_env(string $key, $default = null) {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    static $fileConfig = null;
    if ($fileConfig === null) {
        $envPath = ROOT_PATH . '/.env';
        $fileConfig = file_exists($envPath) ? parse_ini_file($envPath) : [];
    }

    return $fileConfig[$key] ?? $default;
}

if (!defined('APP_URL')) {
    $appUrl = portal_env('APP_URL', 'http://localhost/Student-Portal');
    define('APP_URL', rtrim($appUrl, '/'));
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', portal_env('SMTP_HOST', 'smtp.gmail.com'));
}

if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int) portal_env('SMTP_PORT', 587));
}

if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', portal_env('SMTP_USERNAME', ''));
}

if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', portal_env('SMTP_PASSWORD', ''));
}

if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', portal_env('SMTP_FROM_EMAIL', SMTP_USERNAME));
}

if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', portal_env('SMTP_FROM_NAME', 'Student Portal'));
}

// Initialize AltoRouter
$router = new AltoRouter();
// Set base path if your project isn't at the root of the domain.
// e.g. localhost/Student-Portal
$router->setBasePath('/Student-Portal');

/*====================================
 * PUBLIC ROUTES
 *====================================*/
$router->map('GET', '/', 'App\\Controllers\\AuthController#showLoginForm', 'home');
$router->map('GET', '/login', 'App\\Controllers\\AuthController#showLoginForm', 'login_form');
$router->map('POST', '/login', 'App\\Controllers\\AuthController#login', 'login_post');
$router->map('GET', '/logout', 'App\\Controllers\\AuthController#logout', 'logout');

/*====================================
 * ADMIN ROUTES (Requires Admin Role)
 *====================================*/
$router->map('GET', '/admin/dashboard', 'App\\Controllers\\AdminController#dashboard', 'admin_dashboard');

$router->map('GET', '/admin/api/students', 'App\\Controllers\\AdminController#getStudentList', 'api_admin_students');
$router->map('GET', '/admin/api/students/search', 'App\\Controllers\\AdminController#searchStudents', 'api_admin_students_search');
$router->map('GET', '/admin/api/students/create', 'App\\Controllers\\AdminController#getCreateStudentForm', 'api_admin_student_create_form');
$router->map('GET', '/admin/api/students/edit', 'App\\Controllers\\AdminController#getEditStudentForm', 'api_admin_student_edit_form');

$router->map('POST', '/admin/api/students/store', 'App\\Controllers\\AdminController#createStudent', 'api_admin_student_store');
$router->map('POST', '/admin/api/students/update', 'App\\Controllers\\AdminController#editStudent', 'api_admin_student_update');
$router->map('POST', '/admin/api/students/delete', 'App\\Controllers\\AdminController#deleteStudent', 'api_admin_student_delete');

$router->map('GET', '/admin/api/subjects', 'App\\Controllers\\AdminController#getManageSubjects', 'api_admin_subjects');
$router->map('POST', '/admin/api/subjects/manage', 'App\\Controllers\\AdminController#manageSubject', 'api_admin_subject_manage');

$router->map('GET', '/admin/api/curriculum', 'App\\Controllers\\AdminController#getManageCurriculum', 'api_admin_curriculum');
$router->map('GET', '/admin/api/curriculum/data', 'App\\Controllers\\AdminController#getCurriculumData', 'api_admin_curriculum_data');
$router->map('POST', '/admin/api/curriculum/manage', 'App\\Controllers\\AdminController#manageCurriculum', 'api_admin_curriculum_manage');

$router->map('GET', '/admin/api/grades/edit', 'App\\Controllers\\AdminController#getGradeEditor', 'api_admin_grades_edit');
$router->map('GET', '/admin/api/subject/history', 'App\\Controllers\\AdminController#getSubjectHistoryApi', 'api_admin_subject_history');
$router->map('POST', '/admin/api/grades/save', 'App\\Controllers\\AdminController#saveGrade', 'api_admin_grades_save');

// Enrollment routes
$router->map('GET',  '/admin/api/students/enroll-form',       'App\\Controllers\\AdminController#getEnrollmentForm',    'api_admin_enroll_form');
$router->map('POST', '/admin/api/students/enroll',            'App\\Controllers\\AdminController#enrollStudent',        'api_admin_enroll');
$router->map('POST', '/admin/api/students/drop-subject',      'App\\Controllers\\AdminController#dropSubject',          'api_admin_drop_subject');
$router->map('GET',  '/admin/api/students/enroll-form-subjects', 'App\\Controllers\\AdminController#getEnrollFormSubjects', 'api_admin_enroll_form_subjects');
$router->map('GET',  '/admin/api/students/enrollment-history','App\\Controllers\\AdminController#getEnrollmentHistory', 'api_admin_enrollment_history');
$router->map('GET',  '/admin/api/students/retake-candidates', 'App\\Controllers\\AdminController#getRetakeCandidates', 'api_admin_retake_candidates');
$router->map('GET',  '/admin/api/subjects/list',              'App\\Controllers\\AdminController#getSubjectsList',       'api_admin_subjects_list');

$router->map('GET', '/admin/api/account', 'App\\Controllers\\AdminController#getManageAccount', 'api_admin_manage_account');
$router->map('POST', '/admin/api/account/update', 'App\\Controllers\\AdminController#updateAccountProfile', 'api_admin_account_update');

/*====================================
 * STUDENT ROUTES (Requires Student Role)
 *====================================*/
$router->map('GET', '/student/dashboard', 'App\\Controllers\\StudentController#dashboard', 'student_dashboard');
$router->map('GET', '/student/api/info', 'App\\Controllers\\StudentController#getStudentInfo', 'api_student_info');
$router->map('GET', '/student/api/grades', 'App\\Controllers\\StudentController#getStudentGrades', 'api_student_grades');
$router->map('GET', '/student/api/grades/progress', 'App\\Controllers\\StudentController#getGradesProgress', 'api_student_grades_progress');
$router->map('GET', '/student/api/grades/history', 'App\\Controllers\\StudentController#getScholasticHistory', 'api_student_grades_history');
$router->map('GET', '/student/printables/academic-record', 'App\\Controllers\\StudentController#exportAcademicRecord', 'student_print_academic_record');
$router->map('GET', '/student/printables/curriculum-progress', 'App\\Controllers\\StudentController#exportCurriculumProgress', 'student_print_curriculum_progress');
$router->map('GET', '/student/change-password', 'App\\Controllers\\StudentController#showChangePasswordForm', 'student_change_password_form');
$router->map('POST', '/student/api/password/change', 'App\\Controllers\\StudentController#changePassword', 'api_student_password_change');

// New student API routes
$router->map('GET', '/student/api/grades/term',  'App\\Controllers\\StudentController#getGradesByTerm',   'api_student_grades_term');
$router->map('GET', '/student/api/grades/terms', 'App\\Controllers\\StudentController#getEnrolledTerms',  'api_student_grades_terms');

/*====================================
 * MATCH AND ROUTE
 *====================================*/
$match = $router->match();

if (is_array($match) && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else if (is_array($match) && is_string($match['target'])) {
    // String mapping like 'App\Controllers\AdminController#dashboard'
    list($controllerNamespace, $methodName) = explode('#', $match['target']);
    
    try {
        // Connect to DB once and inject into controller
        $conn = connect();
        $controller = new $controllerNamespace($conn);
        
        if (method_exists($controller, $methodName)) {
            call_user_func_array([$controller, $methodName], $match['params']);
        } else {
            throw new Exception("Method $methodName not found in $controllerNamespace.");
        }
    } catch (Throwable $e) {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            if (ob_get_length() > 0) ob_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
        http_response_code(500);
        echo "<h1>Internal Server Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    // 404
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        if (ob_get_length() > 0) ob_clean();
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'API endpoint not found.']);
        exit();
    }
    http_response_code(404);
    echo "404 Not Found.";
}
?>
