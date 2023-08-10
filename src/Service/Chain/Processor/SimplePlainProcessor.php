<?php

namespace App\Service\Chain\Processor;

class SimplePlainProcessor implements ProcessorInterface
{
    public function process(string $source): string
    {
        return $source.' -> simple plain processor';
    }
}
