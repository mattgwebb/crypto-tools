<?php


namespace App\Command;


use App\Entity\Data\TimeFrames;
use App\Repository\Data\CandleRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ClearDataCommand extends Command
{
    // Candles that are more than DAYS_VALID_CANDLE_DATA days old will be deleted
    const DAYS_VALID_CANDLE_DATA = 50;


    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:data:clear';

    /**
     * @var CandleRepository
     */
    private $repo;


    /**
     * ClearDataCommand constructor.
     * @param CandleRepository $dataService
     */
    public function __construct(CandleRepository $repo)
    {
        parent::__construct();
        $this->repo = $repo;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lastDailyOpen = $this->getLastOpen(TimeFrames::TIMEFRAME_1D * 60);
        $validTimeRange = self::DAYS_VALID_CANDLE_DATA * 86400;
        $timestampDeleteBefore = $lastDailyOpen - $validTimeRange;

        $this->repo->deleteCandlesBeforeTimestamp($timestampDeleteBefore);
    }

    /**
     * @param int $timeFrameSeconds
     * @return int
     */
    private function getLastOpen(int $timeFrameSeconds)
    {
        $now = time();
        return (int)(floor($now / $timeFrameSeconds) * $timeFrameSeconds);
    }
}
