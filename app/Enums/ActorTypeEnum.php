<?php

namespace App\Enums;

enum ActorTypeEnum: string
{
    case Brand = 'brand';

    case Product = 'product';

    case Customer = 'customer';

    case System = 'system';
}
