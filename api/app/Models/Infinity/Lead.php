<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Models\Infinity;

use Illuminate\Database\Eloquent\Model;

/**
 * @property  land_code
 * @property  lead_id
 * @property  phone
 * @property  title
 * @property state
 */
class Lead extends Model
{
    protected $connection = 'infinity';
    protected $table = 'leads';
    public $timestamps = false;
}
