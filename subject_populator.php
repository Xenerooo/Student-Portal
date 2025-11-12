<?php
// Include database connection
require_once 'app/includes/db_connect.php';

$message = '';
$subjects = [];

$conn = connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
    $file = $_FILES['json_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $jsonContent = file_get_contents($file['tmp_name']);
        $data = json_decode($jsonContent, true);

        if ($data && isset($data['subject'])) {
            try {
                foreach ($data['subject'] as $subject) {
                    try {
                        // First try to insert
                        $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, units) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $subject['subject_code'],
                            $subject['subject_name'],
                            $subject['units']
                        ]);
                    } catch (mysqli_sql_exception $e) {
                        if ($e->getCode() == 1062) {
                            // If duplicate, update instead
                            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, units = ? WHERE subject_code = ?");
                            $stmt->execute([
                                $subject['subject_name'],
                                $subject['units'],
                                $subject['subject_code']
                            ]);
                        } else {
                            // If other error, throw it
                            throw $e;
                        }
                    }
                }
                $message = '<div class="alert alert-success">Subjects successfully imported!</div>';
            } catch (mysqli_sql_exception $e) {
                $message = '<div class="alert alert-danger">Error ' . $e->getCode() . ' : ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Invalid JSON format!</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Error uploading file!</div>';
    }
}

// Fetch existing subjects
try {
    $stmt = $conn->query("SELECT * FROM subjects ORDER BY subject_code");
    $subjects = $stmt->fetch_all(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching subjects: ' . $e->getMessage() . '</div>';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Populator</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Subject Populator</h2>

        <?php echo $message; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Upload JSON File</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" class="form-control" name="json_file" accept=".json" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Subjects</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Current Subjects</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $_subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($_subject[1]); ?></td>
                                <td><?php echo htmlspecialchars($_subject[2]); ?></td>
                                <td><?php echo htmlspecialchars($_subject[3]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>