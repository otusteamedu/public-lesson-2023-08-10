# Codeception: практические кейсы

## Подготовка

### Готовим проект

1. Запускаем контейнеры командой `docker-compose up -d`
2. Входим в контейнер командой `docker exec -it php sh`. Дальнейшие команды будем выполнять из контейнера
3. Устанавливаем зависимости командой `composer install`
4. Выполняем миграции командой `php bin/console doctrine:migrations:migrate`

### Устанавливаем codeception и пишем первый тест

1. Устанавливаем пакеты `codeception/codeception`, `codeception/module-symfony`, `codeception/module-doctrine2`,
`codeception/module-asserts`, **в dev-режиме**
2. Переходим в браузере по адресу `http://localhost:7777/chain`, видим ответ сервиса `App\ServiceChainService`
3. Удаляем файл `tests/acceptance.suite.yml`
4. Добавляем в файле `tests/functional.suite.yml` в секцию `modules.enabled` модуль `Asserts`
5. Выполняем команду `vendor/bin/codecept build`
6. Добавляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Service\Chain\Processor\FinalPlainProcessor;
    use App\Service\Chain\Processor\FinalProcessor;
    use App\Service\Chain\Processor\SimplePlainProcessor;
    use App\Service\Chain\Processor\SimpleProcessor;
    use App\Tests\FunctionalTester;
    
    class ChainServiceCest
    {
        public function testProcess(FunctionalTester $I): void
        {
            $chainService = new ChainService(
                new SimplePlainProcessor(),
                new FinalPlainProcessor(),
                new SimpleProcessor(),
                new FinalProcessor(),
            );
    
            $expectedMessage = 'Start'.
                ' -> simple plain processor'.
                ' -> final plain processor'.
                ' -> simple processor'.
                ' -> final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```
7. Запускаем тест командой `vendor/bin/codecept run functional`, видим, что тест проходит

## Заменяем и декорируем сервисы 

### Заменяем один из процессоров на мок

1. Исправляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Service\Chain\Processor\FinalPlainProcessor;
    use App\Service\Chain\Processor\FinalProcessor;
    use App\Service\Chain\Processor\SimplePlainProcessor;
    use App\Service\Chain\Processor\SimpleProcessor;
    use App\Tests\FunctionalTester;
    use Codeception\Stub;
    use Exception;
    
    class ChainServiceCest
    {
        /**
         * @throws Exception
         */
        public function testProcess(FunctionalTester $I): void
        {
            $simplePlainProcessorMock = Stub::make(SimplePlainProcessor::class, ['process' => 'test simple plain processor']);
            $chainService = new ChainService(
                $simplePlainProcessorMock,
                new FinalPlainProcessor(),
                new SimpleProcessor(),
                new FinalProcessor(),
            );

            $expectedMessage = 'test simple plain processor'.
                ' -> final plain processor'.
                ' -> simple processor'.
                ' -> final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```
2. Запускаем тест командой `vendor/bin/codecept run functional`, видим ошибку
3. Фиксируем версию `phpunit` командой `composer require phpunit/phpunit:10.2.7 --dev`
4. Запускаем тест командой `vendor/bin/codecept run functional`, видим, что тест проходит

### Заменяем прямую сборку получением сервиса из контейнера

1. Исправляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Tests\FunctionalTester;
    use Exception;
    
    class ChainServiceCest
    {
        /**
         * @throws Exception
         */
        public function testProcess(FunctionalTester $I): void
        {
            $chainService = $I->grabService(ChainService::class);
    
            $expectedMessage = 'test simple plain processor'.
                ' -> final plain processor'.
                ' -> simple processor'.
                ' -> final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```
2. Запускаем тест командой `vendor/bin/codecept run functional`, тест ожидаемо падает
3. В файл `config/services` добавляем новую секцию
    ```yaml
    when@test:
        services:
            App\Service\Chain\Processor\SimplePlainProcessor:
                factory: ['Codeception\Stub', 'make']
                arguments:
                    - App\Service\Chain\Processor\SimplePlainProcessor
                    - { process: 'test simple plain processor' }
    ```
4. Запускаем тест командой `vendor/bin/codecept run functional`, тест проходит

### Пробуем заменить сервис, реализованный с помощью финального класса

1. В файле `config/services` исправляем секцию `when@test.services`
    ```yaml
    App\Service\Chain\Processor\FinalPlainProcessor:
        factory: ['Codeception\Stub', 'make']
        arguments:
            - App\Service\Chain\Processor\FinalPlainProcessor
            - { process: 'test final plain processor' }
    ```
2. Запускаем тест командой `vendor/bin/codecept run functional`, видим ошибку, что нельзя сделать двойник финального
класса

### Заменяем сервис, внедрённый как интерфейс

1. В файле `config/services` исправляем секцию `when@test.services`
    ```yaml
    App\Service\Chain\Processor\SimpleProcessorInterface: '@TestSimpleProcessor'

    TestSimpleProcessor:
        class: App\Service\Chain\Processor\SimpleProcessor
        factory: ['Codeception\Stub', 'make']
        arguments:
            - App\Service\Chain\Processor\SimpleProcessor
            - { process: 'test simple processor' }
    ```
2. Исправляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Tests\FunctionalTester;
    use Exception;
    
    class ChainServiceCest
    {
        /**
         * @throws Exception
         */
        public function testProcess(FunctionalTester $I): void
        {
            $chainService = $I->grabService(ChainService::class);
    
            $expectedMessage = 'test simple processor'.
                ' -> final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```
 
### Декорируем сервис, реализованный финальным классом и внедрённый как интерфейс

1. Добавляем класс `App\Tests\Mock\FinalProcessorProxy`
    ```php
    <?php
    
    namespace App\Tests\Mock;
    
    use App\Service\Chain\Processor\FinalProcessor;
    use App\Service\Chain\Processor\FinalProcessorInterface;
    
    class FinalProcessorProxy implements FinalProcessorInterface
    {
        public function __construct(
            private readonly FinalProcessor $baseProcessor
        ) {
        }
    
        public function process(string $source): string
        {
            return 'test final processor';
        }
    }
    ```
2. В файле `config/services` исправляем секцию `when@test.services`
    ```yaml
    App\Tests\Mock\FinalProcessorProxy:
        decorates: App\Service\Chain\Processor\FinalProcessor
        arguments:
            - '@.inner'
    ```
3. Исправляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Tests\FunctionalTester;
    use Exception;
    
    class ChainServiceCest
    {
        /**
         * @throws Exception
         */
        public function testProcess(FunctionalTester $I): void
        {
            $chainService = $I->grabService(ChainService::class);
    
            $expectedMessage = 'test final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```

## Тестируем код с обработкой исключений БД

### Добавляем функционал с обработкой исключений

1. Переходим в браузере по адресу `http://localhost:7777/task?name=some_task`, видим идентификатор созданной задачи и
созданную задачу в БД
2. В двух смежных вкладках браузера одновременно переходим по адресу `http://localhost:7777/task?name=other_task`,
видим, что в одном из случаев задача не создалась, т.к. в БД есть уникальный индекс по названию.
3. Исправляем класс `App\Service\TaskService`
    ```php
    <?php
    
    namespace App\Service;
    
    use App\Entity\Task;
    use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\Persistence\ManagerRegistry;
    use Throwable;
    
    class TaskService
    {
        public function __construct(
            private readonly ManagerRegistry $managerRegistry,
            private EntityManagerInterface $entityManager
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
    ```
4. В двух смежных вкладках браузера одновременно переходим по адресу `http://localhost:7777/task?name=new_task`,
   видим, что в одном из случаев задача не создалась, т.к. в БД есть уникальный индекс по названию.

### Пишем тест на новый код

1. В файле `tests/functional.suite.yml` раскомментируем настройки модуля `Doctrine2`
2. Пересобираем акторов тестов командой `vendor/bin/codecept build`
3. Добавляем тест `App\Tests\functional\TaskServiceCest`
    ```php
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
    ```
4. Выполняем миграции для тестового окружения командами
    ```shell
    php bin/console doctrine:database:create --env=test
    php bin/console doctrine:migrations:migrate --env=test
    ```
5. Запускаем тест командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php` и видим ошибку
`EntityManager is closed`  

### Исправляем тест

1. В файле `tests/functional.suite.yml` в секции `modules.enabled.Doctrine2` исправляем значение параметра `cleanup`
на `false`
2. Запускаем тест командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php`, он успешно проходит
3. Запускаем тест повторно, и видим ошибку, возникающую ещё до вызова тестируемого функционала.
4. Удаляем тестовые данные из БД
5. Исправляем тест `App\Tests\functional\TaskServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Entity\Task;
    use App\Service\TaskService;
    use App\Tests\FunctionalTester;
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
            $I->haveInRepository(Task::class, ['name' => self::TEST_TASK_NAME]);
            /** @var TaskService $taskService */
            $taskService = $I->grabService(TaskService::class);
            $taskService->addTask(self::TEST_TASK_NAME);
    
            $I->canSeeInRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME]);
    
            // cleanup
            $task1 = $I->grabEntityFromRepository(Task::class, ['name' => self::TEST_TASK_NAME]);
            $task2 = $I->grabEntityFromRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME]);
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $I->grabService('doctrine.orm.entity_manager');
            $entityManager->remove($task1);
            $entityManager->remove($task2);
            $I->flushToDatabase();
        }
    }
    ```
6. Запускаем тест дважды командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php`, он успешно проходит
оба раза

### Добавляем проверку на уникальность в код и исправляем тест

1. Исправляем класс `App\Service\TaskService`
    ```php
    <?php
    
    namespace App\Service;
    
    use App\Entity\Task;
    use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\ORM\EntityRepository;
    use Doctrine\Persistence\ManagerRegistry;
    use Throwable;
    
    class TaskService
    {
        public function __construct(
            private readonly ManagerRegistry $managerRegistry,
            private EntityManagerInterface $entityManager
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
    
                /** @var EntityRepository $taskRepository */
                $taskRepository = $this->entityManager->getRepository(Task::class);
                $existingTask = $taskRepository->findOneBy(['name' => $name]);
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
    ```
2. Запускаем тест командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php`, он выдаёт ошибку
3. Добавляем класс `App\Repository\TaskRepository`
    ```php
    <?php
    
    namespace App\Repository;
    
    use App\Entity\Task;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;
    
    class TaskRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Task::class);
        }
    
        public function findByName(string $name): ?Task
        {
            /** @var Task|null $task */
            $task = $this->findOneBy(['name' => $name]);
    
            return $task;
        }
    }
    ```
4. В классе `App\Entity\Task` исправляем атрибут класса
    ```php
    #[ORM\Entity(repositoryClass: TaskRepository::class)]
    ```
5. Исправляем класс `App\Service\TaskService`
    ```php
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
    ```
6. Добавляем класс `App\Tests\Mock\TaskRepositoryMock`
    ```php
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
    ```
7. В файле `config/services` добавляем в секцию `when@test.services` новый сервис
    ```yaml
    App\Repository\TaskRepository:
        public: true
        autowire: true
        autoconfigure: true
        class: App\Tests\Mock\TaskRepositoryMock
    ```
8. Исправляем тест `App\Tests\functional\TaskServiceCest`
    ```php
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
    ```
9. Запускаем тест командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php`, он успешно проходит

## Реализуем кастомные модули Codeception

### Добавим модуль для замены сервисов

1. Добавляем класс `App\Tests\Helper\MockService`
    ```php
    <?php
    
    namespace App\Tests\Helper;
    
    use Codeception\Module;
    
    class MockService extends Module
    {
        /**
         * @throws \Codeception\Exception\ModuleException
         */
        public function wantMockService(string $id, ?object $mock): void
        {
            /** @var Module\Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            
            $symfony->_getContainer()
                ->set($id, $mock);
        }
    }
    ```
2. В файле `config/services.yaml` в секции `when@test.services` убираем описание сервиса
`App\Tests\Mock\FinalProcessorProxy`
3. В файле `tests/functional.suite.yml` в секцию `modules.enabled` модуль `\App\Tests\Helper\MockService`
4. Исполняем команду `vendor/bin/codecept build`
5. Исправляем тест `App\Tests\functional\ChainServiceCest`
    ```php
    <?php
    
    namespace App\Tests\functional;
    
    use App\Service\Chain\ChainService;
    use App\Service\Chain\Processor\SimplePlainProcessor;
    use App\Tests\FunctionalTester;
    use Codeception\Stub;
    use Exception;
    
    class ChainServiceCest
    {
        /**
         * @throws Exception
         */
        public function testProcess(FunctionalTester $I): void
        {
            $simplePlainProcessorMock = Stub::make(SimplePlainProcessor::class, ['process' => 'test simple plain processor']);
            $I->wantMockService(SimplePlainProcessor::class, $simplePlainProcessorMock);
    
            $chainService = $I->grabService(ChainService::class);
            $expectedMessage = 'test simple plain processor'.
                ' -> final plain processor'.
                ' -> simple processor'.
                ' -> final processor'.
                ' -> Finish';
    
            $I->assertSame($expectedMessage, $chainService->process());
        }
    }
    ```
6. Запускаем тест командой `vendor/bin/codecept run tests/functional/ChainServiceCest.php`, он успешно проходит

### Добавим модуль для ручной очистки

1. Добавляем класс `App\Tests\Helper\CleanupService`
    ```php
    <?php
    
    namespace App\Tests\Helper;
    
    use Codeception\Module;
    
    class CleanupService extends Module
    {
        /**
         * @throws \Codeception\Exception\ModuleException
         */
        public function wantCleanupEntities(array $entities): void
        {
            /** @var Module\Doctrine2 $doctrine2 */
            $doctrine2 = $this->getModule('Doctrine2');
    
            $entityManager = $doctrine2->_getEntityManager();
            foreach ($entities as $entity) {
                $entityManager->remove($entity);
            }
            $doctrine2->flushToDatabase();
        }
    }
    ```
2. В файле `tests/functional.suite.yml` в секцию `modules.enabled` модуль `\App\Tests\Helper\CleanupService`
3. Исполняем команду `vendor/bin/codecept build`
4. Исправляем тест `App\Tests\functional\TaskServiceCest`
    ```php
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
    
            $I->wantCleanupEntities(
                [$I->grabEntityFromRepository(Task::class, ['name' => self::FIXED_TEST_TASK_NAME])]
            );
        }
    }
    ```
5. Запускаем дважды тест командой `vendor/bin/codecept run tests/functional/TaskServiceCest.php`, он успешно проходит
оба раза
