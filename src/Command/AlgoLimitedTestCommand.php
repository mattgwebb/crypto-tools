<?php


namespace App\Command;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\TestTypes;
use App\Entity\Data\Candle;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\TimeFrames;
use App\Model\BotAlgorithmManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AlgoLimitedTestCommand extends Command
{
    const ALL_TIMEFRAMES = [
        //TimeFrames::TIMEFRAME_5M,
        //TimeFrames::TIMEFRAME_15M,
        //TimeFrames::TIMEFRAME_30M,
        //TimeFrames::TIMEFRAME_45M,
        //TimeFrames::TIMEFRAME_1H,
        TimeFrames::TIMEFRAME_2H,
        //TimeFrames::TIMEFRAME_3H,
        //TimeFrames::TIMEFRAME_4H,
        //TimeFrames::TIMEFRAME_1D,
        //TimeFrames::TIMEFRAME_1W
    ];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:test:limited';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BotAlgorithmManager
     */
    private $algoManager;


    /**
     * BatchAlgoTestCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param BotAlgorithmManager $algoManager
     */
    public function __construct(EntityManagerInterface $entityManager, BotAlgorithmManager $algoManager)
    {
        $this->entityManager = $entityManager;
        $this->algoManager = $algoManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('algo_id', InputArgument::REQUIRED, 'Algo id');
        $this->addArgument('start_time', InputArgument::OPTIONAL, 'Start time', 1503014400);
        $this->addArgument('end_time', InputArgument::OPTIONAL, 'End time', 1524009600);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var BotAlgorithm $algo */
        $algo = $this->entityManager
            ->getRepository(BotAlgorithm::class)
            ->find($input->getArgument('algo_id'));

        if (!$algo) {
            $output->writeln([
                "ERROR",
                "Algo not found"
            ]);
            return;
        }

        // TODO possibly get random start time

        $originalTimeFame = $algo->getTimeFrame();
        $originalEntryStrategy = $algo->getEntryStrategyCombination();
        $originalExitStrategy = $algo->getExitStrategyCombination();

        foreach(self::ALL_TIMEFRAMES as $timeFrame) {
            $algo->setTimeFrame($timeFrame);
            $this->algoManager->runLimitedTest($algo, $input->getArgument('start_time'), $input->getArgument('end_time'));
            $this->entityManager->clear(Candle::class);
        }

        // TODO find better way to stop the timeframe from updating
        $algo->setTimeFrame($originalTimeFame);
        $algo->setEntryStrategyCombination($originalEntryStrategy);
        $algo->setExitStrategyCombination($originalExitStrategy);

        $this->entityManager->persist($algo);
        $this->entityManager->flush();
    }
}
