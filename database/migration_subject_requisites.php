<?php
require_once __DIR__ . '/../Core/db_connect.php';

$conn = connect();

$sql = "CREATE TABLE IF NOT EXISTS `subject_prerequisites` (
  `prerequisite_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `required_subject_id` int(11) NOT NULL,
  `type` enum('prerequisite','corequisite') NOT NULL DEFAULT 'prerequisite',
  PRIMARY KEY (`prerequisite_id`),
  UNIQUE KEY `unique_requisite` (`subject_id`,`required_subject_id`),
  KEY `fk_requisite_target` (`subject_id`),
  KEY `fk_requisite_required` (`required_subject_id`),
  CONSTRAINT `fk_requisite_required` FOREIGN KEY (`required_subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_requisite_target` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table subject_prerequisites created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
