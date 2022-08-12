<?php

namespace Claroline\CursusBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2022/08/09 08:12:11
 */
class Version20220809081209 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_quota 
            ADD `default` LONGTEXT NOT NULL COMMENT "(DC2Type:json)", 
            ADD years LONGTEXT NOT NULL COMMENT "(DC2Type:json)" DEFAULT "{}"
        ');
        $this->addSql('
            UPDATE claro_cursusbundle_quota AS a INNER JOIN claro_cursusbundle_quota AS b ON a.id = b.id SET a.default = CONCAT(\'{"quota":\', b.threshold, \',"enabled":\', b.use_quotas, \'}\')
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_quota 
            DROP threshold, 
            DROP use_quotas
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_cursusbundle_quota 
            ADD threshold DOUBLE PRECISION NOT NULL, 
            ADD use_quotas TINYINT(1) NOT NULL, 
            DROP `default`, 
            DROP years
        ');
    }
}
