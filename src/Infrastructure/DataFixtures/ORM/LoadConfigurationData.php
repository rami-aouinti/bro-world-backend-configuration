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
    public const int CONFIGURATION_COUNT = 10;

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

        for ($i = 0; $i < self::CONFIGURATION_COUNT; $i++) {
            $configuration = new Configuration();
            $configuration->setConfigurationKey(sprintf('system.smtp.settings.user.test_%d', $i + 1));
            $configurationValue = [
                'direction' => [
                    'username' => $faker->userName(),
                    'password' => $faker->password(),
                ],
                'transport' => $faker->randomElement(['smtp', 'sendmail', 'native']),
            ];
            $configuration->setConfigurationValue($configurationValue);
            $configuration->setContextKey(sprintf('user_%d', $i + 1));
            $configuration->setContextId(Uuid::uuid4());
            $configuration->setWorkplaceId(Uuid::uuid4());
            $configuration->setFlags([FlagType::PROTECTED_SYSTEM->value]);
            $manager->persist($configuration);
        }
    }
}
