<?php
class Subject {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function subjectExists($subject_code) {
        $checkStmt = $this->conn->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('s', $subject_code);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        $exists = ($result && $result->num_rows > 0);
        $checkStmt->close();
        
        return $exists;
    }

    public function subjectExistsById($subject_id) {
        $checkStmt = $this->conn->prepare("SELECT subject_code FROM subjects WHERE subject_id = ?");
        if (!$checkStmt) {
            throw new Exception('Failed to prepare check statement.');
        }
        $checkStmt->bind_param('i', $subject_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        $exists = ($result && $result->num_rows > 0);
        $checkStmt->close();
        
        return $exists;
    }

    public function addSubject($subject_code, $units) {
        $stmt = $this->conn->prepare("INSERT INTO subjects (subject_code, units) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement.');
        }
        $stmt->bind_param('si', $subject_code, $units);
        $stmt->execute();

        $success = ($stmt->affected_rows > 0);
        $stmt->close();
        
        return $success;
    }

    public function deleteSubject($subject_id) {
        // Start transaction for safe deletion
        $this->conn->begin_transaction();

        try {
            // Delete from curriculum first (if it exists and has foreign key)
            $delCurriculum = $this->conn->prepare("DELETE FROM curriculum WHERE subject_id = ?");
            if ($delCurriculum) {
                $delCurriculum->bind_param('i', $subject_id);
                $delCurriculum->execute();
                $delCurriculum->close();
            }

            // Delete from subjects
            $stmt = $this->conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
            if (!$stmt) {
                throw new Exception('Failed to prepare delete statement.');
            }
            $stmt->bind_param('i', $subject_id);
            $stmt->execute();

            if ($stmt->affected_rows < 1) {
                $stmt->close();
                throw new Exception('Failed to delete subject. subject input: ' . $subject_id);
            }

            $stmt->close();
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>
