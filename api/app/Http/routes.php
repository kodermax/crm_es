<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

$app->group(['prefix' => 'crm', 'middleware' => ['JsonApi'], 'namespace' => 'App\Http\Controllers\Crm'], function ($app) {
    $app->post('leads', 'LeadController@create');
});
$app->group(['prefix' => 'users', 'middleware' => ['JsonApi'], 'namespace' => 'App\Http\Controllers'], function ($app) {
    $app->get('{userId}/getExtension', 'UsersController@getPhoneExtension');
});
$app->group(['prefix' => 'es/crm', 'namespace' => 'App\Http\Controllers\Es\Crm'], function ($app) {
    $app->post('searchByPhone', 'CrmController@searchByPhone');
    $app->post('searchByMail', 'CrmController@searchByMail');
    $app->get('lead/{id}', 'LeadController@get');
    $app->get('contact/phone/{phone}', 'ContactController@searchByPhone');
    $app->get('contact/mail/{mail}', 'ContactController@searchByMail');
    $app->post('contacts', 'CrmController@searchContacts');
    $app->post('lead/searchDuplicate', 'LeadController@searchDuplicate');
    $app->post('lead/searchRepeat', 'LeadController@searchRepeat');
});

$app->group(['prefix' => 'es', 'middleware' => ['JsonApi'], 'namespace' => 'App\Http\Controllers\Es'], function ($app) {
    $app->post('jobs', 'JobController@insert');
});
$app->group(['prefix' => 'pap', 'middleware' => ['JsonApi'], 'namespace' => 'App\Http\Controllers\Pap'], function ($app) {
    $app->post('jobs', 'JobController@insert');
});
$app->group(['prefix' => 'infinity', 'middleware' => ['JsonApi'], 'namespace' => 'App\Http\Controllers\Infinity'], function ($app) {
    $app->post('leads', 'LeadController@insert');
    $app->post('call', 'CallController@call');
});

$app->get('/', function () use ($app) {
    return $app->welcome();
});
