<?php
/**
 * migration_enrollment.php
 * Creates the enrollments table.
 */

try {
    // Manually define constants if not loaded
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'student_portal_v2');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Creating 'enrollments' table...\n";

    $sql = "CREATE TABLE IF NOT EXISTS `enrollments` (
      `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` int(11) NOT NULL,
      `subject_id` int(11) NOT NULL,
      `school_year` varchar(20) NOT NULL,
      `semester` varchar(20) NOT NULL,
      `status` enum('enrolled','passed','failed','incomplete','dropped') DEFAULT 'enrolled',
      `is_retake` tinyint(1) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`enrollment_id`),
      UNIQUE KEY `unique_enrollment` (`student_id`,`subject_id`,`school_year`,`semester`),
      FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
      FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($sql)) {
        echo "Success: Table 'enrollments' created.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
