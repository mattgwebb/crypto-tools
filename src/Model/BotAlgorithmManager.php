<?php


namespace App\Model;


use App\Entity\Algorithm\AlgoTestResult;
use App\Entity\Algorithm\BotAccount;
use App\Entity\Algorithm\BotAlgorithm;
use App\Entity\Algorithm\StrategyConfig;
use App\Entity\Algorithm\TestingPhases;
use App\Entity\Algorithm\TestTypes;
use App\Entity\Data\Candle;
use App\Entity\Algorithm\StrategyResult;
use App\Entity\Data\CurrencyPair;
use App\Entity\Data\ExternalIndicatorData;
use App\Entity\Data\ExternalIndicatorDataType;
use App\Entity\Data\TimeFrames;
use App\Entity\TechnicalAnalysis\TrendLine;
use App\Entity\Trade\TradeTypes;
use App\Exceptions\Algorithm\IncorrectTestingPhaseException;
use App\Exceptions\Algorithm\StrategyNotFoundException;
use App\Repository\Algorithm\AlgoTestResultRepository;
use App\Repository\Algorithm\BotAlgorithmRepository;
use App\Repository\Data\CandleRepository;
use App\Repository\Data\CurrencyPairRepository;
use App\Repository\Data\ExternalIndicatorDataRepository;
use App\Repository\Trade\TradeRepository;
use App\Service\Config\ConfigService;
use App\Service\TechnicalAnalysis\Strategies;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BotAlgorithmManager
{
    /**
     * Number of candles passed on to the strategy calculations
     */
    const CANDLES_TO_LOAD = 400;

    /**
     * Fee per trade
     */
    const TRADE_FEE = 0.06 / 100;

    /**
     * Monkey iterations
     */
    const MONKEY_ITERATIONS = 2000;

    /**
     * Monte Carlo iterations
     */
    const MONTE_CARLO_ITERATIONS = 1000;

    /**
     * @var BotAlgorithmRepository
     */
    private $botAlgorithmRepo;

    /**
     * @var CurrencyPairRepository
     */
    private $currencyPairRepo;

    /**
     * @var CandleRepository
     */
    private $candleRepository;

    /**
     * @var Strategies
     */
    private $strategies;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TradeRepository
     */
    private $tradeRepository;

    /**
     * @var ExternalIndicatorDataRepository
     */
    private $externalIndicatorRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var AlgoTestResultRepository
     */
    private $algoTestResultRepository;

    /**
     * BotAlgorithmManager constructor.
     * @param BotAlgorithmRepository $botAlgorithmRepo
     * @param CurrencyPairRepository $currencyRepo
     * @param CandleRepository $candleRepository
     * @param Strategies $strategies
     * @param LoggerInterface $algosLogger
     * @param TradeRepository $tradeRepository
     * @param ExternalIndicatorDataRepository $externalIndicatorRepository
     * @param EntityManagerInterface $entityManager
     * @param ConfigService $configService
     * @param AlgoTestResultRepository $algoTestResultRepository
     */
    public function __construct(BotAlgorithmRepository $botAlgorithmRepo, CurrencyPairRepository $currencyRepo,
                                CandleRepository $candleRepository, Strategies $strategies, LoggerInterface $algosLogger,
                                TradeRepository $tradeRepository, ExternalIndicatorDataRepository $externalIndicatorRepository,
                                EntityManagerInterface $entityManager, ConfigService $configService,
                                AlgoTestResultRepository  $algoTestResultRepository)
    {
        $this->botAlgorithmRepo = $botAlgorithmRepo;
        $this->currencyPairRepo = $currencyRepo;
        $this->candleRepository = $candleRepository;
        $this->strategies = $strategies;
        $this->logger = $algosLogger;
        $this->tradeRepository = $tradeRepository;
        $this->entityManager = $entityManager;
        $this->externalIndicatorRepository = $externalIndicatorRepository;
        $this->configService = $configService;
        $this->algoTestResultRepository = $algoTestResultRepository;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $type
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return AlgoTestResult
     * @throws StrategyNotFoundException
     * @throws IncorrectTestingPhaseException
     */
    public function runTest(BotAlgorithm $algo, int $type, int $from = 0, int $to = 0,
                            int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        if($algo->getTestingPhase() == TestingPhases::LIMITED_TESTING) {
            throw new IncorrectTestingPhaseException();
        }

        $this->logger->info("********************* New test  ************************");
        $this->logger->info(json_encode($algo));

        $lastPositionCandles = $candlesToLoad - 1;

        $candles = $this->getCandlesForTest($algo, $from, $to, $candlesToLoad);

        list($initialPrice, $lastPrice) = $this->getFirstAndLastClosePrices($candles, $lastPositionCandles);

        $periodPricePercentage = (($lastPrice / $initialPrice) - 1) * 100;

        $testResult = $this->runTestIteration($algo, $type, $candles, $from, $lastPositionCandles, $periodPricePercentage);

        return $testResult;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @throws StrategyNotFoundException
     * @throws IncorrectTestingPhaseException
     */
    public function runLimitedTest(BotAlgorithm $algo, int $from = 0, int $to = 0,
                            int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        if($algo->getTestingPhase() != TestingPhases::LIMITED_TESTING) {
            throw new IncorrectTestingPhaseException();
        }

        $this->logger->info("********************* New limited test  ************************");
        $this->logger->info(json_encode($algo));

        $lastPositionCandles = $candlesToLoad - 1;

        $candles = $this->getCandlesForTest($algo, $from, $to, $candlesToLoad);

        list($initialPrice, $lastPrice) = $this->getFirstAndLastClosePrices($candles, $lastPositionCandles);

        $periodPricePercentage = (($lastPrice / $initialPrice) - 1) * 100;

        $entryStrategyConfigPossibleValues = $this->getStrategyPossibleConfigValues($algo->getEntryStrategyCombination());

        $entryCombinations = $this->getAllCombinationsOfArrays($entryStrategyConfigPossibleValues);

        $exitCombinationsFromEntry = $this->getExitParamCombinationsFromEntry($algo->getExitStrategyCombination(), $entryStrategyConfigPossibleValues);

        $exitStrategyConfigPossibleValues = $this->getStrategyPossibleConfigValues($algo->getExitStrategyCombination());

        $exitCombinations = $this->getAllCombinationsOfArrays($exitStrategyConfigPossibleValues);

        $originalEntryStrategy = $algo->getEntryStrategyCombination();
        $originalExitStrategy = $algo->getExitStrategyCombination();

        //ENTRY TESTING
        $entryTestRun = time();
        $algo->setInvalidationStrategyCombination('stopLoss(10)');
        $algo->setExitStrategyCombination('takeProfit(10)');

        // If there are no params we must run 1 iteration
        if(!$entryCombinations) {
            $entryCombinations = [[]];
        }

        foreach($entryCombinations as $entryCombination) {

            $testEntryStrategy = $this->setStrategyCombinationParams($originalEntryStrategy, $entryCombination);

            $algo->setEntryStrategyCombination($testEntryStrategy);
            $this->runTestIteration($algo, TestTypes::LIMITED_ENTRY, $candles, $from, $lastPositionCandles, $periodPricePercentage, $entryTestRun);
        }

        //EXIT TESTING
        $exitTestRun = time();
        $algo->setEntryStrategyCombination('rsi(30,70,14,1)');
        $algo->setExitStrategyCombination($originalExitStrategy);
        $algo->setInvalidationStrategyCombination('');

        // If there are no params we must run 1 iteration
        if(!$exitCombinations) {
            $exitCombinations = [[]];
        }

        foreach($exitCombinations as $exitCombination) {

            // set params that are defined in the exit strategy
            $initialTestExitStrategy = $this->setStrategyCombinationParams($originalExitStrategy, $exitCombination);

            foreach($exitCombinationsFromEntry as $entryCombination) {
                // set params that are defined in the entry strategy but used in the exit as well
                $testExitStrategy = $this->setStrategyCombinationParams($initialTestExitStrategy, $entryCombination);
                $algo->setExitStrategyCombination($testExitStrategy);
                $this->runTestIteration($algo, TestTypes::LIMITED_EXIT, $candles, $from, $lastPositionCandles, $periodPricePercentage, $exitTestRun);
            }
        }

        $algo->setEntryStrategyCombination($originalEntryStrategy);
        $algo->setExitStrategyCombination($originalExitStrategy);

        //CORE SYSTEM TESTING
        $coreTestRun = time();
        foreach($entryCombinations as $entryCombination) {

            $testEntryStrategy = $this->setStrategyCombinationParams($originalEntryStrategy, $entryCombination);
            $algo->setEntryStrategyCombination($testEntryStrategy);

            $initialTestExitStrategy = $this->setStrategyCombinationParams($originalExitStrategy, $entryCombination);

            foreach($exitCombinations as $exitCombination) {

                $testExitStrategy = $this->setStrategyCombinationParams($initialTestExitStrategy, $exitCombination);
                $algo->setExitStrategyCombination($testExitStrategy);
                $this->runTestIteration($algo, TestTypes::LIMITED_CORE, $candles, $from, $lastPositionCandles, $periodPricePercentage, $coreTestRun);
            }
        }
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return array
     * @throws StrategyNotFoundException
     * @throws IncorrectTestingPhaseException
     */
    public function runMonkeyTest(BotAlgorithm $algo, int $from = 0, int $to = 0,
                                   int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        if($algo->getTestingPhase() != TestingPhases::LIMITED_MONKEY_TESTING) {
            throw new IncorrectTestingPhaseException();
        }

        $this->logger->info("********************* New limited test  ************************");
        $this->logger->info(json_encode($algo));

        $lastPositionCandles = $candlesToLoad - 1;

        $candles = $this->getCandlesForTest($algo, $from, $to, $candlesToLoad);

        list($initialPrice, $lastPrice) = $this->getFirstAndLastClosePrices($candles, $lastPositionCandles);

        $periodPricePercentage = (($lastPrice / $initialPrice) - 1) * 100;

        $testResult = $this->runTestIteration($algo, TestTypes::LIMITED_CORE, $candles, $from, $lastPositionCandles, $periodPricePercentage);

        $trades = $testResult->getTrades();
        $profitPercentage = $testResult->getPercentageWithFees();

        $tradeCount = count($trades);

        $longTrades = $shortTrades = $results = [];

        $results[] = "TEST RUN percentage $profitPercentage";

        foreach($trades as $trade) {
            if($trade['trade'] == 'long') {
                $longTrades[] = $trade;
            } else if($trade['trade'] == 'short') {
                $shortTrades[] = $trade;
            }
        }

        $monkeyEntryProfitPercentages = $monkeyExitProfitPercentages = $monkeyCompleteProfitPercentages = [];
        $monkeyEntryBetterIterations = $monkeyExitBetterIterations = $monkeyCompleteBetterIterations = 0;

        // MONKEY ENTRY
        for($i = 0; $i < self::MONKEY_ITERATIONS; $i++) {
            $monkeyProfitPercentage = $this->runMonkeyEntryTestFromExitTrades($candles, $shortTrades, $from);
            $monkeyEntryProfitPercentages[] = $monkeyProfitPercentage;

            if($monkeyProfitPercentage > $profitPercentage) $monkeyEntryBetterIterations ++;
        }

        $monkeyEntryAverageProfitPercentage = array_sum($monkeyEntryProfitPercentages) / count($monkeyEntryProfitPercentages);
        $betterIterations = $monkeyEntryBetterIterations / self::MONKEY_ITERATIONS;

        $results[] = "MONKEY ENTRY average profit: $monkeyEntryAverageProfitPercentage, better iterations: $betterIterations %";

        // MONKEY EXIT
        for($i = 0; $i < self::MONKEY_ITERATIONS; $i++) {
            $monkeyProfitPercentage = $this->runMonkeyExitTestFromEntryTrades($candles, $longTrades);
            $monkeyExitProfitPercentages[] = $monkeyProfitPercentage;

            if($monkeyProfitPercentage > $profitPercentage) $monkeyExitBetterIterations ++;
        }

        $monkeyExitAverageProfitPercentage = array_sum($monkeyExitProfitPercentages) / count($monkeyExitProfitPercentages);
        $betterIterations = $monkeyExitBetterIterations / self::MONKEY_ITERATIONS;

        $results[] = "MONKEY EXIT average profit: $monkeyExitAverageProfitPercentage, better iterations: $betterIterations %";

        // MONKEY ENTRY AND EXIT
        for($i = 0; $i < self::MONKEY_ITERATIONS; $i++) {
            $monkeyProfitPercentage = $this->runMonkeyCompleteTest($candles, $from, $to, $tradeCount, $algo->getTimeFrame());
            $monkeyCompleteProfitPercentages[] = $monkeyProfitPercentage;

            if($monkeyProfitPercentage > $profitPercentage) $monkeyCompleteBetterIterations ++;
        }

        $monkeyCompleteAverageProfitPercentage = array_sum($monkeyCompleteProfitPercentages) / count($monkeyCompleteProfitPercentages);
        $betterIterations = $monkeyCompleteBetterIterations / self::MONKEY_ITERATIONS;

        $results[] = "MONKEY COMPLETE average profit: $monkeyCompleteAverageProfitPercentage, better iterations: $betterIterations %";

        return $results;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $inPeriodLength
     * @param int $outPeriodLength
     * @param int $candlesToLoad
     */
    public function runWalkForwardTest(BotAlgorithm $algo, int $from, int $inPeriodLength, int $outPeriodLength,
                                   int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        if($algo->getTestingPhase() != TestingPhases::WALK_FORWARD_TESTING) {
            throw new IncorrectTestingPhaseException();
        }

        $this->logger->info("********************* New limited test  ************************");
        $this->logger->info(json_encode($algo));

        $lastPositionCandles = $candlesToLoad - 1;

        $inPeriodTo = $from + $inPeriodLength;
        $outPeriodFrom = $inPeriodTo;
        $outPeriodTo = $outPeriodFrom + $outPeriodLength;

        $inPeriodCandles = $this->getCandlesForTest($algo, $from, $inPeriodTo, $candlesToLoad);

        list($inPeriodInitialPrice, $inPeriodLastPrice) = $this->getFirstAndLastClosePrices($inPeriodCandles, $lastPositionCandles);

        $inPeriodLastPricePeriodPricePercentage = (($inPeriodLastPrice / $inPeriodInitialPrice) - 1) * 100;

        $entryStrategyConfigPossibleValues = $this->getStrategyPossibleConfigValues($algo->getEntryStrategyCombination());

        $outPeriodCandles = $this->getCandlesForTest($algo, $outPeriodFrom, $outPeriodTo, $candlesToLoad);

        list($outPeriodInitialPrice, $outPeriodLastPrice) = $this->getFirstAndLastClosePrices($outPeriodCandles, $lastPositionCandles);

        $outPeriodLastPricePeriodPricePercentage = (($outPeriodLastPrice / $outPeriodInitialPrice) - 1) * 100;

        $entryStrategyConfigPossibleValues = $this->getStrategyPossibleConfigValues($algo->getEntryStrategyCombination());

        $entryCombinations = $this->getAllCombinationsOfArrays($entryStrategyConfigPossibleValues);

        // If there are no params we must run 1 iteration
        if(!$entryCombinations) {
            $entryCombinations = [[]];
        }

        $exitStrategyConfigPossibleValues = $this->getStrategyPossibleConfigValues($algo->getExitStrategyCombination());

        $exitCombinations = $this->getAllCombinationsOfArrays($exitStrategyConfigPossibleValues);

        // If there are no params we must run 1 iteration
        if(!$exitCombinations) {
            $exitCombinations = [[]];
        }

        $originalEntryStrategy = $algo->getEntryStrategyCombination();
        $originalExitStrategy = $algo->getExitStrategyCombination();

        $testRun = time();

        $bestProfit = false;
        $bestEntryCombination = $bestExitCombination = '';

        // In-period optimization
        foreach($entryCombinations as $entryCombination) {

            $testEntryStrategy = $this->setStrategyCombinationParams($originalEntryStrategy, $entryCombination);
            $algo->setEntryStrategyCombination($testEntryStrategy);

            $initialTestExitStrategy = $this->setStrategyCombinationParams($originalExitStrategy, $entryCombination);

            foreach($exitCombinations as $exitCombination) {

                $testExitStrategy = $this->setStrategyCombinationParams($initialTestExitStrategy, $exitCombination);
                $algo->setExitStrategyCombination($testExitStrategy);

                $testResult = $this->runTestIteration($algo, TestTypes::WALK_FORWARD_IN, $inPeriodCandles, $from, $lastPositionCandles, $inPeriodLastPricePeriodPricePercentage, $testRun);

                if($bestProfit === false || $bestProfit < $testResult->getPercentageWithFees()) {
                    $bestProfit = $testResult->getPercentageWithFees();
                    $bestEntryCombination = $testEntryStrategy;
                    $bestExitCombination = $testExitStrategy;
                }
            }
        }

        // Out-period evaluation
        $algo->setEntryStrategyCombination($bestEntryCombination);
        $algo->setExitStrategyCombination($bestExitCombination);

        $testResult = $this->runTestIteration($algo, TestTypes::WALK_FORWARD_OUT, $outPeriodCandles, $outPeriodFrom, $lastPositionCandles, $outPeriodLastPricePeriodPricePercentage, $testRun);
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return float[]
     * @throws IncorrectTestingPhaseException
     * @throws StrategyNotFoundException
     */
    public function runMonteCarloTest(BotAlgorithm $algo, int $from = 0, int $to = 0,
                                      int $candlesToLoad = self::CANDLES_TO_LOAD)
    {
        $testResult = $this->runTest($algo, TestTypes::MONTE_CARLO, $from, $to, $candlesToLoad);

        $feeTotalPercentage = $testResult->getPercentage() - $testResult->getPercentageWithFees();
        $feeMultiplier = (100 - $feeTotalPercentage) / 100;

        $shortTrades = [];

        foreach($testResult->getTrades() as $trade) {
            if($trade['trade'] == 'short') {
                $shortTrades[] = $trade;
            }
        }

        $totalShortTrades = count($shortTrades);

        $profits = $maxDrawdowns = [];

        for($i = 0; $i < self::MONTE_CARLO_ITERATIONS; $i++) {

            $compoundProfit = 1;

            $peakProfit = 1;
            $maxDrawdown = 0;

            for($j = 0; $j < $totalShortTrades; $j++) {
                $nextTrade = $shortTrades[array_rand($shortTrades)];

                $profit = (100 + $nextTrade['percentage']) / 100;
                $compoundProfit *= $profit;

                $peakProfit = max($peakProfit, $compoundProfit);

                $currentDrawdown = ($peakProfit - $compoundProfit) / $peakProfit;

                $maxDrawdown = max($maxDrawdown, $currentDrawdown);
            }

            $compoundProfit *= $feeMultiplier;

            $profits[] = $compoundProfit;
            $maxDrawdowns[] = $maxDrawdown;
        }

        $medianDrawdownPercentage = $this->getMedianFromArray($maxDrawdowns) * 100;
        $medianProfitPercentage = ($this->getMedianFromArray($profits) - 1) * 100;

        return [$medianDrawdownPercentage, $medianProfitPercentage];
    }


    /**
     * @param ExternalIndicatorDataType $type
     * @param CurrencyPair $pair
     * @param int $from
     * @param int $to
     * @return array
     */
    public function runExternalIndicatorTest(ExternalIndicatorDataType $type, CurrencyPair $pair, int $from, int $to)
    {
        $openTradePrice = 0;
        $compoundedProfit = 1;
        $trades = [];

        $this->logger->info("********************* New test  ************************");
        $this->logger->info($type->getName()." ".$pair->getSymbol(). "from $from to $to");

        $data = $this->externalIndicatorRepository->getData($type, $from, $to);

        /** @var ExternalIndicatorData $indicatorData */
        foreach($data as $indicatorData) {
            if($openTradePrice == 0 && $indicatorData->getIndicatorValue() <= 20) {
                $trade = $this->newExternalIndicatorTrade($pair, $indicatorData, TradeTypes::TRADE_BUY);
                $trades[] = $trade;

                $openTradePrice = $trade['price'];

                $this->logger->info(json_encode($trade));
            } else if($openTradePrice > 0 && $indicatorData->getIndicatorValue() >= 60) {

                $trade = $this->newExternalIndicatorTrade($pair, $indicatorData, TradeTypes::TRADE_SELL);

                $profit = ($trade['price']/$openTradePrice);
                $percentage = ($profit - 1) * 100;
                $compoundedProfit *= $profit;

                $trade["percentage"] = round($percentage, 2);
                $trade["stopLoss_takeProfit"] = false;
                $trades[] = $trade;

                $openTradePrice = 0;

                $this->logger->info(json_encode($trade));
            }
        }
        $compoundedPercentage = ($compoundedProfit  - 1) * 100;

        $trade = "percentage $compoundedPercentage";
        $this->logger->info($trade);

        return $trades;
    }

    /**
     * @param CurrencyPair $pair
     * @param int $timeFrame
     * @param int $from
     * @param int $to
     * @return array
     */
    public function runTrendLinesTest(CurrencyPair $pair, int $timeFrame = 60, int $from = 0, int $to = 0)
    {
        $candles = $this->currencyPairRepo->getCandlesByTimeFrame($pair, $timeFrame, $from, $to);

        $this->strategies->setData($candles);
        return $this->strategies->detectTrendLines();
    }

    /**
     * @param BotAccount $botAccount
     * @param Candle[] $candles
     * @return StrategyResult
     * @throws StrategyNotFoundException
     */
    public function runAlgo(BotAccount $botAccount, $candles)
    {
        $this->strategies->setData($candles);
        return $this->strategies->runStrategies($botAccount->getAlgo(), $botAccount->getTradeStatus());
    }

    /**
     * @param TrendLine $currentTradeTrendLine
     * @param array $extraData
     * @return bool
     */
    private function checkTrendLine(TrendLine $currentTradeTrendLine, array $extraData)
    {
        if(isset($extraData['trend_line'])) {
            $tradeTrendLine = $extraData['trend_line'];
            if((abs($currentTradeTrendLine->getStartPrice() - $tradeTrendLine->getStartPrice()) / $currentTradeTrendLine->getStartPrice()) < 0.015) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param CurrencyPair $pair
     * @param ExternalIndicatorData $indicatorData
     * @param int $tradeSide
     * @return array
     */
    private function newExternalIndicatorTrade(CurrencyPair $pair, ExternalIndicatorData $indicatorData, int $tradeSide)
    {
        $candle = $this->candleRepository->getCandleByTime($pair, $indicatorData->getCloseTime());

        return $this->newTestTrade($candle, $tradeSide);
    }

    /**
     * @param Candle $currentCandle
     * @param int $tradeSide
     * @return array
     * @throws \Exception
     */
    private function newTestTrade(Candle $currentCandle, int $tradeSide)
    {
        $date = new \DateTime('@' .$currentCandle->getCloseTime());
        $date->setTimezone(new \DateTimeZone("Europe/Madrid"));

        $trade = [
            "time" => date_format($date, 'Y D M j G:i:s'),
            "timestamp"=> $currentCandle->getCloseTime() * 1000,
            "price" => $currentCandle->getClosePrice()
        ];

        if($tradeSide == TradeTypes::TRADE_BUY) {
            $trade["trade"] = "long";
        } else if($tradeSide == TradeTypes::TRADE_SELL) {
            $trade["trade"] = "short";
        }
        return $trade;
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $type
     * @param float $percentage
     * @param float $openPositionPercentage
     * @param float $percentageWithFees
     * @param float $periodPercentage
     * @param array $trades
     * @param int $invalidatedTrades
     * @param int $startTime
     * @param int $finishTime
     * @param array $equityCurve
     * @param int $testRun
     * @return AlgoTestResult
     * @throws \Doctrine\DBAL\Exception
     */
    private function saveAlgoTestResult(BotAlgorithm $algo, int $type, float $percentage, float $openPositionPercentage, float $percentageWithFees, float $periodPercentage,
                                        array $trades, int $invalidatedTrades, int $startTime, int $finishTime, array $equityCurve, int $testRun)
    {
        $testResult = new AlgoTestResult();
        $testResult->setAlgo($algo);
        $testResult->setCurrencyPair($algo->getCurrencyPair());
        $testResult->setPercentage($percentage);
        $testResult->setPercentageWithFees($percentageWithFees);
        $testResult->setPriceChangePercentage($periodPercentage);
        $testResult->setTimestamp(time());
        $testResult->setTrades($trades);
        $testResult->setTradeCount(count($trades));
        $testResult->setStartTime($startTime);
        $testResult->setEndTime($finishTime);
        $testResult->setTimeFrame($algo->getTimeFrame());
        $testResult->setInvalidatedTrades($invalidatedTrades);
        $testResult->setOpenPosition($openPositionPercentage);
        $testResult->setTestType($type);
        $testResult->setEquityCurve($equityCurve);
        $testResult->setMaxDrawdown($this->getMaxDrawdownFromEquityCurve($equityCurve));
        $testResult->setTestRun($testRun);

        if($trades) {
            $winningTrades = [];
            $losingTrades = [];

            foreach($trades as $trade) {
                if(isset($trade['percentage'])) {
                    if($trade['percentage'] > 0) {
                        $winningTrades[] = $trade['percentage'];
                    } else if($trade['percentage'] < 0) {
                        $losingTrades[] = $trade['percentage'];
                    }
                }
            }

            $nWinningTrades = count($winningTrades);
            $nLosingTrades = count($losingTrades);

            if($nWinningTrades > 0) {
                $testResult->setBestWinner(max($winningTrades));
                $testResult->setAverageWinner(array_sum($winningTrades) / $nWinningTrades);
            }

            if($nLosingTrades > 0) {
                $testResult->setWorstLoser(min($losingTrades));
                $testResult->setAverageLoser(array_sum($losingTrades) / $nLosingTrades);
            }

            if(($nWinningTrades + $nLosingTrades) > 0) {
                $testResult->setWinPercentage(($nWinningTrades / ($nWinningTrades + $nLosingTrades)) * 100);
            }

            $testResult->setStandardDeviation($this->calculateStandardDeviation(array_merge($winningTrades, $losingTrades)));
        }

        $extra = [
            "entry_strategies" => $algo->getEntryStrategyCombination(),
            "market_conditions_entry_strategy" => $algo->getMarketConditionsEntry(),
            "exit_strategies" => $algo->getExitStrategyCombination(),
            "market_conditions_exit_strategy" => $algo->getMarketConditionsExit(),
            "invalidation_strategies" => $algo->getInvalidationStrategyCombination()
        ];
        $testResult->setObservations(json_encode($extra));

        if($this->logResults()) {
            $this->algoTestResultRepository->newAlgoTestResult($testResult);
        }
        return $testResult;
    }

    /**
     * @return bool
     */
    private function logResults()
    {
        $configItem = $this->configService->getConfig('testing', 'log_results');
        return $configItem ? (bool)$configItem->getValue() : false;
    }

    /**
     * @param array $a
     * @param bool $sample
     * @return float
     */
    private function calculateStandardDeviation(array $a, $sample = false)
    {
        $n = count($a);

        if($n == 0) {
            return 0.0;
        }

        $mean = array_sum($a) / $n;
        $carry = 0.0;

        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        }
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $from
     * @param int $to
     * @param int $candlesToLoad
     * @return Candle[]
     */
    private function getCandlesForTest(BotAlgorithm $algo, int $from, int $to,
                                       int $candlesToLoad)
    {
        $lastPositionCandles = $candlesToLoad - 1;
        $from -= $lastPositionCandles * ($algo->getTimeFrame() * 60);
        return $this->currencyPairRepo->getCandlesByTimeFrame($algo->getCurrencyPair(), $algo->getTimeFrame(), $from, $to);
    }

    /**
     * @param Candle[] $candles
     * @param int $lastPositionCandles
     * @return array
     */
    private function getFirstAndLastClosePrices(array $candles, int $lastPositionCandles)
    {
        $firstCandle = $candles[$lastPositionCandles];
        $lastCandle = $candles[count($candles)-1];

        return [$firstCandle->getClosePrice(), $lastCandle->getClosePrice()];
    }

    /**
     * @param BotAlgorithm $algo
     * @param int $type
     * @param array $candles
     * @param int $from
     * @param int $candlesToLoad
     * @param float $periodPricePercentage
     * @param int|null $testRun
     * @return AlgoTestResult
     * @throws StrategyNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    private function runTestIteration(BotAlgorithm $algo, int $type, array $candles, int $from, int $candlesToLoad,
                                      float $periodPricePercentage, int $testRun = 0)
    {
        $lastPositionCandles = $candlesToLoad - 1;

        $openTradePrice = $accumulatedFees = $invalidatedTrades = 0;
        $compoundedProfit = 1;

        $trades = $equityCurve = [];

        $currentTradeStatus = TradeTypes::TRADE_SELL;

        $currentTradeTrendLine = null;

        for($i=$lastPositionCandles; $i < count($candles); $i++) {
            $auxData = array_slice($candles, $i - $lastPositionCandles, $candlesToLoad);
            /** TODO delete candles from array after using them
             * TODO for some reason it doesn´t calculate indicators properly
             */
            //array_shift($candles);

            $currentCandle = $auxData[count($auxData) - 1];

            if(($currentCandle->getCloseTime() + 1) % (TimeFrames::TIMEFRAME_1D * 60) == 0) {
                $currentEquity = $this->getCurrentEquity($openTradePrice, $compoundedProfit, $currentCandle->getClosePrice()) - $accumulatedFees;
                $equityCurve[$currentCandle->getCloseTime() + 1] = $currentEquity;
            }

            $this->strategies->setData($auxData);
            $this->strategies->setCurrentTradePrice($openTradePrice);
            $result = $this->strategies->runStrategies($algo, $currentTradeStatus);


            if(($result->isLong()) && $currentTradeStatus == TradeTypes::TRADE_SELL) {

                if($currentTradeTrendLine && !$this->checkTrendLine($currentTradeTrendLine, $result->getExtraData())) {
                    continue;
                }

                $openTradePrice = $currentCandle->getClosePrice();
                $currentTradeStatus = TradeTypes::TRADE_BUY;

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_BUY);
                $trade['extra_data'] = $result->getExtraData();

                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

                $accumulatedFees += $compoundedProfit * self::TRADE_FEE;

                if(isset($trade['extra_data']['trend_line'])) {
                    $currentTradeTrendLine = $trade['extra_data']['trend_line'];
                } else {
                    $currentTradeTrendLine = null;
                }

            } else if($result->isShort() && $currentTradeStatus == TradeTypes::TRADE_BUY) {

                if($currentTradeTrendLine && !$this->checkTrendLine($currentTradeTrendLine, $result->getExtraData())) {
                    continue;
                }

                $profit = ($currentCandle->getClosePrice()/$openTradePrice);
                $percentage = ($profit - 1) * 100;
                $compoundedProfit *= $profit;

                $accumulatedFees += $compoundedProfit * self::TRADE_FEE;

                $trade = $this->newTestTrade($currentCandle, TradeTypes::TRADE_SELL);
                $trade['extra_data'] = $result->getExtraData();
                $trade["percentage"] = round($percentage, 2);

                if($result->isFromInvalidation()) {
                    $trade['invalidation'] = true;
                    $invalidatedTrades++;
                } else {
                    $trade['invalidation'] = false;
                }

                $trades[] = $trade;

                $this->logger->info(json_encode($trade));

                $openTradePrice = 0;
                $currentTradeStatus = TradeTypes::TRADE_SELL;

                if(isset($trade['extra_data']['trend_line'])) {
                    $currentTradeTrendLine = $trade['extra_data']['trend_line'];
                } else {
                    $currentTradeTrendLine = null;
                }
            }
        }

        $compoundedPercentage = ($compoundedProfit - 1) * 100;
        $compoundedPercentageWithFees = ($compoundedProfit - $accumulatedFees - 1) * 100;

        if(isset($currentCandle)) {

            if($currentTradeStatus == TradeTypes::TRADE_BUY) {
                $profit = ($currentCandle->getClosePrice()/$openTradePrice);
                $openPositionPercentage = ($profit - 1) * 100;
            } else {
                $openPositionPercentage = 0;
            }

            $algoTestResult = $this->saveAlgoTestResult($algo, $type, $compoundedPercentage, $openPositionPercentage, $compoundedPercentageWithFees, $periodPricePercentage,
                $trades, $invalidatedTrades, $from, $currentCandle->getCloseTime(), $equityCurve, $testRun);
        }

        $trade = "percentage $compoundedPercentage";
        //$trades[] = $trade;
        $this->logger->info($trade);

        return $algoTestResult;
    }

    /**
     * @param float $openTradePrice
     * @param float $compoundedProfit
     * @param float $lastClosePrice
     * @return float
     */
    private function getCurrentEquity(float $openTradePrice, float $compoundedProfit, float $lastClosePrice)
    {
        if($openTradePrice == 0) {
            return $compoundedProfit;
        } else {
            return ($lastClosePrice / $openTradePrice) * $compoundedProfit;
        }
    }

    /**
     * @param string $strategyString
     * @return array
     * @throws StrategyNotFoundException
     */
    private function getStrategyPossibleConfigValues(string $strategyString)
    {
        $strategy = $this->strategies->parseStrategy($strategyString);

        $strategyConfigPossibleValues = [];

        $strategyList = $strategy->getStrategyConfigList();

        /** @var StrategyConfig $config */
        foreach($strategyList as $config) {
            foreach($config->getConfigParams() as $configParamKey => $configParam) {
                if(strpos($configParam, '{') !== false && strpos($configParam, '}') !== false) {
                    $configParam = substr(substr($configParam, 1),0, -1);
                    list($paramName,$paramValues) = explode("=", $configParam);

                    $paramValues = substr(substr($paramValues, 1),0, -1);
                    $strategyConfigPossibleValues[$paramName] = explode('|', $paramValues);
                }
            }
        }
        return $strategyConfigPossibleValues;
    }

    /**
     * @param $arrays
     * @param string $key
     * @return array
     */
    private function getAllCombinationsOfArrays($arrays, $key = '')
    {
        if(!$arrays) {
            return $arrays;
        }

        if(!$key) {
            $key = key($arrays);
        }

        if(count($arrays) == 1) {
            $singleParamArray = [];
            foreach($arrays[$key] as $value) {
                $singleParamArray[] = [
                    $key => $value
                ];
            }
            return $singleParamArray;
        }

        $foundCurrent = $nextKey = false;

        foreach($arrays as $arrayKey => $value) {
            if($foundCurrent) {
                $nextKey = $arrayKey;
                break;
            }
            if($arrayKey == $key) {
                $foundCurrent = true;
            }
        }


        /*if (!isset($arrays[$key])) {
            return array();
        }*/
        if (!$nextKey) {
            return $arrays[$key];
        }

        // get combinations from subsequent arrays
        $tmp = $this->getAllCombinationsOfArrays($arrays, $nextKey);

        $result = array();

        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$key] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?
                    array_merge(array($key => $v), $t) :
                    array($key => $v, $nextKey => $t);
            }
        }

        return $result;
    }

    /**
     * @param $strategyString
     * @param $paramName
     * @param $paramValue
     * @return string
     */
    private function replaceParamNameWithValue($strategyString, $paramName, $paramValue)
    {
        $strategyString = preg_replace('/{'.$paramName.'=\[[^{,[]*\]}/', $paramValue, $strategyString);
        $replaceString = '~\$'.$paramName.'~';
        $strategyString = preg_replace($replaceString, $paramValue, $strategyString);
        return $strategyString;
    }

    /**
     * @param string $strategy
     * @param array $params
     * @return string
     */
    private function setStrategyCombinationParams(string $strategy, array $params)
    {
        if(!$params) {
            return $strategy;
        }

        foreach($params as $paramName => $paramValue) {
            $strategy = $this->replaceParamNameWithValue($strategy, $paramName, $paramValue);
        }
        return $strategy;
    }

    /**
     * @param string $exitStrategy
     * @param array $entryStrategyConfigPossibleValues
     * @return array
     */
    private function getExitParamCombinationsFromEntry(string $exitStrategy, array $entryStrategyConfigPossibleValues)
    {
        foreach(array_keys($entryStrategyConfigPossibleValues) as $paramName) {
            if(strpos($exitStrategy, "$".$paramName) === false) {
                unset($entryStrategyConfigPossibleValues[$paramName]);
            }
        }
        return $this->getAllCombinationsOfArrays($entryStrategyConfigPossibleValues);
    }

    /**
     * @param array $candles
     * @param int $from
     * @param int $to
     * @return int
     */
    private function getRandomCandle(array $candles, int $from, int $to)
    {
        $possibleCandles = [];

        /** @var Candle $candle */
        foreach($candles as $candle) {
            if($candle->getCloseTime() >= $to) {
                break;
            }
            if($candle->getCloseTime() > $from) {
                $possibleCandles[] = $candle;
            }
        }
        return $possibleCandles[array_rand($possibleCandles)];
    }

    /**
     * @param array $shortTrades
     * @return float
     */
    private function runMonkeyEntryTestFromExitTrades(array $candles, array $shortTrades, int $startTimestamp)
    {
        $lastTradeTimestamp = $startTimestamp;
        $accumulatedFees = 0;
        $compoundedProfit = 1;
        $monkeyEntryTrades = [];

        foreach($shortTrades as $key => $shortTrade) {

            $accumulatedFees += $compoundedProfit * self::TRADE_FEE;

            $shortTradeTimestamp = (int)($shortTrade['timestamp']/1000);
            $randomCandle = $this->getRandomCandle($candles, $lastTradeTimestamp, $shortTradeTimestamp);

            $longTrade = [
                'timestamp' => $randomCandle->getCloseTime(),
                'price' => $randomCandle->getClosePrice()
            ];

            $monkeyEntryTrades[] = $longTrade;

            $profit = $shortTrade['price'] / $longTrade['price'];
            $compoundedProfit *= $profit;

            $accumulatedFees += $compoundedProfit * self::TRADE_FEE;
            $shortTrade['percentage'] = ($profit - 1) * 100;

            $monkeyEntryTrades[] = $shortTrade;
            $lastTradeTimestamp = $shortTradeTimestamp;
        }

        $compoundedPercentage = ($compoundedProfit - 1) * 100;
        $compoundedPercentageWithFees = ($compoundedProfit - $accumulatedFees - 1) * 100;

        return $compoundedPercentageWithFees;
    }

    /**
     * @param array $candles
     * @param array $longTrades
     * @return float
     */
    private function runMonkeyExitTestFromEntryTrades(array $candles, array $longTrades)
    {
        $accumulatedFees = 0;
        $compoundedProfit = 1;
        $monkeyExitTrades = [];

        foreach($longTrades as $key => $longTrade) {

            $nextTradeTimestamp = isset($longTrades[$key + 1]) ? (int)($longTrades[$key + 1]['timestamp']/1000) : 999999999999;

            $accumulatedFees += $compoundedProfit * self::TRADE_FEE;

            $longTradeTimestamp = (int)($longTrade['timestamp']/1000);
            $randomCandle = $this->getRandomCandle($candles, $longTradeTimestamp, $nextTradeTimestamp);

            $shortTrade = [
                'timestamp' => $randomCandle->getCloseTime(),
                'price' => $randomCandle->getClosePrice()
            ];

            $monkeyExitTrades[] = $longTrade;

            $profit = $shortTrade['price'] / $longTrade['price'];
            $compoundedProfit *= $profit;

            $accumulatedFees += $compoundedProfit * self::TRADE_FEE;
            $shortTrade['percentage'] = ($profit - 1) * 100;

            $monkeyExitTrades[] = $shortTrade;
        }
        $compoundedPercentageWithFees = ($compoundedProfit - $accumulatedFees - 1) * 100;

        return $compoundedPercentageWithFees;
    }

    /**
     * @param array $candles
     * @param int $startTime
     * @param int $endTime
     * @param int $tradeCount
     * @param int $timeFrame
     * @return float
     */
    private function runMonkeyCompleteTest(array $candles, int $startTime, int $endTime, int $tradeCount, int $timeFrame)
    {
        $longTradesNeeded = (int)floor($tradeCount / 2);
        $longTrades = [];

        //remove last candle for long trades
        $lastCandle = array_pop($candles);

        for($i = 0; $i < $longTradesNeeded; $i++) {
            $candleFound = false;

            while(!$candleFound) {
                $randomCandle = $this->getRandomCandle($candles, $startTime, $endTime);

                //TODO find cleaner way
                $closeTime = $randomCandle->getCloseTime();
                $previousCandleCloseTime = $closeTime - ($timeFrame * 60);
                $nextCandleCloseTime = $closeTime + ($timeFrame * 60);
                $secondPreviousCandleCloseTime = $closeTime - (($timeFrame * 60) * 2);
                $secondNextCandleCloseTime = $closeTime + (($timeFrame * 60) * 2);

                if(!isset($longTrades[$closeTime]) && !isset($longTrades[$previousCandleCloseTime]) &&
                    !isset($longTrades[$nextCandleCloseTime]) && !isset($longTrades[$secondPreviousCandleCloseTime]) &&
                    !isset($longTrades[$secondNextCandleCloseTime])) {
                    $longTrades[$randomCandle->getCloseTime()] = [
                        'timestamp' => $randomCandle->getCloseTime() * 1000,
                        'price' => $randomCandle->getClosePrice()
                    ];
                    $candleFound = true;
                }
            }
        }
        ksort($longTrades);

        $longTrades = array_values($longTrades);

        $candles[] = $lastCandle;

        return $this->runMonkeyExitTestFromEntryTrades($candles, $longTrades);
    }

    /**
     * @param array $array
     * @return float
     */
    private function getMedianFromArray(array $array)
    {
        sort($array);

        $count = sizeof($array);
        $index = floor($count/2);

        if ($count & 1) {
            return $array[$index];
        } else {
            return ($array[$index-1] + $array[$index]) / 2;
        }
    }

    /**
     * @param array $equityCurve
     * @return float
     */
    private function getMaxDrawdownFromEquityCurve(array $equityCurve)
    {
        $peakEquity = 1.00;
        $maxDrawdown = 0.00;

        foreach($equityCurve as $timestamp => $equity) {

            $peakEquity = max($peakEquity, $equity);

            $currentDrawdown = ($peakEquity - $equity) / $peakEquity;

            $maxDrawdown = max($maxDrawdown, $currentDrawdown);
        }
        return $maxDrawdown;
    }
}