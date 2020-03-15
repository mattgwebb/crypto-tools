<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200315124140 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bot_algorithm CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE candle CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE exchange_id exchange_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency_pair CHANGE first_currency_id first_currency_id INT DEFAULT NULL, CHANGE second_currency_id second_currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trade ADD fill_price DOUBLE PRECISION NOT NULL, CHANGE algo_id algo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trend_line CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bot_algorithm CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE candle CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency CHANGE exchange_id exchange_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE currency_pair CHANGE first_currency_id first_currency_id INT DEFAULT NULL, CHANGE second_currency_id second_currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trade DROP fill_price, CHANGE algo_id algo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trend_line CHANGE currency_pair_id currency_pair_id INT DEFAULT NULL');
    }
}
