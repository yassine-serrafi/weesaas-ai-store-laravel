<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'statut'     => 'boolean',
        'ordre'      => 'integer',
        'created_at' => 'datetime',
    ];

    /** Libellé dans la langue courante du front (fr|ar|en). */
    public function labelFor(string $code): string
    {
        $key = str_starts_with($code, 'ar') ? 'label_ar' : ($code === 'en' ? 'label_en' : 'label_fr');
        return (string) ($this->{$key} ?? $this->label_fr);
    }

    public function scopeVisible($query, string $position)
    {
        return $query->where('statut', 1)->where('position', $position)->orderBy('ordre');
    }
}
