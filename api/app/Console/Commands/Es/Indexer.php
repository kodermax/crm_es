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

class Indexer extends Command
{
    protected $signature = 'es:indexer {type} {index}';

    protected $description = 'Индексация  всех данных из CRM в Elasticsearch';

/**
 * Выполнение команды.
 */
    public function handle()
    {
        $entityType = $this->argument('type');
        $index = $this->argument('index');
        switch (strtoupper($entityType)) {
            case 'LEAD':
                Client::indexLeads($index);
                break;
            case 'CONTACT':
                Client::indexContacts($index);
                break;
            case 'DEAL':
                Client::indexDeals($index);
                break;
            case 'COMPANY':
                break;
            case 'PERMS':
                Client::indexPerms($index);
                break;
        }
    }
}
