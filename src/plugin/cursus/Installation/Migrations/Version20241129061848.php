<?php

namespace Claroline\CursusBundle\Installation\Migrations;

use Claroline\InstallationBundle\Migrations\Helper\ConditionalMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2024/11/29 06:18:48
 */
final class Version20241129061848 extends AbstractMigration
{
    use ConditionalMigrationTrait;

    public function up(Schema $schema): void
    {
        if ($this->checkColumnExists('claro_cursusbundle_course', 'session_duration', $this->connection)) {
            $this->addSql('
                ALTER TABLE claro_cursusbundle_course CHANGE session_duration session_duration DOUBLE PRECISION DEFAULT 1 NOT NULL
            ');
        }

        if (!$this->checkColumnExists('claro_cursusbundle_course_session_user', 'course_id', $this->connection)) {
            $this->addSql('
                ALTER TABLE claro_cursusbundle_course_session_user 
                ADD course_id INT NOT NULL
            ');
        }

        $this->addSql('
            ALTER TABLE claro_cursusbundle_course_session_user 
            CHANGE session_id session_id INT DEFAULT NULL
        ');

        // add course id to session registrations
        $this->addSql('
            UPDATE claro_cursusbundle_course_session_user AS su
            LEFT JOIN claro_cursusbundle_course_session AS s ON su.session_id = s.id
            LEFT JOIN claro_cursusbundle_course AS c ON s.course_id = c.id
            SET su.course_id = c.id
        ');

        if (!$this->checkForeignKeyExists('FK_80B4120F591CC992', $this->connection)) {
            $this->addSql('
                ALTER TABLE claro_cursusbundle_course_session_user 
                ADD CONSTRAINT FK_80B4120F591CC992 FOREIGN KEY (course_id) 
                REFERENCES claro_cursusbundle_course (id) 
                ON DELETE CASCADE
            ');
        }

        if (!$this->checkIndexExists('claro_cursusbundle_course_session_user', 'IDX_80B4120F591CC992')) {
            $this->addSql('
                CREATE INDEX IDX_80B4120F591CC992 ON claro_cursusbundle_course_session_user (course_id)
            ');
        }

        // insert all CourseUser
        $this->addSql('
            INSERT INTO claro_cursusbundle_course_session_user
            (user_id, session_id, registration_date, uuid, registration_type, confirmed, validated, status, remark, cancelled, course_id)
            SELECT cu.user_id, NULL AS session_id, cu.registration_date, cu.uuid, cu.registration_type, cu.confirmed, cu.validated, 0 AS status, "" AS remark, 0 AS cancelled, cu.course_id
            FROM claro_cursusbundle_course_course_user AS cu
            WHERE NOT EXISTS (
                SELECT 1
                FROM claro_cursusbundle_course_session_user AS su
                WHERE su.user_id = cu.user_id
                  AND su.session_id IS NULL
                  AND su.course_id = cu.course_id
            )
        ');

        // insert facet values for CourseUser
        $this->addSql('
            INSERT INTO claro_cursusbundle_session_user_values
            (registration_id, value_id)
            SELECT su.id AS registration_id, cv.value_id
            FROM claro_cursusbundle_course_user_values AS cv
            LEFT JOIN claro_cursusbundle_course_course_user AS cu ON cv.registration_id = cu.id
            LEFT JOIN claro_cursusbundle_course_session_user AS su ON (cu.user_id = su.user_id AND su.session_id IS NULL) 
            WHERE NOT EXISTS (
                SELECT 1
                FROM claro_cursusbundle_session_user_values AS sv
                WHERE sv.registration_id = su.id
                  AND sv.value_id = cv.value_id
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course CHANGE session_duration session_duration DOUBLE PRECISION DEFAULT '1' NOT NULL
        ");
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course_session_user 
            DROP FOREIGN KEY FK_80B4120F591CC992
        ');
        $this->addSql('
            DROP INDEX IDX_80B4120F591CC992 ON claro_cursusbundle_course_session_user
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course_session_user 
            DROP course_id, 
            CHANGE session_id session_id INT NOT NULL
        ');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    private function checkIndexExists(string $tableName, string $indexName): bool
    {
        $stmt = $this->connection->executeQuery('
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = :database
              AND table_name = :table
              AND index_name = :indexName
            LIMIT 1
        ', [
            'database' => $this->connection->getDatabase(),
            'table' => $tableName,
            'indexName' => $indexName,
        ]);

        return !empty($stmt->fetchAllAssociative());
    }
}
