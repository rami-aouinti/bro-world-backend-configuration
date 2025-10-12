<?php

declare(strict_types=1);

namespace App\Configuration\Transport\AutoMapper\Configuration;

use App\Configuration\Application\DTO\Configuration\ConfigurationCreate;
use App\Configuration\Application\DTO\Configuration\ConfigurationPatch;
use App\Configuration\Application\DTO\Configuration\ConfigurationUpdate;
use App\Configuration\Transport\AutoMapper\Configuration\RequestMapper;
use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;

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
