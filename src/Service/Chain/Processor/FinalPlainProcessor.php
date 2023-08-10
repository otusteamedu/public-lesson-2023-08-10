<?php

namespace App\Service\Chain\Processor;

final class FinalPlainProcessor implements ProcessorInterface
{
    public function process(string $source): string
    {
        return $source.' -> final plain processor';
    }
}
