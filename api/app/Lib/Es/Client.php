<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Lib\Es;

//region include bitrix
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../..');
global $DBType;
$DBType = 'mysql';
@require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
//endregion

use App\Models\Es\Job as JobModel;
use Elasticsearch\ClientBuilder;
use Synergy\Crm\Contact;
use Synergy\Crm\Deal;
use Synergy\Crm\Lead;

class Client
{
    public static function addAlias($index, $alias)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => $index,
            'name' => $alias,
        ];
        $client->indices()->putAlias($params);
        echo 'done!';
    }

    public static function removeAlias($index, $alias)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => $index,
            'name' => $alias,
        ];
        $client->indices()->deleteAlias($params);
        echo 'done!';
    }

/**
 * Добавляет в индекс Es данные.
 *
 * @param $id - Ид записи в базе
 * @param $entityId - Id Сущности
 * @param $type - тип Es (таблица)
 * @param $arData - Данные сущности
 */
    private static function insert($entityId, $type, $arData)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => $type,
            'id' => $entityId,
            'body' => $arData,
        ];
        try {
            $client->index($params);
        } catch (\Exception $e) {
        }
    }

/**
 * Работа Jobbera.
 *
 * @param JobModel $job - задание
 */
    public static function index(JobModel $job)
    {
        $type = '';
        $arData = [];
        $bUpdate = $job->job_type === 'INSERT' || $job->job_type === 'UPDATE' ?: false;
        switch ($job->entity_type) {
            case 'LEAD':
                $type = 'lead';
                if ($bUpdate) {
                    $arData = Lead::getDataForEsById($job->entity_id);
                }
                break;
            case 'CONTACT':
                $type = 'contact';
                if ($bUpdate) {
                    $arData = Contact::getDataForEsById($job->entity_id);
                }
                break;
            case 'DEAL':
                $type = 'deal';
                if ($bUpdate) {
                    $arData = Deal::getDataForEsById($job->entity_id);
                }
                break;
            case 'COMPANY':
                $type = 'company';
                break;
        }
        switch ($job->job_type) {
            case 'INSERT':
                self::insert($job->entity_id, $type, $arData);
                break;
            case 'UPDATE':
                self::update($job->id, $job->entity_id, $type, $arData);
                break;
            case 'REMOVE':
                self::delete($job->id, $job->entity_id, $type);
                break;
        }
    }

/**
 * Удаление из индекса сущность CRM.
 *
 * @param $id - ид записи в базе
 * @param $entityId - Ид Сущности
 * @param $type - Тип сущности
 */
    private static function delete($id, $entityId, $type)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => $type,
            'id' => $entityId,
        ];
        try {
            $client->delete($params);
        } catch (\Exception $e) {
        }
    }

/**
 * Обновляем контент в ES.
 *
 * @param $id - Ид записи в базе
 * @param $entityId - Ид Сущности
 * @param $type - Тип Сущности
 * @param $arData - данные сущности
 */
    private static function update($id, $entityId, $type, $arData)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => $type,
            'id' => $entityId,
            'body' => [
                'doc' => $arData,
            ],
        ];
        try {
            $client->update($params);
        } catch (\Exception $e) {
        }
    }

/**
 * Функция индексирует всех лидов CRM.
 *
 * @param $index
 */
    public static function indexLeads($index)
    {
        $params = [];
        $client = ClientBuilder::create()->build();
        $arFilter = ['CHECK_PERMISSIONS' => 'N'];
        $result = \CCrmLead::GetListEx([], $arFilter, [], false, [], []);
        $pages = floor($result / 10000);
        $arFields = \CCrmLead::GetFields();
        $arSelect = array_keys($arFields);
        $arSelect[] = 'UF_*';
        for ($i = 0; $i <= $pages + 1; ++$i) {
            $list = \CCrmLead::GetListEx([], $arFilter, false, ['iNumPage' => $i, 'nPageSize' => 10000], $arSelect);
            while ($row = $list->GetNext()) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => 'lead',
                        '_id' => $row['ID'],
                    ],
                ];
                $params['body'][] = Lead::getDataForEsByArray($row);
            }
            unset($row);
            unset($list);
            if (count($params) > 0) {
                $response = $client->bulk($params);
                $params = [];
                unset($response);
            }
        }
    }

