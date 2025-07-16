<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2025/06/30 08:46:00
 */
class Version20250630084557 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            DROP INDEX training_event_unique_user ON claro_cursusbundle_session_event_user
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_session_event_user 
            ADD state INT NOT NULL
        ");
        $this->addSql("
            DROP INDEX training_session_unique_user ON claro_cursusbundle_course_course_user
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_course_user 
            ADD state INT NOT NULL
        ");
        $this->addSql("
            DROP INDEX training_session_unique_user ON claro_cursusbundle_course_session_user
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_session_user 
            ADD state INT NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_course_user 
            DROP state
        ");
        $this->addSql("
            CREATE UNIQUE INDEX training_session_unique_user ON claro_cursusbundle_course_course_user (course_id, user_id)
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_session_user 
            DROP state
        ");
        $this->addSql("
            CREATE UNIQUE INDEX training_session_unique_user ON claro_cursusbundle_course_session_user (session_id, user_id)
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_session_event_user 
            DROP state
        ");
        $this->addSql("
            CREATE UNIQUE INDEX training_event_unique_user ON claro_cursusbundle_session_event_user (event_id, user_id)
        ");
    }
}
