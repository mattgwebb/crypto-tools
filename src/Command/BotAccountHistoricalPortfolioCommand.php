<?php


namespace App\Command;


use App\Model\CandleManager;
use App\Service\Algorithm\BotAccountService;
use App\Service\Trade\TradeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class BotAccountHistoricalPortfolioCommand extends Command
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:bot:portfolio';

    /**
     * @var TradeService
     */
    private $tradeService;

    /**
     * @var BotAccountService
     */
    private $botAccountService;

    /**
     * @var CandleManager
     */
    private $candleManager;

    /**
     * BotAccountHistoricalPortfolioCommand constructor.
     * @param TradeService $tradeService
     * @param BotAccountService $botAccountService
     */
    public function __construct(TradeService $tradeService, BotAccountService $botAccountService, CandleManager $candleManager)
    {
        $this->tradeService = $tradeService;
        $this->botAccountService = $botAccountService;
        $this->candleManager = $candleManager;

        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $botAccounts = $this->botAccountService->getAllBotAccounts();

        foreach($botAccounts as $botAccount) {
            $pair = $botAccount->getAlgo()->getCurrencyPair();
            $latestCandle = $this->candleManager->getLatestCandle($pair);
            $this->tradeService->calculateBotAccountPnL($botAccount, $latestCandle->getClosePrice());
        }
    }
}
