<?php
/**
 * migration_student_profile_fields.php
 * Adds extended profile fields to the students table.
 */

try {
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'student_portal_v2');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Updating students table with extended profile fields...\n";

    $queries = [
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER birthday",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS last_school_attended VARCHAR(255) NULL AFTER address",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS contact_number VARCHAR(50) NULL AFTER last_school_attended",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER contact_number",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS place_of_birth VARCHAR(255) NULL AFTER email"
    ];

    foreach ($queries as $query) {
        if ($conn->query($query)) {
            echo "Success: $query\n";
        } else {
            echo "Warning/Error: " . $conn->error . " (Query: $query)\n";
        }
    }

    echo "Student profile migration completed.\n";
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
