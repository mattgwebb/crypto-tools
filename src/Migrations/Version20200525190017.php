<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200525190017 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bot_account (id INT AUTO_INCREMENT NOT NULL, algo_id INT DEFAULT NULL, exchange_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, trade_status SMALLINT NOT NULL, mode SMALLINT NOT NULL, INDEX IDX_4E51DB3B1ECC0724 (algo_id), INDEX IDX_4E51DB3B68AFD1A0 (exchange_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bot_account ADD CONSTRAINT FK_4E51DB3B1ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE bot_account ADD CONSTRAINT FK_4E51DB3B68AFD1A0 FOREIGN KEY (exchange_id) REFERENCES exchange (id)');
        $this->addSql('ALTER TABLE bot_algorithm DROP trade_status, DROP mode');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bot_account');
        $this->addSql('ALTER TABLE bot_algorithm ADD trade_status SMALLINT NOT NULL, ADD mode SMALLINT NOT NULL');
    }
}
