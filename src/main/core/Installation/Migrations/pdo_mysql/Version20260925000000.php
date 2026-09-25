<?php

namespace Claroline\CoreBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration CEP — index de prefixe sur claro_resource_node.path.
 *
 * Contexte
 * --------
 * ResourceNode porte @Gedmo\Tree(type="materializedPath"). A chaque creation de
 * ressource, l'extension Tree cherche les descendants du noeud avec :
 *
 *     SELECT c0_.* FROM claro_resource_node c0_
 *     WHERE c0_.path LIKE '<chemin du parent>%' AND c0_.path <> ?
 *     ORDER BY c0_.path ASC
 *
 * Version20220922111929 a fait passer `path` de VARCHAR(3000) a LONGTEXT sans
 * poser d'index de remplacement. Un LONGTEXT ne peut pas etre indexe sans
 * longueur de prefixe : la requete ci-dessus degenere donc en balayage complet
 * de la table, colonnes LONGTEXT comprises, suivi d'un filesort.
 *
 * Mesure sur la base de production copiee en local (140 195 lignes, longueur
 * moyenne de `path` 1 161 caracteres) :
 *
 *     sans index : requete a 5,145 s, creation de ressource en 7,3 a 10,6 s
 *     avec index : requete a 0,010 s, creation de ressource en 1,9 a 2,5 s
 *
 * En production, ce cout depasse par moments le delai maximal d'execution et la
 * creation de ressource repond alors HTTP 500 avec une page HTML. Le middleware
 * Former22 avalait cette erreur, d'ou des attestations annoncees comme generees
 * et absentes des comptes participants.
 *
 * Voir docs/90-incidents/investigation_attestations_che_plantes_2026-09.md
 *
 * Longueur de prefixe
 * -------------------
 * 255 caracteres. La table est en utf8mb3 et ROW_FORMAT=Dynamic, soit 768 octets
 * de cle, sous la limite de 3 072 octets d'InnoDB. Un prefixe plus court suffirait
 * a discriminer la plupart des chemins, mais 255 couvre les arborescences
 * profondes sans surcout mesurable.
 */
class Version20260925000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_resource_node
            ADD INDEX IDX_A76799FF_PATH (path(255))
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE claro_resource_node
            DROP INDEX IDX_A76799FF_PATH
        ');
    }
}
