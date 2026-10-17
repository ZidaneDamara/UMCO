<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'slot',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function slotLabel(): string
    {
        return match ($this->slot) {
            'shift1' => 'Shift 1 (08.00–15.00)',
            'shift2' => 'Shift 2 (15.00–22.00)',
            'kitchen' => 'Kitchen (09.00–17.00)',
            default => ucfirst($this->slot),
        };
    }
}
