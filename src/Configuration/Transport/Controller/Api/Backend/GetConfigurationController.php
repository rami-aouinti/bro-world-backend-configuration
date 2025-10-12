<?php

declare(strict_types=1);

namespace App\Configuration\Transport\Controller\Api\Backend;

use App\General\Domain\Utils\JSON;
use App\Configuration\Domain\Entity\Configuration;
use App\Configuration\Domain\Repository\Interfaces\ConfigurationRepositoryInterface;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Configuration
 */
#[AsController]
#[OA\Tag(name: 'Configuration')]
readonly class GetConfigurationController
{
    public function __construct(
        private SerializerInterface $serializer,
        private ConfigurationRepositoryInterface $repository,
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Get current user Configuration data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @throws JsonException
     * @throws NotSupported
     * @throws InvalidArgumentException
     */
    #[Route(
        path: '/v1/admin/configuration/{configuration}',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(SymfonyUser $symfonyUser, string $configuration): JsonResponse
    {
        $cacheKey = 'system_configurations';
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheData = $cacheItem->isHit() ? (array)$cacheItem->get() : [];

        if (array_key_exists($configuration, $cacheData)) {
            return new JsonResponse($cacheData[$configuration]);
        }

        $configurationEntity = $this->repository->findOneBy([
            'configurationKey' => $configuration,
        ]);

        /** @var array<string, mixed>|null $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $configurationEntity,
                'json',
                [
                    'groups' => 'Configuration',
                ]
            ),
            true,
        );

        $cacheData[$configuration] = $output;
        $cacheItem->set($cacheData);
        $cacheItem->expiresAfter(GetConfigurationsController::CACHE_TTL_IN_SECONDS);
        $this->cache->save($cacheItem);

        return new JsonResponse($output);
    }
}
