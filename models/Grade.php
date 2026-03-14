<?php
namespace App\Models;

use App\Core\BaseModel;
use Throwable;

class Grade extends BaseModel {

    public function upsertGrades($student_id, $grades, $semester_id, $school_year_id) {
        try {
            $this->conn->begin_transaction();

            foreach ($grades as $subject_id => $grade) {
                $grade = trim((string)$grade);
                if (!filter_var($subject_id, FILTER_VALIDATE_INT)) {
                    continue;
                }

                if ($grade !== '' && is_numeric($grade)) {
                    $stmt = $this->conn->prepare("CALL upsertGrade(?, ?, ?, ?, ?);");
                    $stmt->bind_param("iidii", $student_id, $subject_id, $grade, $semester_id, $school_year_id);
                    $stmt->execute();
                    $stmt->close();
                } else if ($grade === '') {
                    $stmt = $this->conn->prepare("CALL deleteGrade(?, ?, ?, ?);");
                    $stmt->bind_param("iiii", $student_id, $subject_id, $semester_id, $school_year_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function getStudentGrades($student_id) {
        // First get student's course_id
        $stmt = $this->conn->prepare("SELECT course_id FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $course_id = $stmt->get_result()->fetch_assoc()['course_id'] ?? null;
        $stmt->close();

        // Get all subjects and grades for this student from their curriculum
        // We use a query that joins subjects and potential grades
        $sql = "
            SELECT 
                c.year_level, 
                c.semester, 
                s.subject_id,
                s.subject_code, 
                c.subject_name, 
                s.units,
                g.grade
            FROM curriculum c
            JOIN subjects s ON c.subject_id = s.subject_id
            LEFT JOIN grades g ON s.subject_id = g.subject_id AND g.student_id = ?
            WHERE c.course_id = ?
            ORDER BY c.year_level, c.semester, s.subject_code
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        while ($this->conn->more_results()) { $this->conn->next_result(); }

        // Group by Year and Semester
        $grouped = [];
        foreach ($all_data as $row) {
            $year = $row['year_level'];
            $sem = $row['semester'];
            
            if (!isset($grouped[$year])) {
                $grouped[$year] = [];
            }
            if (!isset($grouped[$year][$sem])) {
                $grouped[$year][$sem] = [];
            }
            $grouped[$year][$sem][] = $row;
        }

        return [
            'course_id' => $course_id,
            'grades' => $grouped
        ];
    }
}
?>
