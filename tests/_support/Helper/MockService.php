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
