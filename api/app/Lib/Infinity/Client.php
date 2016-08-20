<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Lib\Infinity;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Client
{
    protected $cacheKey = 'infinity_tables';
    protected $connection = 'infinity';

    public function getTables()
    {
        if (!Cache::has($this->cacheKey)) {
            $rsTables = DB::connection($this->connection)->select('SHOW TABLES');
            foreach ($rsTables as $table) {
                $arTables[] = $table->Tables_in_infinity;
            }
            $expiresAt = Carbon::now()->addDay(1);
            Cache::add($this->cacheKey, $arTables, $expiresAt);
        } else {
            $arTables = Cache::get($this->cacheKey, []);
        }

        return $arTables;
    }

    public function deleteCache()
    {
        if (Cache::has($this->cacheKey)) {
            Cache::forget($this->cacheKey);
        }
    }

    public function insert($arData, $tableName)
    {
        $arTables = $this->getTables();
        if (array_search($tableName, $arTables) !== false) {
            DB::connection($this->connection)->table($tableName)->insert($arData);
        }
    }

    public function actualLeads()
    {
        $arTables = $this->getTables();
        foreach ($arTables as $table) {
            $rows = DB::connection($this->connection)->table($table)->select('id', 'lead_id', 'manager_login')->where('status', 0)->where('state', '7')->get();
            foreach ($rows as $row) {
                //TODO Change Responsible Lead in CRM
                DB::connection($this->connection)->table($table)->where('id', $row->id)->update(['status' => 1]);
            }
        }
    }
}
