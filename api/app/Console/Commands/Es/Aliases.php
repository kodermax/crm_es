<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace app\Console\Commands\Es;

use App\Lib\Es\Client;
use Illuminate\Console\Command;

class Aliases extends Command
{
    protected $signature = 'es:aliases {action} {index} {alias}';

    protected $description = 'Creating or removing alias Elasticsearch';

    public function handle()
    {
        $action = $this->argument('action');
        $index = $this->argument('index');
        $alias = $this->argument('alias');
        switch ($action) {
            case 'add':
                Client::addAlias($index, $alias);
                break;
            case 'remove':
                Client::removeAlias($index, $alias);
                break;
        }
    }
}
