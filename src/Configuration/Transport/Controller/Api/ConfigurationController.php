<?php

declare(strict_types=1);

namespace App\Configuration\Transport\Controller\Api;

use Bro\WorldCoreBundle\Transport\Rest\Controller;
use Bro\WorldCoreBundle\Transport\Rest\ResponseHandler;
use Bro\WorldCoreBundle\Transport\Rest\Traits\Actions;
use App\Configuration\Application\DTO\Configuration\ConfigurationCreate;
use App\Configuration\Application\DTO\Configuration\ConfigurationPatch;
use App\Configuration\Application\DTO\Configuration\ConfigurationUpdate;
use App\Configuration\Application\Resource\ConfigurationResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @package App\Configuration
 *
 * @method ConfigurationResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/configuration',
)]
#[OA\Tag(name: 'Configuration Management')]
class ConfigurationController extends Controller
{
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\FindOneAction;
    use Actions\Admin\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => ConfigurationCreate::class,
        Controller::METHOD_UPDATE => ConfigurationUpdate::class,
        Controller::METHOD_PATCH => ConfigurationPatch::class,
    ];

    public function __construct(
        ConfigurationResource $resource,
    ) {
        parent::__construct($resource);
    }
}
