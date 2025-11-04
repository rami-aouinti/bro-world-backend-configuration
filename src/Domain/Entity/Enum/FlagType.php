<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

/**
 * Class FlagType
 *
 * @package App\Configuration\Domain\Entity\Enum
 * @author  Rami Aouinti <rami.aouinti@gmail.com>
 */
enum FlagType: string
{
    case PROTECTED_SYSTEM = 'PROTECTED_SYSTEM';
    case PROTECTED_WORKPLACE = 'PROTECTED_WORKPLACE';
    case USER = 'USER';
}
