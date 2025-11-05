<?php

declare(strict_types=1);

namespace App\Transport\Controller\Api\Frontend;

use App\Domain\Repository\Interfaces\ConfigurationRepositoryInterface;
use Bro\WorldCoreBundle\Domain\Utils\JSON;
use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use OpenApi\Attributes as OA;
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
readonly class GetConfigurationsController
{
    public const int CACHE_TTL_IN_SECONDS = 3600;

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
        path: '/v1/platform/configuration',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(SymfonyUser $symfonyUser): JsonResponse
    {
        $cacheKey = 'configurations_' . $symfonyUser->getUserIdentifier();
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var array<string, mixed> $cached */
            $cached = (array)$cacheItem->get();

            return new JsonResponse(array_values($cached));
        }

        $configurations = $this->repository->findBy([
            'userId' => $symfonyUser->getId(),
        ]);

        /** @var array<int, array<string, mixed>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $configurations,
                'json',
                [
                    'groups' => 'Configuration',
                ]
            ),
            true,
        );

        $mapped = [];

        foreach ($output as $configuration) {
            if (is_array($configuration) && array_key_exists('configurationKey', $configuration)) {
                $mapped[(string)$configuration['configurationKey']] = $configuration;
            }
        }

        $cacheItem->set($mapped);
        $cacheItem->expiresAfter(self::CACHE_TTL_IN_SECONDS);
        $this->cache->save($cacheItem);

        return new JsonResponse(array_values($mapped));
    }
}
