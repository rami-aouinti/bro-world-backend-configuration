<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\ORM;

use App\Domain\Entity\Configuration;
use App\Domain\Entity\Enum\FlagType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Override;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * @package App\Configuration
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadConfigurationData extends Fixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @throws Throwable
     */
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Create entities
        $this->createConfiguration($manager);
        // Flush database changes
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    #[Override]
    public function getOrder(): int
    {
        return 1;
    }

    /**
     * Method to create User entity with specified role.
     *
     * @throws Throwable
     */
    private function createConfiguration(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        for ($i = 0; $i < 10; $i++) {
            $configuration = new Configuration();
            $configuration->setConfigurationKey('system.smtp.settings.user.test');
            $configurationValue = [
                'direction' => [
                    'username' => 'username_user',
                    'password' => 'password_user',
                ],
            ];
            $configuration->setConfigurationValue($configurationValue);
            $configuration->setContextKey('user');
            $configuration->setContextId(Uuid::fromString('5d6ae46c-ce1f-376f-82a6-28b0054083b7'));
            $configuration->setWorkplaceId(Uuid::fromString('95a6e45d-da43-345b-99b1-611e27d4ba2e'));
            $configuration->setFlags([FlagType::PROTECTED_SYSTEM->value]);
            $manager->persist($configuration);
        }
    }
}
