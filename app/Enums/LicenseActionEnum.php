<?php

namespace App\Enums;

enum LicenseActionEnum: string
{
    case Renew = 'renew';

    case Suspend = 'suspend';

    case Resume = 'resume';

    case Cancel = 'cancel';
}
