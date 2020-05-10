<?php


namespace App\Entity\Algorithm;


abstract class AlgorithmCategoryTypes
{
    const POSSIBLY_REMOVE = 0;
    const STILL_IMPLEMENTING = 1;
    const IDEA = 2;
    const TESTING_CONFIG = 3;
    const POSSIBLE_TWEAKING = 4;
    const CONFIG_READY = 5;
    const CANDIDATE = 6;
    const LIVE_READY = 7;
}