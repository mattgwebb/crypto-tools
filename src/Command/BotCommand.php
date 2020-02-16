<?php


namespace App\Command;


use App\Entity\BotAlgorithm;
use App\Entity\Candle;
use App\Entity\CurrencyPair;
use App\Service\ExternalDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;


class BotCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:run-bots-pair';

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
     * BotCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param ExternalDataService $dataService
     */
    public function __construct(EntityManagerInterface $entityManager, ExternalDataService $dataService, KernelInterface $kernel)
    {
        $this->entityManager= $entityManager;
        $this->dataService = $dataService;
        $this->projectDir = $kernel->getProjectDir();

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('currency_pair_id', InputArgument::REQUIRED, 'Currency pair id');
    }

    /**
     * TODO log bot actions (new channel)
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** For some reason pthreads doesn´t work in Symfony commands, this is a workaround */
        /*if ($phpHandler = set_exception_handler(function() {})) {
            restore_exception_handler();
            if (is_array($phpHandler) && $phpHandler[0] instanceof ErrorHandler) {
                $phpHandler[0]->setExceptionHandler(null);
            }
        }*/

        /** @var CurrencyPair $pair */
        $pair = $this->entityManager
            ->getRepository(CurrencyPair::class)
            ->find($input->getArgument('currency_pair_id'));

        if(!$pair) {
            $output->writeln(["ERROR: Pair not found"]);
            return;
        }

        $now = new \DateTime();
        $output->writeln([$now->format('d-m-Y H:i:s').": GETTING NEW CANDLES FOR ".$pair->getSymbol()]);

        /** @var int $newCandles */
        /** @var Candle $lastCandle */
        /** @var int $lastPrice */
        list($newCandles, $lastCandle, $lastPrice) = $this->dataService->loadPairNewCandles($pair);

        $output->writeln([
            "NEW CANDLES: $newCandles",
            "LATEST CANDLE: ".json_encode($lastCandle),
            "LATEST PRICE: $lastPrice"
        ]);

        $algos = $pair->getAlgos();

        $runningProcesses = [];

        /** @var BotAlgorithm $algo */
        foreach($algos as $algo) {
            //$output->writeln(["php", "bin\console", "app:run-bot", $algo->getId(), $lastPrice, "--no-debug"]);
            $process = new Process(["php", $this->projectDir."/bin/console", "app:run-bot", $algo->getId(), $lastPrice, $lastCandle->getId(), "--no-debug"]);
            $process->start();
            $runningProcesses[] = $process;
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


        /*$algos = $pair->getAlgos();
        $pool = new \Pool($algos->count());

        $lastCandleId = !$lastCandle->isEmpty() ? $lastCandle->getId() : 0;

        $kernelEnv = $GLOBALS['kernel']->getEnvironment();
        $kernelDebug = $GLOBALS['kernel']->isDebug();*/


        /** @var BotAlgorithm $algo */
        /*foreach($algos as $algo) {
            $pool->submit(new BotProcess($algo->getId(), $lastPrice, $lastCandleId, $kernelEnv, $kernelDebug));
        }

        while ($pool->collect());

        $pool->shutdown();*/
    }
}
