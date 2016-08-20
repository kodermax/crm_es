<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Crm;

use Illuminate\Console\Command;

#region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
#endregion
use Synergy\Crm\Lead;

class LeadsAllocation extends Command
{
    protected $name = 'leads:allocation';

    protected $description = 'Распределение лидов';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        Lead::allocationLeads();
    }
}
