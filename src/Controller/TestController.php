<?php

namespace App\Controller;

use Google\GTrends;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function test()
    {
        return new Response(
            '<html><body></body></html>'
        );
    }
}