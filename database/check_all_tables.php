<?php
try {
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'student_portal_v2');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_row()) {
        $table = $row[0];
        echo "--- Table: $table ---\n";
        $res2 = $conn->query("SHOW CREATE TABLE $table");
        $row2 = $res2->fetch_assoc();
        echo $row2['Create Table'] . "\n\n";
    }

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
