<?php

declare(strict_types=1);

namespace App\Configuration\Application\DTO\Configuration;

use App\Configuration\Domain\Entity\Configuration as Entity;
use App\Configuration\Domain\Entity\Enum\FlagType;
use Bro\WorldCoreBundle\Application\DTO\Interfaces\RestDtoInterface;
use Bro\WorldCoreBundle\Application\DTO\RestDto;
use Bro\WorldCoreBundle\Domain\Entity\Interfaces\EntityInterface;
use Override;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

use function array_map;

/**
 * @package App\Configuration
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Configuration extends RestDto
{
    #[Assert\NotBlank(message: 'User ID cannot be blank.')]
    protected ?UuidInterface $userId = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected string $configurationKey = '';

    #[Assert\NotNull]
    protected mixed $configurationValue = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected string $contextKey = '';

    #[Assert\NotNull]
    protected ?UuidInterface $contextId = null;

    #[Assert\NotNull]
    protected ?UuidInterface $workplaceId = null;

    /**
     * @var array<int, string>
     */
    #[Assert\Choice(callback: [self::class, 'availableFlagValues'], multiple: true, strict: true)]
    protected array $flags = [];

    public function getUserId(): ?UuidInterface
    {
        return $this->userId;
    }

    public function setUserId(UuidInterface|string|null $userId): self
    {
        $this->setVisited('userId');
        $this->userId = $userId instanceof UuidInterface || $userId === null
            ? $userId
            : Uuid::fromString($userId);

        return $this;
    }

    public function getConfigurationKey(): string
    {
        return $this->configurationKey;
    }

    public function setConfigurationKey(string $configurationKey): self
    {
        $this->setVisited('configurationKey');
        $this->configurationKey = $configurationKey;

        return $this;
    }

    public function getConfigurationValue(): mixed
    {
        return $this->configurationValue;
    }

    public function setConfigurationValue(mixed $configurationValue): self
    {
        $this->setVisited('configurationValue');
        $this->configurationValue = $configurationValue;

        return $this;
    }

    public function getContextKey(): string
    {
        return $this->contextKey;
    }

    public function setContextKey(string $contextKey): self
    {
        $this->setVisited('contextKey');
        $this->contextKey = $contextKey;

        return $this;
    }

    public function getContextId(): ?UuidInterface
    {
        return $this->contextId;
    }

    public function setContextId(UuidInterface|string|null $contextId): self
    {
        $this->setVisited('contextId');
        $this->contextId = $contextId instanceof UuidInterface || $contextId === null
            ? $contextId
            : Uuid::fromString($contextId);

        return $this;
    }

    public function getWorkplaceId(): ?UuidInterface
    {
        return $this->workplaceId;
    }

    public function setWorkplaceId(UuidInterface|string|null $workplaceId): self
    {
        $this->setVisited('workplaceId');
        $this->workplaceId = $workplaceId instanceof UuidInterface || $workplaceId === null
            ? $workplaceId
            : Uuid::fromString($workplaceId);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @param array<int, FlagType|string> $flags
     */
    public function setFlags(array $flags): self
    {
        $this->setVisited('flags');
        $this->flags = array_map(
            static fn (FlagType|string $flag): string => $flag instanceof FlagType ? $flag->value : (string) $flag,
            $flags,
        );

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public static function availableFlagValues(): array
    {
        return array_map(
            static fn (FlagType $flag): string => $flag->value,
            FlagType::cases(),
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    #[Override]
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->userId = $entity->getUserId();
            $this->configurationKey = $entity->getConfigurationKey();
            $this->configurationValue = $entity->getConfigurationValue();
            $this->contextKey = $entity->getContextKey();
            $this->contextId = $entity->getContextId();
            $this->workplaceId = $entity->getWorkplaceId();
            $this->flags = $entity->getFlags();
        }

        return $this;
    }
}
