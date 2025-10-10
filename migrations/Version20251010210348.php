<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010210348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE asset (id SERIAL NOT NULL, site_id INT NOT NULL, path VARCHAR(255) NOT NULL, metadata JSON DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2AF5A5CF6BD1646 ON asset (site_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE block (id SERIAL NOT NULL, page_id INT NOT NULL, parent_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, text TEXT DEFAULT NULL, data JSON DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_831B9722C4663E4 ON block (page_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_831B9722727ACA70 ON block (parent_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP CONSTRAINT FK_2AF5A5CF6BD1646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block DROP CONSTRAINT FK_831B9722C4663E4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE block DROP CONSTRAINT FK_831B9722727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE block
        SQL);
    }
}
