<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200527174328 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE trade DROP FOREIGN KEY FK_7E1A43661ECC0724');
        $this->addSql('DROP INDEX IDX_7E1A43661ECC0724 ON trade');
        $this->addSql('ALTER TABLE trade ADD bot_account_id INT DEFAULT NULL, DROP algo_id');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A43662160998D FOREIGN KEY (bot_account_id) REFERENCES bot_account (id)');
        $this->addSql('CREATE INDEX IDX_7E1A43662160998D ON trade (bot_account_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE trade DROP FOREIGN KEY FK_7E1A43662160998D');
        $this->addSql('DROP INDEX IDX_7E1A43662160998D ON trade');
        $this->addSql('ALTER TABLE trade ADD algo_id INT DEFAULT NULL, DROP bot_account_id');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A43661ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('CREATE INDEX IDX_7E1A43661ECC0724 ON trade (algo_id)');
    }
}
