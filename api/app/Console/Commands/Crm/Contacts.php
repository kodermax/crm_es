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
use Synergy\Crm\Contact;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

//endregion

class Contacts extends Command
{
    protected $signature = 'crm:contacts {type}';

    protected $description = 'Команда для работы с контактами crm';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        $command = $this->argument('type');
        switch ($command) {
            case 'source':
                $by = '';
                $order = '';
                $list = \CCrmContact::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID', 'ASSIGNED_BY_ID']);
                while ($row = $list->GetNext()) {
                    $rsUser = \CUser::GetList($by, $order, ['ID' => $row['ASSIGNED_BY_ID']], ['SELECT' => ['UF_DEPARTMENT']]);
                    if ($arUser = $rsUser->Fetch()) {
                    }
                    break;
                }
                break;
            case 'rebuild_access':
                $list = \CCrmContact::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID']);
                while ($row = $list->GetNext()) {
                    \CCrmContact::RebuildEntityAccessAttrs($row['ID']);
                }
                break;
            case 'rebuild_structure':
                $list = \CCrmContact::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID', 'ASSIGNED_BY_ID']);
                while ($row = $list->GetNext()) {
                    if ($row['ASSIGNED_BY_ID'] > 0) {
                        Contact::changeDepartment($row['ID'], $row['ASSIGNED_BY_ID']);
                    }
                }
                break;

        }
    }
}
