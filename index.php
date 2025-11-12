<?php
session_start();

require_once "app/includes/db_connect.php"; // DB connection file

$conn = connect();

// --- PRE-LOGIN CHECK: Redirect if user is already authenticated ---
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        // Close connection before redirecting
        $conn->close();
        header("Location: student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'admin') {
        // Close connection before redirecting
        $conn->close();
        header("Location: admin_dashboard.php");
        exit();
    }
}


// Close connection if it was opened and not already closed during redirect
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/images/icon.png">
    
    <title>Portal Login</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" >

</head>
<body class="bg-dark d-flex align-items-center justify-content-center vh-100" style="height: 100%;">

    <div class="card shadow p-3" style="width: 350px;">
        <div class="d-flex align-items-center justify-content-center m-1" >
            <img src="assets/images/icon.png" alt="" width="96" height="96">
        </div>
        <!-- <h3 class="text-center mb-4">University Portal</h3> -->

        <form method="POST" action="app/proccess_login.php" id="loginForm">

            <div class="form-floating mb-3">
                <input type="text" name="username" class="form-control" id="username" placeholder="25-001" required>
                <label for="floatingInput">Username</label>
            </div>
            <!-- <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div> -->
            <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control" id="password" placeholder="25-001" required>
                <label for="floatingInput">Password</label>
            </div>

            <!-- <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div> -->

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

</body>

<script>
    // document.getElementById('loginForm').addEventListener('submit', function(event) {
    //     event.preventDefault();
    //     fetch('app/proccess_login.php', {
    //         method: 'POST',
    //         body: new FormData(event.target)
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         console.log(data);
    //     })
    //     .catch(error => {
    //         console.error('Error:', error);
    //     });
    // });
</script>

</html>