<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191002161456 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE exchange (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE currency ADD exchange_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency ADD CONSTRAINT FK_6956883F68AFD1A0 FOREIGN KEY (exchange_id) REFERENCES exchange (id)');
        $this->addSql('CREATE INDEX IDX_6956883F68AFD1A0 ON currency (exchange_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency DROP FOREIGN KEY FK_6956883F68AFD1A0');
        $this->addSql('DROP TABLE exchange');
        $this->addSql('DROP INDEX IDX_6956883F68AFD1A0 ON currency');
        $this->addSql('ALTER TABLE currency DROP exchange_id');
    }
}
