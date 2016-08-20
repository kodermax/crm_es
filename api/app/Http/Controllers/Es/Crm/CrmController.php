<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Es\Crm;

use Carbon\Carbon;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Lumen\Routing\Controller as BaseController;

class CrmController extends BaseController
{
    /**
 * Функция поиска контакта через ES, а также выполнение функций GET_ROW_COUNT и REBUILD_DUPLICATE_INDEX для контактов.
 *
 * @param Request $request
 */
    public function searchContacts(Request $request)
    {
        $mode = $request->input('MODE');
        $action = $request->input('ACTION');
        if ($mode === 'SEARCH') {
            $value = $request->input('VALUE');
            if (strlen($value) > 0) {
                $arPerms = [];
                $client = ClientBuilder::create()->build();
                @session_start();
                if (isset($_SESSION['SESS_AUTH']['USER_ID'])) {
                    $userId = $_SESSION['SESS_AUTH']['USER_ID'];
                } else {
                    $userId = 0;
                }
                //Получить права юзера
                if ($userId > 0) {
                    $cacheKey = 'crm_perms_user_'.$userId;
                    if (!Cache::has($cacheKey)) {
                        $params = [
                            'index' => 'portal',
                            'type' => 'perms',
                            'id' => $userId,
                        ];
                        try {
                            $response = $client->get($params);
                        } catch (\Exception $e) {
                        }
                        if (isset($response)) {
                            $arResult = $response['_source']['perms']['CONTACT'];
                            $arPerms = [];
                            foreach ($arResult[0] as $item) {
                                if (!empty($item)) {
                                    $arPerms[] = $item;
                                }
                            }
                        }
                        $expiresAt = Carbon::now()->addDay(1);
                        Cache::add($cacheKey, $arPerms, $expiresAt);
                    } else {
                        $arPerms = Cache::get($cacheKey, []);
                    }
                }
                $params = [
                    'index' => 'portal',
                    'type' => 'contact',
                    'size' => 50,
                ];
                if (!is_numeric($value)) {
                    $params['body'] = [
                        'query' => [
                            'filtered' => [
                                'query' => [
                                    'match' => [
                                        'full_name' => [
                                            'query' => $value,
                                            'operator' => 'and',
                                            'fuzziness' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ];
                } else {
                    $params['body'] = [
                        'query' => [
                            'filtered' => [
                                'query' => [
                                    'ids' => [
                                        'values' => [$value],
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
                if (!empty($arPerms)) {
                    $params['body']['query']['filtered']['filter'] = [
                        'terms' => [
                            'perms' => $arPerms,
                        ],
                    ];
                }
                try {
                    $results = $client->search($params);
                } catch (\Exception $e) {
                }

                $arResult = [];
                if (!empty($results)) {
                    foreach ($results['hits']['hits'] as $item) {
                        $arResult[] = [
                            'id' => isset($item['_id']) ? $item['_id'] : '',
                            'url' => isset($item['_source']['url']) ? $item['_source']['url'] : '',
                            'title' => isset($item['_source']['full_name']) ? $item['_source']['full_name'] : '',
                            'desc' => '',
                            'image' => '',
                            'perms' => isset($item['_source']['perms']) ? $item['_source']['perms'] : [],
                            'type' => 'contact',
                            'advancedInfo' => [
                                'contactType' => [
                                    'id' => isset($item['_source']['type_id']) ? $item['_source']['type_id'] : '',
                                    'name' => 'Общие контакты',
                                ],
                                'multiFields' => isset($item['_source']['multi_fields']) ? $item['_source']['multi_fields'] : [], ],
                        ];
                    }
                }

                return response($arResult, 200)->header('Content-Type', 'application/json');
            }
        } else {
            session_start();
            //region initialization
            $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../../..');
            define('NO_KEEP_STATISTIC', true);
            define('NOT_CHECK_PERMISSIONS', true);
            global $DBType;
            $DBType = 'mysql';
            require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
            if (!function_exists('__CrmContactListEndResonse')) {
                function __CrmContactListEndResonse($result)
                {
                    $GLOBALS['APPLICATION']->RestartBuffer();
                    Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    if (!empty($result)) {
                        echo \CUtil::PhpToJSObject($result);
                    }
                    require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';
                    die();
                }
            }

            if (!\CModule::IncludeModule('crm')) {
                __CrmContactListEndResonse(['ERROR' => 'Could not include crm module.']);
            }

            $userPerms = \CCrmPerms::GetCurrentUserPermissions();
            if (!\CCrmPerms::IsAuthorized()) {
                __CrmContactListEndResonse(['ERROR' => 'Access denied.']);
            }
            if (!\CCrmContact::CheckReadPermission(0, $userPerms)) {
                __CrmContactListEndResonse(['ERROR' => 'Access denied.']);
            }
            //endregion

            switch ($action) {
                case 'GET_ROW_COUNT':
                    $params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
                    $gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
                    if (!($gridID !== ''
                        && isset($_SESSION['CRM_GRID_DATA'])
                        && isset($_SESSION['CRM_GRID_DATA'][$gridID])
                        && is_array($_SESSION['CRM_GRID_DATA'][$gridID]))
                    ) {
                        __CrmContactListEndResonse(['DATA' => ['TEXT' => '']]);
                    }

                    $gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
                    $filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : [];
                    $result = \CCrmContact::GetListEx([], $filter, [], false, [], []);

                    $text = GetMessage('CRM_CONTACT_LIST_ROW_COUNT', ['#ROW_COUNT#' => $result]);
                    if ($text === '') {
                        $text = $result;
                    }
                    __CrmContactListEndResonse(['DATA' => ['TEXT' => $text]]);
                    break;
                case 'REBUILD_DUPLICATE_INDEX':
                    $params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : [];
                    $entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
                    if ($entityTypeName === '') {
                        __CrmContactListEndResonse(['ERROR' => 'Entity type is not specified.']);
                    }

                    $entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
                    if ($entityTypeID === \CCrmOwnerType::Undefined) {
                        __CrmContactListEndResonse(['ERROR' => 'Undefined entity type is specified.']);
                    }

                    if ($entityTypeID !== \CCrmOwnerType::Contact) {
                        __CrmContactListEndResonse(['ERROR' => "The '{$entityTypeName}' type is not supported in current context."]);
                    }

                    if (!\CCrmContact::CheckUpdatePermission(0)) {
                        __CrmContactListEndResonse(['ERROR' => 'Access denied.']);
                    }

                    if (\COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX', 'N') !== 'Y') {
                        __CrmContactListEndResonse(
                            [
                                'STATUS' => 'NOT_REQUIRED',
                                'SUMMARY' => GetMessage('CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY'),
                            ]
                        );
                    }

                    $progressData = \COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS', '');
                    $progressData = $progressData !== '' ? unserialize($progressData) : [];
                    $lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
                    $processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
                    $totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
                    if ($totalItemQty <= 0) {
                        $totalItemQty = \CCrmContact::GetListEx([], ['CHECK_PERMISSIONS' => 'N'], [], false);
                    }

                    $filter = ['CHECK_PERMISSIONS' => 'N'];
                    if ($lastItemID > 0) {
                        $filter['>ID'] = $lastItemID;
                    }

                    $dbResult = \CCrmContact::GetListEx(
                        ['ID' => 'ASC'],
                        $filter,
                        false,
                        ['nTopCount' => 20],
                        ['ID']
                    );

                    $itemIDs = [];
                    $itemQty = 0;
                    if (is_object($dbResult)) {
                        while ($fields = $dbResult->Fetch()) {
                            $itemIDs[] = intval($fields['ID']);
                            ++$itemQty;
                        }
                    }

                    if ($itemQty > 0) {
                        \CCrmContact::RebuildDuplicateIndex($itemIDs);

                        $progressData['TOTAL_ITEMS'] = $totalItemQty;
                        $processedItemQty += $itemQty;
                        $progressData['PROCESSED_ITEMS'] = $processedItemQty;
                        $progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

                        \COption::SetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS', serialize($progressData));
                        __CrmContactListEndResonse(
                            [
                                'STATUS' => 'PROGRESS',
                                'PROCESSED_ITEMS' => $processedItemQty,
                                'TOTAL_ITEMS' => $totalItemQty,
                                'SUMMARY' => GetMessage(
                                    'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
                                    [
                                        '#PROCESSED_ITEMS#' => $processedItemQty,
                                        '#TOTAL_ITEMS#' => $totalItemQty,
                                    ]
                                ),
                            ]
                        );
                    } else {
                        \COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX');
                        \COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS');
                        __CrmContactListEndResonse(
                            [
                                'STATUS' => 'COMPLETED',
                                'PROCESSED_ITEMS' => $processedItemQty,
                                'TOTAL_ITEMS' => $totalItemQty,
                                'SUMMARY' => GetMessage(
                                    'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
                                    ['#PROCESSED_ITEMS#' => $processedItemQty]
                                ),
                            ]
                        );
                    }
                    break;
            }
            //region include bitrix
            //endregion
        }
    }

/**
 * Поиск лида по телефону.
 *
 * @param Request $request
 *
 * @return mixed - json данные
 */
    public function searchByPhone(Request $request)
    {
        $arData = json_decode($request->getContent(), true);
        $q = $arData['phone'];
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => ['lead', 'contact'],
            'size' => 10,
        ];
        if (preg_match("/^[78]\d{10}/", $q)) {
            $phone = substr($q, 1);
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['7'.$phone]]];
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['8'.$phone]]];
        } else {
            $params['body']['query']['filtered']['query']['bool']['must']['terms']['phones'] = [$q];
        }
        $params['body']['query']['filtered']['filter']['bool']['must_not'] = [
            'term' => [
                '_id' => $arData['id'],
            ],
        ];

        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                return response($response['hits']['hits'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }

/**
 * Поиск лида по почте.
 *
 * @param Request $request
 *
 * @return mixed - json данные
 */
    public function searchByMail(Request $request)
    {
        $arData = json_decode($request->getContent(), true);
        $q = $arData['mail'];
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => ['lead', 'contact'],
            'size' => 10,
        ];
        $params['body']['query']['filtered']['query']['bool']['must']['terms']['emails'] = [$q];
        $params['body']['query']['filtered']['filter']['bool']['must_not'] = [
            'term' => [
                '_id' => $arData['id'],
            ],
        ];

        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                return response($response['hits']['hits'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }
}
