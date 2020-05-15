<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515154509 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE algo_test_result ADD currency_pair_id INT DEFAULT NULL AFTER algo_id');
        $this->addSql('ALTER TABLE algo_test_result ADD CONSTRAINT FK_1D11236AA311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('CREATE INDEX IDX_1D11236AA311484C ON algo_test_result (currency_pair_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE algo_test_result DROP FOREIGN KEY FK_1D11236AA311484C');
        $this->addSql('DROP INDEX IDX_1D11236AA311484C ON algo_test_result');
        $this->addSql('ALTER TABLE algo_test_result DROP currency_pair_id');
    }
}
