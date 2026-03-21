<?php
namespace App\Models;

use App\Core\BaseModel;
use Exception;

class Curriculum extends BaseModel {

    public function addEntry($course_id, $subject_id, $year_level, $semester) {
        $stmt = $this->conn->prepare("INSERT INTO curriculum (course_id, subject_id, year_level, semester) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement.');
        }
        $stmt->bind_param('iiii', $course_id, $subject_id, $year_level, $semester);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            $stmt->close();
            throw new Exception('Failed to add curriculum entry.');
        }

        $curriculum_id = $this->conn->insert_id;
        $stmt->close();
        
        return $this->getEntryById($curriculum_id);
    }

    public function updateEntry($curriculum_id, $course_id, $subject_id, $year_level, $semester) {
        $stmt = $this->conn->prepare("UPDATE curriculum SET course_id = ?, subject_id = ?, year_level = ?, semester = ? WHERE curriculum_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement.');
        }
        $stmt->bind_param('iiiii', $course_id, $subject_id, $year_level, $semester, $curriculum_id);
        $stmt->execute();

        if ($stmt->affected_rows < 1 && $this->conn->errno !== 0) {
            $stmt->close();
            throw new Exception('Failed to update curriculum entry.');
        }

        $stmt->close();
        return $this->getEntryById($curriculum_id);
    }

    public function deleteEntry($curriculum_id) {
        $stmt = $this->conn->prepare("DELETE FROM curriculum WHERE curriculum_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare delete statement.');
        }
        $stmt->bind_param('i', $curriculum_id);
        $stmt->execute();

        $success = ($stmt->affected_rows > 0);
        $stmt->close();
        
        return $success;
    }

    public function getEntryById($curriculum_id) {
        $stmt = $this->conn->prepare("
            SELECT c.curriculum_id, c.course_id, c.subject_id, c.year_level, c.semester,
                   co.course_name, s.subject_code, s.subject_name
            FROM curriculum c
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.curriculum_id = ?
        ");
        $stmt->bind_param('i', $curriculum_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entry = $result->fetch_assoc();
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }
        
        return $entry;
    }

    public function bulkSave($curriculum_data) {
        $subjects_to_insert = count($curriculum_data);
        if ($subjects_to_insert === 0) {
            throw new Exception('No subjects to insert.');
        }

        $value_placeholder = "(?, ?, ?, ?)";
        $placeholders = implode(", ", array_fill(0, $subjects_to_insert, $value_placeholder));
        
        $sql = "INSERT INTO curriculum (course_id, subject_id, year_level, semester)
                VALUES {$placeholders}
                ON DUPLICATE KEY UPDATE
                    year_level = VALUES(year_level),
                    semester = VALUES(semester);";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare bulk insert statement.');
        }
        
        $types = str_repeat("iiii", $subjects_to_insert);
        $params = array();
        
        foreach ($curriculum_data as $entry) {
            $params[] = (int)($entry['course_id'] ?? 0);
            $params[] = (int)($entry['subject_id'] ?? 0);
            $params[] = (int)($entry['year_level'] ?? 1);
            $params[] = (int)($entry['semester'] ?? 1);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }

        return $affected_rows;
    }

    public function exists($curriculum_id) {
        $stmt = $this->conn->prepare("SELECT curriculum_id FROM curriculum WHERE curriculum_id = ?");
        $stmt->bind_param('i', $curriculum_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();
        return $exists;
    }
    public function getEntriesByCourse($course_id) {
        $stmt = $this->conn->prepare("
            SELECT c.curriculum_id, c.course_id, c.subject_id, c.year_level, c.semester,
                   co.course_name, s.subject_code, s.subject_name
            FROM curriculum c
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.course_id = ?
            ORDER BY c.year_level, c.semester, s.subject_code
        ");
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }
        
        return $entries;
    }
}
?>
