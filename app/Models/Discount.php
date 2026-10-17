<?php

namespace App\Models;

use App\Enums\DiscountKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = [
        'name',
        'kind',
        'value',
        'applies_to',
        'conditions',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => DiscountKind::class,
            'value' => 'integer',
            'conditions' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if discount is currently valid (active and within date range).
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        // Check days of week condition (1 = Monday, 7 = Sunday)
        if (! empty($this->conditions['days_of_week'])) {
            if (! in_array($now->dayOfWeekIso, $this->conditions['days_of_week'])) {
                return false;
            }
        }

        return true;
    }
}
