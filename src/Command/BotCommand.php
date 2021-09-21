<?php


namespace App\Command;


use App\Entity\Algorithm\AlgoModes;
use App\Entity\Algorithm\BotAccount;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Service\Data\ExternalDataService;
use App\Service\System\UtilsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;


class BotCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:bot:pair';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ExternalDataService
     */
    private $dataService;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UtilsService
     */
    private $utils;

    /**
     * BotCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param ExternalDataService $dataService
     * @param KernelInterface $kernel
     * @param LoggerInterface $botsLogger
     * @param UtilsService $utils
     */
    public function __construct(EntityManagerInterface $entityManager, ExternalDataService $dataService, KernelInterface $kernel,
                                LoggerInterface $botsLogger, UtilsService $utils)
    {
        $this->entityManager= $entityManager;
        $this->dataService = $dataService;
        $this->projectDir = $kernel->getProjectDir();
        $this->logger = $botsLogger;
        $this->utils = $utils;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('currency_pair_id', InputArgument::REQUIRED, 'Currency pair id');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var CurrencyPair $pair */
        $pair = $this->entityManager
            ->getRepository(CurrencyPair::class)
            ->find($input->getArgument('currency_pair_id'));

        if(!$pair) {
            $output->writeln(["ERROR: Pair not found"]);
            return;
        }

        $this->log("GETTING NEW CANDLES FOR ".$pair->getSymbol());

        /** @var int $newCandles */
        /** @var Candle $lastCandle */
        /** @var int $lastPrice */
        list($newCandles, $lastCandle, $lastPrice) = $this->dataService->loadPairNewCandles($pair);

        $this->log($pair->getSymbol()." NEW CANDLES: $newCandles");
        $this->log($pair->getSymbol()." LATEST CANDLE: ".json_encode($lastCandle));
        $this->log($pair->getSymbol()." LATEST PRICE: $lastPrice");

        $botAccounts = $this->entityManager
            ->getRepository(BotAccount::class)
            ->findAll();

        $runningProcesses = [];

        /** @var BotAccount $botAccount */
        foreach($botAccounts as $botAccount) {

            $algo = $botAccount->getAlgo();
            if($algo && $algo->getCurrencyPair() == $pair && $botAccount->getMode() != AlgoModes::NOT_ACTIVE){
                $process = new Process(["php", $this->projectDir."/bin/console", "app:bot:run", $botAccount->getId(), $lastPrice, $lastCandle->getId(), "--no-debug"]);
                $process->start();
                $runningProcesses[] = $process;
            }

            $dcaStrategy = $botAccount->getDcaStrategy();
            if($dcaStrategy && $dcaStrategy->getCurrencyPair() == $pair && $dcaStrategy->getMode() != AlgoModes::NOT_ACTIVE) {
                $process = new Process(["php", $this->projectDir."/bin/console", "app:bot:dca", $botAccount->getId(), $lastPrice, "--no-debug"]);
                $process->start();
                $runningProcesses[] = $process;
            }
        }

        while (count($runningProcesses)) {
            foreach ($runningProcesses as $i => $runningProcess) {
                // specific process is finished, so we remove it
                if (! $runningProcess->isRunning()) {
                    unset($runningProcesses[$i]);
                }

                // check every second
                sleep(1);
            }
        }
        $this->utils->checkTemperature();
    }

    /**
     * @param string $message
     */
    private function log(string $message)
    {
        try {
            $now = new \DateTime();
            $nowString = $now->format('d-m-Y H:i:s');
            $this->logger->info("$nowString: $message");
        } catch (\Exception $ex) {}
    }
}
