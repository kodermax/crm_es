<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Es\Crm;

use Bitrix\Main\DB\Exception;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class LeadController extends BaseController
{
    /**
 * Получить лид из Elastic.
 *
 * @param $id - ид лида
 *
 * @return mixed - json данные
 */
    public function get($id)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => 'lead',
            'id' => $id,
        ];

        try {
            $response = $client->get($params);

            return response($response['_source'], 200)->header('Content-Type', 'application/json');
        } catch (Missing404Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }

/**
 * Поиск дубликата.
 *
 * @param Request $request
 *
 * @return mixed - json данные
 */
    public function searchDuplicate(Request $request)
    {
        $arData = json_decode($request->getContent(), true);
        if (empty($arData['id']) || empty($arData['phone']) || empty($arData['landCode'])) {
            return response('', 400)->header('Content-Type', 'application/json');
        }
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => 'lead',
            'size' => 10,
        ];
        if (preg_match("/^[78]\d{10}/", $arData['phone'])) {
            $phone = substr($arData['phone'], 1);
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['7'.$phone]]];
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['8'.$phone]]];
        } else {
            $params['body']['query']['filtered']['query']['bool']['must']['terms']['phones'] = [$arData['phone']];
        }
        $params['body']['query']['filtered']['filter']['bool']['must_not'] = [
            'term' => [
                '_id' => $arData['id'],
            ],
        ];
        $params['body']['query']['filtered']['filter']['bool']['must'] = [
            'term' => [
                'landCode' => $arData['landCode'],
            ],
        ];
        $params['body']['query']['filtered']['filter']['bool']['must'] = [
            'range' => [
                'date_create' => [
                    'gte' => 'now',
                    'format' => 'dd.MM.yyyy HH:mm:ss',
                ],
            ],
        ];
        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                return response($response['hits']['hits'][0]['_source'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }

/**
 * Поиск повторных заявок.
 *
 * @param Request $request - POST данные
 *
 * @return mixed - json данные
 */
    public function searchRepeat(Request $request)
    {
        $arData = json_decode($request->getContent(), true);
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => 'lead',
            'size' => 10,
        ];
        if (preg_match("/^[78]\d{10}/", $arData['phone'])) {
            $phone = substr($arData['phone'], 1);
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['7'.$phone]]];
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['8'.$phone]]];
        } else {
            $params['body']['query']['filtered']['query']['bool']['must']['terms']['phones'] = [$arData['phone']];
        }
        $params['body']['query']['filtered']['filter']['bool']['must_not'] = [
            'term' => [
                '_id' => $arData['id'],
            ],
        ];
        $params['body']['query']['filtered']['filter']['bool']['must'] = [
            'range' => [
                'date_create' => [
                    'gte' => 'now',
                    'format' => 'dd.MM.yyyy HH:mm:ss',
                ],
            ],
        ];
        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                return response($response['hits']['hits'][0]['_source'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }
}
