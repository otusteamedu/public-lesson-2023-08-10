<?php

namespace App\Service\Chain\Processor;

interface ProcessorInterface
{
    public function process(string $source): string;
}
