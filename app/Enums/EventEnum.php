<?php

namespace App\Enums;

enum EventEnum: string
{
    case Created = 'created';

    case Updated = 'updated';

    case Deleted = 'deleted';
}
