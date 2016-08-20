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
use Laravel\Lumen\Routing\DispatchesJobs;


//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

//endregion

class Quotes extends Command
{
    use DispatchesJobs;

    protected $signature = 'crm:quotes {type}';

    protected $description = 'Команда для работы с предложениями crm';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $command = $this->argument('type');
        switch ($command) {
            case 'rebuild_access':
                $list = \CCrmQuote::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID']);
                while ($row = $list->GetNext()) {
                    \CCrmQuote::RebuildEntityAccessAttrs($row['ID']);
                }
                break;
        }
    }
}
