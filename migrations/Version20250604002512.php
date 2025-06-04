<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604002512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE blurb (id SERIAL NOT NULL, profile_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, text TEXT NOT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B8AF2A30CCFA12B8 ON blurb (profile_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE blurb ADD CONSTRAINT FK_B8AF2A30CCFA12B8 FOREIGN KEY (profile_id) REFERENCES admin (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE blurb DROP CONSTRAINT FK_B8AF2A30CCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE blurb
        SQL);
    }
}
