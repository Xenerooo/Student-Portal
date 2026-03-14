<?php
namespace App\Core;

class BaseModel {
    protected $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
}
?>
