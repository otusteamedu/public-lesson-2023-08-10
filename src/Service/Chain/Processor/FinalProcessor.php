<?php

namespace App\Service\Chain\Processor;

final class FinalProcessor implements FinalProcessorInterface
{
    public function process(string $source): string
    {
        return $source.' -> final processor';
    }
}
