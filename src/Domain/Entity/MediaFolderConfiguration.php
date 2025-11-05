<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Bro\WorldCoreBundle\Domain\Entity\Interfaces\EntityInterface;
use Bro\WorldCoreBundle\Domain\Entity\Traits\Timestampable;
use Bro\WorldCoreBundle\Domain\Entity\Traits\Uuid;
use Bro\WorldCoreBundle\Domain\Entity\Traits\WorkplaceTrait;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * Class MediaFolderConfiguration
 *
 * @package App\Configuration\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table(name: 'media_folder_configuration')]
class MediaFolderConfiguration implements EntityInterface
{
    use Uuid;
    use Timestampable;
    use WorkplaceTrait;

    public const string SET_USER_Configuration = 'Configuration';

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

    #[ORM\Column(type: 'boolean')]
    private bool $createThumbnails = false;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $mediaThumbnailSizesRo;

    #[ORM\Column(type: 'boolean')]
    private bool $private = false;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function isCreateThumbnails(): bool
    {
        return $this->createThumbnails;
    }

    public function setCreateThumbnails(bool $createThumbnails): self
    {
        $this->createThumbnails = $createThumbnails;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMediaThumbnailSizesRo()
    {
        return $this->mediaThumbnailSizesRo;
    }

    /**
     * @param $mediaThumbnailSizesRo
     *
     * @return $this
     */
    public function setMediaThumbnailSizesRo($mediaThumbnailSizesRo): self
    {
        if ($mediaThumbnailSizesRo !== null && !is_string($mediaThumbnailSizesRo)) {
            throw new InvalidArgumentException('mediaThumbnailSizesRo must be a string or null.');
        }
        $this->mediaThumbnailSizesRo = $mediaThumbnailSizesRo;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }
}

