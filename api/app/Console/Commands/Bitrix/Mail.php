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
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
//endregion

class Mail extends Command
{
    protected $signature = 'bitrix:mail {type}';

    protected $description = 'Команда для работы с почтой bitrix';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        \Bitrix\Main\Loader::includeModule('mail');
        $command = $this->argument('type');
        switch ($command) {
            case 'clear':
                $list = \CMailMessage::GetList([], []);
                while ($row = $list->GetNext()) {
                    \CMailMessage::Delete($row['ID']);
                }

                break;
        }
    }
}
