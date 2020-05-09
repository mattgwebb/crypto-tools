<?php


namespace App\Service\TechnicalAnalysis;


use App\Entity\Algorithm\StrategyResult;

class DeviationStrategies extends AbstractStrategyService
{
    /**
     * @param array $data
     * @param float $currentPrice
     * @param bool $crossOnly
     * @return StrategyResult
     */
    public function bollingerBands(array $data, bool $crossOnly = false) : StrategyResult
    {
        $result = new StrategyResult();

        $currentPrice = $this->getCurrentPrice($data);
        list($highBand, $lowBand) = $this->indicators->bollingerBands($data);

        if($crossOnly) {
            $priorPrice = $this->getPreviousPrice($data);
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