/**
 * Функция индексирует все контакты CRM.
 *
 * @param $index
 */
    public static function indexContacts($index)
    {
        $client = ClientBuilder::create()->build();
        $arFilter = ['CHECK_PERMISSIONS' => 'N'];
        $result = \CCrmContact::GetListEx([], $arFilter, [], false, [], []);
        $pages = floor($result / 10000);
        $arFields = \CCrmContact::GetFields();
        $arSelect = array_keys($arFields);
        $arSelect[] = 'UF_*';
        for ($i = 0; $i <= $pages + 1; ++$i) {
            $params = [];
            $list = \CCrmContact::GetListEx([], $arFilter, false, ['iNumPage' => $i, 'nPageSize' => 10000], $arSelect);
            while ($row = $list->GetNext()) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => 'contact',
                        '_id' => $row['ID'],
                    ],
                ];
                $params['body'][] = Contact::getDataForEsByArray($row);
            }
            if (count($params) > 0) {
                $client->bulk($params);
            }
        }
    }

/**
 * Индексация сделок.
 *
 * @param $index
 */
    public static function indexDeals($index)
    {
        $client = ClientBuilder::create()->build();
        $arFilter = ['CHECK_PERMISSIONS' => 'N'];
        $result = \CCrmDeal::GetListEx([], $arFilter, [], false, [], []);
        $pages = floor($result / 10000);
        $arFields = \CCrmDeal::GetFields();
        $arSelect = array_keys($arFields);
        $arSelect[] = 'UF_*';
        for ($i = 0; $i <= $pages + 1; ++$i) {
            $params = [];
            $list = \CCrmDeal::GetListEx([], $arFilter, false, ['iNumPage' => $i, 'nPageSize' => 10000], $arSelect);
            while ($row = $list->GetNext()) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_type' => 'deal',
                        '_id' => $row['ID'],
                    ],
                ];
                $params['body'][] = Deal::getDataForEsByArray($row);
            }
            if (count($params) > 0) {
                $client->bulk($params);
            }
        }
    }

/**
 * Индексация прав юзеров для crm сущностей.
 *
 * @param $index
 */
    public static function indexPerms($index)
    {
        $client = ClientBuilder::create()->build();
        $arTypes = ['LEAD', 'CONTACT', 'COMPANY', 'DEAL', 'INVOICE', 'QUOTE'];
        $list = \CUser::GetList($by, $order, ['ACTIVE' => 'Y'], ['FIELDS' => 'ID', 'NAME']);
        $params = [];
        while ($row = $list->GetNext()) {
            $arUserAttr = [];
            $perms = new \CCrmPerms($row['ID']);
            foreach ($arTypes as $type) {
                $arUserAttr[$type] = $perms->GetUserAttrForSelectEntity($type, 'READ');
            }
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_type' => 'perms',
                    '_id' => $row['ID'],
                ],
            ];
            $params['body'][] = ['perms' => $arUserAttr];
        }
        $client->bulk($params);
    }

    public static function reIndex($indexFrom, $indexTo)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'search_type' => 'scan',
            'scroll' => '1m',
            'size' => 50,
            'index' => $indexFrom,
            'body' => [
                'query' => [
                    'match_all' => [],
                ],
            ],
        ];
        $docs = $client->search($params);
        $scroll_id = $docs['_scroll_id'];
        while (\true) {
            $response = $client->scroll([
                    'scroll_id' => $scroll_id,
                    'scroll' => '1m',
                ]
            );
            if (count($response['hits']['hits']) > 0) {
                $scroll_id = $response['_scroll_id'];
                $paramsTo = [];
                foreach ($response['hits']['hits'] as $hit) {
                    $paramsTo['body'][] = [
                        'index' => [
                            '_index' => $indexTo,
                            '_type' => $hit['_type'],
                            '_id' => $hit['_id'],
                        ],
                    ];
                    $paramsTo['body'][] = $hit['_source'];
                }
                $client->bulk($paramsTo);
                unset($paramsTo);
            } else {
                break;
            }
        }
    }
}
