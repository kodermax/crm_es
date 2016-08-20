<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Http\Controllers\Es;

use App\Jobs\Es\Job;
use App\Models\Es\Job as JobModel;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Validator;

class JobController extends BaseController
{
    /**
 * Функция добавляет задание jobber Es.
 *
 * @param Request $request - запрос
 *
 * @return mixed
 */
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_id' => 'required',
            'entity_type' => 'required',
            'job_type' => 'required',
        ]);
        if (!$validator->fails()) {
            $job = new JobModel();
            $job->entity_id = $request->input('entity_id');
            $job->entity_type = $request->input('entity_type');
            $job->job_type = $request->input('job_type');
            $job->status = 0;
            $task = (new Job($job))->onQueue('es_jobs');
            $this->dispatch($task);

            return response(['code' => 201, 'status' => 'success'], 201)->header('Content-Type', 'application/json');
        } else {
            return response(['message' => 'Validation Failed', 'errors' => $validator->errors()], 200)->header('Content-Type', 'application/json');
        }
    }
}
