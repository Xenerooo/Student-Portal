<?php
require_once 'core/db_connect.php';

class User {
    private $conn;

    public function __construct() {
        $this->conn = connect();
    }

    public function authenticate($username, $password) {
        $stmt = $this->conn->prepare("CALL getUserDetailByUsername(?)");
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $result->close();
        $stmt->close();
        
        // This is a stored procedure that might return multiple result sets, clean them up
        while ($this->conn->more_results()) {
            $this->conn->next_result();
        }

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
}
?>
