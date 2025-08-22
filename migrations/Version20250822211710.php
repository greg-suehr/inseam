<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822211710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, schema JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN category.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE content (id SERIAL NOT NULL, category_id INT NOT NULL, data JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FEC530A912469DE2 ON content (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN content.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE page (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, data JSONB NOT NULL, is_published BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, page_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN page.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE profile_user (id INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE profile_user_site (profile_user_id INT NOT NULL, site_id INT NOT NULL, PRIMARY KEY(profile_user_id, site_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF5DAC5674D00D09 ON profile_user_site (profile_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF5DAC56F6BD1646 ON profile_user_site (site_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE revision (id SERIAL NOT NULL, table_name VARCHAR(255) NOT NULL, data JSON NOT NULL, archived_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN revision.archived_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE public.sites (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, is_production BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8DD1CB07A7A91E0B ON public.sites (domain)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN public.sites.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id SERIAL NOT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dtype VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE content ADD CONSTRAINT FK_FEC530A912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user ADD CONSTRAINT FK_3B5B59DDBF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user_site ADD CONSTRAINT FK_AF5DAC5674D00D09 FOREIGN KEY (profile_user_id) REFERENCES profile_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user_site ADD CONSTRAINT FK_AF5DAC56F6BD1646 FOREIGN KEY (site_id) REFERENCES public.sites (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE content DROP CONSTRAINT FK_FEC530A912469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user DROP CONSTRAINT FK_3B5B59DDBF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user_site DROP CONSTRAINT FK_AF5DAC5674D00D09
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user_site DROP CONSTRAINT FK_AF5DAC56F6BD1646
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE content
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE page
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE profile_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE profile_user_site
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE revision
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE public.sites
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
