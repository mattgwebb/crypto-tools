<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200426144317 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_oscillator_config (algo_id INT NOT NULL, buy_under DOUBLE PRECISION NOT NULL, sell_over DOUBLE PRECISION NOT NULL, calculation_period INT NOT NULL, cross_only TINYINT(1) NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE algo_oscillator_config ADD CONSTRAINT FK_15620E401ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('DROP TABLE algo_rsi_config');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_rsi_config (algo_id INT NOT NULL, buy_under DOUBLE PRECISION NOT NULL, sell_over DOUBLE PRECISION NOT NULL, calculation_period INT NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE algo_rsi_config ADD CONSTRAINT FK_CDD83D11ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('DROP TABLE algo_oscillator_config');
    }
}
