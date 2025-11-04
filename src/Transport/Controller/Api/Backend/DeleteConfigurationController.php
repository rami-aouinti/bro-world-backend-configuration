<?php

declare(strict_types=1);

namespace App\Transport\Controller\Api\Backend;

use App\Domain\Entity\Configuration;
use Bro\WorldCoreBundle\Domain\Utils\JSON;
use Doctrine\ORM\EntityManagerInterface;
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
readonly class DeleteConfigurationController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Get current user Configuration data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param Configuration $configuration
     * @return JsonResponse
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    #[Route(
        path: '/v1/admin/configuration/{configuration}',
        methods: [Request::METHOD_DELETE],
    )]
    public function __invoke(Configuration $configuration): JsonResponse
    {
        $cacheKey = "system_configurations";
        $this->cache->deleteItem($cacheKey);

        $this->entityManager->remove($configuration);
        $this->entityManager->flush();

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                'success',
                'json',
                [
                    'groups' => 'Configuration',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
