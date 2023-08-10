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
