<?php

namespace App\Models;

use App\Enums\MenuType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'type',
        'price',
        'image_path',
        'is_available',
        'partner_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuType::class,
            'price' => 'integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Format price as Rupiah string.
     */
    public function formattedPrice(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }
}
