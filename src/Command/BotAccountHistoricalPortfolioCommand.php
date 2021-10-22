<?php


namespace App\Command;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Data\CurrencyPair;
use App\Entity\Trade\BotAccountHistoricalPortfolio;
use App\Model\CandleManager;
use App\Service\Algorithm\BotAccountService;
use App\Service\Data\ExternalDataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class BotAccountHistoricalPortfolioCommand extends Command
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:bot:portfolio';

    /**
     * @var ExternalDataService
     */
    private $externalDataService;

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
     * @param ExternalDataService $externalDataService
     * @param BotAccountService $botAccountService
     * @param CandleManager $candleManager
     */
    public function __construct(ExternalDataService $externalDataService, BotAccountService $botAccountService, CandleManager $candleManager)
    {
        $this->externalDataService = $externalDataService;
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
            if($botAccount->getMode() <> AlgoModes::LIVE) {
                continue;
            }
            /** @var CurrencyPair $pair */
            $pair = $botAccount->getAlgo()->getCurrencyPair();
            $latestCandle = $this->candleManager->getLatestCandle($pair);

            $firstAsset = $pair->getFirstCurrency();
            $secondAsset = $pair->getSecondCurrency();

            $netBalanceUSDT = 0.00;
            $balances = $this->externalDataService->loadBalances($botAccount);

            foreach($balances as $asset => $balance) {
                if($asset == $firstAsset->getSymbol()) {
                    $netBalanceUSDT += (float)$balance['netAsset'] * $latestCandle->getClosePrice();
                } else if($asset == $secondAsset->getSymbol()) {
                    $netBalanceUSDT += (float)$balance['netAsset'];
                }
            }
            $dailyPortfolioData = new BotAccountHistoricalPortfolio();
            $dailyPortfolioData->setBotAccount($botAccount);
            $dailyPortfolioData->setTimeStamp(time());
            $dailyPortfolioData->setTotalValue($netBalanceUSDT);
            $this->botAccountService->saveHistoricalPortfolioValue($dailyPortfolioData);
        }
    }
}
