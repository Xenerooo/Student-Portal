<?php
try {
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'student_portal_v2');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Disable FK checks temporarily for schema changes
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $fixes = [
        "ALTER TABLE users ADD PRIMARY KEY (user_id)",
        "ALTER TABLE users MODIFY user_id INT AUTO_INCREMENT",
        "ALTER TABLE users ADD UNIQUE (username)",
        
        "ALTER TABLE admins MODIFY admin_id INT AUTO_INCREMENT",
        
        "ALTER TABLE students MODIFY student_id INT AUTO_INCREMENT",
        
        "ALTER TABLE courses MODIFY course_id INT AUTO_INCREMENT",
        
        "ALTER TABLE subjects ADD PRIMARY KEY (subject_id)",
        "ALTER TABLE subjects MODIFY subject_id INT AUTO_INCREMENT",
        "ALTER TABLE subjects ADD UNIQUE (subject_code)",
        
        "ALTER TABLE curriculum MODIFY curriculum_id INT AUTO_INCREMENT",
        
        "ALTER TABLE grades MODIFY grade_id INT AUTO_INCREMENT"
    ];

    foreach ($fixes as $q) {
        if ($conn->query($q)) {
            echo "Success: $q\n";
        } else {
            echo "Error: " . $conn->error . " (Query: $q)\n";
        }
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
