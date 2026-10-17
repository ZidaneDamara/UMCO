<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderVoid;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

class VerifyManagerPinAndVoid
{
    public function __construct(
        protected ApproveVoid $approveVoid,
    ) {}

    /**
     * Verify manager PIN with rate limiting (5 attempts -> 5 min lock) and execute void.
     */
    public function execute(
        Order $order,
        User $requester,
        string $pin,
        string $reason,
        ?string $reasonDetail = null,
        ?int $managerId = null,
    ): OrderVoid {
        $throttleKey = 'void_pin_attempts|' . Request::ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            throw new InvalidArgumentException("Terlalu banyak percobaan PIN salah. Sistem terkunci selama {$minutes} menit untuk keamanan.");
        }

        // Find candidate managers
        $query = User::permission('orders.void.approve')->where('is_active', true);
        if ($managerId) {
            $query->where('id', $managerId);
        }

        $managers = $query->get();
        $matchedManager = null;

        foreach ($managers as $manager) {
            if ($manager->pin_hash && Hash::check($pin, $manager->pin_hash)) {
                $matchedManager = $manager;
                break;
            }
        }

        if (! $matchedManager) {
            RateLimiter::hit($throttleKey, 300); // lock 5 minutes on reaching 5 hits
            $remaining = RateLimiter::remaining($throttleKey, 5);
            throw new InvalidArgumentException("PIN Manajer tidak valid. Sisa percobaan: {$remaining}.");
        }

        // PIN is valid: clear limiter and execute void
        RateLimiter::clear($throttleKey);

        return $this->approveVoid->execute(
            order: $order,
            requester: $requester,
            approver: $matchedManager,
            reason: $reason,
            reasonDetail: $reasonDetail,
        );
    }
}
