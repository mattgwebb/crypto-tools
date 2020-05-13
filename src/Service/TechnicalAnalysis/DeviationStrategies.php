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

    /**
     * @param array $data
     * @param int $candlesAgo
     * @param int $period
     * @param float $currentPrice
     * @return StrategyResult
     */
    public function keltnerSqueeze(array $data, int $candlesAgo, int $period, float $currentPrice)
    {
        $result = new StrategyResult();

        $keltnerChannelPeriod = $this->indicators->keltnerChannelPeriod($data, $period);
        $bollingerBandsPeriod = $this->indicators->bollingerBandsPeriod($data);

        $lastKey = array_key_last($keltnerChannelPeriod[0]);

        $recentSqueeze = false;

        for($i = $lastKey; $i > ($lastKey - $candlesAgo); $i--) {
            $bollingerUpperBand = $bollingerBandsPeriod[0][$i];
            $bollingerLowerBand = $bollingerBandsPeriod[2][$i];

            $keltnerUpperBand = $keltnerChannelPeriod[0][$i];
            $keltnerLowerBand = $keltnerChannelPeriod[2][$i];

            if($bollingerUpperBand < $keltnerUpperBand && $bollingerLowerBand > $keltnerLowerBand) {
                $recentSqueeze = true;
                break;
            }
        }

        if($recentSqueeze) {
            $lastUpperBand = $bollingerBandsPeriod[0][$lastKey];
            $lastLowerBand = $bollingerBandsPeriod[2][$lastKey];

            if($currentPrice > $lastUpperBand) {
                $result->setTradeResult(StrategyResult::TRADE_LONG);
            } else if($currentPrice < $lastLowerBand) {
                $result->setTradeResult(StrategyResult::TRADE_SHORT);
            }
        }

        return $result;
    }
}