<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200405124742 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_adaptive_pq_config (algo_id INT NOT NULL, p_value DOUBLE PRECISION NOT NULL, q_value DOUBLE PRECISION NOT NULL, ma_indicator VARCHAR(255) NOT NULL, ma_period INT NOT NULL, oscillator_indicator VARCHAR(255) NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE algo_adaptive_pq_config ADD CONSTRAINT FK_6C8C524E1ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE algo_adaptive_pq_config');
    }
}
