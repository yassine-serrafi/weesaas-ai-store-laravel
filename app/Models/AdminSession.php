<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSession extends Model
{
    protected $table = 'admin_sessions';

    public $timestamps = false; // gère created_at / last_activity manuellement

    protected $guarded = ['id'];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at'    => 'datetime',
        'created_at'    => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
