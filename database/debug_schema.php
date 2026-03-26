<?php
/**
 * debug_schema.php
 * Checks table structures.
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

    $tables = ['students', 'subjects'];
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $res = $conn->query("DESCRIBE $table");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                print_r($row);
            }
        } else {
            echo "Error describing $table: " . $conn->error . "\n";
        }
        $res = $conn->query("SHOW CREATE TABLE $table");
        if ($res) {
            $row = $res->fetch_assoc();
            echo $row['Create Table'] . "\n\n";
        }
    }

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
