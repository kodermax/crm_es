<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Queue;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Queue::after(function ($connection, $job, $data) {

        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
