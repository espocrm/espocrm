<?php

namespace Espo\Modules\AdminPanel\Controllers;

use Espo\Core\Controllers\Record;

class Company extends Record
{
    protected function getServiceName(): string
    {
        return 'AdminPanel:Company';
    }
}
