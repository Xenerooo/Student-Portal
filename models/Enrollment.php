<?php
namespace App\Models;

use App\Core\BaseModel;
use Exception;

class Enrollment extends BaseModel {

  // Returns enrolled subjects + joined grade data for a specific term
  // Used by: grade editor, student "This Term" tab
  public function getEnrollmentsByTerm($student_id, $school_year, $semester): array {
    $sql = "SELECT e.enrollment_id, e.subject_id, e.status, e.is_retake,
                   s.subject_code, s.units,
                   s.subject_name,
                   g.semester_grade as grade, g.average_grade, g.remarks,
                   g.prelim, g.midterm, g.prefinal, g.finals
            FROM enrollments e
            JOIN subjects s ON s.subject_id = e.subject_id
            LEFT JOIN curriculum c ON c.subject_id = e.subject_id
              AND c.course_id = (SELECT course_id FROM students WHERE student_id = ?)
            LEFT JOIN grades g ON g.subject_id = e.subject_id
              AND g.student_id = e.student_id
              AND g.school_year = e.school_year
              AND g.semester = e.semester
            WHERE e.student_id = ? AND e.school_year = ? AND e.semester = ?
            ORDER BY s.subject_code";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiss", $student_id, $student_id, $school_year, $semester);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // Returns curriculum subjects for pre-filling enrollment checklist
  // AND all other subjects not in that curriculum position for "Add others"
  // semester_int is 1 or 2 (INT from curriculum table)
  public function getSubjectsForEnrollment($student_id, $year_level, $semester_int): array {
    // Part A — curriculum subjects (pre-fill)
    $sqlA = "SELECT c.subject_id, s.subject_code, s.subject_name, s.units,
                   (SELECT COUNT(*) FROM enrollments
                    WHERE student_id = ? AND subject_id = c.subject_id
                    AND status = 'passed') as already_passed
            FROM curriculum c
            JOIN subjects s ON s.subject_id = c.subject_id
            WHERE c.course_id = (SELECT course_id FROM students WHERE student_id = ?)
              AND c.year_level = ? AND c.semester = ?
            ORDER BY s.subject_code";
    
    $stmtA = $this->conn->prepare($sqlA);
    $stmtA->bind_param("iiii", $student_id, $student_id, $year_level, $semester_int);
    $stmtA->execute();
    $curriculum = $stmtA->get_result()->fetch_all(MYSQLI_ASSOC);

    // Part B — all other subjects not in Part A
    $sqlB = "SELECT subject_id, subject_code, subject_name, units
            FROM subjects
            WHERE subject_id NOT IN (
              SELECT subject_id FROM curriculum
              WHERE course_id = (SELECT course_id FROM students WHERE student_id = ?)
                AND year_level = ? AND semester = ?
            )
            ORDER BY subject_code";
    
    $stmtB = $this->conn->prepare($sqlB);
    $stmtB->bind_param("iii", $student_id, $year_level, $semester_int);
    $stmtB->execute();
    $others = $stmtB->get_result()->fetch_all(MYSQLI_ASSOC);

    return ['curriculum' => $curriculum, 'others' => $others];
  }

  // Returns failed subjects with no subsequent passing enrollment
  // Used for: Summer enrollment retake candidates, irregular detection
  public function getFailedSubjectsWithoutRetake($student_id): array {
    $sql = "SELECT e.subject_id, s.subject_code,
                   s.subject_name,
                   s.units, e.school_year, e.semester,
                   g.semester_grade as grade, g.remarks
            FROM enrollments e
            JOIN subjects s ON s.subject_id = e.subject_id
            LEFT JOIN curriculum c ON c.subject_id = e.subject_id
              AND c.course_id = (SELECT course_id FROM students WHERE student_id = ?)
            LEFT JOIN grades g ON g.student_id = e.student_id
              AND g.subject_id = e.subject_id
              AND g.school_year = e.school_year AND g.semester = e.semester
            WHERE e.student_id = ?
              AND e.status = 'failed'
              AND e.subject_id NOT IN (
                SELECT subject_id FROM enrollments
                WHERE student_id = ? AND status = 'passed'
              )
            ORDER BY s.subject_code";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iii", $student_id, $student_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // Bulk insert enrollment records
  // $subject_ids: array of all subject_ids to enroll
  // $retake_subject_ids: subset of subject_ids that are retakes (is_retake=1)
  // ON DUPLICATE KEY UPDATE resets status to 'enrolled' and updates is_retake
  public function bulkEnroll($student_id, array $subject_ids, $school_year, $semester, array $retake_subject_ids): int {
    if (empty($subject_ids)) return 0;

    $placeholders = [];
    $params = [];
    $types = "";

    foreach ($subject_ids as $subject_id) {
        $is_retake = in_array($subject_id, $retake_subject_ids) ? 1 : 0;
        $placeholders[] = "(?,?,'$school_year','$semester','enrolled',?)";
        $params[] = $student_id;
        $params[] = $subject_id;
        $params[] = $is_retake;
        $types .= "iii";
    }

    $sql = "INSERT INTO enrollments (student_id, subject_id, school_year, semester, status, is_retake)
            VALUES " . implode(", ", $placeholders) . "
            ON DUPLICATE KEY UPDATE status='enrolled', is_retake=VALUES(is_retake)";

    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $this->conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->affected_rows;
  }

  // Mark an enrollment as dropped
  public function dropSubject($enrollment_id): bool {
    $sql = "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
  }

  // Returns all enrollment history for a student grouped by term
  // Used for: enrollment form history section, admin history view
  public function getEnrollmentHistory($student_id): array {
    $sql = "SELECT e.enrollment_id, e.school_year, e.semester, e.status, e.is_retake,
                   s.subject_code, s.units,
                   s.subject_name,
                   g.semester_grade as grade, g.remarks
            FROM enrollments e
            JOIN subjects s ON s.subject_id = e.subject_id
            LEFT JOIN grades g ON g.student_id = e.student_id
              AND g.subject_id = e.subject_id
              AND g.school_year = e.school_year AND g.semester = e.semester
            WHERE e.student_id = ?
            ORDER BY e.school_year DESC, e.semester DESC, s.subject_code";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // Called after every grade save to sync enrollment status
  public function updateStatusFromGrade($student_id, $subject_id, $school_year, $semester, $semester_grade, $remarks): void {
    $status = 'enrolled';
    if ($remarks === 'Incomplete') {
        $status = 'incomplete';
    } else if ($semester_grade !== null && floatval($semester_grade) <= 3.00) {
        $status = 'passed';
    } else if (floatval($semester_grade) === 5.00 || $remarks === 'Failed') {
        $status = 'failed';
    }

    $sql = "UPDATE enrollments SET status = ?
            WHERE student_id = ? AND subject_id = ? AND school_year = ? AND semester = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("siiss", $status, $student_id, $subject_id, $school_year, $semester);
    $stmt->execute();
  }

  // Returns distinct school_year + semester pairs where student has enrollments
  // Ordered most recent first — used to populate term dropdowns
  public function getTermsWithEnrollment($student_id): array {
    $sql = "SELECT DISTINCT school_year, semester
            FROM enrollments
            WHERE student_id = ?
            ORDER BY school_year DESC, semester DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}
