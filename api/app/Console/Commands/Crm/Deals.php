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
use Illuminate\Support\Facades\File;
use Laravel\Lumen\Routing\DispatchesJobs;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

//endregion

class Deals extends Command
{
    use DispatchesJobs;

    protected $signature = 'crm:deals {type}';

    protected $description = 'Команда для работы со сделками crm';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        $command = $this->argument('type');
        switch ($command) {
            case 'rebuild_access':
                $list = \CCrmDeal::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array('ID'));
                while ($row = $list->GetNext()) {
                    \CCrmDeal::RebuildEntityAccessAttrs($row['ID']);
                }
                break;
        }
    }
}
