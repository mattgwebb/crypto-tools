<?php


namespace App\Command;


use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Data\Candle;
use App\Entity\Data\TimeFrames;
use App\Model\BotAlgorithmManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class AlgoWalkForwardTestCommand
 * @package App\Command
 */
class AlgoWalkForwardTestCommand extends Command
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
    protected static $defaultName = 'app:test:walkforward';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BotAlgorithmManager
     */
    private $algoManager;


    /**
     * AlgoWalkForwardTestCommand constructor.
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

        $this->addArgument('start_time', InputArgument::OPTIONAL, 'Start time', 1590969600);

        //default 100 days
        $this->addArgument('in_period_length', InputArgument::OPTIONAL, 'In period length in seconds', 8640000);

        //default 50 days
        $this->addArgument('out_period_length', InputArgument::OPTIONAL, 'Out period length in seconds', 4320000);
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

        foreach(self::ALL_TIMEFRAMES as $timeFrame) {
            $algo->setTimeFrame($timeFrame);
            $this->algoManager->runWalkForwardTest($algo, $input->getArgument('start_time'),
                $input->getArgument('in_period_length'), $input->getArgument('out_period_length'));
            $this->entityManager->clear(Candle::class);
        }
    }
}
