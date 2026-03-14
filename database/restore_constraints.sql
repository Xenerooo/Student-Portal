-- Restore missing foreign key constraints and standardize cascading behavior
-- This script corrects discrepancies between SQLSchematic.sql and SQLSchematic_v2.sql

-- 1. Fix Admins relationship
ALTER TABLE `admins` DROP FOREIGN KEY `admins_ibfk_1`;
ALTER TABLE `admins` ADD CONSTRAINT `fk_admin_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- 2. Fix Students relationships
ALTER TABLE `students` DROP FOREIGN KEY `students_ibfk_1`;
ALTER TABLE `students` ADD CONSTRAINT `fk_student_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `students` ADD CONSTRAINT `fk_student_course` 
    FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- 3. Fix Curriculum relationships
ALTER TABLE `curriculum` ADD CONSTRAINT `fk_curriculum_course` 
    FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `curriculum` ADD CONSTRAINT `fk_curriculum_subject` 
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- 4. Fix Grades relationships (standardizing names and adding UPDATE CASCADE)
ALTER TABLE `grades` DROP FOREIGN KEY `grades_student_fk`;
ALTER TABLE `grades` ADD CONSTRAINT `fk_grade_student` 
    FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `grades` DROP FOREIGN KEY `grades_subject_fk`;
ALTER TABLE `grades` ADD CONSTRAINT `fk_grade_subject` 
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;
