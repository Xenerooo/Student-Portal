<?php
// Database connection settings
$servername = "localhost";   // since you’re using XAMPP
$username   = "root";        // default MySQL username
$password   = "";            // default MySQL password is empty
$dbname     = "student_portal"; // <-- change this to your database name

// Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);

function connect() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Check connection
// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// }
// ?>
