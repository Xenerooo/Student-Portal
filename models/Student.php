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
            $stmt_user = $this->conn->prepare("CALL createUser(?, ?, 'student');");
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

            while ($this->conn->more_results()) { $this->conn->next_result(); }

            $stmt_get_id = $this->conn->prepare("CALL getUserDetailByUsername(?);");
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
            while ($this->conn->more_results()) { $this->conn->next_result(); }

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
                    birthday,
                    img,
                    address,
                    last_school_attended,
                    contact_number,
                    email,
                    place_of_birth
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $img_param = null;
            $stmt_student->bind_param(
                "issisbsssss",
                $new_user_id,
                $name,
                $number,
                $course_id,
                $birthday,
                $img_param,
                $address,
                $last_school_attended,
                $contact_number,
                $email,
                $place_of_birth
            );

            if ($image_data !== null) {
                $stmt_student->send_long_data(5, $image_data);
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
            while ($this->conn->more_results()) { $this->conn->next_result(); }
            
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
            $stmt_user = $this->conn->prepare("CALL updateUser(?, ?, ?);");
            $stmt_user->bind_param("ssi", $username, $hashed_password, $user_id);
            
            if (!$stmt_user->execute()) {
                $this->conn->rollback();
                if ($this->conn->errno === 1062) {
                     throw new Exception("Error: Username '{$username}' already exists. Please choose a different one.");
                } else {
                     throw new Exception("User update failed: " . htmlspecialchars($stmt_user->error));
                }
            }
            $stmt_user->close();
            while ($this->conn->more_results()) { $this->conn->next_result(); }

            // --- UPDATE 2: Update Student Profile ---
            $studentSql = "
                UPDATE students
                SET student_name = ?,
                    student_number = ?,
                    course_id = ?,
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
                    "ssissssssbi",
                    $name,
                    $number,
                    $course_id,
                    $birthday,
                    $address,
                    $last_school_attended,
                    $contact_number,
                    $email,
                    $place_of_birth,
                    $null,
                    $student_id
                );
                $stmt_student->send_long_data(9, $image_data);
            } else {
                $stmt_student->bind_param(
                    "ssissssssi",
                    $name,
                    $number,
                    $course_id,
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
            while ($this->conn->more_results()) { $this->conn->next_result(); }
            
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
            SELECT s.student_id, s.student_number, s.student_name, c.course_name 
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

    public function searchStudents($search) {
        $search = "%{$search}%";
        $sql = "
            SELECT s.student_id, s.student_number, s.student_name, c.course_name 
            FROM students s 
            LEFT JOIN courses c ON s.course_id = c.course_id 
            WHERE s.student_name LIKE ? OR s.student_number LIKE ? OR c.course_name LIKE ?
            ORDER BY s.student_name
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $students;
    }

    public function getStudentDashboardData($student_id) {
        return $this->getStudentById($student_id);
    }
}
?>
