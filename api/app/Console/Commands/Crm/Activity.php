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

class Activity extends Command
{
    use DispatchesJobs;

    protected $signature = 'crm:activity {type} {user}';

    protected $description = 'Команда для работы с делами crm';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $command = $this->argument('type');
        $user = $this->argument('user');
        switch ($command) {
            case 'resave':
                $list = \CCrmActivity::GetList([],['CHECK_PERMISSIONS' => 'N','RESPONSIBLE_ID' => $user], false, false,['ID']);
                while ($row = $list->GetNext()) {
                    \CCrmActivity::Update($row['ID'], [], false, true, array('REGISTER_SONET_EVENT' => true));
                }
                break;
        }
    }
}
