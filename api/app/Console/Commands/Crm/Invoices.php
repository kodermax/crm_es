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
use Synergy\Crm\Invoice;


//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

//endregion

class Invoices extends Command
{
    use DispatchesJobs;

    protected $signature = 'crm:invoices {type}';

    protected $description = 'Команда для работы со счетами crm';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $command = $this->argument('type');
        switch ($command) {
            case 'rebuild_access':
                $list = \CCrmInvoice::GetList([], ['CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
                while ($row = $list->GetNext()) {
                    \CCrmInvoice::RebuildEntityAccessAttrs($row['ID']);
                }
                break;
            case 'rebuild_structure':
                $list = \CCrmInvoice::GetList([], ['CHECK_PERMISSIONS' => 'N'], false, false, ['ID','RESPONSIBLE_ID']);
                while ($row = $list->GetNext()) {
                    if ($row['RESPONSIBLE_ID'] > 0) {
                        Invoice::changeDepartment($row['ID'], $row['RESPONSIBLE_ID']);
                    }
                }
                break;
        }
    }
}
