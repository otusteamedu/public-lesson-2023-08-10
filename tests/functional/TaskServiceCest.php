<?php

namespace App\Tests\functional;

use App\Entity\Task;
use App\Service\TaskService;
use App\Tests\FunctionalTester;
use App\Tests\Mock\TaskRepositoryMock;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class TaskServiceCest
{
    private const TEST_TASK_NAME = 'Test task';
    private const FIXED_TEST_TASK_NAME = 'Test task_fixed';

    /**
     * @throws Exception
     */
    public function testAddTask(FunctionalTester $I): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        /** @var TaskRepositoryMock $taskRepository */
        $taskRepository = $entityManager->getRepository(Task::class);
        $taskRepository->enableEmulateRaceConditionForName(self::TEST_TASK_NAME);
        /** @var TaskService $taskService */
        $taskService = $I->grabService(TaskService::class);
        $taskService->addTask(self::TEST_TASK_NAME);

        $I->canSeeInRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME]);

        // cleanup
        $task2 = $I->grabEntityFromRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME]);
        $entityManager->remove($task2);
        $I->flushToDatabase();
    }
}
