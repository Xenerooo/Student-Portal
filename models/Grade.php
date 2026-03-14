<?php
class Grade {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

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
        // First get course_id
        $stmt = $this->conn->prepare("CALL getStudentDetailsByStudentId(?);");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $course_id = $student['course_id'] ?? null;
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }

        // Fetch subjects and grades
        $stmt = $this->conn->prepare("CALL getSubjectsByStudentId(?);");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $curriculum_data = [];
        while ($row = $result->fetch_assoc()) {
            $curriculum_data[$row['year_level']][$row['semester']][] = $row;
        }
        $stmt->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }

        return [
            'course_id' => $course_id,
            'grades' => $curriculum_data
        ];
    }
}
?>
