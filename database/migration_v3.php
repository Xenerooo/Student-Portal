<?php
/**
 * migration_v3.php
 * Migrates the database schema and data to the new grading system.
 */

require_once __DIR__ . '/../Core/BaseModel.php';

// Mocking required parts to get DB connection
class MigrationHelper extends \App\Core\BaseModel {
    public function getConn() { return $this->conn; }
}

try {
    // Manually define constants if not loaded
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'student_portal_v2'); // Adjust if different

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Starting Migration to Grading System v3...\n";

    // 1. Alter Table
    echo "Updating grades table structure...\n";
    $queries = [
        "ALTER TABLE grades CHANGE COLUMN grade semester_grade DECIMAL(3,2) DEFAULT NULL",
        "ALTER TABLE grades MODIFY finals DECIMAL(5,2), MODIFY prefinal DECIMAL(5,2), MODIFY midterm DECIMAL(5,2), MODIFY prelim DECIMAL(5,2)",
        "ALTER TABLE grades ADD COLUMN IF NOT EXISTS average_grade DECIMAL(5,2) AFTER semester_grade",
        "ALTER TABLE grades ADD COLUMN IF NOT EXISTS remarks VARCHAR(20) AFTER finals"
    ];

    foreach ($queries as $q) {
        if ($conn->query($q)) {
            echo "Success: $q\n";
        } else {
            echo "Warning/Error: " . $conn->error . " (Query: $q)\n";
        }
    }

    // 2. Update Procedure
    echo "Updating upsertGrade procedure...\n";
    $conn->query("DROP PROCEDURE IF EXISTS upsertGrade");
    $procedure = "
    CREATE PROCEDURE `upsertGrade` (IN `p_student_id` INT, IN `p_subject_id` INT, IN `p_semester_grade` DECIMAL(3,2), IN `p_average_grade` DECIMAL(5,2), IN `p_prelim` DECIMAL(5,2), IN `p_midterm` DECIMAL(5,2), IN `p_prefinal` DECIMAL(5,2), IN `p_finals` DECIMAL(5,2), IN `p_remarks` VARCHAR(20), IN `p_semester` VARCHAR(20), IN `p_school_year` VARCHAR(20))   BEGIN
        INSERT INTO grades (
            student_id, subject_id, semester_grade, 
            average_grade, prelim, midterm, prefinal, finals, 
            remarks, semester, school_year
        )
        VALUES (
            p_student_id, p_subject_id, p_semester_grade, 
            p_average_grade, p_prelim, p_midterm, p_prefinal, p_finals, 
            p_remarks, p_semester, p_school_year
        ) 
        ON DUPLICATE KEY UPDATE 
            semester_grade = VALUES(semester_grade),
            average_grade = VALUES(average_grade),
            prelim = VALUES(prelim),
            midterm = VALUES(midterm),
            prefinal = VALUES(prefinal),
            finals = VALUES(finals),
            remarks = VALUES(remarks);
    END";
    
    if ($conn->query($procedure)) {
        echo "Success: Stored procedure updated.\n";
    } else {
        echo "Error updating procedure: " . $conn->error . "\n";
    }

    // 3. Migrate existing data
    echo "Migrating existing grade data...\n";
    $updateData = "
    UPDATE grades SET 
        average_grade = (COALESCE(prelim, 0) + COALESCE(midterm, 0) + COALESCE(prefinal, 0) + COALESCE(finals, 0)) / 4,
        remarks = CASE 
            WHEN prelim IS NULL OR midterm IS NULL OR prefinal IS NULL OR finals IS NULL THEN 'Incomplete'
            WHEN semester_grade <= 3.0 THEN 'Passed'
            ELSE 'Failed'
        END
    WHERE average_grade IS NULL OR remarks IS NULL";
    
    if ($conn->query($updateData)) {
        echo "Success: Existing records updated.\n";
    } else {
        echo "Error migrating data: " . $conn->error . "\n";
    }

    echo "Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
