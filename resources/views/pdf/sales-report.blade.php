<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan — UMCO House</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0E7044;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #0E7044;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 11px;
            color: #555;
        }
        .meta-box {
            background-color: #f8faf8;
            border: 1px solid #dcdfdc;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .summary-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-grid td {
            padding: 6px 8px;
            border: 1px solid #e0e0e0;
            font-size: 11px;
        }
        .summary-grid .label {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 25%;
        }
        .summary-grid .value {
            text-align: right;
            font-weight: bold;
            font-family: monospace;
            font-size: 12px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.data-table th {
            background-color: #0E7044;
            color: #ffffff;
            font-weight: bold;
            padding: 7px 6px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #0E7044;
        }
        table.data-table td {
            padding: 6px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>UMCO HOUSE — UNIVERSITAS MUHAMMADIYAH RIAU</h1>
        <p>Badan Usaha Milik Kampus (BUMK) UMRI • Jl. Tuanku Tambusai, Pekanbaru</p>
        <p style="font-weight: bold; margin-top: 4px;">LAPORAN REKAPITULASI TRANSAKSI PENJUALAN</p>
        <p>Periode: {{ $periodLabel }} (Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB)</p>
    </div>

    <!-- Metrics Summary -->
    <table class="summary-grid">
        <tr>
            <td class="label">Total Transaksi Lunas</td>
            <td class="value">{{ number_format($metrics['total_transactions'], 0, ',', '.') }} nota</td>
            <td class="label">Penjualan Bruto</td>
            <td class="value">Rp{{ number_format($metrics['gross_sales'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Diskon / Promo</td>
            <td class="value" style="color: #c026d3;">Rp{{ number_format($metrics['discount_total'], 0, ',', '.') }}</td>
            <td class="label">Penjualan Neto (Bersih)</td>
            <td class="value" style="color: #0E7044;">Rp{{ number_format($metrics['net_sales'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Penerimaan Kas Tunai</td>
            <td class="value">Rp{{ number_format($metrics['cash_sales'], 0, ',', '.') }}</td>
            <td class="label">Penerimaan QRIS</td>
            <td class="value">Rp{{ number_format($metrics['qris_sales'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Piutang Belum Lunas (Open Bill)</td>
            <td class="value" style="color: #d97706;" colspan="3">Rp{{ number_format($metrics['open_bill_total'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <!-- Orders Table -->
    <h3 style="font-size: 12px; margin-bottom: 6px; font-weight: bold;">Rincian Transaksi</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">No. Order</th>
                <th style="width: 14%;">Tanggal/Jam</th>
                <th style="width: 18%;">Pelanggan</th>
                <th style="width: 10%;">Metode</th>
                <th style="width: 12%;" class="text-right">Subtotal</th>
                <th style="width: 12%;" class="text-right">Diskon</th>
                <th style="width: 14%;" class="text-right">Total Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
            <tr>
                <td><strong>{{ $order->number }}</strong></td>
                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->customer_name ?: 'Pelanggan Umum' }}</td>
                <td class="text-center">
                    @if ($order->isPaid())
                    {{ $order->payments->first()?->method->value ?? 'LUNAS' }}
                    @elseif ($order->isVoid())
                    VOID
                    @else
                    BELUM LUNAS
                    @endif
                </td>
                <td class="text-right">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($order->discount_total, 0, ',', '.') }}</td>
                <td class="text-right"><strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 15px;">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 40px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <p>Mengetahui,</p>
                <p style="margin-top: 50px; font-weight: bold; text-decoration: underline;">Manajer UMCO House / BUMK</p>
            </td>
            <td style="width: 50%; text-align: center;">
                <p>Pekanbaru, {{ now()->isoFormat('D MMMM Y') }}</p>
                <p style="margin-top: 50px; font-weight: bold; text-decoration: underline;">Petugas Kasir / Finance</p>
            </td>
        </tr>
    </table>

</body>
</html>
