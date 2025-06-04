<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604002843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bio_link (id SERIAL NOT NULL, profile_id INT NOT NULL, title VARCHAR(255) NOT NULL, hyperlink TEXT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BDCBD2E1CCFA12B8 ON bio_link (profile_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bio_link ADD CONSTRAINT FK_BDCBD2E1CCFA12B8 FOREIGN KEY (profile_id) REFERENCES admin (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bio_link DROP CONSTRAINT FK_BDCBD2E1CCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bio_link
        SQL);
    }
}
