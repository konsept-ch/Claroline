<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2024/10/01 07:35:51
 */
class Version20241001073547 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course 
            ADD generateInvoice TINYINT(1) DEFAULT '0' NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course 
            DROP generateInvoice
        ");
    }
}
