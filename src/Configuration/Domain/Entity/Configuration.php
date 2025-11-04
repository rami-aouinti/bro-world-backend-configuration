<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Entity;

use Bro\WorldCoreBundle\Domain\Entity\Interfaces\EntityInterface;
use Bro\WorldCoreBundle\Domain\Entity\Traits\Timestampable;
use Bro\WorldCoreBundle\Domain\Entity\Traits\Uuid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidDigestLength;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSignature;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SodiumException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Configuration\Domain\Entity\Enum\FlagType;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * @package Workplacenow\ConfigService\Entity
 * @author  Rami Aouinti <rami.aouinti@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table(name: 'configuration', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'unique_config_key', columns: ['configurationKey', 'contextKey', 'workplaceId']),
])]
#[UniqueEntity(
    fields: ['configurationKey', 'contextKey', 'workplaceId'],
    message: 'this configuration with this contextKey and workplaceId exist.'
)]
#[ORM\HasLifecycleCallbacks]
class Configuration implements EntityInterface
{
    use Uuid;
    use Timestampable;

    public const string SET_USER_CONFIGURATION = 'configuration';

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups([
        'Configuration',
        'Configuration.id'
    ])]
    private UuidInterface $id;


    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Assert\NotBlank(message: 'User ID cannot be blank.')]
    #[Groups([
        'Configuration',
        'Configuration.userId'
    ])]
    private ?UuidInterface $userId;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([
        'Configuration',
        'Configuration.configurationKey'
    ])]
    private string $configurationKey;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\NotBlank]
    #[Groups([
        'Configuration',
        'Configuration.configurationValue'
    ])]
    private mixed $configurationValue = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([
        'Configuration',
        'Configuration.contextKey'
    ])]
    private string $contextKey;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Assert\NotNull]
    #[Groups([
        'Configuration',
        'Configuration.contextId'
    ])]
    private ?UuidInterface $contextId = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Assert\NotNull]
    #[Groups([
        'Configuration',
        'Configuration.workplaceId'
    ])]
    private ?UuidInterface $workplaceId = null;

    #[ORM\Column(type: 'json', nullable: true, enumType: FlagType::class)]
    #[Assert\Choice(callback: [FlagType::class, 'cases'], multiple: true, strict: true)]
    #[Groups([
        'Configuration',
        'Configuration.flags'
    ])]
    private array $flags;

    private ?EncryptionKey $encryptionKey = null;

    /**
     * @throws CannotPerformOperation
     * @throws InvalidKey
     * @throws Exception
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $keyPath = $this->doEncryptionKeyPath();

        if (!file_exists($keyPath)) {
            throw new Exception("Encryption key file not found at: {$keyPath}");
        }

        $this->encryptionKey = KeyFactory::loadEncryptionKey($keyPath);
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUserId(): ?UuidInterface
    {
        return $this->userId;
    }

    public function setUserId(?UuidInterface $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getConfigurationKey(): string
    {
        return $this->configurationKey;
    }

    public function setConfigurationKey(string $configurationKey): void
    {
        $this->configurationKey = $configurationKey;
    }

    public function getConfigurationValue(): mixed
    {
        return $this->configurationValue ?? null;
    }

    public function setConfigurationValue(mixed $configurationValue): void
    {
        if (isset($configurationValue['_value'])) {
            $this->configurationValue = [
                '_value' => $configurationValue['_value'],
            ];
        } else {
            $this->configurationValue = [
                '_value' => $configurationValue,
            ];
        }
    }

    public function getContextKey(): string
    {
        return $this->contextKey;
    }

    public function setContextKey(string $contextKey): void
    {
        $this->contextKey = $contextKey;
    }

    public function getContextId(): ?UuidInterface
    {
        return $this->contextId;
    }

    public function setContextId(?UuidInterface $contextId): void
    {
        $this->contextId = $contextId;
    }

    public function getWorkplaceId(): ?UuidInterface
    {
        return $this->workplaceId;
    }

    public function setWorkplaceId(?UuidInterface $workplaceId): void
    {
        $this->workplaceId = $workplaceId;
    }

    public function getFlags(): array
    {
        return array_map(fn ($flag) => $flag->value, $this->flags);
    }

    public function setFlags(array $flags): void
    {
        $this->flags = array_map(fn ($flagValue) => FlagType::from($flagValue), $flags);
    }

    public function getEncryptionKey(): string
    {
        return base64_encode((string)$this->encryptionKey);
    }

    public function setEncryptionKey(?EncryptionKey $encryptionKey): void
    {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @throws CannotPerformOperation
     * @throws InvalidDigestLength
     * @throws InvalidKey
     * @throws InvalidMessage
     * @throws InvalidSignature
     * @throws InvalidType
     * @throws Exception
     */
    #[ORM\PostLoad]
    public function decryptConfigurationValue(): void
    {
        $keyPath = realpath(__DIR__ . '/../../../../encryption.key');
        if ($keyPath === false) {
            throw new Exception('Encryption key file not found.');
        }
        $this->encryptionKey = KeyFactory::loadEncryptionKey($keyPath);

        if (in_array(FlagType::PROTECTED_SYSTEM, $this->flags)) {
            if (isset($this->configurationValue)) {
                $this->decryptConfigurationValueRecursive($this->configurationValue['_value']);
            } else {
                $configurationValue = new HiddenString($this->configurationValue);
                $this->configurationValue = Symmetric::decrypt(
                    $configurationValue->getString(),
                    $this->encryptionKey
                )->getString();
            }
        }
    }

    public function validateUniqueCombination(
        ExecutionContextInterface $context,
        EntityManagerInterface $entityManager
    ): void {
        $existingConfig = $entityManager->getRepository(self::class)
            ->findOneBy([
                'configurationKey' => $this->getConfigurationKey(),
                'contextKey' => $this->getContextKey(),
                'workplaceId' => $this->getWorkplaceId(),
            ]);

        if ($existingConfig && $existingConfig->getId() !== $this->getId()) {
            $context->buildViolation('This combination of configurationKey, contextKey et workplaceId exist.')
                ->atPath('configurationKey')
                ->addViolation();
        }
    }

    /**
     * @throws CannotPerformOperation
     * @throws InvalidDigestLength
     * @throws InvalidMessage
     * @throws InvalidType
     * @throws SodiumException
     * @return mixed
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function encryptConfigurationValue()
    {
        if (in_array(FlagType::PROTECTED_SYSTEM, $this->flags)) {
            $this->encryptConfigurationValueRecursive($this->configurationValue['_value']);

            return $this->configurationValue['_value'];
        }

        return $this->configurationValue['_value'];
    }

    /**
     * @param $value
     *
     * @throws CannotPerformOperation
     * @throws InvalidDigestLength
     * @throws InvalidMessage
     * @throws InvalidSignature
     * @throws InvalidType
     * @throws SodiumException
     */
    public function decryptConfigurationValueRecursive(&$value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => &$subValue) {
                $this->decryptConfigurationValueRecursive($subValue);
            }
        } elseif (is_string($value)) {
            $encryptedString = new HiddenString($value);
            $value = Symmetric::decrypt($encryptedString->getString(), $this->encryptionKey)->getString();
        }
    }

    /**
     * @throws CannotPerformOperation
     * @throws InvalidDigestLength
     * @throws InvalidMessage
     * @throws InvalidType
     * @throws SodiumException
     */
    private function encryptConfigurationValueRecursive(&$value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => &$subValue) {
                $this->encryptConfigurationValueRecursive($subValue);
            }
        } elseif (is_string($value)) {
            $value = Symmetric::encrypt(new HiddenString($value), $this->encryptionKey);
        }
    }

    /**
     * Get the path of the encryption key.
     */
    private function doEncryptionKeyPath(): string
    {
        return realpath(__DIR__ . '/../../../../encryption.key');
    }
}
