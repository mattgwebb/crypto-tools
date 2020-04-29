<?php


namespace App\Service\Algorithm;


use App\Entity\Algorithm\StrategyCombination;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\TechnicalAnalysis\Strategy;
use App\Repository\TechnicalAnalysis\StrategyRepository;
use Doctrine\Common\Collections\ArrayCollection;

class StrategyLanguageParser
{
    /**
     * @var StrategyRepository
     */
    private $strategyRepo;

    /**
     * @var array
     */
    private $strategyList = [];

    /**
     * StrategyLanguageParser constructor.
     * @param StrategyRepository $strategyRepo
     */
    public function __construct(StrategyRepository $strategyRepo)
    {
        $this->strategyRepo = $strategyRepo;
    }

    /**
     * @param string $strategyString
     * @return StrategyCombination
     * @throws \Exception
     */
    public function getStrategies(string $strategyString)
    {
        if(!$this->strategyList) {
            $this->loadStrategies();
        }

        $strategyCombination = new StrategyCombination();

        $strategyString = str_replace(' ', '', $strategyString);

        if(strpos($strategyString, '||') !== false) {
            $strategyCombination->setOperator('||');
        } else if(strpos($strategyString, '&&') !== false)  {
            $strategyCombination->setOperator('&&');
        } else {
            $strategyCombination->setOperator('&&');
        }

        $rawStrategies = explode($strategyCombination->getOperator(), $strategyString);

        foreach($rawStrategies as $rawStrategy) {
            $strategyConfig = new StrategyConfig();

            $split = explode('(', $rawStrategy);

            $strategy = $this->getStrategy($split[0]);
            $strategyConfig->setStrategy($strategy);

            if(strlen($split[1]) > 1) {
                $strategyConfig->setConfigParams(explode(',', str_replace(')', '', $split[1])));
            }
            $strategyCombination->addStrategyConfig($strategyConfig);
        }
        return $strategyCombination;
    }

    private function loadStrategies()
    {
        /** @var Strategy $strategy */
        foreach($this->strategyRepo->findAll() as $strategy) {
            $this->strategyList[$strategy->getName()] = $strategy;
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    private function getStrategy(string $name)
    {
        if(!isset($this->strategyList[$name])) {
            throw new \Exception();
        }
        return $this->strategyList[$name];
    }
}