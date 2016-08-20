<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Pap;

use App\Jobs\Pap\Job;
use App\Models\Pap\Job as JobModel;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class JobController extends BaseController
{
    public function insert(Request $request)
    {
        $jobModel = new JobModel();
        $jobModel->cost = $request->input('cost', 0);
        $jobModel->dateCreate = $request->input('dateCreate', date('d.m.Y H:i:s'));
        $jobModel->educationLevel = $request->input('educationLevel', '');
        $jobModel->entityId = $request->input('entityId');
        $jobModel->entityType = $request->input('entityType');
        $jobModel->ip = $request->input('ip', '');
        $jobModel->jobType = $request->input('jobType');
        $jobModel->sourceCode = $request->input('sourceCode', '');
        $jobModel->status = $request->input('status');
        $jobModel->title = $request->input('title', '');
        $jobModel->dealId = $request->input('dealId', '');
        $jobModel->visitorId = $request->input('visitorId', '');
        $task = (new Job($jobModel))->onQueue('pap_jobs');
        $this->dispatch($task);

        return response(['code' => 201, 'status' => 'success'], 201)->header('Content-Type', 'application/json');
    }
}
