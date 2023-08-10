<?php

namespace App\Tests\Mock;

use App\Entity\Task;
use App\Repository\TaskRepository;

class TaskRepositoryMock extends TaskRepository
{
    private ?string $emulateRaceConditionForName = null;

    public function enableEmulateRaceConditionForName(string $name): void
    {
        $this->emulateRaceConditionForName = $name;
    }

    public function findByName(string $name): ?Task
    {
        if ($this->emulateRaceConditionForName === $name) {
            $task = new Task();
            $task->setName($name);
            $this->getEntityManager()->persist($task);
            $this->getEntityManager()->flush();

            return null;
        }

        return parent::findByName($name);
    }
}
