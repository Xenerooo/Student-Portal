<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/db_connect.php';

// Initialize AltoRouter
$router = new AltoRouter();
// Set base path if your project isn't at the root of the domain.
// e.g. localhost/Student-Portal
$router->setBasePath('/Student-Portal');

/*====================================
 * PUBLIC ROUTES
 *====================================*/
$router->map('GET', '/', 'AControlluther#showLoginForm', 'home');
$router->map('GET', '/login', 'AuthController#showLoginForm', 'login_form');
$router->map('POST', '/login', 'AuthController#login', 'login_post');
$router->map('GET', '/logout', 'AuthController#logout', 'logout');

/*====================================
 * ADMIN ROUTES (Requires Admin Role)
 *====================================*/
$router->map('GET', '/admin/dashboard', 'AdminController#dashboard', 'admin_dashboard');
// Admin AJAX Routes
$router->map('GET', '/admin/api/students', 'AdminController#getStudentList', 'api_admin_students');
$router->map('GET', '/admin/api/students/search', 'AdminController#searchStudents', 'api_admin_students_search');
$router->map('GET', '/admin/api/students/create', 'AdminController#getCreateStudentForm', 'api_admin_student_create_form');
$router->map('GET', '/admin/api/students/edit', 'AdminController#getEditStudentForm', 'api_admin_student_edit_form');

$router->map('POST', '/admin/api/students/store', 'AdminController#createStudent', 'api_admin_student_store');
$router->map('POST', '/admin/api/students/update', 'AdminController#editStudent', 'api_admin_student_update');
$router->map('POST', '/admin/api/students/delete', 'AdminController#deleteStudent', 'api_admin_student_delete');

$router->map('GET', '/admin/api/subjects', 'AdminController#getManageSubjects', 'api_admin_subjects');
$router->map('POST', '/admin/api/subjects/manage', 'AdminController#manageSubject', 'api_admin_subject_manage');

$router->map('GET', '/admin/api/curriculum', 'AdminController#getManageCurriculum', 'api_admin_curriculum');
$router->map('GET', '/admin/api/curriculum/data', 'AdminController#getCurriculumData', 'api_admin_curriculum_data');
$router->map('POST', '/admin/api/curriculum/manage', 'AdminController#manageCurriculum', 'api_admin_curriculum_manage');

$router->map('GET', '/admin/api/grades/edit', 'AdminController#getGradeEditor', 'api_admin_grades_edit');
$router->map('POST', '/admin/api/grades/save', 'AdminController#saveGrade', 'api_admin_grades_save');

/*====================================
 * STUDENT ROUTES (Requires Student Role)
 *====================================*/
$router->map('GET', '/student/dashboard', 'StudentController#dashboard', 'student_dashboard');
// Student AJAX Routes
$router->map('GET', '/student/api/info', 'StudentController#getStudentInfo', 'api_student_info');
$router->map('GET', '/student/api/grades', 'StudentController#getStudentGrades', 'api_student_grades');
$router->map('GET', '/student/api/grades/data', 'StudentController#getGradesData', 'api_student_grades_data');
$router->map('POST', '/student/api/password/change', 'StudentController#changePassword', 'api_student_password_change');

/*====================================
 * MATCH AND ROUTE
 *====================================*/
$match = $router->match();

if (is_array($match) && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else if (is_array($match) && is_string($match['target'])) {
    // String mapping like 'AdminController#dashboard'
    list($controllerName, $methodName) = explode('#', $match['target']);
    
    // Require the controller file dynamically
    $controllerPath = __DIR__ . '/controllers/' . $controllerName . '.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        $controller = new $controllerName();
        call_user_func_array([$controller, $methodName], $match['params']);
    } else {
        http_response_code(500);
        echo "Controller $controllerName not found.";
    }
} else {
    // 404
    http_response_code(404);
    echo "404 Not Found.";
}
?>