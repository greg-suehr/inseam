<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604000709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bio_tag (id SERIAL NOT NULL, profile_id INT NOT NULL, title VARCHAR(255) NOT NULL, text VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DA7A4DD9CCFA12B8 ON bio_tag (profile_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bio_tag ADD CONSTRAINT FK_DA7A4DD9CCFA12B8 FOREIGN KEY (profile_id) REFERENCES admin (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bio_tag DROP CONSTRAINT FK_DA7A4DD9CCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bio_tag
        SQL);
    }
}
