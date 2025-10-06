-- Idempotent helper to ensure the session_years table and key indexes exist.
-- Safe to run multiple times. Targets MySQL 8.0 CLI (no psql/MariaDB DO blocks).

SET NAMES utf8mb4;

-- Create helper table if missing (no FK to avoid failures if base table is absent).
CREATE TABLE IF NOT EXISTS `claro_cursusbundle_session_years` (
  `session_id` INT NOT NULL,
  `min_year` INT NOT NULL,
  `max_year` INT NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `IDX_SESSION_YEARS_MAX_YEAR` (`max_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add indexes and optionally populate via a stored procedure (allows IF logic in MySQL CLI)
DELIMITER //

DROP PROCEDURE IF EXISTS ensure_session_years //
CREATE PROCEDURE ensure_session_years()
BEGIN
  DECLARE idx_exists INT DEFAULT 0;
  DECLARE has_session INT DEFAULT 0;
  DECLARE has_su INT DEFAULT 0;
  DECLARE has_sc INT DEFAULT 0;

  -- course_session_user.registration_date index
  SELECT COUNT(*) INTO idx_exists
    FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name = 'claro_cursusbundle_course_session_user'
     AND index_name = 'IDX_CSU_REGISTRATION_DATE';
  IF idx_exists = 0 THEN
    SET @stmt := 'ALTER TABLE `claro_cursusbundle_course_session_user` ADD INDEX `IDX_CSU_REGISTRATION_DATE` (`registration_date`)';
    PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;

  -- course_session_cancellation.registration_date index
  SELECT COUNT(*) INTO idx_exists
    FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name = 'claro_cursusbundle_course_session_cancellation'
     AND index_name = 'IDX_CSC_REGISTRATION_DATE';
  IF idx_exists = 0 THEN
    SET @stmt := 'ALTER TABLE `claro_cursusbundle_course_session_cancellation` ADD INDEX `IDX_CSC_REGISTRATION_DATE` (`registration_date`)';
    PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;

  -- Populate/refresh materialized years if source tables exist; otherwise skip
  SELECT COUNT(*) INTO has_session
    FROM information_schema.tables
   WHERE table_schema = DATABASE() AND table_name = 'claro_cursusbundle_course_session';
  SELECT COUNT(*) INTO has_su
    FROM information_schema.tables
   WHERE table_schema = DATABASE() AND table_name = 'claro_cursusbundle_course_session_user';
  SELECT COUNT(*) INTO has_sc
    FROM information_schema.tables
   WHERE table_schema = DATABASE() AND table_name = 'claro_cursusbundle_course_session_cancellation';

  IF has_session = 1 AND has_su = 1 AND has_sc = 1 THEN
    INSERT INTO `claro_cursusbundle_session_years` (`session_id`, `min_year`, `max_year`)
    SELECT s.id AS session_id,
           LEAST(
             COALESCE((SELECT MIN(YEAR(su.registration_date))
                       FROM `claro_cursusbundle_course_session_user` su
                       WHERE su.session_id = s.id), 9999),
             COALESCE((SELECT MIN(YEAR(sc.registration_date))
                       FROM `claro_cursusbundle_course_session_cancellation` sc
                       WHERE sc.session_id = s.id), 9999)
           ) AS min_year,
           GREATEST(
             COALESCE((SELECT MAX(YEAR(su.registration_date))
                       FROM `claro_cursusbundle_course_session_user` su
                       WHERE su.session_id = s.id), 0),
             COALESCE((SELECT MAX(YEAR(sc.registration_date))
                       FROM `claro_cursusbundle_course_session_cancellation` sc
                       WHERE sc.session_id = s.id), 0)
           ) AS max_year
    FROM `claro_cursusbundle_course_session` s
    ON DUPLICATE KEY UPDATE `min_year` = VALUES(`min_year`), `max_year` = VALUES(`max_year`);
  END IF;
END //

DELIMITER ;

CALL ensure_session_years();
DROP PROCEDURE IF EXISTS ensure_session_years;
