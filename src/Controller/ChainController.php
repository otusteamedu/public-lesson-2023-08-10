<?php

namespace App\Controller;

use App\Service\Chain\ChainService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChainController extends AbstractController
{
    public function __construct(
        private readonly ChainService $chainService,
    ) {
    }

    public function callChain(): Response
    {
        return new JsonResponse(['result' => $this->chainService->process()]);
    }
}
