<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
    ];
}
