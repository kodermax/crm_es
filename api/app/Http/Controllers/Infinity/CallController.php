<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Infinity;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class CallController extends BaseController
{
    public function call(Request $request)
    {
        $arData = $request->all();
        $extension = $arData['extension'];
        $phone = $arData['phone'];
        $firstLetter = (int) $phone[0];
        $twoLetter = (int) substr($phone, 0, 2);
        //Казахстан
        if ($twoLetter === 77 || $twoLetter === 76) {
            $phone = '810'.$phone;
        } elseif ($firstLetter === 7 || $firstLetter === 8) {
            $phone[0] = 8;
        } else {
            $phone = '810'.$phone;
        }
        $url = "http://pbx1.synergy.local:10080/call/make/?Extension=$extension&Number=$phone";

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);

            return response()->json(['code' => '200', 'result' => 'success'], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => '400', 'message' => 'Validation failed', 'error' => 'Ошибка infinity: '.$e->getMessage()], 200);
        }
    }
}
