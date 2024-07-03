<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2024/07/02 04:07:09
 */
class Version20240702160706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            DROP INDEX training_session_unique_user ON claro_cursusbundle_course_session_user
        ");
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_session_user 
            ADD cancelled TINYINT(1) NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course_session_user 
            DROP cancelled
        ");
        $this->addSql("
            CREATE UNIQUE INDEX training_session_unique_user ON claro_cursusbundle_course_session_user (session_id, user_id)
        ");
    }
}
