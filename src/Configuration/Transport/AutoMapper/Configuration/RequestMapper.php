<?php

declare(strict_types=1);

namespace App\Configuration\Transport\AutoMapper\Configuration;

use Bro\WorldCoreBundle\Transport\AutoMapper\RestRequestMapper;

/**
 * @package App\Configuration
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'userId',
        'configurationKey',
        'configurationValue',
        'contextKey',
        'contextId',
        'workplaceId',
        'flags',
    ];
}
