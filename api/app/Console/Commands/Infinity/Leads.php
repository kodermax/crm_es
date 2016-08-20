<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Infinity;

use App\Lib\Infinity\Client as InfinityClient;

class Leads extends Command
{
    protected $signature = 'infinity:leads';

    protected $description = 'Актуализация лидов Infinity и CRM';

    /**
     * Execute.
     */
    public function handle()
    {
        $client = new InfinityClient();
    }
}
