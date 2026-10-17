<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'source',
        'table_id',
        'customer_name',
        'status',
        'payment_status',
        'is_open_bill',
        'open_bill_note',
        'subtotal',
        'discount_total',
        'total',
        'shift_id',
        'cashier_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => OrderSource::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'is_open_bill' => 'boolean',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function voids(): HasMany
    {
        return $this->hasMany(OrderVoid::class);
    }

    public function latestVoid(): HasOne
    {
        return $this->hasOne(OrderVoid::class)->latestOfMany();
    }

    public function void(): HasOne
    {
        return $this->hasOne(OrderVoid::class)->latestOfMany();
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function getTableLabelAttribute(): string
    {
        return $this->table?->label ?? 'Takeaway';
    }

    /**
     * Format total as Rupiah string.
     */
    public function formattedTotal(): string
    {
        return 'Rp' . number_format($this->total, 0, ',', '.');
    }

    public function formattedSubtotal(): string
    {
        return 'Rp' . number_format($this->subtotal, 0, ',', '.');
    }

    public function formattedDiscountTotal(): string
    {
        return 'Rp' . number_format($this->discount_total, 0, ',', '.');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isVoid(): bool
    {
        return $this->status === OrderStatus::Void;
    }

    public function itemsForStation(string|StationType $station)
    {
        $stationValue = $station instanceof StationType ? $station->value : $station;
        return $this->items->filter(fn ($item) => ($item->station instanceof StationType ? $item->station->value : $item->station) === $stationValue);
    }
}
