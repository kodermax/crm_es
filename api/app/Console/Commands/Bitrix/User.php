<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Bitrix;

use Illuminate\Console\Command;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

//endregion

class User extends Command
{
    protected $signature = 'bitrix:user {type}';

    protected $description = 'Команда для работы с юзерами bitrix';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $command = $this->argument('type');
        switch ($command) {
            case 'fired':
                $user = new \CUser();
                $arCompanies = [
                    'Школа бизнеса Синергия',
                    'ШБ "Синергия"',
                    'Частный репетитор',
                    'ООО Компания "Частный репетитор"',
                    'ООО "Школа Бизнеса Синергия"'
                ];
                $list = \CUser::GetList($by, $order, ['ACTIVE' => 'N'], [
                    'FIELDS' => ['ID', 'WORK_COMPANY']
                ]);
                while ($row = $list->GetNext()) {
                    if (in_array($row['~WORK_COMPANY'], $arCompanies)){
                        $user->Update($row['ID'], ['UF_DEPARTMENT' => [3249]]);
                    }
                }
                break;
        }
    }
}
