<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'status',
        'photo_in_path',
        'photo_out_path',
        'lat',
        'lng',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function isCheckedIn(): bool
    {
        return ! is_null($this->check_in_at);
    }

    public function isCheckedOut(): bool
    {
        return ! is_null($this->check_out_at);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'late' => 'bg-amber-100 text-amber-800 border-amber-200',
            'absent' => 'bg-rose-100 text-rose-800 border-rose-200',
            'leave' => 'bg-blue-100 text-blue-800 border-blue-200',
            default => 'bg-neutral-100 text-neutral-800 border-neutral-200',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Hadir Tepat Waktu',
            'late' => 'Terlambat',
            'absent' => 'Tidak Hadir',
            'leave' => 'Izin / Cuti',
            default => ucfirst($this->status),
        };
    }
}
