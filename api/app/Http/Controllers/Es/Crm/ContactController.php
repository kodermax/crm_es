<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Es\Crm;

use Elasticsearch\ClientBuilder;
use Laravel\Lumen\Routing\Controller as BaseController;

class ContactController extends BaseController
{
    /**
     * Поиск контактов по телефону.
     * @param $phone - телефон
     * @return mixed - json данные
     */
    public function searchByPhone($phone)
    {
        $q = $phone;
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => 'contact',
            'size' => 10,
        ];
        if (preg_match("/^[78]\d{10}/", $q)) {
            $phone = substr($q, 1);
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['7' . $phone]]];
            $params['body']['query']['filtered']['query']['bool']['should'][] = ['terms' => ['phones' => ['8' . $phone]]];
        } else {
            $params['body']['query']['filtered']['query']['bool']['must']['terms']['phones'] = [$q];
        }
        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                $response['hits']['hits'][0]['_source']['_id'] = $response['hits']['hits'][0]['_id'];

                return response($response['hits']['hits'][0]['_source'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }

    /**
     * Поиск контакта по email.
     * @param $mail - Email
     * @return mixed - json данные
     */
    public function searchByMail($mail)
    {
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'portal',
            'type' => 'contact',
            'size' => 10,
        ];
        $params['body']['query']['filtered']['query']['bool']['must']['terms']['emails'] = [$mail];
        try {
            $response = $client->search($params);
            if (!empty($response['hits']['hits'])) {
                $response['hits']['hits'][0]['_source']['_id'] = $response['hits']['hits'][0]['_id'];

                return response($response['hits']['hits'][0]['_source'], 200)->header('Content-Type', 'application/json');
            } else {
                return response('', 404)->header('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
        }

        return response('', 404)->header('Content-Type', 'application/json');
    }
}
