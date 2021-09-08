<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210908141829 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bot_account_historical_portfolio (id INT AUTO_INCREMENT NOT NULL, bot_account_id INT DEFAULT NULL, time_stamp INT NOT NULL, total_value DOUBLE PRECISION NOT NULL, pnl_percentage DOUBLE PRECISION NOT NULL, pnl_amount DOUBLE PRECISION NOT NULL, INDEX IDX_FED0E9992160998D (bot_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bot_account_historical_portfolio ADD CONSTRAINT FK_FED0E9992160998D FOREIGN KEY (bot_account_id) REFERENCES bot_account (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE bot_account_historical_portfolio');
    }
}
