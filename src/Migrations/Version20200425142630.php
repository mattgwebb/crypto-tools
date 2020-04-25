<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200425142630 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_strategies (id INT AUTO_INCREMENT NOT NULL, algo_id INT DEFAULT NULL, strategy_id INT DEFAULT NULL, type INT NOT NULL, INDEX IDX_CAABCF101ECC0724 (algo_id), INDEX IDX_CAABCF10D5CAD932 (strategy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE algo_strategies ADD CONSTRAINT FK_CAABCF101ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_strategies ADD CONSTRAINT FK_CAABCF10D5CAD932 FOREIGN KEY (strategy_id) REFERENCES strategy (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE algo_strategies');
    }
}
