<?php
require_once __DIR__ . '/public/index.php'; // This might be too much, let's just use db_connect
require_once __DIR__ . '/Core/db_connect.php';
$conn = connect();
$result = $conn->query("SELECT * FROM events");
if ($result) {
    echo "Count: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}
