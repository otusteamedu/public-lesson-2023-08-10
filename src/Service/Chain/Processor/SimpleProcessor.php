<?php

namespace App\Service\Chain\Processor;

class SimpleProcessor implements SimpleProcessorInterface
{
    public function process(string $source): string
    {
        return $source.' -> simple processor';
    }
}
