<?php

namespace Claroline\CursusBundle\Installation\Migrations;

use Claroline\InstallationBundle\Migrations\Helper\ConditionalMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2024/11/29 09:45:56
 */
final class Version20241129000000 extends AbstractMigration
{
    use ConditionalMigrationTrait;

    public function up(Schema $schema): void
    {
        if ($this->checkColumnExists('claro_cursusbundle_course', 'session_duration', $this->connection)) {
            $this->addSql('
                ALTER TABLE claro_cursusbundle_course CHANGE session_duration session_duration DOUBLE PRECISION DEFAULT 1 NOT NULL
            ');
        }
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            DROP FOREIGN KEY FK_DFE5E1FE349A94C7
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            DROP FOREIGN KEY FK_DFE5E1FEEE7B114B
        ');

        if ($this->checkColumnExists('claro_cursusbundle_presence_status', 'evidences', $this->connection)) {
            $this->addSql('
                ALTER TABLE claro_cursusbundle_presence_status CHANGE evidences evidence JSON DEFAULT NULL
            ');
        }

        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            ADD CONSTRAINT FK_DFE5E1FEE8DE7170 FOREIGN KEY (updatedBy) 
            REFERENCES claro_user (id) 
            ON DELETE SET NULL
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            ADD CONSTRAINT FK_DFE5E1FEEE7B114B FOREIGN KEY (evidence_added_by) 
            REFERENCES claro_user (id) 
            ON DELETE SET NULL
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course_session 
            DROP plainDescription, 
            DROP entity_name, 
            DROP entity_order, 
            DROP poster, 
            DROP thumbnail
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE claro_cursusbundle_course CHANGE session_duration session_duration DOUBLE PRECISION DEFAULT '1' NOT NULL
        ");
        $this->addSql('
            ALTER TABLE claro_cursusbundle_course_session 
            ADD plainDescription VARCHAR(255) DEFAULT NULL, 
            ADD entity_name VARCHAR(255) NOT NULL, 
            ADD entity_order INT NOT NULL, 
            ADD poster VARCHAR(255) DEFAULT NULL, 
            ADD thumbnail VARCHAR(255) DEFAULT NULL
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            DROP FOREIGN KEY FK_DFE5E1FEE8DE7170
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            DROP FOREIGN KEY FK_DFE5E1FEEE7B114B
        ');
        $this->addSql("
            ALTER TABLE claro_cursusbundle_presence_status CHANGE evidence evidence LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'
        ");
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            ADD CONSTRAINT FK_DFE5E1FE349A94C7 FOREIGN KEY (updatedBy) 
            REFERENCES claro_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status 
            ADD CONSTRAINT FK_DFE5E1FEEE7B114B FOREIGN KEY (evidence_added_by) 
            REFERENCES claro_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ');
        $this->addSql('
            ALTER TABLE claro_cursusbundle_presence_status RENAME INDEX idx_dfe5e1fee8de7170 TO IDX_DFE5E1FE349A94C7
        ');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
