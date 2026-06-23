<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paramètre boutique (clé/valeur). Les valeurs sensibles sont chiffrées
 * (colonne `chiffre`) avec la clé historique — voir App\Services\SettingsRepository.
 */
class Setting extends Model
{
    protected $table = 'settings';

    public $timestamps = true;
    const CREATED_AT = null; // table legacy : updated_at uniquement

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $casts = [
        'chiffre'    => 'boolean',
        'updated_at' => 'datetime',
    ];
}
