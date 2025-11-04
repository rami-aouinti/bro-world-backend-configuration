<?php

declare(strict_types=1);

namespace App\Transport\AutoMapper\Configuration;

use App\Application\DTO\Configuration\ConfigurationCreate;
use App\Application\DTO\Configuration\ConfigurationPatch;
use App\Application\DTO\Configuration\ConfigurationUpdate;
use Bro\WorldCoreBundle\Transport\AutoMapper\RestAutoMapperConfiguration;

/**
 * @package App\User
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * Classes to use specified request mapper.
     *
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        ConfigurationCreate::class,
        ConfigurationUpdate::class,
        ConfigurationPatch::class,
    ];

    public function __construct(
        RequestMapper $requestMapper,
    ) {
        parent::__construct($requestMapper);
    }
}
