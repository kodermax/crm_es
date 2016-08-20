<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Es;

use App\Lib\Es\Client;
use Illuminate\Console\Command;

class ReIndexer extends Command
{
    protected $signature = 'es:reindex {aliasFrom} {aliasTo}';

    protected $description = 'Переиндексация  всех данных в Elasticsearch';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $aliasFrom = $this->argument('aliasFrom');
        $aliasTo = $this->argument('aliasTo');
        Client::reIndex($aliasFrom, $aliasTo);
    }
}
