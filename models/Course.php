<?php
namespace App\Models;

use App\Core\BaseModel;
use Exception;

class Course extends BaseModel {

    public function getAllCourses() {
        $result = $this->conn->query("SELECT course_id, course_name, acronym FROM courses ORDER BY course_name");
        if (!$result) {
            return [];
        }
        $courses = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        
        while ($this->conn->more_results()) { $this->conn->next_result(); }
        
        return $courses;
    }

    public function getCourseList() {
        return $this->getAllCourses();
    }
}
?>
