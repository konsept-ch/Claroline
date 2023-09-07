<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2023/09/07 10:03:35
 */
class Version20230907100335 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course 
            ADD session_days DOUBLE PRECISION DEFAULT "1" NOT NULL,
            DROP session_period
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course 
            ADD session_period VARCHAR(255) DEFAULT "fd" NOT NULL,
            DROP session_days
        ');
    }
}
