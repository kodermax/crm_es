<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
 * The Artisan commands provided by your application.
 *
 * @var array
 */
    protected $commands = [
        'App\Console\Commands\Es\Indexer',
        'App\Console\Commands\Crm\LeadsAllocation',
        'App\Console\Commands\Crm\Activity',
        'App\Console\Commands\Crm\Leads',
        'App\Console\Commands\Crm\Deals',
        'App\Console\Commands\Crm\Contacts',
        'App\Console\Commands\Crm\Events',
        'App\Console\Commands\Crm\Quotes',
        'App\Console\Commands\Crm\Invoices',
        'App\Console\Commands\Crm\Company',
        'App\Console\Commands\Crm\Duplicate',
        'App\Console\Commands\Crm\Phone',
        'App\Console\Commands\Es\ReIndexer',
        'App\Console\Commands\Es\Migrate',
        'App\Console\Commands\Es\Aliases',
        'App\Console\Commands\Bitrix\Mail',
        'App\Console\Commands\Bitrix\User',
        //
    ];

/**
 * Define the application's command schedule.
 *
 * @param \Illuminate\Console\Scheduling\Schedule $schedule
 */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('es:indexer portal perms')->daily();
        $schedule->command('crm:leads queue_clear')->daily();
    }
}
