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

#region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
#endregion
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Merger;

class Duplicate extends Command
{
    protected $signature = 'crm:duplicate {entityType}';

    protected $description = 'Объединение дубликатов в crm';

    public function handle()
    {
        \Bitrix\Main\loader::includeModule('crm');
        $_SESSION['SESS_AUTH']['USER_ID'] = 1;
        $allCount = 0;
        $f = fopen('/home/bitrix/www/merge.html', 'w+');
        fwrite($f, '<style>table{ border-collapse: collapse;} td, th {border:1px solid gray}</style>');
        //Список дубликатов
        $entityTypeName = $this->argument('entityType');
        $entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
        $layoutID = $entityTypeID;
        $merger = Merger\EntityMerger::create($entityTypeID, 1, false);
        $list = new Integrity\DuplicateList(
            Integrity\DuplicateIndexType::joinType([
                Integrity\DuplicateIndexType::COMMUNICATION,
            ]),
            $entityTypeID,
            1,
            false
        );
        $list->setSortTypeID(2);
        $list->setSortOrder(4);
        $items = $list->getRootItems();

        foreach ($items as $item) {
            $entityID = $item->getRootEntityID();
            if (!isset($entityInfos[$entityID])) {
                $entityInfos[$entityID] = [];
            }
        }
        $entityInfoOptions = [
            'ENABLE_EDIT_URL' => false,
            'ENABLE_RESPONSIBLE' => false,
            'ENABLE_RESPONSIBLE_PHOTO' => false,
        ];
        if ($entityTypeID === \CCrmOwnerType::Lead) {
            $entityInfoOptions[$layoutID === \CCrmOwnerType::Company ? 'TREAT_AS_COMPANY' : 'TREAT_AS_CONTACT'] = true;
        }

        if (!empty($entityInfos)) {
            \CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $entityInfos, false, $entityInfoOptions);
            \CCrmFieldMulti::PrepareEntityInfoBatch('PHONE', $entityTypeName, $entityInfos, ['ENABLE_NORMALIZATION' => true]);
            \CCrmFieldMulti::PrepareEntityInfoBatch('EMAIL', $entityTypeName, $entityInfos);
        }
        switch ($entityTypeName) {
            case 'lead':
                $crm = new \CCrmLead(false);
                break;
            case 'contact':
                $crm = new \CCrmContact(false);
                break;
            default:
                $crm = new \CCrmContact(false);
        }

