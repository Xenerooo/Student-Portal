<?php
namespace App\Models;

use App\Core\BaseModel;

class User extends BaseModel {

    public function __construct($dbConnection = null) {
        if ($dbConnection === null) {
            $dbConnection = \connect(); // fallback if not provided for some reason
        }
        parent::__construct($dbConnection);
    }

    public function authenticate($username, $password) {
        $stmt = $this->conn->prepare("
            SELECT user_id, username, password_hash, role, must_change_password
            FROM users
            WHERE username = ? AND is_active = 1
            LIMIT 1
        ");
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $result->close();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    public function getStudentIdByUserId($user_id) {
        $stmt = $this->conn->prepare("CALL getStudentByUserId(?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $result->close();
        $stmt->close();
        
        while ($this->conn->more_results()) {
            $this->conn->next_result();
        }
        
        return $student ? $student['student_id'] : null;
    }

    public function getAdminIdByUserId($user_id) {
        $stmt = $this->conn->prepare("CALL getAdminByUserId(?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $result->close();
        $stmt->close();
        
        while ($this->conn->more_results()) {
            $this->conn->next_result();
        }
        
        return $admin ? $admin['admin_id'] : null;
    }

    public function getUserAccountDetails($student_id) {
        $stmt = $this->conn->prepare("
            SELECT u.user_id, u.password_hash, u.username, u.must_change_password
            FROM students st
            JOIN users u ON st.user_id = u.user_id
            WHERE st.student_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $result->close();
        $stmt->close();
        
        return $user;
    }

    public function updatePassword($user_id, $new_password_hash) {
        $stmt = $this->conn->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?');
        $stmt->bind_param('si', $new_password_hash, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    public function getAdminDetailsByUserId($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.user_id, u.username, a.admin_id, a.admin_name 
            FROM users u 
            JOIN admins a ON u.user_id = a.user_id 
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();
        return $admin;
    }

    public function updateAdminProfile($user_id, $admin_id, $username, $admin_name, $new_password_hash = null) {
        $this->conn->begin_transaction();
        try {
            // Update User table
            if ($new_password_hash) {
                $stmt = $this->conn->prepare("UPDATE users SET username = ?, password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $username, $new_password_hash, $user_id);
            } else {
                $stmt = $this->conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                $stmt->bind_param("si", $username, $user_id);
            }
            $stmt->execute();
            $stmt->close();

            // Update Admin table
            $stmt = $this->conn->prepare("UPDATE admins SET admin_name = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $admin_name, $admin_id);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>
