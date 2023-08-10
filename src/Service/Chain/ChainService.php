<?php

namespace App\Service\Chain;

use App\Service\Chain\Processor\FinalPlainProcessor;
use App\Service\Chain\Processor\FinalProcessorInterface;
use App\Service\Chain\Processor\ProcessorInterface;
use App\Service\Chain\Processor\SimplePlainProcessor;
use App\Service\Chain\Processor\SimpleProcessorInterface;

class ChainService
{
    /** @var ProcessorInterface[] */
    private array $processors;

    public function __construct(
        SimplePlainProcessor $first,
        FinalPlainProcessor $second,
        SimpleProcessorInterface $third,
        FinalProcessorInterface $fourth,
    ) {
        $this->processors = [$first, $second, $third, $fourth];
    }

    public function process(): string
    {
        $message = 'Start';
        foreach ($this->processors as $processor) {
            $message = $processor->process($message);
        }

        return $message.' -> Finish';
    }
}
