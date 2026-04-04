<?php
/**
 * System Health Check Utility
 * Checks essential system components and configurations.
 */

// Basic security: only allow admin or local access?
// For now, simple check.

require_once __DIR__ . '/../core/db_connect.php';

header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'healthy',
    'checks' => []
];

function addCheck(&$results, $name, $status, $message = '') {
    $results['checks'][$name] = [
        'status' => $status ? 'ok' : 'fail',
        'message' => $message
    ];
    if (!$status) {
        $results['status'] = 'unhealthy';
    }
}

// 1. PHP Version
addCheck($results, 'php_version', version_compare(PHP_VERSION, '7.4.0', '>='), 'Current Version: ' . PHP_VERSION);

// 2. Required Extensions
$requiredExtensions = ['mysqli', 'fileinfo', 'mbstring', 'gd', 'json'];
foreach ($requiredExtensions as $ext) {
    $exists = extension_loaded($ext);
    addCheck($results, 'extension_' . $ext, $exists, $exists ? 'Extension loaded' : 'Extension missing');
}

// 3. Database & Schema
try {
    $conn = connect();
    if ($conn) {
        addCheck($results, 'database_connection', true, 'Connected successfully');
        
        // Check for essential tables
        $tables = ['users', 'students', 'enrollments', 'subjects', 'curriculum', 'grades'];
        $missingTables = [];
        foreach ($tables as $table) {
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->num_rows == 0) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            addCheck($results, 'database_schema', true, 'All essential tables found');
        } else {
            addCheck($results, 'database_schema', false, 'Missing tables: ' . implode(', ', $missingTables));
        }
        
        $conn->close();
    } else {
        addCheck($results, 'database_connection', false, 'Failed to connect');
    }
} catch (Exception $e) {
    addCheck($results, 'database_connection', false, 'DB Error: ' . $e->getMessage());
}

// 4. Folder Permissions
$writablePaths = [
    __DIR__ . '/../tmp'
];

foreach ($writablePaths as $path) {
    $name = basename($path);
    if (file_exists($path)) {
        $isWritable = is_writable($path);
        addCheck($results, 'writable_' . $name, $isWritable, $isWritable ? 'Accessible and writable' : 'Directory is not writable');
    } else {
        addCheck($results, 'exists_' . $name, false, 'Path not found: ' . $path);
    }
}

// 5. App URL Configuration
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
// Trim trailing slash and subfolder if present for comparison
$configuredUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
$urlMatch = (strpos($configuredUrl, $currentUrl) === 0);
addCheck($results, 'app_url_config', $urlMatch, $urlMatch ? 'Configured URL matches current host' : 'APP_URL in .env does not match actual URL');

// 6. SMTP Settings
$smtpCheck = defined('SMTP_HOST') && SMTP_HOST !== '' && defined('SMTP_USERNAME') && SMTP_USERNAME !== '';
addCheck($results, 'smtp_configured', $smtpCheck, $smtpCheck ? 'SMTP settings defined' : 'SMTP settings missing in .env');

// Set HTTP response code based on overall status
if ($results['status'] === 'unhealthy') {
    http_response_code(500);
} else {
    http_response_code(200);
}

echo json_encode($results, JSON_PRETTY_PRINT);
exit();
