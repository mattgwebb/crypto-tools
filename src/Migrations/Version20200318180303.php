<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200318180303 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_ema_crossover_config (algo_id INT NOT NULL, small_period INT NOT NULL, long_period INT NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE algo_rsi_config (algo_id INT NOT NULL, buy_under DOUBLE PRECISION NOT NULL, sell_over DOUBLE PRECISION NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE algo_ema_crossover_config ADD CONSTRAINT FK_A4717A681ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_rsi_config ADD CONSTRAINT FK_CDD83D11ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE algo_ema_crossover_config');
        $this->addSql('DROP TABLE algo_rsi_config');
    }
}
