<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Jobs\Es;

use App\Jobs\Crm\LeadAllocation;
use App\Lib\Es\Client as EsClient;
use App\Models\Es\Job as JobModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Lumen\Routing\DispatchesJobs;

class Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, DispatchesJobs;

    protected $task;

    public function __construct(JobModel $task)
    {
        $this->task = $task;
    }

/**
 * Запуск задачи.
 */
    public function handle()
    {
        EsClient::index($this->task);
        if ($this->task->job_type === 'INSERT' && $this->task->entity_type === 'LEAD') {
            $task = (new LeadAllocation($this->task->entity_id))->onQueue('lead_allocation');
            $this->dispatch($task);
        }
    }
}
