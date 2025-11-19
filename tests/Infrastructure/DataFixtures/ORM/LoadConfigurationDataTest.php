<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\DataFixtures\ORM;

use App\Domain\Entity\Configuration;
use App\Infrastructure\DataFixtures\ORM\LoadConfigurationData;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class LoadConfigurationDataTest extends TestCase
{
    public function testLoadCreatesUniqueConfigurations(): void
    {
        $fixture = new LoadConfigurationData();
        $persistedKeys = [];
        $persistedContextIds = [];
        $persistedWorkplaceIds = [];

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->expects(self::exactly(LoadConfigurationData::CONFIGURATION_COUNT))
            ->method('persist')
            ->with(self::callback(function (Configuration $configuration) use (&$persistedKeys, &$persistedContextIds, &$persistedWorkplaceIds): bool {
                $configurationKey = $configuration->getConfigurationKey();
                self::assertNotContains($configurationKey, $persistedKeys, 'Configuration keys must be unique.');
                $persistedKeys[] = $configurationKey;

                $contextId = $configuration->getContextId()?->toString();
                if ($contextId !== null) {
                    self::assertNotContains($contextId, $persistedContextIds, 'Context IDs must be unique.');
                    $persistedContextIds[] = $contextId;
                }

                $workplaceId = $configuration->getWorkplaceId()?->toString();
                if ($workplaceId !== null) {
                    self::assertNotContains($workplaceId, $persistedWorkplaceIds, 'Workplace IDs must be unique.');
                    $persistedWorkplaceIds[] = $workplaceId;
                }

                return true;
            }));

        $objectManager->expects(self::once())->method('flush');

        $fixture->load($objectManager);
    }
}
