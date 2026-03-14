<?php
class Student {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function createStudent($username, $hashed_password, $name, $number, $course_id, $birthday, $image_data) {
        try {
            // Start Transaction
            $this->conn->begin_transaction();
            
            // --- INSERT 1: Create the User Account (Authentication/Login) ---
            $stmt_user = $this->conn->prepare("CALL createUser(?, ?, 'student');");
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
            
            // Query the user_id by username
            $stmt_get_id = $this->conn->prepare("CALL getUserDetailByUsername(?);");
            $stmt_get_id->bind_param("s", $username);
            
            if (!$stmt_get_id->execute()) {
                $this->conn->rollback();
                throw new Exception("Error: Could not retrieve user_id after user creation.");
            }
            
            $id_result = $stmt_get_id->get_result();
            if ($id_row = $id_result->fetch_assoc()) {
                $new_user_id = (int)$id_row['user_id'];
                if ($new_user_id <= 0) {
                    $this->conn->rollback();
                    throw new Exception("Error: Invalid user_id retrieved after user creation.");
                }
            } else {
                $this->conn->rollback();
                throw new Exception("Error: User was created but user_id could not be retrieved.");
            }
            
            $stmt_get_id->close();
            
            // Clean up multiple result sets if any (stored procedure)
            while ($this->conn->more_results()) { $this->conn->next_result(); }

            // --- INSERT 2: Create the Student Profile (Profile/Academic Data) ---
            $stmt_student = $this->conn->prepare("CALL createStudent(?, ?, ?, ?, ?, ?)");
            $img_param = null;
            $stmt_student->bind_param("issisb", $new_user_id, $name, $number, $course_id, $birthday, $img_param);
            
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
            throw clone $e; // Rethrow to let the controller catch
        }
    }

    public function editStudent($student_id, $user_id, $username, $password, $name, $number, $course_id, $birthday, $image_data) {
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
            $image_param = $image_data !== null ? $image_data : null;
            $stmt_student = $this->conn->prepare("CALL UpdateStudent(?, ?, ?, ?, ?, ?)");
            $null = null;
            $stmt_student->bind_param("ssisbi", $name, $number, $course_id, $birthday, $null, $student_id);
            if ($image_data !== null) {
                // Send binary data (parameter index 4, which is the 5th parameter - 0-indexed)
                $stmt_student->send_long_data(4, $image_data);
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
            throw clone $e;
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
            throw clone $e;
        }
    }

    public function searchStudents($searchQuery = '') {
        $students = [];
        
        if (!empty($searchQuery)) {
            $searchParam = "%{$searchQuery}%";
            $stmt = $this->conn->prepare("CALL getStudentBySearch(?);");
            if ($stmt) {
                $stmt->bind_param("s", $searchParam);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $students = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
                }
                $stmt->close();
                while ($this->conn->more_results()) { $this->conn->next_result(); }
            }
        } else {
            $sql = "CALL getAllStudents();";
            $result = $this->conn->query($sql);
            if ($result) {
                $students = ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
            while ($this->conn->more_results()) { $this->conn->next_result(); }
        }
        
        return $students;
    }
}
?>
