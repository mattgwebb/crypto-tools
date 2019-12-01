<?php


namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;

class IndicatorPointList extends ArrayCollection
{

    /**
     * IndicatorPointList constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct();

        foreach($data as $period => $value) {
            $this->set($period, new IndicatorPoint($period, $value));
        }

    }

    /**
     * @param int $secondPointKey
     * @param bool $lower
     * @return DivergenceLine|bool
     */
    public function getValidLine(int $secondPointKey, bool $lower)
    {
        /** @var IndicatorPoint $secondPoint */
        $secondPoint = $this->get($secondPointKey);
        if(!$secondPoint) {
            return false;
        }

        /** @var IndicatorPoint $currentPoint */
        $currentPoint = $this->first();

        $line = new DivergenceLine($currentPoint, $secondPoint);

        $RSIDifferenceByPeriod = $line->getDifferencePerPeriod();

        /** @var IndicatorPoint $point */
        foreach($this as $point) {
            if($point->getPeriod() == 0) {
                continue;
            }
            if($point->getPeriod() == $secondPoint->getPeriod()) {
                return $line;
            }

            $allowedRSI = $currentPoint->getValue() - ($point->getPeriod() * $RSIDifferenceByPeriod);

            if($lower && $allowedRSI > $point->getValue()) {
                return false;
            }

            if(!$lower && $allowedRSI < $point->getValue()) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param bool $desc
     * @return IndicatorPointList
     */
    public function getOrderedList($desc = false)
    {
        $array = $this->toArray();
        if($desc) {
            usort($array, function($a, $b) { return($a->getValue() < $b->getValue()); });
        } else {
            usort($array, function($a, $b) { return($a->getValue() > $b->getValue()); });
        }

        $list = new IndicatorPointList();
        /** @var IndicatorPoint $point */
        foreach($array as $point) {
            $list->set($point->getPeriod(), $point);
        }
        return $list;
    }
}