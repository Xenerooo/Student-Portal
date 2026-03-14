<?php
function connect() {
    $envPath = __DIR__ . '/../.env';
    $config = [];
    if (file_exists($envPath)) {
        $config = parse_ini_file($envPath);
    }

    $servername = getenv('DB_HOST') ?: ($config['DB_HOST'] ?? "localhost");
    $username   = getenv('DB_USER') ?: ($config['DB_USER'] ?? "root");
    $password   = getenv('DB_PASS') ?: ($config['DB_PASS'] ?? "");
    $dbname     = getenv('DB_NAME') ?: ($config['DB_NAME'] ?? "student_portal");

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Check connection
// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// }
// ?>
