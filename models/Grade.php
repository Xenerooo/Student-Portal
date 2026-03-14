<?php
namespace App\Models;

use App\Core\BaseModel;
use Throwable;

class Grade extends BaseModel {

    public function upsertGrades($student_id, $grades, $semester, $school_year) {
        try {
            $this->conn->begin_transaction();

            foreach ($grades as $subject_id => $grade) {
                $grade = trim((string)$grade);
                if (!filter_var($subject_id, FILTER_VALIDATE_INT)) {
                    continue;
                }

                if ($grade !== '' && is_numeric($grade)) {
                    // Call updated upsertGrade procedure with text params
                    $stmt = $this->conn->prepare("CALL upsertGrade(?, ?, ?, ?, ?);");
                    $stmt->bind_param("iidss", $student_id, $subject_id, $grade, $semester, $school_year);
                    $stmt->execute();
                    $stmt->close();
                } else if ($grade === '') {
                    // Call updated deleteGrade procedure with text params
                    $stmt = $this->conn->prepare("CALL deleteGrade(?, ?, ?, ?);");
                    $stmt->bind_param("iiss", $student_id, $subject_id, $semester, $school_year);
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

    /**
     * Get all grades for a student, showing the layout of the curriculum
     * and merging in the LATEST attempt for each subject.
     */
    public function getCurriculumProgress($student_id, $semester = null, $school_year = null) {
        // First get student's course_id
        $stmt = $this->conn->prepare("SELECT course_id FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $course_id = $stmt->get_result()->fetch_assoc()['course_id'] ?? null;
        $stmt->close();

        if (!$course_id) return [];

        // Query curriculum subjects and left join either the LATEST grade or a SPECIFIC term grade
        if ($semester && $school_year) {
            $joinSql = "
                LEFT JOIN grades g_latest 
                    ON s.subject_id = g_latest.subject_id 
                    AND g_latest.student_id = ? 
                    AND g_latest.semester = ? 
                    AND g_latest.school_year = ?
            ";
            $params = [$student_id, $semester, $school_year, $course_id];
            $types = "issi";
        } else {
            $joinSql = "
                LEFT JOIN (
                    SELECT g1.*
                    FROM grades g1
                    INNER JOIN (
                        SELECT student_id, subject_id, MAX(grade_id) as max_id
                        FROM grades
                        WHERE student_id = ?
                        GROUP BY subject_id
                    ) g2 ON g1.grade_id = g2.max_id
                ) g_latest ON s.subject_id = g_latest.subject_id
            ";
            $params = [$student_id, $course_id];
            $types = "ii";
        }

        $sql = "
            SELECT 
                c.year_level, 
                c.semester as curriculum_semester, 
                s.subject_id,
                s.subject_code, 
                c.subject_name, 
                s.units,
                g_latest.grade,
                g_latest.school_year,
                g_latest.semester as graded_semester
            FROM curriculum c
            JOIN subjects s ON c.subject_id = s.subject_id
            $joinSql
            WHERE c.course_id = ?
            ORDER BY c.year_level, c.semester, s.subject_code
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Group by Year and Semester for the UI
        $grouped = [];
        foreach ($all_data as $row) {
            $year = $row['year_level'];
            $sem = $row['curriculum_semester'];
            
            if (!isset($grouped[$year])) $grouped[$year] = [];
            if (!isset($grouped[$year][$sem])) $grouped[$year][$sem] = [];
            
            $grouped[$year][$sem][] = $row;
        }

        return $grouped;
    }

    /**
     * Get every grade entry the student has ever had (Scholastic History)
     */
    public function getScholasticHistory($student_id) {
        $sql = "
            SELECT 
                g.school_year,
                g.semester,
                s.subject_code,
                c.subject_name,
                s.units,
                g.grade
            FROM grades g
            JOIN subjects s ON g.subject_id = s.subject_id
            LEFT JOIN curriculum c ON g.subject_id = c.subject_id -- To get the display name if available
            WHERE g.student_id = ?
            ORDER BY g.school_year DESC, g.semester DESC, s.subject_code ASC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get every grade entry for a specific subject for a student
     */
    public function getSubjectHistory($student_id, $subject_id) {
        $sql = "
            SELECT 
                school_year,
                semester,
                grade
            FROM grades
            WHERE student_id = ? AND subject_id = ?
            ORDER BY school_year DESC, semester DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $subject_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
