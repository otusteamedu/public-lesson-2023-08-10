<?php

namespace App\Controller;

use App\Entity\Task;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function addTask(Request $request): Response
    {
        $result = $this->taskService->addTask($request->query->get('name'));
        $statusCode = $result === null ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK;
        return new JsonResponse(['taskId' => $result], $statusCode);
    }
}
