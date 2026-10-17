<?php

namespace App\Models;

use App\Enums\MenuType;
use App\Enums\StationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'name',
        'type',
        'qty',
        'unit_price',
        'discount_amount',
        'discount_id',
        'note',
        'is_custom',
        'station',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuType::class,
            'qty' => 'integer',
            'unit_price' => 'integer',
            'discount_amount' => 'integer',
            'is_custom' => 'boolean',
            'station' => StationType::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get subtotal for this line item (before discount).
     */
    public function lineSubtotal(): int
    {
        return $this->unit_price * $this->qty;
    }

    /**
     * Get total for this line item (after discount).
     */
    public function lineTotal(): int
    {
        return max(0, $this->lineSubtotal() - ($this->discount_amount * $this->qty));
    }
}
