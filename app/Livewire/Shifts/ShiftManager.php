<?php

namespace App\Livewire\Shifts;

use App\Actions\CloseShift;
use App\Actions\OpenShift;
use App\Actions\RecordExpense;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shift;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShiftManager extends Component
{
    // Open shift state
    public int $openingCash = 0;

    // Expense state
    public bool $showExpenseModal = false;
    public string $expenseCategory = 'operasional';
    public int $expenseAmount = 0;
    public string $expenseNote = '';

    // Close shift state
    public bool $showCloseModal = false;
    public int $countedCash = 0;
    public int $nextDayCash = 0;

    // Print summary modal
    public bool $showPrintSummaryModal = false;

    /**
     * Open a new shift.
     */
    public function openNewShift(OpenShift $openShift): void
    {
        $this->validate([
            'openingCash' => ['required', 'integer', 'min:0'],
        ]);

        $openShift->execute(auth()->user(), $this->openingCash);
        session()->flash('success', 'Shift kasir berhasil dibuka.');
    }

    /**
     * Record operational expense in current shift.
     */
    public function recordExpense(RecordExpense $recordExpense): void
    {
        $this->validate([
            'expenseAmount' => ['required', 'integer', 'min:1000'],
            'expenseNote' => ['required', 'string', 'min:3'],
        ], [
            'expenseAmount.min' => 'Nominal pengeluaran minimal Rp1.000.',
            'expenseNote.required' => 'Catatan keperluan pengeluaran wajib diisi.',
        ]);

        $shift = auth()->user()->activeShift();
        if (! $shift) {
            $this->addError('expenseAmount', 'Tidak ada shift aktif.');
            return;
        }

        $recordExpense->execute(
            shift: $shift,
            user: auth()->user(),
            category: $this->expenseCategory,
            amount: $this->expenseAmount,
            note: $this->expenseNote,
        );

        $this->showExpenseModal = false;
        $this->expenseAmount = 0;
        $this->expenseNote = '';
        session()->flash('success', 'Pengeluaran kasir berhasil dicatat.');
    }

    /**
     * Pre-populate close modal.
     */
    public function openCloseShiftModal(): void
    {
        $shift = auth()->user()->activeShift();
        if (! $shift) return;

        $metrics = $this->getShiftMetrics($shift);
        $this->countedCash = $metrics['expected_cash'];
        $this->nextDayCash = $shift->opening_cash;
        $this->showCloseModal = true;
    }

    /**
     * Close the current shift.
     */
    public function closeCurrentShift(CloseShift $closeShift): void
    {
        $this->validate([
            'countedCash' => ['required', 'integer', 'min:0'],
            'nextDayCash' => ['required', 'integer', 'min:0'],
        ]);

        $shift = auth()->user()->activeShift();
        if (! $shift) return;

        $closeShift->execute($shift, $this->countedCash, $this->nextDayCash);

        $this->showCloseModal = false;
        $this->showPrintSummaryModal = true;
        session()->flash('success', 'Shift kasir berhasil ditutup dan selisih kas telah dihitung.');
    }

    protected function getShiftMetrics(Shift $shift): array
    {
        $orderIds = $shift->orders()->pluck('id');

        $cashSales = (int) Payment::whereIn('order_id', $orderIds)
            ->where('method', PaymentMethod::Cash)
            ->sum('amount');

        $qrisSales = (int) Payment::whereIn('order_id', $orderIds)
            ->where('method', PaymentMethod::Qris)
            ->sum('amount');

        $totalExpenses = (int) $shift->expenses()->sum('amount');
        $expectedCash = $shift->opening_cash + $cashSales - $totalExpenses;
        $transactionsCount = $shift->orders()->where('payment_status', 'paid')->count();

        return [
            'cash_sales' => $cashSales,
            'qris_sales' => $qrisSales,
            'total_sales' => $cashSales + $qrisSales,
            'total_expenses' => $totalExpenses,
            'expected_cash' => $expectedCash,
            'transactions_count' => $transactionsCount,
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $activeShift = $user->activeShift();
        $recentShifts = Shift::with(['user', 'expenses'])
            ->latest('opened_at')
            ->limit(10)
            ->get();

        $metrics = $activeShift ? $this->getShiftMetrics($activeShift) : null;
        $activeExpenses = $activeShift ? $activeShift->expenses()->latest()->get() : collect();

        return view('livewire.shifts.shift-manager', [
            'activeShift' => $activeShift,
            'metrics' => $metrics,
            'activeExpenses' => $activeExpenses,
            'recentShifts' => $recentShifts,
        ])->title('Manajemen Shift & Saldo Kas');
    }
}
