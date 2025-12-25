<?php

namespace App\Enums;

enum LicenseStatusEnum: string
{
    case Active = 'active';

    case Suspended = 'suspended';

    case Expired = 'expired';

    case Cancelled = 'cancelled';
}
