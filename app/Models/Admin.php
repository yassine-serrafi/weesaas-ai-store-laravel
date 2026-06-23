<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Compte administrateur du panneau /weeadmin.
 *
 * Le mot de passe est stocké dans `password_hash` (bcrypt), pas `password` :
 * getAuthPassword() est surchargé pour rester compatible avec Hash::check
 * et le guard custom (voir App\Services\AdminAuth).
 */
class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $guarded = ['id'];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'actif'      => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function sessions()
    {
        return $this->hasMany(AdminSession::class, 'admin_id');
    }
}
