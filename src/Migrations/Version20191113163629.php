<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191113163629 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE trade DROP FOREIGN KEY FK_7E1A4366A311484C');
        $this->addSql('DROP INDEX IDX_7E1A4366A311484C ON trade');
        $this->addSql('ALTER TABLE trade CHANGE currency_pair_id algo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A43661ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('CREATE INDEX IDX_7E1A43661ECC0724 ON trade (algo_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE trade DROP FOREIGN KEY FK_7E1A43661ECC0724');
        $this->addSql('DROP INDEX IDX_7E1A43661ECC0724 ON trade');
        $this->addSql('ALTER TABLE trade CHANGE algo_id currency_pair_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A4366A311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('CREATE INDEX IDX_7E1A4366A311484C ON trade (currency_pair_id)');
    }
}
