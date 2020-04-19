<?php


namespace App\Command;


use App\Service\Data\ExternalDataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ExternalIndicatorDataCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:get-external-indicator-data';

    /**
     * @var ExternalDataService
     */
    private $dataService;


    /**
     * ExternalDataCommand constructor.
     * @param ExternalDataService $dataService
     */
    public function __construct(ExternalDataService $dataService)
    {
        parent::__construct();
        $this->dataService = $dataService;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newData = $this->dataService->loadAllExternalIndicatorData();
        foreach($newData as $type => $newEntries) {
            $output->writeln([
                "New $type entries: $newEntries"
            ]);
        }
    }
}
