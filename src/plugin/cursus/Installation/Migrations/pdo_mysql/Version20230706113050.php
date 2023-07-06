<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2023/07/06 11:30:50
 */
class Version20230706113050 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course 
            ADD session_period VARCHAR(255) DEFAULT "fd" NOT NULL,
            DROP session_days,
            DROP session_hours
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course 
            ADD session_hours DOUBLE PRECISION DEFAULT "0" NOT NULL,
            ADD session_days DOUBLE PRECISION DEFAULT "1" NOT NULL,
            DROP session_period
        ');
    }
}
