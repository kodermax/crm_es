<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers;

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
global $DBType;
$DBType = 'mysql';
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use Laravel\Lumen\Routing\Controller as BaseController;

class UsersController extends BaseController
{
    public function getPhoneExtension($userId)
    {
        $arCompanies = [
            'Школа бизнеса Синергия',
            'ШБ "Синергия"',
            'Частный репетитор',
            'ООО Компания "Частный репетитор"',
            'ООО "Школа Бизнеса Синергия"',
        ];
        $inner = '';
        $list = \CUser::GetList($by, $order, ['ID' => $userId], ['SELECT' => ['UF_PHONE_INNER'], 'FIELDS' => ['ID', 'WORK_COMPANY']]);
        if ($row = $list->GetNext()) {
            $inner = $row['UF_PHONE_INNER'];
            if (in_array($row['~WORK_COMPANY'], $arCompanies, true)) {
                $inner = '';
            }
        }

        return response()->json(['result' => $inner], 200);
    }
}
