<?php

declare(strict_types=1);

namespace App\Transport\Controller\Api\Frontend;

use App\Domain\Entity\Configuration;
use App\Domain\Entity\Enum\FlagType;
use App\Domain\Repository\Interfaces\ConfigurationRepositoryInterface;
use Bro\WorldCoreBundle\Domain\Utils\JSON;
use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Ramsey\Uuid\Uuid;
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
readonly class PostConfigurationController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private ConfigurationRepositoryInterface $repository,
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Get current user Configuration data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request $request
     * @return JsonResponse
     * @throws JsonException
     * @throws NotSupported
     * @throws InvalidArgumentException
     */
    #[Route(
        path: '/v1/platform/configuration',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(SymfonyUser $symfonyUser, Request $request): JsonResponse
    {
        $cacheKey = "configurations_" . $symfonyUser->getUserIdentifier();
        $this->cache->deleteItem($cacheKey);

        $configuration = $this->repository->findOneBy([
            'configurationKey' => $request->request->get('configurationKey'),
            'userId' => $symfonyUser->getId()
        ]);

        if(!$configuration) {
            $configuration = new Configuration();
            $configuration->setUserId(Uuid::fromString($symfonyUser->getId()));
            $configuration->setConfigurationKey($request->request->get('configurationKey'));
            $configuration->setContextId(Uuid::fromString($request->request->get('contextId')));
            $configuration->setContextKey($request->request->get('contextKey'));
            $configuration->setWorkplaceId(Uuid::fromString($request->request->get('workplaceId')));;
            $configuration->setFlags([FlagType::USER->value]);
        }

        $configuration->setConfigurationValue($request->request->get('configurationValue'));

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $configuration,
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
