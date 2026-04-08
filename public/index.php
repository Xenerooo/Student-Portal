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

define('ROOT_PATH', __DIR__ . '/..');

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

// Environment-based Error Reporting
$appEnv = portal_env('APP_ENV', 'production');
if ($appEnv === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Global Requirements
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/db_connect.php';
require_once __DIR__ . '/../core/utilities.php';

if (!defined('APP_URL')) {
    $envUrl = portal_env('APP_URL', 'http://localhost/Student-Portal');
    
    // Auto-detect URL for tunnels (ngrok) or if accessing via IP/remote host
    $hasForwardedHost = isset($_SERVER['HTTP_X_FORWARDED_HOST']);
    $isNotLocalhost = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1';

    if ($hasForwardedHost || $isNotLocalhost) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];
        
        // Extract the path (subdirectory) from the ENV URL to maintain it
        $envPath = parse_url($envUrl, PHP_URL_PATH) ?: '';
        $appUrl = $protocol . '://' . $host . rtrim($envPath, '/');
    } else {
        $appUrl = $envUrl;
    }
    
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
$urlPath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
if (empty($urlPath) || $urlPath === '/') {
    // If APP_URL is root but we're in a folder, try to detect from script name
    $urlPath = str_replace(['/public/index.php', '/index.php'], '', $_SERVER['SCRIPT_NAME'] ?? '');
}
$router->setBasePath(rtrim($urlPath, '/'));

/*====================================
 * PUBLIC ROUTES
 *====================================*/
$router->map('GET', '/', 'App\\Controllers\\AuthController#showLoginForm', 'home');
$router->map('GET', '/login', 'App\\Controllers\\AuthController#showLoginForm', 'login_form');
$router->map('POST', '/login', 'App\\Controllers\\AuthController#login', 'login_post');
$router->map('GET', '/logout', 'App\\Controllers\\AuthController#logout', 'logout');
$router->map('GET', '/health', function() {
    require __DIR__ . '/health_check.php';
}, 'health_check');

/*====================================
 * ADMIN ROUTES (Requires Admin Role)
 *====================================*/
$router->map('GET', '/admin/dashboard', 'App\\Controllers\\AdminController#dashboard', 'admin_dashboard');
$router->map('GET', '/admin/api/overview', 'App\\Controllers\\AdminController#getOverview', 'api_admin_overview');

$router->map('GET', '/admin/api/students', 'App\\Controllers\\AdminController#getStudentList', 'api_admin_students');
$router->map('GET', '/admin/api/students/search', 'App\\Controllers\\AdminController#searchStudents', 'api_admin_students_search');
$router->map('GET', '/admin/api/students/create', 'App\\Controllers\\AdminController#getCreateStudentForm', 'api_admin_student_create_form');
$router->map('GET', '/admin/api/students/edit', 'App\\Controllers\\AdminController#getEditStudentForm', 'api_admin_student_edit_form');

$router->map('POST', '/admin/api/students/store', 'App\\Controllers\\AdminController#createStudent', 'api_admin_student_store');
$router->map('POST', '/admin/api/students/update', 'App\\Controllers\\AdminController#editStudent', 'api_admin_student_update');
$router->map('POST', '/admin/api/students/delete', 'App\\Controllers\\AdminController#deleteStudent', 'api_admin_student_delete');
$router->map('POST', '/admin/api/students/sync-year', 'App\\Controllers\\AdminController#syncYearLevelApi', 'api_admin_student_sync_year');
$router->map('GET', '/admin/api/subjects', 'App\\Controllers\\AdminController#getManageSubjects', 'api_admin_subjects');
$router->map('POST', '/admin/api/subjects/manage', 'App\\Controllers\\AdminController#manageSubject', 'api_admin_subject_manage');
$router->map('GET',  '/admin/api/subjects/requisites',       'App\\Controllers\\AdminController#getSubjectRequisitesApi', 'api_admin_subject_requisites');
$router->map('POST', '/admin/api/subjects/requisites/manage', 'App\\Controllers\\AdminController#manageRequisites',       'api_admin_subject_requisites_manage');


$router->map('GET', '/admin/api/curriculum', 'App\\Controllers\\AdminController#getManageCurriculum', 'api_admin_curriculum');
$router->map('GET', '/admin/api/curriculum/data', 'App\\Controllers\\AdminController#getCurriculumData', 'api_admin_curriculum_data');
$router->map('POST', '/admin/api/curriculum/manage', 'App\\Controllers\\AdminController#manageCurriculum', 'api_admin_curriculum_manage');

$router->map('GET', '/admin/api/grades/edit', 'App\\Controllers\\AdminController#getGradeEditor', 'api_admin_grades_edit');
$router->map('GET', '/admin/api/subject/history', 'App\\Controllers\\AdminController#getSubjectHistoryApi', 'api_admin_subject_history');
$router->map('POST', '/admin/api/grades/save', 'App\\Controllers\\AdminController#saveGrade', 'api_admin_grades_save');
$router->map('GET', '/admin/api/calendar', 'App\\Controllers\\AdminController#getCalendar', 'admin_calendar');
$router->map('GET', '/admin/api/events', 'App\\Controllers\\AdminController#getEventsApi', 'api_admin_events');
$router->map('POST', '/admin/api/events/save', 'App\\Controllers\\AdminController#saveEventApi', 'api_admin_events_save');
$router->map('POST', '/admin/api/events/delete', 'App\\Controllers\\AdminController#deleteEventApi', 'api_admin_events_delete');

// Enrollment routes
$router->map('GET',  '/admin/api/students/enroll-form',       'App\\Controllers\\AdminController#getEnrollmentForm',    'api_admin_enroll_form');
$router->map('POST', '/admin/api/students/enroll',            'App\\Controllers\\AdminController#enrollStudent',        'api_admin_enroll');
$router->map('POST', '/admin/api/students/drop-subject',      'App\\Controllers\\AdminController#dropSubject',          'api_admin_drop_subject');
$router->map('POST', '/admin/api/students/delete-enrollment',    'App\\Controllers\\AdminController#deleteEnrollment',      'api_admin_delete_enrollment');

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
$router->map('GET', '/student/api/overview', 'App\\Controllers\\StudentController#getOverview', 'api_student_overview');
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
$router->map('GET', '/student/api/events', 'App\\Controllers\\StudentController#getEventsApi', 'api_student_events');

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
        $isApi = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || 
                 (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

        if ($isApi) {
            if (ob_get_length() > 0) ob_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => ($appEnv === 'development') ? $e->getMessage() : "An internal server error occurred.",
                'trace' => ($appEnv === 'development') ? $e->getTraceAsString() : null
            ]);
            exit();
        }
        http_response_code(500);
        $errorMessage = ($appEnv === 'development') ? $e->getMessage() : "An internal server error occurred. Please contact the administrator.";
        echo "<h1>Internal Server Error</h1><p>" . htmlspecialchars($errorMessage) . "</p>";
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
