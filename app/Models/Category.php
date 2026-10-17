<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
        'sort_order',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function availableMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->where('is_available', true)
            ->where('is_active', true);
    }
}
