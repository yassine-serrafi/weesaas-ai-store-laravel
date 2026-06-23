<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cache de géolocalisation IP (PK = ip, pas d'auto-increment).
 */
class IpCache extends Model
{
    protected $table = 'ip_cache';

    protected $primaryKey = 'ip';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
