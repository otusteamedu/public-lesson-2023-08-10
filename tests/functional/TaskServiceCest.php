<?php

namespace App\Tests\functional;

use App\Entity\Task;
use App\Service\TaskService;
use App\Tests\FunctionalTester;
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
        $I->haveInRepository(Task::class, ['name' => self::TEST_TASK_NAME]);
        /** @var TaskService $taskService */
        $taskService = $I->grabService(TaskService::class);
        $taskService->addTask(self::TEST_TASK_NAME);

        $I->canSeeInRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME]);
    }
}
