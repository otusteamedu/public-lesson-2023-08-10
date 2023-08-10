<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Throwable;

class TaskService
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
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

            /** @var TaskRepository $taskRepository */
            $taskRepository = $this->entityManager->getRepository(Task::class);
            $existingTask = $taskRepository->findByName($name);
            if ($existingTask !== null) {
                return null;
            }

            $task = new Task();
            $task->setName($name);

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            sleep(3);

            $this->entityManager->getConnection()->commit();

            return $task->getId();
        } catch (UniqueConstraintViolationException) {
            $this->entityManager->getConnection()->rollBack();
            $this->entityManager->close();
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->managerRegistry->resetManager();
            $this->entityManager = $entityManager;

            return $this->addTask($name.'_fixed');
        } catch (Throwable) {
            $this->entityManager->getConnection()->rollBack();
        }

        return null;
    }
}
