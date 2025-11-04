<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Configuration as Entity;
use App\Domain\Repository\Interfaces\ConfigurationRepositoryInterface;
use Bro\WorldCoreBundle\Infrastructure\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @package App\Configuration
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null, ?string $entityManagerName = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null, string|null $entityManagerName = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?string $entityManagerName = null)
 * @method Entity[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?string $entityManagerName = null)
 * @method Entity[] findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null, ?string $entityManagerName = null)
 * @method Entity[] findAll(?string $entityManagerName = null)
 *
 * @codingStandardsIgnoreEnd
 */
class ConfigurationRepository extends BaseRepository implements ConfigurationRepositoryInterface
{
    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['username', 'firstName', 'lastName', 'email'];

    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry
    ) {
    }
}
