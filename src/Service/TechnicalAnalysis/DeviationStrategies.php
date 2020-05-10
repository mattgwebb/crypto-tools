<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;

class DeviationStrategies extends AbstractStrategyService
{
    /**
     * @param array $data
     * @param float $currentPrice
     * @param float $priorPrice
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function bollingerBands(array $data, float $currentPrice, float $priorPrice, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();

        list($highBand, $lowBand) = $this->indicators->bollingerBands($data);

        if($crossOnly) {
            list($priorHighBand, $priorLowBand) = $this->indicators->bollingerBands($data, true);

            if($currentPrice >= $lowBand && $priorPrice < $priorLowBand) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($currentPrice <= $highBand && $priorPrice > $priorHighBand) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        } else {
            if($currentPrice < $lowBand) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($currentPrice > $highBand) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }
        return $result;
    }
}