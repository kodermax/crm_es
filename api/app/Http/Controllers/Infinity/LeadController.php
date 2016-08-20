<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Infinity;

use App\Lib\Infinity\Client as InfinityClient;
use App\Models\Infinity\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;

class LeadController extends BaseController
{
    public function insert(Request $request)
    {
        $arData = $request->all();
        $landCode = trim($arData['landCode']);
        $client = new InfinityClient();
        try {
            if (!empty($landCode)) {
                $arInsert = [
                    'title' => $arData['title'],
                    'lead_id' => $arData['leadId'],
                    'land_code' => $landCode,
                    'phone' => $arData['phone'],
                ];
                $client->insert($arInsert, $arData['tableName']);
            } else {
                return response()->json(['code' => '400', 'message' => 'Validation failed', 'error' => 'Ошибка при добавлении лида в базу infinity: Таблицы не найдено'], 200);
            }

            return response()->json(['code' => '201', 'result' => 'success'], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => '400', 'message' => 'Validation failed', 'error' => 'Ошибка при добавлении лида в базу infinity: ' . $e->getMessage()], 200);
        }
    }
}
