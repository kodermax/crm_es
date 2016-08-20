<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Jobs\Pap;

use App\Models\Pap\Job as JobModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../..');
include_once $_SERVER['DOCUMENT_ROOT'].'/local/lib/pap/PapApi.class.php';

class Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
        switch ($this->task->entityType) {
            case 'LEAD':
                if ($this->task->jobType === 'INSERT') {
                    try {
                        $saleTracker = new \Pap_Api_SaleTracker('http://af.synergy.ru/scripts/sale.php', true);
                        $saleTracker->setAccountId('default1');
                        $saleTracker->setVisitorId($this->task->visitorId);
                        $sale = $saleTracker->createAction('signup');
                        $sale->setStatus('P');
                        $sale->setData1($this->task->entityId);
                        $sale->setData2($this->task->dateCreate);
                        $sale->setData3($this->task->status);
                        $sale->setData4($this->task->sourceCode);
                        $sale->setData5($this->task->educationLevel);
                        $_SERVER['HTTP_X_FORWARDED_FOR'] = $this->task->ip;
                        $_SERVER['REMOTE_ADDR'] = $this->task->ip;
                        $saleTracker->register();
                    } catch (\Exception $e) {
                    }
                } elseif ($this->task->jobType === 'UPDATE') {
                    $session = new \Gpf_Api_Session('http://af.synergy.ru/scripts/server.php');
                    if (!@$session->login('bitrix@synergy.ru', 'Synergy_2016')) {
                        die('Cannot login. Message: '.$session->getMessage());
                    }
                    $request = new \Pap_Api_TransactionsGrid($session);
                    $request->addFilter('data1', \Gpf_Data_Filter::EQUALS, $this->task->entityId);
                    $request->sendNow();
                    $grid = $request->getGrid();
                    $recordset = $grid->getRecordset();
                    foreach ($recordset as $rec) {
                        $transId = $rec->get('transid');
                        break;
                    }
                    if (!empty($transId)) {
                        $sale = new \Pap_Api_Transaction($session);
                        $sale->setTransId($transId);
                        if (!($sale->load())) { //loads the record with the given transaction id.
                            die('Loading of transaction failed!'.$sale->getMessage());
                        }
                        $sale->setData(3, $this->task->status);
                        $sale->save();
                    }
                }
                break;
            case
                'DEAL':
                if ($this->task->jobType === 'INSERT') {
                    $saleTracker = new \Pap_Api_SaleTracker('http://af.synergy.ru/scripts/sale.php', true);
                    $saleTracker->setAccountId('default1');
                    $sale = $saleTracker->createSale();
                    $sale->setTotalCost($this->task->cost);
                    $sale->setOrderID($this->task->dealId);
                    $saleTracker->register();
                    $saleTracker->setVisitorId($this->task->visitorId);
                } elseif ($this->task->job_type === 'UPDATE') {
                    $session = new \Gpf_Api_Session('http://af.synergy.ru/scripts/server.php');
                    if (!@$session->login('mkarpychev@synergy.ru', 'qwertymax')) {
                        die('Cannot login. Message: '.$session->getMessage());
                    }
                    $request = new \Pap_Api_TransactionsGrid($session);
                    $request->addFilter('orderid', \Gpf_Data_Filter::EQUALS, $this->task->dealId);
                    $request->sendNow();
                    $grid = $request->getGrid();
                    $recordset = $grid->getRecordset();
                    foreach ($recordset as $rec) {
                        $transId = $rec->get('transid');
                        break;
                    }
                    $sale = new \Pap_Api_Transaction($session);
                    $sale->setTransId($transId);
                    if (!($sale->load())) { //loads the record with the given transaction id.
                        die('Loading of transaction failed!'.$sale->getMessage());
                    }
                    $sale->setStatus($this->task->status);
                    $sale->save();
                }
                break;
        }
    }
}
