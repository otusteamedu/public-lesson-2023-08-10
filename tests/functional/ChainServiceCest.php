<?php

namespace App\Tests\functional;

use App\Service\Chain\ChainService;
use App\Tests\FunctionalTester;
use Exception;

class ChainServiceCest
{
    /**
     * @throws Exception
     */
    public function testProcess(FunctionalTester $I): void
    {
        $chainService = $I->grabService(ChainService::class);

        $expectedMessage = 'test simple processor'.
            ' -> final processor'.
            ' -> Finish';

        $I->assertSame($expectedMessage, $chainService->process());
    }
}
