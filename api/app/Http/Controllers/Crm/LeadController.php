<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Crm;

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

use Synergy\Crm\Lead;

class LeadController extends BaseController
{
    /**
     * Контроллер на создание лида.
     * @param Request $request - post данные в виде JSON
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request)
    {
        $arData = $request->all();
        $response = Lead::createLead($arData);
        if (is_int($response)) {
            return response()->json(['id' => $response, 'code' => '201', 'result' => 'success'], 201);
        } else {
            return response()->json(['code' => '200', 'message' => 'Validation failed', 'error' => 'Ошибка при создании лида: ' . $response], 200);
        }
    }
}
