<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Crm;

use Alchemy\Zippy\Zippy;
use App\Jobs\Crm\LeadAllocation;
use Bitrix\Main\Loader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Routing\DispatchesJobs;
use Synergy\Crm\Lead;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

//endregion

class Leads extends Command
{
    use DispatchesJobs;

    protected $signature = 'crm:leads {type}';

    protected $description = 'Команда для работы с лидами crm';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        $command = $this->argument('type');
        switch ($command) {
            case 'delete_test':
                $_SESSION['SESS_AUTH']['USER_ID'] = 1;
                $crmLead = new \CCrmLead(false);
                $list = \CCrmLead::GetList([], ['CHECK_PERMISSIONS' => 'N', 'STATUS_ID' => 11], ['ID']);
                while ($row = $list->GetNext()) {
                    $result = $crmLead->Delete($row['ID'], ['PROCESS_BIZPROC' => true]);
                    if (!$result) {
                        echo $crmLead->LAST_ERROR;
                    }
                }
                break;
            case 'queue_clear':
                $exists = Storage::disk('local')->exists('dispatch.json');
                if ($exists) {
                    Storage::disk('local')->delete('dispatch.json');
                }
            case 'allocation':
                $list = \CCrmLead::GetList([], ['CHECK_PERMISSIONS' => 'N', 'ASSIGNED_BY_ID' => USER_ID_LANDER], ['ID']);
                while ($row = $list->GetNext()) {
                    $task = (new LeadAllocation($row['ID']))->onQueue('lead_allocation');
                    $this->dispatch($task);
                }
                break;
            case 'mail':
                $crm = new \CCrmLead(false);
                $list = \CCrmLead::GetList([], ['!'.Lead::PROP_SOURCE_CODE => false, 'CHECK_PERMISSIONS' => 'N'], ['ID', Lead::PROP_CAMPAIGN_CODE]);
                while ($row = $list->GetNext()) {
                    $arUpdate = [Lead::PROP_GETRESPONSE => $row[Lead::PROP_CAMPAIGN_CODE]];
                    $crm->Update($row['ID'], $arUpdate, false, false);
                }
                break;
            case 'export':
                Loader::includeModule('crm');
                $this->export();
                break;
            case 'rebuild_access':
                $list = \CCrmLead::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID']);
                while ($row = $list->GetNext()) {
                    \CCrmLead::RebuildEntityAccessAttrs($row['ID']);
                }
                break;
            case 'rebuild_structure':
                $list = \CCrmLead::GetList([], ['CHECK_PERMISSIONS' => 'N'], ['ID', 'ASSIGNED_BY_ID']);
                while ($row = $list->GetNext()) {
                    if ($row['ASSIGNED_BY_ID'] > 0) {
                        Lead::changeDepartment($row['ID'], $row['ASSIGNED_BY_ID']);
                    }
                }
                break;
        }
    }

    private function export()
    {
        $arStatus = \CCrmStatus::GetStatusList('STATUS');
        $path = storage_path().'/app/mails/';
        if (!File::isDirectory($path)) {
            Storage::makeDirectory('mails');
        }

        $dateTime = new \DateTime();
        $dateTime->sub(new \DateInterval('P1D'));
        $dateFrom = $dateTime->format('d.m.Y 00:00:00');
        $dateTo = $dateTime->format('d.m.Y 23:59:59');
        $fileName = 'export_'.$dateTime->format('dmY').'.csv';
        $filePath = $path.$fileName;
        $fileNameTar = $path.'export_'.$dateTime->format('dmY').'.tar';
        $arUsers = [];
        $csv = new \CCSVData('R', true);
        $csv->SaveFile($filePath, ['Ид', 'Дата создания', 'Старый/Новый', 'Лид', 'Ленд', 'Статус', 'Имя', 'Телефон', 'Email', 'Продукт', 'Город', 'Ответственный', 'Диспетчер']);
        $arFilter = [
            'CHECK_PERMISSIONS' => 'N',
            [
                '>=DATE_CREATE' => $dateFrom,
                '<=DATE_CREATE' => $dateTo,
            ],
        ];
        $list = \CCrmLead::GetList([],
            $arFilter,
            ['ID', 'DATE_CREATE', Lead::PROP_NEW_OLD, 'TITLE', Lead::PROP_LAND_CODE, 'STATUS_ID', 'FULL_NAME', Lead::PROP_PRODUCT, Lead::PROP_CITY, 'ASSIGNED_BY', Lead::PROP_DISPATCHER]);
        while ($row = $list->GetNext()) {
            $email = '';
            $multiFields = \CCrmFieldMulti::GetList([], ['ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $row['ID']]);
            while ($multi = $multiFields->GetNext()) {
                if ($multi['TYPE_ID'] === 'EMAIL' && strlen($multi['VALUE']) > 0) {
                    $email = strip_tags($multi['VALUE']);
                }
                if ($multi['TYPE_ID'] === 'PHONE' && strlen($multi['VALUE']) > 0) {
                    $phone = strip_tags($multi['VALUE']);
                }
            }
            if (!array_key_exists($row[Lead::PROP_DISPATCHER], $arUsers)) {
                $rsUsers = \CUser::GetList($by, $order, ['ID' => $row[Lead::PROP_DISPATCHER]], ['FIELDS' => ['ID', 'NAME', 'LAST_NAME']]);
                if ($arUser = $rsUsers->GetNext()) {
                    $arUsers[$arUser['ID']] = $arUser['NAME'].' '.$arUser['LAST_NAME'];
                }
            }
            $csv->SaveFile($filePath, [$row['ID'], $row['DATE_CREATE'], $row[Lead::PROP_NEW_OLD], $row['TITLE'], $row[Lead::PROP_LAND_CODE], $arStatus[$row['STATUS_ID']], $row['FULL_NAME'], $phone, $email, $row[Lead::PROP_PRODUCT], $row[Lead::PROP_CITY], $row['ASSIGNED_BY_NAME'].' '.$row['ASSIGNED_BY_LAST_NAME'], $arUsers[$row[Lead::PROP_DISPATCHER]]]);
        }
        $fileContent = file_get_contents($filePath);
        $data = iconv('UTF-8', 'CP1251', $fileContent);
        file_put_contents($filePath, $data);
        $zippy = Zippy::load();
        $zippy->create($fileNameTar, [
            $fileName => $filePath,
        ]);
        File::delete($filePath);
        //SendMail
        Mail::send('emails.export_leads', [], function ($message) use ($dateTime, $fileNameTar) {
            $message->from('no-reply@synergy.ru', 'Portal Robot');
            $message->to('TKHaritonova@synergy.ru')->subject('Экспорт лидов '.$dateTime->format('d.m.Y'));
            $message->attach($fileNameTar);
        });
    }
}
