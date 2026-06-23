<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Notification métier admin (legacy). Distincte des notifications Eloquent natives.
 */
class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'lu'         => 'boolean',
        'created_at' => 'datetime',
    ];
}
