<?php
namespace App\Models;

use App\Core\BaseModel;
use Exception;

class Subject extends BaseModel {

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

    public function addSubject($subject_code, $subject_name, $units) {
        $stmt = $this->conn->prepare("INSERT INTO subjects (subject_code, subject_name, units) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement.');
        }
        $stmt->bind_param('ssi', $subject_code, $subject_name, $units);
        $stmt->execute();

        $success = ($stmt->affected_rows > 0);
        $stmt->close();
        
        return $success;
    }

    public function updateSubject($subject_id, $subject_code, $subject_name, $units) {
        $stmt = $this->conn->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, units = ? WHERE subject_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement.');
        }
        $stmt->bind_param('ssii', $subject_code, $subject_name, $units, $subject_id);
        $stmt->execute();

        $success = ($stmt->affected_rows >= 0); // 0 rows affected is also a success if nothing changed
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
    public function getAllSubjects() {
        $result = $this->conn->query("SELECT subject_id, subject_code, subject_name, units FROM subjects ORDER BY subject_code");
        if (!$result) {
            return [];
        }
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }
        
        return $subjects;
    }

    public function getTotalSubjectsCount() {
        $sql = "SELECT COUNT(*) as total FROM subjects";
        $result = $this->conn->query($sql);
        if (!$result) return 0;
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    public function getRequisites($subject_id) {
        $sql = "SELECT pr.prerequisite_id, pr.required_subject_id, pr.type, 
                       s.subject_code, s.subject_name 
                FROM subject_prerequisites pr
                JOIN subjects s ON s.subject_id = pr.required_subject_id
                WHERE pr.subject_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addRequisite($subject_id, $required_id, $type) {
        // Prevent circular dependency: Check if the 'target' is already a requirement for the 'required' course
        if ($this->isRequisiteOf($required_id, $subject_id)) {
            throw new Exception("Cannot add requisite: This would create a circular dependency.");
        }

        $sql = "INSERT INTO subject_prerequisites (subject_id, required_subject_id, type) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $subject_id, $required_id, $type);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteRequisite($prerequisite_id) {
        $sql = "DELETE FROM subject_prerequisites WHERE prerequisite_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $prerequisite_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Checks if $potential_ancestor is a requisite of $subject_id (directly or indirectly)
     */
    public function isRequisiteOf($subject_id, $potential_ancestor, &$visited = []) {
        if ($subject_id == $potential_ancestor) return true;
        
        // Return false if we've already checked this subject branch
        if (in_array($subject_id, $visited)) return false;
        $visited[] = $subject_id;
        
        // Get direct requisites
        $sql = "SELECT required_subject_id FROM subject_prerequisites WHERE subject_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($res as $row) {
            if ($this->isRequisiteOf($row['required_subject_id'], $potential_ancestor, $visited)) {
                return true;
            }
        }
        return false;
    }
}
?>
