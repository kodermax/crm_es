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

class Phone extends Command
{
    protected $signature = 'crm:phone {entityType}';

    protected $description = 'Чистка телефонов в crm';

    public function handle()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        $entityType = $this->argument('entityType');
        //Вывод телефонов с текстом
        $list = \CCrmLead::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID', 'FM'], 50);
        while ($row = $list->GetNext()) {
            echo '<pre>';
            print_r($row);
            echo '</pre>';
        }
    }
}
