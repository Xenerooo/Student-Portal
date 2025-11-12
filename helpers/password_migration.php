<?php
// WARNING: DELETE THIS FILE IMMEDIATELY AFTER SUCCESSFUL EXECUTION!
session_start();
require_once "../app/includes/db_connect.php"; // Assuming this connects to MySQL
$conn = connect();

// --- Admin Credentials ---
$admin_username = 'admin';
$admin_plaintext_password = 'admin';
$admin_name = 'Registrar'; // Use a real name later

// Securely hash the password
$hashed_password = password_hash($admin_plaintext_password, PASSWORD_DEFAULT);

// Start a transaction to ensure both inserts succeed or both fail
$conn->begin_transaction();

try {
    // 1. Check if the admin user already exists
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $admin_username);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $check_stmt->close();
        throw new Exception("Admin user '$admin_username' already exists in the users table.");
    }
    $check_stmt->close();
    
    // 2. Insert into users table
    $stmt1 = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, 'admin', 1)");
    $stmt1->bind_param("ss", $admin_username, $hashed_password);
    $stmt1->execute();
    
    // Get the new user_id for linking
    $new_user_id = $conn->insert_id;
    $stmt1->close();

    // 3. Insert into admins table (linking the user_id)
    $stmt2 = $conn->prepare("INSERT INTO admins (user_id, admin_name) VALUES (?, ?)");
    $stmt2->bind_param("is", $new_user_id, $admin_name);
    $stmt2->execute();
    $stmt2->close();
    
    // Commit the transaction
    $conn->commit();
    
    echo "<h1>✅ Admin Account Created Successfully!</h1>";
    echo "<p>Username: <strong>$admin_username</strong></p>";
    echo "<p>Initial Password: <strong>$admin_plaintext_password</strong> (Hashed)</p>";
    echo "<p>Admin Record ID: {$conn->insert_id}</p>";
    echo "<p style='color:red; font-weight:bold;'>ACTION REQUIRED: IMMEDIATELY DELETE THIS FILE (setup_admin.php) FROM YOUR SERVER!</p>";

} catch (Exception $e) {
    // Rollback if any part of the transaction failed
    $conn->rollback();
    echo "<h1>❌ Error Creating Admin Account</h1>";
    echo "<p>Reason: " . $e->getMessage() . "</p>";
    error_log("Admin setup error: " . $e->getMessage());

} finally {
    $conn->close();
}
?>