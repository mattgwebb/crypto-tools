<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190925165147 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE candle (id INT AUTO_INCREMENT NOT NULL, currency_id INT DEFAULT NULL, open_time INT NOT NULL, close_time INT NOT NULL, open_price DOUBLE PRECISION NOT NULL, close_price DOUBLE PRECISION NOT NULL, high_price DOUBLE PRECISION NOT NULL, low_price DOUBLE PRECISION NOT NULL, volume DOUBLE PRECISION NOT NULL, INDEX IDX_FB47D01A38248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, symbol VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candle ADD CONSTRAINT FK_FB47D01A38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE candle DROP FOREIGN KEY FK_FB47D01A38248176');
        $this->addSql('DROP TABLE candle');
        $this->addSql('DROP TABLE currency');
    }
}
