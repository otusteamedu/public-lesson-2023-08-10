<?php

namespace App\Tests\functional;

use App\Service\Chain\ChainService;
use App\Service\Chain\Processor\FinalPlainProcessor;
use App\Service\Chain\Processor\SimplePlainProcessor;
use App\Tests\FunctionalTester;
use Codeception\Stub;
use Exception;

class ChainServiceCest
{
    /**
     * @throws Exception
     */
    public function testProcess(FunctionalTester $I): void
    {
        $simplePlainProcessorMock = Stub::make(SimplePlainProcessor::class, ['process' => 'test simple plain processor']);
        $I->wantMockService(FinalPlainProcessor::class, $simplePlainProcessorMock);

        $chainService = $I->grabService(ChainService::class);
        $expectedMessage = 'test simple plain processor'.
            ' -> final plain processor'.
            ' -> simple processor'.
            ' -> final processor'.
            ' -> Finish';

        $I->assertSame($expectedMessage, $chainService->process());
    }
}
