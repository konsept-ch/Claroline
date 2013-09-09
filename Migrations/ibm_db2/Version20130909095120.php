<?php

namespace Claroline\AnnouncementBundle\Migrations\ibm_db2;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/09/09 09:51:21
 */
class Version20130909095120 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_announcement_aggregate (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY NOT NULL, 
                resourceNode_id INTEGER DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_79BF2C8CB87FAB32 ON claro_announcement_aggregate (resourceNode_id)
        ");
        $this->addSql("
            CREATE TABLE claro_announcement (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY NOT NULL, 
                creator_id INTEGER NOT NULL, 
                aggregate_id INTEGER NOT NULL, 
                title VARCHAR(255) DEFAULT NULL, 
                content VARCHAR(1023) NOT NULL, 
                announcer VARCHAR(255) DEFAULT NULL, 
                creation_date TIMESTAMP(0) NOT NULL, 
                publication_date TIMESTAMP(0) DEFAULT NULL, 
                visible SMALLINT NOT NULL, 
                visible_from TIMESTAMP(0) DEFAULT NULL, 
                visible_until TIMESTAMP(0) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_778754E361220EA6 ON claro_announcement (creator_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_778754E3D0BBCCBE ON claro_announcement (aggregate_id)
        ");
        $this->addSql("
            ALTER TABLE claro_announcement_aggregate 
            ADD CONSTRAINT FK_79BF2C8CB87FAB32 FOREIGN KEY (resourceNode_id) 
            REFERENCES claro_resource_node (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_announcement 
            ADD CONSTRAINT FK_778754E361220EA6 FOREIGN KEY (creator_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_announcement 
            ADD CONSTRAINT FK_778754E3D0BBCCBE FOREIGN KEY (aggregate_id) 
            REFERENCES claro_announcement_aggregate (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_announcement 
            DROP FOREIGN KEY FK_778754E3D0BBCCBE
        ");
        $this->addSql("
            DROP TABLE claro_announcement_aggregate
        ");
        $this->addSql("
            DROP TABLE claro_announcement
        ");
    }
}