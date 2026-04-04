<?php
namespace App\Models;

use App\Core\BaseModel;
use Exception;

class Student extends BaseModel {

    public function createStudent(
        $username,
        $hashed_password,
        $name,
        $number,
        $course_id,
        $year_level,
        $birthday,
        $image_data,
        $address,
        $last_school_attended,
        $contact_number,
        $email,
        $place_of_birth
    ) {
        try {
            // Start Transaction
            $this->conn->begin_transaction();
            
            // --- INSERT 1: Create the User Account (Authentication/Login) ---
            $stmt_user = $this->conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'student');");
            if (!$stmt_user) {
                throw new Exception("User creation preparation failed: " . $this->conn->error);
            }
            $stmt_user->bind_param("ss", $username, $hashed_password);
            
            if (!$stmt_user->execute()) {
                $this->conn->rollback();
                if ($this->conn->errno === 1062) {
                     throw new Exception("Error: Username '{$username}' already exists. Please choose a different one.");
                } else {
                     throw new Exception("User creation failed: " . htmlspecialchars($stmt_user->error));
                }
            }
            $stmt_user->close();



            $stmt_get_id = $this->conn->prepare("SELECT user_id, password_hash, role FROM users WHERE username = ? AND is_active = 1");
            if (!$stmt_get_id) {
                $this->conn->rollback();
                throw new Exception("Error: User was created but user_id could not be retrieved.");
            }
            $stmt_get_id->bind_param("s", $username);

            if (!$stmt_get_id->execute()) {
                $stmt_get_id->close();
                $this->conn->rollback();
                throw new Exception("Error: User was created but user_id could not be retrieved.");
            }

            $id_result = $stmt_get_id->get_result();
            if ($id_row = $id_result->fetch_assoc()) {
                $new_user_id = (int)$id_row['user_id'];
                if ($new_user_id <= 0) {
                    $stmt_get_id->close();
                    $this->conn->rollback();
                    throw new Exception("Error: User was created but user_id could not be retrieved.");
                }
            } else {
                $stmt_get_id->close();
                $this->conn->rollback();
                throw new Exception("Error: User was created but user_id could not be retrieved.");
            }

            $stmt_get_id->close();

            $stmt_flag = $this->conn->prepare("UPDATE users SET must_change_password = 1 WHERE user_id = ?");
            if (!$stmt_flag) {
                $this->conn->rollback();
                throw new Exception("Failed to prepare password change flag update.");
            }
            $stmt_flag->bind_param("i", $new_user_id);

            if (!$stmt_flag->execute()) {
                $stmt_flag->close();
                $this->conn->rollback();
                throw new Exception("Failed to mark the new account for password change.");
            }
            $stmt_flag->close();

            // --- INSERT 2: Create the Student Profile (Profile/Academic Data) ---
            $stmt_student = $this->conn->prepare(
                "INSERT INTO students (
                    user_id,
                    student_name,
                    student_number,
                    course_id,
                    year_level,
                    birthday,
                    img,
                    address,
                    last_school_attended,
                    contact_number,
                    email,
                    place_of_birth
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $img_param = null;
            $stmt_student->bind_param(
                "issiisbsssss",
                $new_user_id,
                $name,
                $number,
                $course_id,
                $year_level,
                $birthday,
                $img_param,
                $address,
                $last_school_attended,
                $contact_number,
                $email,
                $place_of_birth
            );

            if ($image_data !== null) {
                $stmt_student->send_long_data(6, $image_data);
            }

            if (!$stmt_student->execute()) {
                $this->conn->rollback();
                if ($this->conn->errno === 1062) {
                    throw new Exception("Error: Student number '{$number}' already exists.");
                } else {
                    throw new Exception("Student profile creation failed: " . htmlspecialchars($stmt_student->error));
                }
            }
            
            $stmt_student->close();

            
            // --- Success: Commit Transaction ---
            $this->conn->commit();
            
            return [
                'success' => true, 
                'message' => "Student **{$name}** created successfully! User ID: {$new_user_id}",
                'user_id' => $new_user_id
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function editStudent(
        $student_id,
        $user_id,
        $username,
        $password,
        $name,
        $number,
        $course_id,
        $year_level,
        $birthday,
        $image_data,
        $address,
        $last_school_attended,
        $contact_number,
        $email,
        $place_of_birth
    ) {
        try {
            // Start Transaction
            $this->conn->begin_transaction();
            
            // --- UPDATE 1: Update User Account (Username and optionally Password) ---
            $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
            if ($hashed_password !== null) {
                $stmt_user = $this->conn->prepare("UPDATE users SET username = ?, password_hash = ? WHERE user_id = ?");
                $stmt_user->bind_param("ssi", $username, $hashed_password, $user_id);
            } else {
                $stmt_user = $this->conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                $stmt_user->bind_param("si", $username, $user_id);
            }
            
            if (!$stmt_user->execute()) {
                $this->conn->rollback();
                if ($this->conn->errno === 1062) {
                     throw new Exception("Error: Username '{$username}' already exists. Please choose a different one.");
                } else {
                     throw new Exception("User update failed: " . htmlspecialchars($stmt_user->error));
                }
            }
            $stmt_user->close();
            

            // --- UPDATE 2: Update Student Profile ---
            $studentSql = "
                UPDATE students
                SET student_name = ?,
                    student_number = ?,
                    course_id = ?,
                    year_level = ?,
                    birthday = ?,
                    address = ?,
                    last_school_attended = ?,
                    contact_number = ?,
                    email = ?,
                    place_of_birth = ?";

            if ($image_data !== null) {
                $studentSql .= ", img = ?";
            }

            $studentSql .= " WHERE student_id = ?";

            $stmt_student = $this->conn->prepare($studentSql);

            if ($image_data !== null) {
                $null = null;
                $stmt_student->bind_param(
                    "ssiissssssbi",
                    $name,
                    $number,
                    $course_id,
                    $year_level,
                    $birthday,
                    $address,
                    $last_school_attended,
                    $contact_number,
                    $email,
                    $place_of_birth,
                    $null,
                    $student_id
                );
                $stmt_student->send_long_data(10, $image_data);
            } else {
                $stmt_student->bind_param(
                    "ssiissssssi",
                    $name,
                    $number,
                    $course_id,
                    $year_level,
                    $birthday,
                    $address,
                    $last_school_attended,
                    $contact_number,
                    $email,
                    $place_of_birth,
                    $student_id
                );
            }

            if (!$stmt_student->execute()) {
                $this->conn->rollback();
                if ($this->conn->errno === 1062) {
                    throw new Exception("Error: Student number '{$number}' already exists.");
                } else {
                    throw new Exception("Student profile update failed: " . htmlspecialchars($stmt_student->error));
                }
            }
            
            $stmt_student->close();

            
            // --- Success: Commit Transaction ---
            $this->conn->commit();
            return ['success' => true, 'message' => "Student **{$name}** updated successfully!"];

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function deleteStudent($student_id) {
        $this->conn->begin_transaction();
        try {
            // Fetch related user_id for cleanup after deleting the student record
            $getStmt = $this->conn->prepare("SELECT user_id FROM students WHERE student_id = ?");
            if (!$getStmt) {
                throw new Exception('Failed to prepare fetch statement.');
            }
            $getStmt->bind_param('i', $student_id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $getStmt->close();

            if (!$row) {
                throw new Exception('Student not found.');
            }

            $userId = (int)$row['user_id'];

            // Delete dependent rows that reference this student to satisfy FK constraints
            // 1) grades -> student_id FK
            $delGrades = $this->conn->prepare("DELETE FROM grades WHERE student_id = ?");
            if (!$delGrades) {
                throw new Exception('Failed to prepare grades delete statement.');
            }
            $delGrades->bind_param('i', $student_id);
            $delGrades->execute();
            $delGrades->close();

            // Delete from students (now safe after dependent rows removed)
            $delStudent = $this->conn->prepare("DELETE FROM students WHERE student_id = ?");
            if (!$delStudent) {
                throw new Exception('Failed to prepare student delete statement.');
            }
            $delStudent->bind_param('i', $student_id);
            $delStudent->execute();
            if ($delStudent->affected_rows < 1) {
                throw new Exception('Failed to delete student record.');
            }
            $delStudent->close();

            // Optionally delete the linked user account if it exists
            if ($userId > 0) {
                $delUser = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
                if ($delUser) {
                    $delUser->bind_param('i', $userId);
                    $delUser->execute();
                    $delUser->close();
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function getStudentById($student_id) {
        $stmt = $this->conn->prepare("
            SELECT
                s.student_id,
                s.student_name,
                s.student_number,
                s.course_id,
                s.year_level,
                s.birthday,
                s.img,
                s.address,
                s.last_school_attended,
                s.contact_number,
                s.email,
                s.place_of_birth,
                u.user_id,
                u.username,
                c.course_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN courses c ON s.course_id = c.course_id
            WHERE s.student_id = ?
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        return $student;
    }

    public function getAllStudents() {
        $sql = "
            SELECT s.student_id, s.student_number, s.student_name, c.course_name, c.acronym, s.year_level 
            FROM students s 
            LEFT JOIN courses c ON s.course_id = c.course_id 
            ORDER BY s.student_name
        ";
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        return $students;
    }

    public function getTotalStudentsCount() {
        $sql = "SELECT COUNT(*) as total FROM students";
        $result = $this->conn->query($sql);
        if (!$result) return 0;
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    public function getRecentStudents($limit = 5) {
        $sql = "
            SELECT s.student_id, s.student_number, s.student_name, c.course_name, u.created_at
            FROM students s 
            LEFT JOIN courses c ON s.course_id = c.course_id 
            LEFT JOIN users u ON s.user_id = u.user_id
            ORDER BY s.student_id DESC LIMIT ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $students;
    }

    public function searchStudents($search, $course_id = null, $limit = 10, $offset = 0) {
        $searchQuery = "%{$search}%";
        $sql = "
            SELECT s.student_id, s.student_number, s.student_name, c.course_name, c.acronym, s.year_level
            FROM students s 
            LEFT JOIN courses c ON s.course_id = c.course_id 
            WHERE (s.student_name LIKE ? OR s.student_number LIKE ? OR c.course_name LIKE ? OR c.acronym LIKE ?)
        ";
        
        if ($course_id) {
            $sql .= " AND s.course_id = ? ";
        }
        
        $sql .= " ORDER BY s.student_name LIMIT ? OFFSET ? ";

        $stmt = $this->conn->prepare($sql);
        if ($course_id) {
            $stmt->bind_param("ssssiii", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $course_id, $limit, $offset);
        } else {
            $stmt->bind_param("ssssii", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $students;
    }

    public function getSearchCount($search, $course_id = null) {
        $searchQuery = "%{$search}%";
        $sql = "
            SELECT COUNT(*) as total
            FROM students s 
            LEFT JOIN courses c ON s.course_id = c.course_id 
            WHERE (s.student_name LIKE ? OR s.student_number LIKE ? OR c.course_name LIKE ? OR c.acronym LIKE ?)
        ";
        
        if ($course_id) {
            $sql .= " AND s.course_id = ? ";
        }

        $stmt = $this->conn->prepare($sql);
        if ($course_id) {
            $stmt->bind_param("ssssi", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $course_id);
        } else {
            $stmt->bind_param("ssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }

    public function getStudentDashboardData($student_id) {
        return $this->getStudentById($student_id);
    }

    /**
     * Detects the student's year level based on their enrolled subjects' majority year level.
     * @param int $student_id
     * @return int|null Predicted year level
     */
    public function detectYearLevel($student_id) {
        $sql = "
            SELECT curr.year_level, COUNT(*) as count
            FROM enrollments e
            JOIN curriculum curr ON e.subject_id = curr.subject_id
            JOIN (
                SELECT school_year, semester
                FROM enrollments
                WHERE student_id = ?
                ORDER BY school_year DESC, 
                         (CASE WHEN semester = 'Summer' THEN 3 WHEN semester = '2nd Semester' THEN 2 ELSE 1 END) DESC
                LIMIT 1
            ) latest_term ON e.school_year = latest_term.school_year AND e.semester = latest_term.semester
            JOIN students s ON e.student_id = s.student_id AND s.course_id = curr.course_id
            WHERE e.student_id = ?
            GROUP BY curr.year_level
            ORDER BY count DESC, curr.year_level DESC
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        
        return $row ? (int)$row['year_level'] : null;
    }

    /**
     * Syncs the stored year_level in the database with the detected value.
     * @param int $student_id
     * @return bool
     */
    public function syncYearLevel($student_id) {
        $detectedYear = $this->detectYearLevel($student_id);
        if ($detectedYear === null) return false;

        $sql = "UPDATE students SET year_level = ? WHERE student_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $detectedYear, $student_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
?>
