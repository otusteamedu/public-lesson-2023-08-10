<?php

namespace App\Service;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function addTask(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        try {
            $this->entityManager->getConnection()->beginTransaction();

            $task = new Task();
            $task->setName($name);

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            sleep(3);

            $this->entityManager->getConnection()->commit();

            return $task->getId();
        } catch (Throwable) {
            $this->entityManager->getConnection()->rollBack();
        }

        return null;
    }
}
