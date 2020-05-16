<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200516140039 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE algo_adaptive_pq_config');
        $this->addSql('DROP TABLE algo_divergence_config');
        $this->addSql('DROP TABLE algo_ma_config');
        $this->addSql('DROP TABLE algo_ma_crossover_config');
        $this->addSql('DROP TABLE algo_oscillator_config');
        $this->addSql('DROP TABLE algo_strategies');
        $this->addSql('ALTER TABLE bot_algorithm DROP stop_loss, DROP take_profit');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE algo_adaptive_pq_config (algo_id INT NOT NULL, p_value DOUBLE PRECISION NOT NULL, q_value DOUBLE PRECISION NOT NULL, ma_indicator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ma_period INT NOT NULL, oscillator_indicator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE algo_divergence_config (algo_id INT NOT NULL, last_candles INT NOT NULL, min_candle_difference INT NOT NULL, min_divergence_percentage INT NOT NULL, regular_divergences TINYINT(1) NOT NULL, hidden_divergences TINYINT(1) NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE algo_ma_config (algo_id INT NOT NULL, period INT NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE algo_ma_crossover_config (algo_id INT NOT NULL, small_period INT NOT NULL, long_period INT NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE algo_oscillator_config (algo_id INT NOT NULL, buy_under DOUBLE PRECISION NOT NULL, sell_over DOUBLE PRECISION NOT NULL, calculation_period INT NOT NULL, cross_only TINYINT(1) NOT NULL, PRIMARY KEY(algo_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE algo_strategies (id INT AUTO_INCREMENT NOT NULL, algo_id INT DEFAULT NULL, strategy_id INT DEFAULT NULL, type INT NOT NULL, INDEX IDX_CAABCF101ECC0724 (algo_id), INDEX IDX_CAABCF10D5CAD932 (strategy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE algo_adaptive_pq_config ADD CONSTRAINT FK_6C8C524E1ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_divergence_config ADD CONSTRAINT FK_C00089E01ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_ma_config ADD CONSTRAINT FK_82619FD61ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_ma_crossover_config ADD CONSTRAINT FK_C4C50C151ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_oscillator_config ADD CONSTRAINT FK_15620E401ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE algo_strategies ADD CONSTRAINT FK_CAABCF101ECC0724 FOREIGN KEY (algo_id) REFERENCES bot_algorithm (id)');
        $this->addSql('ALTER TABLE bot_algorithm ADD stop_loss INT NOT NULL, ADD take_profit INT NOT NULL');
    }
}
