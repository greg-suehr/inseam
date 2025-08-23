<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823225112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE profile_user_site DROP CONSTRAINT fk_af5dac56f6bd1646
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE compat_stylesheet (id BIGSERIAL NOT NULL, plan_id_fk BIGINT NOT NULL, scope_class VARCHAR(128) NOT NULL, css_text TEXT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE import_discovery (id BIGSERIAL NOT NULL, session_id BIGINT NOT NULL, graph_id VARCHAR(64) NOT NULL, graph_json JSON NOT NULL, stats JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_discovery_graph ON import_discovery (graph_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN import_discovery.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE import_execution (id BIGSERIAL NOT NULL, plan_id_fk BIGINT NOT NULL, status VARCHAR(32) NOT NULL, progress JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN import_execution.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE import_session (id BIGSERIAL NOT NULL, site_id VARCHAR(64) NOT NULL, platform VARCHAR(32) NOT NULL, source TEXT NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN import_session.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE redirect_rule (id BIGSERIAL NOT NULL, plan_id_fk BIGINT NOT NULL, old_url TEXT NOT NULL, new_route TEXT NOT NULL, applied BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_redirect_old ON redirect_rule (old_url)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE stored_import_plan (id BIGSERIAL NOT NULL, session_id BIGINT NOT NULL, plan_id VARCHAR(64) NOT NULL, checksum VARCHAR(64) NOT NULL, plan_json JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_plan_checksum ON stored_import_plan (checksum)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN stored_import_plan.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE compat_stylesheet
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE import_discovery
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE import_execution
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE import_session
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE redirect_rule
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE stored_import_plan
        SQL);
    }
}
