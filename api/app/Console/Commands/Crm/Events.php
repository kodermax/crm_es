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

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
//endregion

class Events extends Command
{
    protected $signature = 'crm:events {type}';

    protected $description = 'Команда для работы с событиями crm';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        $command = $this->argument('type');
        switch ($command) {
            case 'clear':
                $_SESSION["SESS_AUTH"]["ADMIN"] = true;
                $event = new \CCrmEvent();
                $list = \CCrmEvent::GetList([], ['CHECK_PERMISSIONS' => 'N']);
                while ($row = $list->GetNext()) {
                    $event->Delete($row['ID']);
                }
                break;
        }
    }
}
