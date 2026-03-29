<?php
require_once __DIR__ . '/Core/db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Subject;

$conn = connect();
$subjectModel = new Subject($conn);

echo "Testing Circular Dependency Detection...\n";

try {
    // 1. Let's find two subjects or create them
    $conn->query("INSERT INTO subjects (subject_code, subject_name, units) VALUES ('TEST101', 'Test A', 3) ON DUPLICATE KEY UPDATE subject_id=LAST_INSERT_ID(subject_id)");
    $idA = $conn->insert_id;
    
    $conn->query("INSERT INTO subjects (subject_code, subject_name, units) VALUES ('TEST102', 'Test B', 3) ON DUPLICATE KEY UPDATE subject_id=LAST_INSERT_ID(subject_id)");
    $idB = $conn->insert_id;

    echo "ID A: $idA, ID B: $idB\n";

    // 2. Clean old requisites
    $conn->query("DELETE FROM subject_prerequisites WHERE subject_id IN ($idA, $idB) OR required_subject_id IN ($idA, $idB)");

    // 3. Add A requires B
    echo "Adding A requires B...\n";
    $subjectModel->addRequisite($idA, $idB, 'prerequisite');
    echo "Success.\n";

    // 4. Try to add B requires A (Should fail)
    echo "Attempting to add B requires A (Expected failure)...\n";
    try {
        $subjectModel->addRequisite($idB, $idA, 'prerequisite');
        echo "FAILED: Circular dependency NOT detected!\n";
    } catch (Exception $e) {
        echo "SUCCESS: Caught expected exception: " . $e->getMessage() . "\n";
    }

    // 5. Cleanup
    $conn->query("DELETE FROM subjects WHERE subject_code IN ('TEST101', 'TEST102')");
    echo "Cleanup complete.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
