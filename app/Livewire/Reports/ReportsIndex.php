<?php

namespace App\Livewire\Reports;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class ReportsIndex extends Component
{
    public string $period = 'today'; // today | yesterday | week | month | custom
    public string $reportTab = 'sales'; // sales | top_menu | discounts | partners
    public ?string $customStart = null;
    public ?string $customEnd = null;

    public function mount(): void
    {
        $this->customStart = now()->startOfMonth()->toDateString();
        $this->customEnd = now()->toDateString();
    }

    protected function getDateRange(): array
    {
        $startDate = match ($this->period) {
            'yesterday' => Carbon::yesterday()->startOfDay(),
            'week' => Carbon::now()->subDays(7)->startOfDay(),
            'month' => Carbon::now()->startOfMonth(),
            'custom' => $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : Carbon::today()->startOfDay(),
            default => Carbon::today()->startOfDay(),
        };

        $endDate = match ($this->period) {
            'yesterday' => Carbon::yesterday()->endOfDay(),
            'custom' => $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : Carbon::now()->endOfDay(),
            default => Carbon::now()->endOfDay(),
        };

        $periodLabel = match ($this->period) {
            'yesterday' => 'Kemarin (' . Carbon::yesterday()->format('d/m/Y') . ')',
            'week' => '7 Hari Terakhir',
            'month' => 'Bulan Ini (' . Carbon::now()->isoFormat('MMMM Y') . ')',
            'custom' => Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y'),
            default => 'Hari Ini (' . Carbon::today()->format('d/m/Y') . ')',
        };

        return [$startDate, $endDate, $periodLabel];
    }

    /**
     * Export reports as PDF document (KF-31).
     */
    public function exportPdf()
    {
        [$startDate, $endDate, $periodLabel] = $this->getDateRange();

        $orders = Order::with(['payments', 'cashier', 'table'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        $paidOrders = $orders->where('payment_status', PaymentStatus::Paid);
        $openBillOrders = $orders->where('payment_status', PaymentStatus::Unpaid)->where('is_open_bill', true);

        $paidOrderIds = $paidOrders->pluck('id');
        $cashSales = (int) Payment::whereIn('order_id', $paidOrderIds)->where('method', PaymentMethod::Cash)->sum('amount');
        $qrisSales = (int) Payment::whereIn('order_id', $paidOrderIds)->where('method', PaymentMethod::Qris)->sum('amount');

        $metrics = [
            'total_transactions' => $paidOrders->count(),
            'gross_sales' => $paidOrders->sum('subtotal'),
            'discount_total' => $paidOrders->sum('discount_total'),
            'net_sales' => $paidOrders->sum('total'),
            'cash_sales' => $cashSales,
            'qris_sales' => $qrisSales,
            'open_bill_total' => (int) $openBillOrders->sum('total'),
        ];

        $pdf = Pdf::loadView('pdf.sales-report', [
            'orders' => $orders,
            'metrics' => $metrics,
            'periodLabel' => $periodLabel,
        ]);

        $filename = 'Laporan_Penjualan_UMCO_' . now()->format('Ymd_His') . '.pdf';
        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    /**
     * Export transactions as CSV / Excel (KF-31).
     */
    public function exportCsv(): StreamedResponse
    {
        [$startDate, $endDate] = $this->getDateRange();

        $orders = Order::with(['payments', 'cashier'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();

        $filename = 'Transaksi_UMCO_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No. Order',
                'Tanggal',
                'Jam',
                'Petugas Kasir',
                'Nama Pelanggan',
                'Status Pesanan',
                'Status Pembayaran',
                'Metode Pembayaran',
                'Subtotal (Rp)',
                'Diskon (Rp)',
                'Total Akhir (Rp)',
            ]);

            foreach ($orders as $o) {
                fputcsv($handle, [
                    $o->number,
                    $o->created_at->format('Y-m-d'),
                    $o->created_at->format('H:i:s'),
                    $o->cashier?->name ?? 'Kasir',
                    $o->customer_name ?: 'Umum',
                    $o->status->value,
                    $o->payment_status->value,
                    $o->payments->first()?->method->value ?? ($o->isPaid() ? 'paid' : 'unpaid'),
                    $o->subtotal,
                    $o->discount_total,
                    $o->total,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        [$startDate, $endDate, $periodLabel] = $this->getDateRange();

        $ordersQuery = Order::with(['payments', 'cashier', 'table', 'items.menuItem.partner'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        $orders = (clone $ordersQuery)->latest()->get();

        $paidOrders = $orders->where('payment_status', PaymentStatus::Paid);
        $openBillOrders = $orders->where('payment_status', PaymentStatus::Unpaid)->where('is_open_bill', true);

        $grossSales = $paidOrders->sum('subtotal');
        $discountTotal = $paidOrders->sum('discount_total');
        $netSales = $paidOrders->sum('total');

        $paidOrderIds = $paidOrders->pluck('id');
        $cashSales = (int) Payment::whereIn('order_id', $paidOrderIds)->where('method', PaymentMethod::Cash)->sum('amount');
        $qrisSales = (int) Payment::whereIn('order_id', $paidOrderIds)->where('method', PaymentMethod::Qris)->sum('amount');
        $openBillTotal = (int) $openBillOrders->sum('total');

        // Top selling items
        $topItems = OrderItem::whereIn('order_id', $paidOrderIds)
            ->select('name', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_revenue'))
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Discounts breakdown
        $discountsBreakdown = OrderItem::whereIn('order_id', $paidOrderIds)
            ->where('discount_amount', '>', 0)
            ->with('discount')
            ->select('discount_id', DB::raw('COUNT(*) as total_applied'), DB::raw('SUM(discount_amount * qty) as total_cut'))
            ->groupBy('discount_id')
            ->get();

        // Consignment Partner Shares (KF-10)
        $partnerSales = OrderItem::whereIn('order_id', $paidOrderIds)
            ->whereHas('menuItem.partner')
            ->with(['menuItem.partner'])
            ->get()
            ->groupBy(fn ($item) => $item->menuItem?->partner?->name ?? 'Mitra Lain')
            ->map(function ($items, $partnerName) {
                $totalRevenue = $items->sum(fn ($i) => $i->qty * $i->unit_price);
                $partner = $items->first()->menuItem->partner;
                $sharePercent = $partner ? $partner->share_percent : 20;
                $partnerShare = (int) round($totalRevenue * ($sharePercent / 100));
                $cafeShare = $totalRevenue - $partnerShare;

                return [
                    'partner_name' => $partnerName,
                    'share_percent' => $sharePercent,
                    'total_sold' => $items->sum('qty'),
                    'total_revenue' => $totalRevenue,
                    'partner_share' => $partnerShare,
                    'cafe_share' => $cafeShare,
                ];
            });

        return view('livewire.reports.reports-index', [
            'orders' => $orders,
            'topItems' => $topItems,
            'discountsBreakdown' => $discountsBreakdown,
            'partnerSales' => $partnerSales,
            'periodLabel' => $periodLabel,
            'metrics' => [
                'total_transactions' => $paidOrders->count(),
                'gross_sales' => $grossSales,
                'discount_total' => $discountTotal,
                'net_sales' => $netSales,
                'cash_sales' => $cashSales,
                'qris_sales' => $qrisSales,
                'open_bill_total' => $openBillTotal,
            ],
        ])->title('Laporan Transaksi & Keuangan');
    }
}
