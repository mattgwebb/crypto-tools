<?php


namespace App\Entity\Algorithm;


abstract class TestingPhases
{
    const IMPLEMENTING = 1;
    const LIMITED_TESTING = 2;
    const LIMITED_MONKEY_TESTING = 3;
    const WALK_FORWARD_TESTING = 4;
    const TESTING_CONFIG = 5;
    const POSSIBLE_TWEAKING = 6;
    const CONFIG_READY = 7;
    const CANDIDATE = 8;
    const LIVE_READY = 9;
}