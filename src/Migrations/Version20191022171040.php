<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191022171040 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bot_algorithm (id INT AUTO_INCREMENT NOT NULL, currency_pair_id INT DEFAULT NULL, time_frame INT NOT NULL, strategy VARCHAR(255) NOT NULL, stop_loss INT NOT NULL, take_profit INT NOT NULL, observations LONGTEXT NOT NULL, INDEX IDX_4EF5FE4DA311484C (currency_pair_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candle (id INT AUTO_INCREMENT NOT NULL, currency_pair_id INT DEFAULT NULL, open_time INT NOT NULL, close_time INT NOT NULL, open_price DOUBLE PRECISION NOT NULL, close_price DOUBLE PRECISION NOT NULL, high_price DOUBLE PRECISION NOT NULL, low_price DOUBLE PRECISION NOT NULL, volume DOUBLE PRECISION NOT NULL, INDEX IDX_FB47D01AA311484C (currency_pair_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, exchange_id INT DEFAULT NULL, symbol VARCHAR(255) NOT NULL, balance DOUBLE PRECISION NOT NULL, INDEX IDX_6956883F68AFD1A0 (exchange_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency_pair (id INT AUTO_INCREMENT NOT NULL, first_currency_id INT DEFAULT NULL, second_currency_id INT DEFAULT NULL, symbol VARCHAR(255) NOT NULL, INDEX IDX_83ED5D1DA6D18CB4 (first_currency_id), INDEX IDX_83ED5D1D51C11D68 (second_currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE exchange (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trend_line (id INT AUTO_INCREMENT NOT NULL, currency_pair_id INT DEFAULT NULL, type INT NOT NULL, start_price DOUBLE PRECISION NOT NULL, end_price DOUBLE PRECISION NOT NULL, start_time INT NOT NULL, end_time INT NOT NULL, INDEX IDX_5889411CA311484C (currency_pair_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bot_algorithm ADD CONSTRAINT FK_4EF5FE4DA311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('ALTER TABLE candle ADD CONSTRAINT FK_FB47D01AA311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('ALTER TABLE currency ADD CONSTRAINT FK_6956883F68AFD1A0 FOREIGN KEY (exchange_id) REFERENCES exchange (id)');
        $this->addSql('ALTER TABLE currency_pair ADD CONSTRAINT FK_83ED5D1DA6D18CB4 FOREIGN KEY (first_currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE currency_pair ADD CONSTRAINT FK_83ED5D1D51C11D68 FOREIGN KEY (second_currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE trend_line ADD CONSTRAINT FK_5889411CA311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency_pair DROP FOREIGN KEY FK_83ED5D1DA6D18CB4');
        $this->addSql('ALTER TABLE currency_pair DROP FOREIGN KEY FK_83ED5D1D51C11D68');
        $this->addSql('ALTER TABLE bot_algorithm DROP FOREIGN KEY FK_4EF5FE4DA311484C');
        $this->addSql('ALTER TABLE candle DROP FOREIGN KEY FK_FB47D01AA311484C');
        $this->addSql('ALTER TABLE trend_line DROP FOREIGN KEY FK_5889411CA311484C');
        $this->addSql('ALTER TABLE currency DROP FOREIGN KEY FK_6956883F68AFD1A0');
        $this->addSql('DROP TABLE bot_algorithm');
        $this->addSql('DROP TABLE candle');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE currency_pair');
        $this->addSql('DROP TABLE exchange');
        $this->addSql('DROP TABLE trend_line');
    }
}
