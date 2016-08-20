<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Jobs\Crm;

use Bitrix\Main\Loader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
Loader::includeModule('iblock');
//endregion

use Synergy\Crm\Lead;

class LeadAllocation implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $leadId;

    public function __construct($id)
    {
        $this->leadId = $id;
    }

/**
 * Запуск задачи.
 */
    public function handle()
    {
        $_SESSION['SESS_AUTH']['USER_ID'] = USER_ID_LANDER;
        $list = \CCrmLead::GetList([],
            [
                'CHECK_PERMISSIONS' => 'N',
                'ID' => $this->leadId,
                'ASSIGNED_BY_ID' => USER_ID_LANDER,
            ],
            ['ID', 'NAME', 'LAST_NAME', Lead::PROP_LAND_CODE, 'SOURCE_DESCRIPTION', 'UF_NOT_ALLOCATION'],
            false);
        if ($row = $list->GetNext()) {
            $responsibleId = Lead::allocationLead($row);
            if ($responsibleId === USER_ID_LANDER) {
                Lead::updateUserField($row['ID'], 'UF_NOT_ALLOCATION', 1);
            } else {
                Lead::changeResponsible($row['ID'], $responsibleId);
                //Актуализируем структурное подразделение
                Lead::changeDepartment($row['ID'], $responsibleId);
            }
        }
        unset($row, $list);
    }
}
