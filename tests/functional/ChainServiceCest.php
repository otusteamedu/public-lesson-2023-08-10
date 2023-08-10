<?php

namespace App\Tests\functional;

use App\Service\Chain\ChainService;
use App\Service\Chain\Processor\FinalPlainProcessor;
use App\Service\Chain\Processor\FinalProcessor;
use App\Service\Chain\Processor\SimplePlainProcessor;
use App\Service\Chain\Processor\SimpleProcessor;
use App\Tests\FunctionalTester;

class ChainServiceCest
{
    public function testProcess(FunctionalTester $I): void
    {
        $chainService = new ChainService(
            new SimplePlainProcessor(),
            new FinalPlainProcessor(),
            new SimpleProcessor(),
            new FinalProcessor(),
        );

        $expectedMessage = 'Start'.
            ' -> simple plain processor'.
            ' -> final plain processor'.
            ' -> simple processor'.
            ' -> final processor'.
            ' -> Finish';

        $I->assertSame($expectedMessage, $chainService->process());
    }
}
