<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191111175159 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE trade (id INT AUTO_INCREMENT NOT NULL, currency_pair_id INT DEFAULT NULL, order_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION NOT NULL, time_stamp INT NOT NULL, type SMALLINT NOT NULL, status SMALLINT NOT NULL, INDEX IDX_7E1A4366A311484C (currency_pair_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A4366A311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('ALTER TABLE bot_algorithm ADD trade_status SMALLINT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE trade');
        $this->addSql('ALTER TABLE bot_algorithm DROP trade_status');
    }
}
