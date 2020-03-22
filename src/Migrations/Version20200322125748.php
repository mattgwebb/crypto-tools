<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200322125748 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_test_result (id INT AUTO_INCREMENT NOT NULL, algo_id INT DEFAULT NULL, time_frame INT NOT NULL, timestamp INT NOT NULL, start_time INT NOT NULL, end_time INT NOT NULL, percentage DOUBLE PRECISION NOT NULL, observations LONGTEXT NOT NULL, trades INT NOT NULL, INDEX IDX_1D11236A1ECC0724 (algo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE algo_test_result ADD CONSTRAINT FK_1D11236A1ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE algo_test_result');
    }
}
