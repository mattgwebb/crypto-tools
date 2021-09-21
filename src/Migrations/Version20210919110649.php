<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210919110649 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dcastrategy (bot_account_id INT NOT NULL, currency_pair_id INT DEFAULT NULL, last_trade_id INT DEFAULT NULL, trade_amount DOUBLE PRECISION NOT NULL, frequency VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, mode SMALLINT NOT NULL, INDEX IDX_8B26E446A311484C (currency_pair_id), UNIQUE INDEX UNIQ_8B26E446744CD2FE (last_trade_id), PRIMARY KEY(bot_account_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dcastrategy ADD CONSTRAINT FK_8B26E4462160998D FOREIGN KEY (bot_account_id) REFERENCES bot_account (id)');
        $this->addSql('ALTER TABLE dcastrategy ADD CONSTRAINT FK_8B26E446A311484C FOREIGN KEY (currency_pair_id) REFERENCES currency_pair (id)');
        $this->addSql('ALTER TABLE dcastrategy ADD CONSTRAINT FK_8B26E446744CD2FE FOREIGN KEY (last_trade_id) REFERENCES trade (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE dcastrategy');
    }
}
