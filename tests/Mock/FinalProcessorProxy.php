<?php

namespace App\Tests\Mock;

use App\Service\Chain\Processor\FinalProcessor;
use App\Service\Chain\Processor\FinalProcessorInterface;

class FinalProcessorProxy implements FinalProcessorInterface
{
    public function __construct(
        private readonly FinalProcessor $baseProcessor
    ) {
    }

    public function process(string $source): string
    {
        return 'test final processor';
    }
}
