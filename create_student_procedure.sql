-- Stored procedure to create a student record
-- This procedure handles both cases: with and without image

DELIMITER //

DROP PROCEDURE IF EXISTS createStudent //

CREATE PROCEDURE createStudent(
    IN p_user_id INT,
    IN p_student_name VARCHAR(255),
    IN p_student_number VARCHAR(50),
    IN p_course_id INT,
    IN p_birthday DATE,
    IN p_img LONGBLOB
)
BEGIN
    INSERT INTO students 
    (user_id, student_name, student_number, course_id, birthday, img) 
    VALUES (p_user_id, p_student_name, p_student_number, p_course_id, p_birthday, p_img);
END //

DELIMITER ;




