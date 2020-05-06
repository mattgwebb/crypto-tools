<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;

class DeviationStrategies extends AbstractStrategyService
{
    /**
     * @param array $data
     * @param float $curremtPrice
     * @return StrategyResult
     */
    public function bollingerBands(array $data, float $curremtPrice) : StrategyResult
    {
        $result = new StrategyResult();
        list($highBand, $lowBand) = $this->indicators->bollingerBands($data);

        if($curremtPrice < $lowBand) {
            $result->setTradeResult(StrategyResult::TRADE_LONG);
        } else if($curremtPrice > $highBand) {
            $result->setTradeResult(StrategyResult::TRADE_SHORT);
        } else {
            $result->setTradeResult(StrategyResult::NO_TRADE);
        }
        return $result;
    }
}