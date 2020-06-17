<?php


namespace App\Service\Algorithm;


use App\Entity\Algorithm\StrategyCombination;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\TechnicalAnalysis\Strategy;
use App\Entity\TechnicalAnalysis\StrategyCategories;
use App\Exceptions\Algorithm\StrategyNotFoundException;
use App\Repository\TechnicalAnalysis\StrategyRepository;

class StrategyLanguageParser
{
    const AND_OPERATOR = '&&';
    const OR_OPERATOR = '||';
    const NOT_OPERATOR = '!';

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
     * @param int $type
     * @return StrategyCombination
     * @throws StrategyNotFoundException
     */
    public function getStrategies(string $strategyString, int $type = StrategyCategories::TRADE)
    {
        if(!$this->strategyList) {
            $this->loadStrategies();
        }

        $strategyCombination = new StrategyCombination();

        $strategyString = str_replace(' ', '', $strategyString);

        if(strpos($strategyString, self::OR_OPERATOR) !== false) {
            $strategyCombination->setOperator(self::OR_OPERATOR);
        } else if(strpos($strategyString, self::AND_OPERATOR) !== false)  {
            $strategyCombination->setOperator(self::AND_OPERATOR);
        } else {
            $strategyCombination->setOperator(self::AND_OPERATOR);
        }

        $rawStrategies = explode($strategyCombination->getOperator(), $strategyString);

        foreach($rawStrategies as $rawStrategy) {
            $strategyConfig = new StrategyConfig();

            if($rawStrategy[0] == self::NOT_OPERATOR) {
                $strategyConfig->setReverseResult(true);
                $rawStrategy = substr($rawStrategy, 1);
            }

            $split = explode('(', $rawStrategy);

            $strategy = $this->getStrategy($split[0], $type);
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
     * @param int $type
     * @return mixed
     * @throws StrategyNotFoundException
     */
    private function getStrategy(string $name, int $type)
    {
        if(!isset($this->strategyList[$name]) || $this->strategyList[$name]->getType() != $type) {
            throw new StrategyNotFoundException();
        }
        return $this->strategyList[$name];
    }
}