        foreach ($entityInfos as $key => $entityInfo) {
            if ($entityInfo['PHONE']['FIRST_VALUE']) {
                $arCriterionMatches[$key]['COMMUNICATION_PHONE'] = ['TYPE' => 'PHONE', 'VALUE' => $entityInfo['PHONE']['FIRST_VALUE']];
            }
            if ($entityInfo['EMAIL']['FIRST_VALUE']) {
                $arCriterionMatches[$key]['COMMUNICATION_EMAIL'] = ['TYPE' => 'EMAIL', 'VALUE' => $entityInfo['EMAIL']['FIRST_VALUE']];
            }
            $list = $crm->GetList([], ['ID' => $key], ['NAME', 'LAST_NAME', 'SECOND_NAME']);
            if ($row = $list->GetNext()) {
                $arCriterionMatches[$key]['PERSON'] = [
                    'LAST_NAME' => $row['LAST_NAME'],
                    'SECOND_NAME' => $row['SECOND_NAME'],
                    'NAME' => $row['NAME'],
                ];
            }
        }
        //Получаем список совпадений для каждого дубликата
        foreach ($entityInfos as $key => $entityInfo) {
            $rootEntityID = $key;
            $arDuplicates = [];
            //Поиск по 3 критериям

            #region phone
            if (isset($arCriterionMatches[$key]['COMMUNICATION_PHONE'])) {
                $typeID = Integrity\DuplicateIndexType::resolveID('COMMUNICATION_PHONE');
                $matches = $arCriterionMatches[$key]['COMMUNICATION_PHONE'];
                $criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);
                $dup = $criterion->createDuplicate($entityTypeID, $rootEntityID, 1, false, false, 0);
                if ($dup) {
                    $entities = $dup->getEntitiesByType($entityTypeID);
                    foreach ($entities as $entity) {
                        $entityId = $entity->getEntityID();
                        $arDuplicates[$entityId]['ALL']++;
                        $arDuplicates[$entityId]['PHONE'] = true;
                    }
                }
            }
            #endregion
            #region email
            if (isset($arCriterionMatches[$key]['COMMUNICATION_EMAIL'])) {
                $typeID = Integrity\DuplicateIndexType::resolveID('COMMUNICATION_EMAIL');
                $matches = $arCriterionMatches[$key]['COMMUNICATION_EMAIL'];
                $criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);
                $dup = $criterion->createDuplicate($entityTypeID, $rootEntityID, 1, false, false, 0);
                if ($dup) {
                    $entities = $dup->getEntitiesByType($entityTypeID);
                    foreach ($entities as $entity) {
                        $entityId = $entity->getEntityID();
                        $arDuplicates[$entityId]['ALL']++;
                        $arDuplicates[$entityId]['EMAIL'] = true;
                    }
                }
            }
            #endregion
            #region fio
            if (isset($arCriterionMatches[$key]['PERSON'])) {
                if (!empty($arCriterionMatches[$key]['PERSON']['LAST_NAME']) && !empty($arCriterionMatches[$key]['PERSON']['NAME'])) {
                    $typeID = Integrity\DuplicateIndexType::resolveID('PERSON');
                    $matches = $arCriterionMatches[$key]['PERSON'];
                    $criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);
                    $dup = $criterion->createDuplicate($entityTypeID, $rootEntityID, 1, false, false, 0);
                    if ($dup) {
                        $entities = $dup->getEntitiesByType($entityTypeID);
                        foreach ($entities as $entity) {
                            $entityId = $entity->getEntityID();
                            $arDuplicates[$entityId]['ALL']++;
                            $arDuplicates[$entityId]['PERSON'] = true;
                        }
                    }
                }
            }
            #endregion
            $bAllMatches = false;
            foreach ($arDuplicates as $dupId => $item) {
                if ($item['ALL'] == 3) {
                    $bAllMatches = true;
                    break;
                }
            }
            if ($bAllMatches) {
                fwrite($f, '===========================================================<br/>');
                fwrite($f, 'Главный элемент: <b>'.$entityInfo['TITLE'].'</b><br/>');
                fwrite($f, 'ID: <b>'.$key.'</b><br/>');
                fwrite($f, 'Phone: <b>'.$entityInfo['PHONE']['FIRST_VALUE'].'</b><br/>');
                fwrite($f, 'Email: <b>'.$entityInfo['EMAIL']['FIRST_VALUE'].'</b><br/>');
                fwrite($f, '<br>Таблица соответствий:<br/>');
                fwrite($f, '<table><thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th></tr></thead>');
                fwrite($f, '<tbody>');
                foreach ($arDuplicates as $dupId => $item) {
                    if ($item['ALL'] == 3) {
                        $duplicateInfo = [];
                        $duplicateInfo[$dupId] = [];
                        \CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $duplicateInfo, false, []);
                        \CCrmFieldMulti::PrepareEntityInfoBatch('PHONE', $entityTypeName, $duplicateInfo, ['ENABLE_NORMALIZATION' => true]);
                        \CCrmFieldMulti::PrepareEntityInfoBatch('EMAIL', $entityTypeName, $duplicateInfo);
                        try {
                            $merger->merge($dupId, $key, $criterion);
                        } catch (Merger\EntityMergerException $e) {
                        }
                        $allCount++;
                        fwrite($f, '<tr>');
                        fwrite($f, '<td>'.$dupId.'</td>');
                        fwrite($f, '<td>'.$duplicateInfo[$dupId]['TITLE'].'</td>');
                        fwrite($f, '<td>'.$duplicateInfo[$dupId]['PHONE']['FIRST_VALUE'].'</td>');
                        fwrite($f, '<td>'.$duplicateInfo[$dupId]['EMAIL']['FIRST_VALUE'].'</td>');
                    }
                }
                fwrite($f, '</tbody>');
                fwrite($f, '</table>');
                fwrite($f, '===========================================================');
            }
        }
        fwrite($f, '<br/>Всего объединено: <b>'.$allCount.'</b><br/>');
        fclose($f);
    }
}
