# Keputusan Desain (DECISIONS.md)

Dokumen ini mencatat keputusan yang diambil saat implementasi ketika ada hal ambigu.
Mengacu ke DESIGN.md Bagian 13 (Asumsi).

## Fase 1

### D-001: Barista tidak dapat membuat pesanan
- **Topik:** Asumsi #1 — Barista input pesanan
- **Keputusan:** Barista TIDAK dapat membuat pesanan (sesuai tabel hak akses 3.3).
- **Alasan:** Default yang dipakai di DESIGN.md. Jika perlu diaktifkan, cukup tambahkan permission `orders.create` ke role barista.

### D-002: Penggabungan diskon
- **Topik:** Asumsi #2 — Aturan penggabungan diskon
- **Keputusan:** Satu nota hanya menerapkan satu diskon berbasis identitas (mahasiswa ATAU dosen/karyawan). Diskon promo/bundling berbasis menu dapat berlaku bersamaan. Potongan tidak boleh membuat harga baris < 0.
- **Alasan:** Default yang dipakai di DESIGN.md.

### D-003: Stok berkurang saat order dikonfirmasi
- **Topik:** Asumsi #5 — Kapan stok berkurang
- **Keputusan:** Stok dikurangi saat order berstatus `confirmed`, bukan saat dibayar.
- **Alasan:** Default yang dipakai di DESIGN.md (Bagian 8.5).

### D-004: QRIS statis + tandai lunas manual
- **Topik:** Asumsi #6 — Pembayaran QRIS
- **Keputusan:** Menampilkan QR statis UMCO + tombol "Tandai lunas" manual. Belum ada integrasi payment gateway.
- **Alasan:** Default yang dipakai di DESIGN.md.

### D-005: Cetak lewat browser
- **Topik:** Asumsi #7 — Printer struk
- **Keputusan:** Cetak struk lewat browser (CSS `@media print`) untuk 58/80 mm. Belum ada ESC/POS atau Web Bluetooth.
- **Alasan:** Default yang dipakai di DESIGN.md (implementasi awal).

### D-006: Shift wajib dibuka sebelum transaksi
- **Topik:** Bagian 8.6
- **Keputusan:** Di Fase 1, shift dibuat otomatis saat kasir login (simplified). Fitur buka/tutup shift lengkap ada di Fase 3.
- **Alasan:** Fase 1 fokus pada POS inti; shift management lengkap bukan bagian Fase 1.

### D-007: PIN ganti pengguna
- **Topik:** Bagian 3 — Ganti operator
- **Keputusan:** Di Fase 1, "Ganti pengguna" menggunakan logout + login ulang biasa. Modal PIN akan diimplementasikan di fase berikutnya.
- **Alasan:** Menyederhanakan Fase 1 agar fokus pada POS inti.
