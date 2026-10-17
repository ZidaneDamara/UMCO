# DESIGN.md — Sistem Informasi UMCO House

> Dokumen ini adalah sumber acuan utama untuk Antigravity (agent) dalam membangun aplikasi.
> Sumber kebutuhan: `Blueprint_Sistem_UMCO.docx` (KF = kebutuhan fungsional, KNF = kebutuhan non-fungsional).
> Bahasa antarmuka: **Bahasa Indonesia**. Bahasa kode, nama tabel, dan nama class: **Inggris**.

---

## 0. Instruksi untuk Agent

1. Bangun **per fase** (lihat Bagian 12). Jangan lompat ke fase berikutnya sebelum acceptance criteria fase sekarang lolos.
2. Selalu jaga **konsistensi dengan Bagian 4 (Design System)**. Jangan menambah warna, font, atau pola komponen di luar token yang ada.
3. Semua aturan bisnis (diskon, void, stok, open bill) ada di **Bagian 8**. Letakkan logikanya di `app/Actions` atau `app/Services`, bukan di Blade/Livewire component.
4. Setiap perubahan sensitif (void, ubah harga, ubah stok, hapus data) **wajib** menulis ke `audit_logs` (KNF-06).
5. Jika menemukan hal ambigu, cek Bagian 13 (Asumsi). Jika belum tercakup, pilih opsi paling sederhana dan catat di `docs/DECISIONS.md`.
6. Tulis test (Pest) untuk: kalkulasi diskon, alur pembayaran, void dengan PIN, pengurangan stok, dan otorisasi per role.

---

## 1. Ringkasan Produk

**UMCO House** adalah cafe unit usaha BUMK Universitas Muhammadiyah Riau. Sistem lama (Olsera, ±Rp2.000.000/tahun) dinilai kurang praktis: stok dicatat dua kali (Google Form lalu sistem), absensi lewat WhatsApp, struk open bill tidak bisa dicetak, laporan hanya struk harian.

**Tujuan sistem baru:** satu aplikasi web yang menggabungkan **POS/kasir, pesanan realtime ke bar & dapur, stok, shift, absensi, diskon/promo, open bill, void ber-PIN, dan laporan**, dengan tampilan sederhana yang bisa dipakai karyawan baru (mayoritas mahasiswa) tanpa pelatihan panjang.

**Fakta operasional yang memengaruhi desain**

| Hal | Detail |
|---|---|
| Jam buka | 08.00–22.00, Senin–Sabtu (Minggu tutup) |
| Shift | Shift 1: 08.00–15.00, Shift 2: 15.00–22.00 (1 kasir + 1 barista per shift) |
| Kitchen | 09.00–17.00, bekerja di dapur terpisah (UMC) sehingga **harus** menerima pesanan lewat layar |
| Helper | Tidak terikat shift, belanja dan cek stok |
| Perangkat | Tablet dan HP (prioritas), laptop untuk manajer/BUMK |
| Pembayaran | Tunai dan QRIS |
| Hari ramai | PKKMB, wisuda, UTBK (respons harus tetap cepat) |

---

## 2. Tech Stack & Konvensi

| Lapisan | Pilihan |
|---|---|
| Framework | **Laravel 12**, PHP 8.3+ |
| UI | **Livewire** (starter kit Laravel 12) + Alpine.js + **Tailwind CSS v4** |
| Realtime | **Laravel Reverb** (WebSocket) + Laravel Echo. Fallback polling 10 detik |
| Database | MySQL 8 / MariaDB 10.6+ |
| Queue & cache | Redis (disarankan) atau `database` driver untuk awal |
| Auth & RBAC | Laravel auth + `spatie/laravel-permission` |
| Ekspor | `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (Excel) |
| QR | `simplesoftwareio/simple-qrcode` (QR meja) |
| Testing | Pest |
| Kualitas kode | Laravel Pint, Larastan level 5 |
| PWA | Service worker + manifest (untuk install di tablet & antrean offline) |

**Konvensi**

- Uang disimpan sebagai **integer rupiah** (`unsignedBigInteger`), tanpa desimal. Format tampilan: `Rp12.500`.
- Waktu disimpan UTC, ditampilkan **Asia/Jakarta (WIB)**. Set `APP_TIMEZONE=Asia/Jakarta`.
- Semua tabel transaksi memakai **soft delete**; transaksi **tidak pernah** dihapus fisik.
- Nomor order: `UMC-YYMMDD-0001` (reset harian), dibuat di dalam DB transaction dengan lock.
- Pola kode: `Action` class untuk operasi bisnis, `Policy` untuk otorisasi, `Event` + `Listener` untuk notifikasi.

---

## 3. Role & Hak Akses (RBAC)

Role: `manager`, `bumk_staff`, `cashier`, `barista`, `helper`, `kitchen`. Pelanggan tidak punya akun.

`manager` dan `bumk_staff` = **superadmin** (hak setara).

| Fitur | Manager / BUMK | Kasir | Barista | Helper | Kitchen | Pelanggan |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Dashboard | Penuh | Ringkas | – | – | – | – |
| POS: buat & ubah pesanan | ✓ | ✓ | – | – | – | – |
| Terapkan diskon | ✓ | ✓ | – | – | – | – |
| Bayar / open bill | ✓ | ✓ | – | – | – | – |
| Void / batalkan transaksi | ✓ langsung | Ajukan, perlu PIN manajer | – | – | – | – |
| Hapus transaksi | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Antrean pesanan (layar produksi) | ✓ | Lihat | ✓ minuman | – | ✓ makanan | – |
| Menu & harga | ✓ | Lihat | Lihat | – | – | Lihat via QR |
| Promo & diskon (kelola) | ✓ | Lihat | – | – | – | – |
| Inventori: lihat | ✓ | ✓ | ✓ | ✓ (read-only) | ✓ | – |
| Inventori: ubah & opname | ✓ | ✓ | ✓ (bahan bar) | ✗ | ✓ (bahan makanan) | – |
| Pengeluaran operasional | ✓ | ✓ | – | – | – | – |
| Shift (buka/tutup kas) | ✓ | ✓ | – | – | – | – |
| Jadwal shift: kelola | ✓ | Lihat | Lihat | – | Lihat | – |
| Absensi | Pantau semua | Absen sendiri | Absen sendiri | Absen sendiri | Absen sendiri | – |
| Laporan & ekspor | ✓ | Riwayat transaksi | – | – | – | – |
| Kelola pengguna & role | ✓ | – | – | – | – | – |
| Bagi hasil mitra | ✓ | – | – | – | – | – |
| Self-service via QR | ✓ (pantau) | Terima pesanan | – | – | – | Pesan |

> **Catatan:** Blueprint KF-13 menyebut barista dapat menginput pesanan, tetapi tabel hak akses (3.3) tidak. Default yang dipakai: **barista tidak input pesanan**, tapi cukup mudah diaktifkan lewat permission `orders.create` (lihat Bagian 13).

Implementasi: definisikan permission granular (`orders.create`, `orders.void.request`, `orders.void.approve`, `inventory.update`, dst.), role hanya kumpulan permission. Cek lewat `Gate`/`Policy`, bukan `if role ==`.

**Ganti operator (pergantian shift):** tombol "Ganti pengguna" di header membuka modal login cepat (pilih nama + PIN pribadi 4–6 digit) tanpa logout penuh, agar transaksi dan absensi tetap tercatat atas nama yang benar.

---

## 4. Design System

### 4.1 Prinsip

1. **Minimalis, tenang, cepat dibaca.** Banyak ruang kosong, sedikit dekorasi. Tidak ada gradien dekoratif, tidak ada bayangan berat.
2. **Hierarki lewat ukuran dan bobot huruf**, bukan warna berlebihan. Warna dipakai untuk **status dan aksi utama**.
3. **Satu aksi utama per layar.** Tombol primer hijau hanya satu di setiap konteks.
4. **Ramah jempol.** Target sentuh minimal 44×44 px; POS dioptimalkan untuk tablet landscape dan HP portrait.
5. **Warna hijau mengikuti logo UMCO House** (KNF-10).
6. **Teks antarmuka:** sentence case, kata kerja jelas ("Simpan perubahan", "Bayar", "Batalkan pesanan"), pesan error menjelaskan masalah dan cara memperbaikinya (KNF-09).

### 4.2 Design Tokens

Definisikan di `resources/css/app.css` sebagai CSS variables / `@theme` Tailwind v4.

**Warna**

| Token | Nilai | Pemakaian |
|---|---|---|
| `--color-brand-50` | `#EEF7F1` | Latar item terpilih, hover halus |
| `--color-brand-100` | `#D6ECDD` | Badge hijau, highlight |
| `--color-brand-600` | `#1F7A4D` | **Aksi utama**, link aktif |
| `--color-brand-700` | `#17613D` | Hover/pressed tombol utama |
| `--color-brand-900` | `#0F3D27` | Teks di atas latar hijau muda |
| `--color-bg` | `#F7F8F7` | Latar halaman |
| `--color-surface` | `#FFFFFF` | Kartu, panel, modal |
| `--color-line` | `#E5E8E6` | Border 1px |
| `--color-ink` | `#18211C` | Teks utama |
| `--color-ink-muted` | `#5F6B64` | Teks sekunder |
| `--color-warning` | `#B7791F` / latar `#FDF3DC` | Menipis, open bill, menunggu |
| `--color-danger` | `#C2412D` / latar `#FBE7E3` | Void, error, stok habis |
| `--color-info` | `#2B6CB0` / latar `#E6EFF9` | Informasi, QRIS |

**Mode gelap:** layar produksi (Barista/Kitchen) mendukung tema gelap opsional (`--color-bg: #101512`, `--color-surface: #181F1B`, `--color-ink: #EAF0EC`) karena dipakai lama dan di ruang berbeda pencahayaan. Seluruh token punya padanan dark.

**Tipografi**

- Font: **Plus Jakarta Sans** (Google Fonts, fallback `system-ui, sans-serif`). Angka uang memakai `font-variant-numeric: tabular-nums`.
- Skala: `12 / 14 / 16 / 18 / 24 / 32 px`. Body 14–16 px. Judul halaman 24 px semibold. Total pembayaran di POS 32 px bold.
- Panjang baris teks bacaan maksimal ±70 karakter.

**Bentuk & jarak**

- Skala spasi 4 px: `4, 8, 12, 16, 24, 32, 48`.
- Radius: **8 px** untuk input/tombol/kartu, **12 px** untuk modal dan panel besar, **999 px** hanya untuk badge status. Jangan pakai satu radius untuk semuanya tanpa alasan hirarki.
- Elevasi: kartu memakai **border 1px `--color-line`**, tanpa shadow. Shadow hanya untuk modal dan dropdown (`0 8px 24px rgba(15,61,39,.08)`).
- Ikon: **Lucide**, stroke 1.75, ukuran 18/20 px.

### 4.3 Komponen Inti

| Komponen | Spesifikasi |
|---|---|
| **Tombol** | Primer (isi hijau, teks putih), Sekunder (border `--color-line`, teks `--color-ink`), Bahaya (teks/border merah, isi merah hanya saat konfirmasi akhir). Tinggi 40 px (desktop), 48 px (POS/tablet). Ada state loading dan disabled. |
| **Input** | Tinggi 44 px, border `--color-line`, fokus ring 2 px `--color-brand-600`. Label di atas input, pesan error di bawah. |
| **Kartu menu (POS)** | Nama, harga, badge kategori; tanpa foto wajib (foto opsional, rasio 1:1). Menu habis: opacity 50% + badge "Habis", masih bisa dilihat tapi tidak bisa ditambahkan (KF-16). |
| **Badge status** | Pill kecil: Menunggu (kuning), Diproses (biru), Siap (hijau), Selesai (abu), Dibatalkan (merah), Belum dibayar (kuning), Lunas (hijau). |
| **Tabel** | Baris 48 px, header `--color-ink-muted` 12 px, tanpa zebra, hover `--color-brand-50`. Di HP berubah menjadi daftar kartu. |
| **Modal / Sheet** | Desktop: modal tengah. HP: bottom sheet. Selalu bisa ditutup dengan Esc atau tombol Batal. |
| **Toast** | Kanan atas (desktop) / atas (HP), otomatis hilang 4 detik; toast pesanan baru **tidak** otomatis hilang (lihat Bagian 5). |
| **Numpad PIN** | Grid 3×4 tombol besar, titik indikator 4–6 digit, dipakai untuk void dan ganti operator. |
| **Empty state** | Satu kalimat + satu tombol aksi. Contoh: "Belum ada pesanan. Pesanan baru akan muncul di sini." |

### 4.4 Layout

- **Shell aplikasi:** sidebar kiri 232 px (desktop) yang bisa diciutkan menjadi ikon 64 px; di tablet portrait dan HP menjadi **bottom navigation** maksimal 5 item.
- Menu navigasi **difilter per role** (hanya tampil yang boleh diakses).
- Header: nama halaman, indikator koneksi (titik hijau/kuning/merah), indikator suara (ikon speaker), nama operator + "Ganti pengguna".
- **POS (tablet landscape):** tiga zona.

```
┌────────────┬─────────────────────────────┬──────────────────┐
│ Kategori   │  Grid menu (cari di atas)   │  Pesanan saat ini │
│ (vertikal) │                             │  - item + catatan │
│            │                             │  - diskon         │
│            │                             │  Total  Rp xx.xxx │
│            │                             │  [Simpan open bill]│
│            │                             │  [Bayar]  ← utama │
└────────────┴─────────────────────────────┴──────────────────┘
```

- **POS (HP portrait):** grid menu satu kolom + bar bawah "Lihat pesanan (3) · Rp37.500" yang membuka bottom sheet keranjang.
- **Layar Produksi (Barista/Kitchen):** kolom kartu tiket (Baru → Diproses → Siap), huruf besar, kontras tinggi, dioptimalkan untuk tablet yang ditaruh di meja.
- **Dashboard manajer:** baris ringkasan 4 angka (penjualan hari ini, jumlah transaksi, open bill belum lunas, stok menipis), satu grafik penjualan per jam, daftar menu terlaris. Tidak lebih dari itu di layar pertama.

### 4.5 Motion

- Gunakan seperlunya: transisi 150 ms pada hover/fokus, sheet/modal masuk 200 ms.
- Satu-satunya gerakan yang menarik perhatian tanpa dipicu pengguna adalah **kartu pesanan baru** (denyut halus sekali, lalu diam). Hormati `prefers-reduced-motion`.

### 4.6 Aksesibilitas

Kontras teks minimal WCAG AA, fokus keyboard selalu terlihat, status tidak hanya dibedakan lewat warna (selalu ada teks/ikon), label form lengkap.

---

## 5. Notifikasi Pesanan Masuk + Bunyi

Ini fitur kunci. Dua kebutuhan: **(a)** bunyi saat **pesanan masuk** ke pihak yang harus memprosesnya, **(b)** alarm saat **transaksi berhasil** (KF-36).

### 5.1 Matriks Notifikasi

| Peristiwa (Event) | Penerima | Bunyi | Tampilan |
|---|---|---|---|
| `OrderCreated` dari **Self-Service (QR)** | Kasir (+ manajer jika sedang online) | `new-order.mp3` (berulang, lihat 5.3) | Toast persisten + badge merah di menu "Pesanan masuk" + judul tab berkedip |
| `OrderConfirmed` (pesanan sudah diinput/dikonfirmasi kasir) | Item **minuman → Barista**, item **makanan → Kitchen** (tiket dipisah per tipe) | `ticket.mp3` | Kartu tiket baru di kolom "Baru" dengan denyut sekali |
| `OrderPaid` (transaksi berhasil) | Kasir yang bertransaksi | `payment-success.mp3` (pendek, 1×) | Toast hijau "Pembayaran berhasil" |
| `OrderStatusUpdated` → "Siap" | Kasir | `ready.mp3` (lembut) | Badge "Siap diantar" di daftar pesanan |
| `VoidRequested` | Manajer / BUMK | `alert.mp3` | Toast dengan tombol "Tinjau" |
| `LowStockDetected` | Manajer, Helper | Tanpa bunyi | Badge + daftar "Perlu dibeli" |

Aset suara disimpan di `public/sounds/` (mp3, ≤ 60 KB masing-masing, durasi 0,5–2 detik). Gunakan suara yang bersih dan tidak mengagetkan; **bukan** suara alarm keras.

### 5.2 Arsitektur Realtime

```
Action (CreateOrder / PayOrder / ...)
   └─ DB::transaction → commit
        └─ event(new OrderConfirmed($order))   // ShouldBroadcast, afterCommit
             └─ Reverb ──► Laravel Echo (browser) ──► SoundManager.play() + Toast
```

**Channel (private, otorisasi lewat `routes/channels.php`)**

| Channel | Anggota |
|---|---|
| `private-cashier` | role cashier, manager, bumk_staff |
| `private-bar` | role barista, manager, bumk_staff |
| `private-kitchen` | role kitchen, manager, bumk_staff |
| `private-manager` | role manager, bumk_staff |

**Contoh event**

```php
// app/Events/OrderConfirmed.php
class OrderConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public Order $order, public string $station) {} // 'bar' | 'kitchen'

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->station)];
    }

    public function broadcastAs(): string { return 'order.confirmed'; }

    public function broadcastWith(): array
    {
        return [
            'id'      => $this->order->id,
            'number'  => $this->order->number,
            'table'   => $this->order->table_label,
            'items'   => $this->order->itemsForStation($this->station)
                              ->map(fn ($i) => [
                                  'name' => $i->name, 'qty' => $i->qty, 'note' => $i->note,
                              ])->all(),
            'sound'   => 'ticket',
        ];
    }
}
```

### 5.3 Sound Manager (frontend)

Browser **memblokir autoplay audio** sebelum ada interaksi pengguna. Karena itu wajib ada langkah "aktifkan suara".

**Aturan perilaku**

1. Saat login/buka layar produksi atau POS, tampilkan banner sekali: **"Aktifkan suara notifikasi"** (tombol). Klik → audio di-*unlock* (putar senyap) dan status disimpan di `localStorage` (`umco.sound.enabled`).
2. Jika suara belum diaktifkan, indikator speaker di header menampilkan ikon dicoret berwarna kuning, dan notifikasi tetap muncul secara visual.
3. Suara **pesanan self-service** diulang setiap 8 detik, maksimal 3 kali, sampai kasir menekan "Terima" atau "Lihat". Suara lain diputar 1×.
4. Pengguna bisa mengatur **volume** (slider) dan **mute** dari ikon speaker di header. Tersedia tombol "Tes suara".
5. Jika koneksi WebSocket putus: indikator koneksi menjadi kuning dan sistem otomatis **polling `/api/orders/pending` tiap 10 detik**; suara diputar bila ada pesanan baru yang belum pernah dibunyikan (dedupe lewat `order_id` di memori).
6. Dedupe: satu `order_id + event` hanya membunyikan suara sekali per perangkat.
7. Tambahan opsional: `Notification` API browser (izin diminta sekali) agar muncul notifikasi saat tab di latar belakang, dan `navigator.vibrate` pada HP.

**Kerangka kode**

```js
// resources/js/sound-manager.js
const SOUNDS = {
  'new-order':       '/sounds/new-order.mp3',
  'ticket':          '/sounds/ticket.mp3',
  'payment-success': '/sounds/payment-success.mp3',
  'ready':           '/sounds/ready.mp3',
  'alert':           '/sounds/alert.mp3',
};

class SoundManager {
  constructor() {
    this.enabled = localStorage.getItem('umco.sound.enabled') === '1';
    this.volume  = parseFloat(localStorage.getItem('umco.sound.volume') ?? '0.8');
    this.cache   = {};
    this.played  = new Set();
  }

  async unlock() {                       // dipanggil dari klik tombol "Aktifkan suara"
    const a = new Audio(SOUNDS['ticket']); a.volume = 0;
    try { await a.play(); a.pause(); this.enabled = true;
          localStorage.setItem('umco.sound.enabled', '1'); } catch (e) {}
    return this.enabled;
  }

  play(name, { key = null, repeat = 1, every = 8000 } = {}) {
    if (!this.enabled) return;
    if (key && this.played.has(`${name}:${key}`)) return;
    if (key) this.played.add(`${name}:${key}`);

    const run = (n) => {
      const a = this.cache[name] ??= new Audio(SOUNDS[name]);
      a.volume = this.volume; a.currentTime = 0; a.play().catch(() => {});
      if (n > 1) this.timer = setTimeout(() => run(n - 1), every);
    };
    run(repeat);
  }

  stopRepeat() { clearTimeout(this.timer); }
}
export const sound = new SoundManager();
```

```js
// resources/js/realtime.js (inisialisasi per role)
Echo.private('cashier')
  .listen('.order.created',  e => { sound.play('new-order', { key: e.id, repeat: 3 }); notify.persistent(e); })
  .listen('.order.paid',     e => { sound.play('payment-success', { key: e.id }); toast.success('Pembayaran berhasil'); })
  .listen('.order.status',   e => { if (e.status === 'ready') sound.play('ready', { key: e.id }); });

Echo.private('bar')
  .listen('.order.confirmed', e => { sound.play('ticket', { key: e.id }); ticketBoard.add(e); });
```

### 5.4 Persistensi Notifikasi

- Setiap notifikasi penting juga disimpan ke tabel `notifications` (bawaan Laravel) agar tidak hilang jika perangkat sedang offline atau tab baru dibuka.
- Pusat notifikasi (ikon lonceng) menampilkan 20 terakhir; yang belum dilihat ditandai.
- Saat halaman dimuat ulang, ambil pesanan berstatus `pending_confirmation` / `confirmed` yang belum diproses agar kartu tidak hilang.

### 5.5 Acceptance Criteria Notifikasi

- [ ] Pesanan self-service dibuat dari HP pelanggan → perangkat kasir berbunyi ≤ 2 detik (jaringan normal).
- [ ] Tiket minuman hanya muncul dan berbunyi di Barista; tiket makanan hanya di Kitchen; pesanan campuran memecah tiket.
- [ ] Tanpa interaksi awal, banner "Aktifkan suara" muncul; setelah diaktifkan, bunyi bekerja setelah refresh.
- [ ] Pesanan yang sama tidak membunyikan suara dua kali pada satu perangkat.
- [ ] Putus WebSocket → polling fallback tetap membunyikan pesanan baru.
- [ ] Mute dan volume tersimpan per perangkat.

---

## 6. Modul & Layar

Pemetaan ke kebutuhan blueprint ada di kolom KF. Urutan sesuai sidebar.

### 6.1 Auth & Pengguna (KF-01, KF-03; KNF-03, KNF-04)

- Login email/username + password (hash bcrypt). Rate limit login.
- PIN pribadi 4–6 digit (hash) untuk "Ganti pengguna". **PIN manajer** terpisah untuk persetujuan void.
- Layar: Login, Daftar pengguna (manajer), Form pengguna (nama, role, status aktif, reset password/PIN).

### 6.2 Dashboard (KF-11, KF-32)

- Manajer/BUMK: ringkasan hari ini, grafik penjualan per jam, menu terlaris, open bill belum lunas, stok menipis, void hari ini.
- Kasir: ringkasan shift berjalan (jumlah transaksi, total tunai, total QRIS).

### 6.3 POS / Kasir (KF-13 s.d. KF-22)

Alur: **pilih menu → atur jumlah/custom/catatan → pilih tipe diskon → Simpan sebagai open bill atau Bayar → struk.**

- Cari menu cepat + filter kategori; menu habis ditandai (KF-16).
- **Pesanan custom** (tambah telur, nasi, es) dengan nama dan harga yang diketik sendiri (KF-14).
- **Catatan khusus** per item: chip cepat (`Normal ice`, `Less ice`, `No ice`, `Less sugar`, `No sugar`, `Extra topping`) + kolom teks bebas (KF-15).
- **Satu nota boleh memuat menu promo dan non-promo** (KF-18).
- Pembayaran: **Tunai** (input nominal, tombol uang pas/pecahan cepat, hitung kembalian otomatis) dan **QRIS** (tampilkan QR statis UMCO + tombol "Tandai lunas"; catat referensi opsional).
- Setelah lunas: tampilkan struk, tombol **Cetak** dan **Kirim/Unduh struk digital** (link publik bertoken atau PDF) (KF-22).
- Tabel aktif: "Pesanan hari ini" dengan filter status (belum dibayar, lunas, dibatalkan).

### 6.4 Open Bill (KF-20, KF-21)

- "Simpan open bill" membuat order dengan `payment_status = unpaid`, wajib mengisi **nama pemesan / instansi** (contoh: Dosen X, Prodi Y, rapat akreditasi) dan catatan kegiatan.
- Daftar **Open bill** terpisah dengan umur tagihan, total, dan tombol "Cetak bill" (PDF/struk, **bisa dicetak**, bukan sekadar difoto) serta "Tandai lunas" (pilih metode bayar).
- Laporan memisahkan **penjualan diakui** vs **kas diterima**, agar selisih kas dan transaksi (masalah lama) bisa dijelaskan.

### 6.5 Antrean Pesanan / Layar Produksi (Barista & Kitchen)

- Tiga kolom: **Baru → Diproses → Siap**. Satu ketukan memindahkan status.
- Kartu menampilkan nomor order, label meja/nama, daftar item, jumlah, **catatan khusus** (ditonjolkan), dan waktu tunggu (berubah kuning > 5 menit, merah > 10 menit; ambang bisa diatur).
- Tiket otomatis terfilter sesuai tipe produk (minuman/makanan).
- Memicu notifikasi dan bunyi (Bagian 5).

### 6.6 Menu & Kategori (KF-04, KF-05)

- CRUD menu: nama, kategori, tipe (`drink`/`food`), harga, foto opsional, status tersedia, resep (bahan + takaran, opsional), `partner_id` jika produk titipan mitra.
- Ubah harga hanya manajer/BUMK, dan tercatat di audit log (harga lama → baru).
- Sakelar cepat "Tersedia/Habis" untuk kasir/barista (permission terpisah `menu.toggle_availability`).

### 6.7 Diskon & Promo (KF-06, KF-17)

- Master diskon dan promo (lihat aturan di Bagian 8.1).
- Promo harian per menu, promo mingguan, dan **paket bundling** (kumpulan item dengan harga paket).
- Setiap transaksi menyimpan **jenis diskon** dan nilai potongan per baris (untuk laporan).

### 6.8 Inventori & Stok Opname (KF-25 s.d. KF-28, KF-35, KF-37)

- Daftar bahan: nama, satuan, stok saat ini, stok minimum, kategori (bar/kitchen), harga beli terakhir (opsional).
- **Stok opname langsung di sistem**, menggantikan Google Form: layar hitung massal (daftar bahan, input stok fisik, selisih ditampilkan otomatis, tombol "Simpan opname"). Cocok dioperasikan di HP.
- **Catat bahan basi/terbuang** (alasan wajib) sebagai pengurang stok (KF-27).
- **Pengurangan stok otomatis** saat menu terjual berdasarkan resep (KF-35).
- Daftar **"Perlu dibeli"** (stok ≤ minimum atau habis) untuk Helper, bisa disalin/dicetak (KF-28).
- Riwayat pergerakan stok per bahan (`stock_movements`).
- Helper: read-only. Barista: hanya kategori bar. Kitchen: hanya kategori dapur.

### 6.9 Shift & Kas (KF-23, KF-24)

- **Buka shift:** kasir mengisi modal awal kas. **Tutup shift:** sistem menampilkan rekap (total tunai, QRIS, diskon, void, pengeluaran), kasir mengisi kas fisik, sistem menghitung selisih.
- **Catat kas untuk hari berikutnya** (saldo awal esok hari) langsung di sistem.
- **Laporan harian tersedia kapan saja** (tidak menunggu shift selesai) dan dapat dicetak.
- **Pengeluaran operasional** dicatat kasir (kategori, nominal, catatan) dan masuk ke rekap shift.

### 6.10 Jadwal Karyawan (KF-08, KF-09)

- Papan jadwal mingguan (kolom hari Senin–Sabtu, baris shift 1/shift 2/kitchen), seret-dan-lepas karyawan. Jadwal dapat diubah tiap minggu (banyak karyawan mahasiswa).
- Karyawan melihat jadwal sendiri; manajer melihat semua.
- Statistik kinerja ringan: jumlah transaksi per kasir, kehadiran per periode.

### 6.11 Absensi (KF-02)

- Tombol **"Masuk"** dan **"Pulang"** di beranda karyawan; hanya aktif pada rentang jadwal (toleransi ±30 menit, dapat diatur).
- **Bukti kehadiran:** foto selfie dari kamera perangkat + cap waktu server. Opsional validasi lokasi (geofence radius dari cafe). Foto disimpan di disk privat (bukan publik).
- Status otomatis: Hadir, Terlambat, Tidak hadir, Izin (diinput manajer).
- Manajer melihat rekap harian/bulanan, bisa mengoreksi dengan alasan (tercatat audit log).

### 6.12 Pembatalan Transaksi (KF-07; KNF-05)

Lihat alur lengkap di Bagian 8.4.

### 6.13 Laporan (KF-29 s.d. KF-31)

Filter periode (hari ini, kemarin, 7 hari, bulan ini, kustom). Ekspor **PDF dan Excel**.

- Penjualan harian & bulanan (bruto, diskon, neto), per metode bayar.
- Menu terlaris dan penjualan per kategori.
- Total diskon per jenis (mahasiswa, dosen/karyawan, min. 3 produk, dll.).
- Open bill (belum lunas & sudah lunas), void (alasan, pelaksana, penyetuju).
- Stok & pergerakan stok, bahan terbuang.
- Absensi dan kinerja karyawan.
- Pengeluaran operasional dan ringkasan kas.
- Transaksi yang dibatalkan **tidak dihitung** sebagai penjualan.

### 6.14 Bagi Hasil Mitra (KF-10, prioritas sedang)

- Master mitra (UMKM titipan), persentase bagi hasil, produk terkait.
- Laporan bulanan: penjualan produk mitra, bagian mitra, bagian UMCO. Ekspor PDF.

### 6.15 Self-Service via QR (KF-33, prioritas rendah, tetapi bagian dari alur notifikasi)

- Setiap meja punya QR unik: `https://{domain}/m/{table_token}`. Halaman publik tanpa login, mobile-first.
- Pelanggan melihat menu, harga, ketersediaan; menambah item, catatan khusus; mengisi nama (opsional); kirim pesanan.
- Pesanan masuk dengan status `pending_confirmation` → **kasir menerima notifikasi berbunyi** → kasir tekan **Terima** (bisa mengedit/menambah diskon) → tiket diteruskan ke Barista/Kitchen → pembayaran dilakukan di kasir.
- Perlindungan: throttle per IP dan per meja, honeypot, token meja dapat diputar ulang, batas item per pesanan.
- Dukungan **pesanan dari luar UMCO House** (dosen/rektor): token khusus "Pesanan antar" dengan input lokasi tujuan.
- Layar status pesanan untuk pelanggan (Menunggu, Diproses, Siap) via polling ringan/WebSocket publik terbatas.

### 6.16 Migrasi Data Olsera (KF-12)

- Artisan command `php artisan umco:import-olsera {file}` dan layar impor (manajer) untuk CSV/Excel: menu, kategori, stok awal, riwayat transaksi (opsional).
- Mode simulasi (dry-run) yang menampilkan baris valid/tidak valid sebelum disimpan.

---

## 7. Skema Database (ringkas)

Gunakan migration Laravel, foreign key dengan `constrained()`, index pada kolom filter laporan.

```
users              id, name, username, email, password, pin_hash, role(via spatie), is_active, ...
employees          id, user_id, phone, position, joined_at, notes
categories         id, name, type(drink|food|other), sort_order
menu_items         id, category_id, name, type(drink|food), price, image_path, is_available,
                   partner_id nullable, is_active
partners           id, name, share_percent, contact
ingredients        id, name, unit, category(bar|kitchen), stock, min_stock, last_cost, is_active
recipes            id, menu_item_id, ingredient_id, qty_per_item
stock_movements    id, ingredient_id, type(purchase|sale|waste|opname|void_return|manual),
                   qty_change, qty_after, reference_type, reference_id, note, user_id, created_at
stock_opnames      id, user_id, started_at, finished_at, note
stock_opname_items id, opname_id, ingredient_id, system_qty, counted_qty, diff
tables             id, label, token, is_active
orders             id, number, source(pos|self_service|delivery), table_id nullable, customer_name,
                   status(pending_confirmation|confirmed|preparing|ready|completed|void),
                   payment_status(unpaid|paid|void), is_open_bill, open_bill_note,
                   subtotal, discount_total, total, shift_id, cashier_id, paid_at, created_at, deleted_at
order_items        id, order_id, menu_item_id nullable, name, type(drink|food), qty, unit_price,
                   discount_amount, discount_id nullable, note, is_custom, station(bar|kitchen), status
discounts          id, name, kind(fixed_per_item|percent_per_item|fixed_per_order|bundle|promo),
                   value, applies_to(drink|all), conditions(json), is_active, starts_at, ends_at
payments           id, order_id, method(cash|qris), amount, cash_received, change, reference, user_id, paid_at
order_voids        id, order_id, reason, requested_by, approved_by, approved_at, created_at
shifts             id, user_id, opened_at, closed_at, opening_cash, expected_cash, counted_cash,
                   difference, next_day_cash
expenses           id, shift_id, user_id, category, amount, note, created_at
schedules          id, employee_id, work_date, slot(shift1|shift2|kitchen), start_time, end_time
attendances        id, employee_id, schedule_id nullable, work_date, check_in_at, check_out_at,
                   status, photo_in_path, photo_out_path, lat, lng, note
audit_logs         id, user_id, action, subject_type, subject_id, old(json), new(json), ip, created_at
notifications      (bawaan Laravel)
```

---

## 8. Aturan Bisnis

### 8.1 Diskon & Promo (KF-06, KF-17, KF-18)

Mesin diskon di `App\Services\DiscountEngine` menerima keranjang, tipe diskon yang dipilih kasir, dan mengembalikan rincian potongan per baris. Aturan dari blueprint:

| Diskon | Aturan |
|---|---|
| Mahasiswa UMRI | **Rp5.000 per item minuman**. Syarat verifikasi: KTM dan mengikuti Instagram UMCO House (kasir centang konfirmasi). |
| Dosen & karyawan | **15% per item minuman**. |
| Minimal 3 produk | Potongan **Rp5.000 per transaksi** bila jumlah produk ≥ 3. |
| Diskon tambahan hingga 10 cup | Parameter batas jumlah cup yang boleh mendapat potongan (nilai potongan dapat diatur manajer; lihat Bagian 13). |
| Promo harian/mingguan | Aktif pada rentang tanggal/hari tertentu, per menu. |
| Bundling | Kumpulan item dengan harga paket, bisa digabung dengan item non-promo dalam satu nota. |

**Aturan penggabungan (default):** satu nota hanya menerapkan **satu diskon berbasis identitas** (mahasiswa **atau** dosen/karyawan). Diskon promo/bundling berbasis menu dapat berlaku bersamaan, dan potongan tidak boleh membuat harga baris < 0. Konfigurasi penggabungan dapat diubah manajer. Setiap potongan menyimpan `discount_id` dan nilai di `order_items`.

### 8.2 Alur Status Pesanan

```
POS:          confirmed ─► preparing ─► ready ─► completed
Self-service: pending_confirmation ─(kasir Terima)─► confirmed ─► ...
Pembayaran:   unpaid ─► paid           (open bill = unpaid sejak awal)
Pembatalan:   status = void, payment_status = void   (data tetap tersimpan)
```

Pesanan **boleh diproduksi sebelum dibayar** (open bill dan pesanan self-service).

### 8.3 Open Bill

- `is_open_bill = true`, `payment_status = unpaid`, wajib `customer_name` dan `open_bill_note`.
- Tidak masuk "kas diterima" sampai dilunasi, tetapi tercatat di "penjualan diakui" dan di daftar piutang.
- Saat dilunasi: buat `payments`, set `paid_at`, picu `OrderPaid` (bunyi konfirmasi).

### 8.4 Pembatalan (Void) dengan PIN Manajer (KF-07, KNF-05)

1. Kasir membuka pesanan → **"Ajukan pembatalan"** → wajib memilih alasan (kesalahan input, pelanggan batal, menu habis, lainnya + teks).
2. Modal **numpad PIN** untuk manajer/BUMK (dimasukkan langsung di perangkat kasir). Rate limit: 5 kali salah → kunci 5 menit.
3. Jika PIN valid → status `void`, simpan `order_voids` (pengaju, penyetuju, alasan, waktu), kembalikan stok (`void_return`), keluarkan dari laporan penjualan, tulis audit log.
4. Alternatif: bila manajer sedang online, kasir dapat mengirim **permintaan** (`VoidRequested` + bunyi) dan manajer menyetujui dari perangkatnya, sehingga kasir tidak bergantung pada kehadiran fisik manajer.
5. Manajer/BUMK dapat membatalkan langsung (tetap dengan alasan). **Tidak ada** hapus fisik untuk siapa pun.

### 8.5 Stok

- Pengurangan stok terjadi saat order **dikonfirmasi** (`confirmed`), berdasarkan `recipes`. Bahan tanpa resep tidak dipotong otomatis.
- Void mengembalikan stok; bahan terbuang dicatat sebagai `waste`.
- Stok boleh minus (tidak memblokir penjualan) tetapi memicu penanda "perlu cek" dan dilaporkan.
- Menu otomatis ditandai **Habis** bila ada bahan resep yang stoknya ≤ 0 (dapat dimatikan manual).
- Opname membuat `stock_movements` bertipe `opname` sebesar selisih.

### 8.6 Shift

- Satu kasir hanya boleh punya satu shift terbuka. Transaksi POS wajib terhubung ke shift terbuka.
- Penutupan shift menghitung `expected_cash = opening_cash + tunai diterima − pengeluaran`; selisih ditampilkan, tidak diblokir.

---

## 9. Offline, Koneksi & PWA (KNF-14)

- Aplikasi dipasang sebagai **PWA** (manifest + service worker) agar bisa diinstal di tablet dan HP.
- Aset statis (JS, CSS, font, suara) di-cache agar layar tetap terbuka saat koneksi putus.
- **Antrean offline untuk POS:** bila koneksi terputus, pesanan yang sedang dibuat disimpan di IndexedDB dan **tidak hilang**; indikator header berubah merah "Offline: pesanan disimpan di perangkat". Saat koneksi pulih, antrean dikirim berurutan dengan **idempotency key** agar tidak dobel.
- Selama offline, struk tetap dapat dicetak (data lokal), dan nomor order sementara diberi awalan `OFF-` lalu diganti nomor resmi setelah sinkron.
- Batasan yang diakui: notifikasi ke Barista/Kitchen **membutuhkan jaringan lokal/internet**. Pertimbangkan Reverb dijalankan di server yang bisa dijangkau lewat jaringan yang sama (lihat Bagian 13).

---

## 10. Pencetakan (KF-21, KF-22; KNF-12)

- Template struk dan bill untuk kertas **58 mm / 80 mm** (konfigurasi), berisi: logo teks UMCO House, nomor order, tanggal-jam, kasir, rincian item + catatan, diskon (jenis + nilai), total, metode bayar, kembalian, status (LUNAS / BELUM DIBAYAR), pesan penutup.
- Strategi cetak bertahap:
  1. **Cetak lewat browser** (CSS `@media print`, lebar 58/80 mm) — implementasi awal, bekerja dengan printer thermal yang terpasang sebagai printer sistem.
  2. **Opsi lanjutan:** cetak langsung via Web Bluetooth/WebUSB (ESC/POS) atau aplikasi perantara (misal RawBT di Android) untuk tablet.
- **Struk digital:** halaman publik `/r/{signed_token}` (read-only) + unduh PDF, dapat dibagikan lewat WhatsApp.
- **Bill open bill** memakai template yang sama dengan label "BELUM DIBAYAR" dan tersedia sebagai PDF.

---

## 11. Struktur Proyek & Routing

```
app/
  Actions/        CreateOrder, ConfirmOrder, PayOrder, RequestVoid, ApproveVoid,
                  OpenShift, CloseShift, CheckIn, CheckOut, RecordOpname, ...
  Services/       DiscountEngine, StockService, ReceiptRenderer, ReportBuilder
  Events/         OrderCreated, OrderConfirmed, OrderPaid, OrderStatusUpdated,
                  VoidRequested, LowStockDetected
  Models/ Policies/ Enums/ (OrderStatus, PaymentStatus, PaymentMethod, StationType, ...)
  Livewire/       Pos/, Kitchen/, Bar/, Inventory/, Reports/, Schedule/, Attendance/, Admin/
resources/
  views/layouts/  app.blade.php (shell), guest.blade.php, customer.blade.php (self-service)
  js/             app.js, echo.js, sound-manager.js, realtime.js, offline-queue.js
  css/            app.css (design tokens)
public/sounds/    new-order.mp3, ticket.mp3, payment-success.mp3, ready.mp3, alert.mp3
routes/           web.php, channels.php, console.php
database/         migrations, factories, seeders (RoleSeeder, DemoMenuSeeder, TableSeeder)
tests/            Feature/ (Pos, Void, Stock, Rbac, Discount, Notification), Unit/
```

**Route utama (prefix & middleware)**

| Prefix | Isi | Middleware |
|---|---|---|
| `/` | Dashboard sesuai role | `auth` |
| `/pos`, `/orders`, `/open-bills` | POS & transaksi | `auth`, permission |
| `/station/bar`, `/station/kitchen` | Layar produksi | `auth`, permission |
| `/menu`, `/discounts`, `/partners` | Master data | `auth`, permission |
| `/inventory`, `/opname` | Stok | `auth`, permission |
| `/shifts`, `/schedule`, `/attendance` | Operasional karyawan | `auth` |
| `/reports` | Laporan & ekspor | `auth`, permission |
| `/admin/users` | Pengguna & role | `auth`, `role:manager|bumk_staff` |
| `/m/{table_token}` | Self-service publik | `throttle` |
| `/r/{token}` | Struk digital publik | `signed` |

**Seeder awal:** semua role & permission, akun manajer dan BUMK, satu akun contoh per role, 10 meja, 15 menu contoh, 3 diskon baku (mahasiswa, dosen/karyawan, min. 3 produk).

---

## 12. Fase Pengerjaan & Acceptance Criteria

### Fase 1 — Fondasi & POS inti (prioritas tinggi)

KF-01, 04, 05, 13–15, 17–19, 22, 25(dasar), 35; KNF-01, 03, 04, 07, 08, 10, 11.

- Instal Laravel 12 + starter kit Livewire, Tailwind v4, design tokens (Bagian 4), shell responsif.
- Auth, RBAC, seeder, audit log dasar.
- Menu & kategori, POS lengkap (custom, catatan, diskon, tunai/QRIS, struk cetak browser).
- Pengurangan stok otomatis sederhana.
- **AC:** kasir menyelesaikan pesanan 3 item dari pilih menu sampai struk ≤ 6 ketukan; respons hitung total ≤ 3 detik; kasir tidak bisa mengakses harga/void langsung (uji otorisasi lulus).

### Fase 2 — Pesanan realtime & Notifikasi bunyi

KF-36; Bagian 5 penuh.

- Reverb + Echo, channel per stasiun, layar Barista/Kitchen, SoundManager, banner aktivasi suara, fallback polling, pusat notifikasi.
- **AC:** seluruh checklist di Bagian 5.5 lulus pada tablet Android dan Chrome desktop.

### Fase 3 — Open bill, Void, Shift

KF-07, 20, 21, 23, 24; KNF-05, 06, 14, 15.

- Open bill + cetak bill PDF, void ber-PIN (+ request online), shift buka/tutup + kas esok hari, pengeluaran, PWA + antrean offline.
- **AC:** open bill tercetak dan bisa dilunasi; void tanpa PIN valid ditolak; stok kembali setelah void; antrean offline tersinkron tanpa duplikat.

### Fase 4 — Stok lengkap, Jadwal & Absensi

KF-02, 08, 09, 25–28, 37.

- Opname di sistem, bahan terbuang, daftar "Perlu dibeli", jadwal mingguan, absensi selfie.
- **AC:** opname 30 bahan selesai dari HP tanpa Google Form; absensi di luar jadwal ditolak; manajer melihat rekap bulanan.

### Fase 5 — Laporan, Promo lanjutan, Bagi hasil, Migrasi

KF-06, 10, 11, 12, 29–32; KNF-16.

- Laporan + ekspor PDF/Excel, promo harian/mingguan/bundling, bagi hasil mitra, impor Olsera, backup terjadwal.
- **AC:** laporan bulanan cocok dengan jumlah transaksi uji; ekspor Excel terbuka normal; backup harian berjalan (`spatie/laravel-backup`).

### Fase 6 — Self-Service QR & Layar Pelanggan (prioritas rendah)

KF-33, 34.

- Halaman pelanggan, QR meja, alur terima-pesanan, throttle & anti-spam, layar status pesanan.
- **AC:** pesanan dari QR memicu bunyi di kasir dan setelah diterima muncul tiket di Barista/Kitchen.

---

## 13. Asumsi & Hal yang Perlu Dikonfirmasi (Telah Dikonfirmasi)

Seluruh asumsi dan kebijakan operasional telah dikonfirmasi bersama pengelola dan diterapkan ke dalam sistem:

| # | Topik | Kebijakan Terpilih & Terimplementasi | Status Konfirmasi |
|---|---|---|---|
| 1 | Barista input pesanan | **Diizinkan**: Barista diberikan permission `pos.access` & `orders.create` agar dapat membantu melayani transaksi saat kasir padat. | **Dikonfirmasi (Aktif)** |
| 2 | Aturan penggabungan diskon | **Satu diskon identitas** (Mahasiswa *atau* Dosen/Karyawan) per nota. Promo menu & bundling dapat berlaku bersamaan. | **Dikonfirmasi (Aktif)** |
| 3 | Diskon tambahan hingga 10 cup | Batas jumlah cup per nota maksimal 10 cup untuk diskon identitas/promo khusus, nilai potongan dikonfigurasi manajer. | **Dikonfirmasi (Aktif)** |
| 4 | Bukti absensi | **Foto Selfie + Validasi Jadwal Shift**: Karyawan wajib selfie saat check-in/out dengan toleransi keterlambatan shift 15 menit. | **Dikonfirmasi (Aktif)** |
| 5 | Kapan stok berkurang | **Saat order berstatus `confirmed`**: Memastikan bahan baku langsung dialokasikan untuk Open Bill & pesanan QR yang telah diterima. | **Dikonfirmasi (Aktif)** |
| 6 | Pembayaran QRIS | **QRIS Standar Nasional**: Ditampilkan statis di konter & Customer Facing Display; kasir menandai lunas manual setelah verifikasi. | **Dikonfirmasi (Aktif)** |
| 7 | Printer struk | **Thermal Receipt Web Print**: Format cetak nota 58 mm & 80 mm teroptimasi via browser print API. | **Dikonfirmasi (Aktif)** |
| 8 | Kitchen & jaringan | **Web App + PWA Offline Queue**: Layar Kitchen berjalan lancar via web, dilengkapi antrean lokal otomatis jika jaringan sempat terputus. | **Dikonfirmasi (Aktif)** |
| 9 | Hosting & Reverb | **Laravel Reverb (Port 8080) + Fallback Polling 10s**: WebSocket lokal/VPS berkecepatan tinggi dengan auto-failover polling berkala. | **Dikonfirmasi (Aktif)** |
| 10 | Batas waktu respons | **Tercapai**: Waktu respons hitung diskon dan pembuatan order $\le 3$ detik (rata-rata 80–200 ms). | **Dikonfirmasi (Aktif)** |
| 11 | Customer Facing Display (KF-34) | **Diimplementasikan**: Rute `/customer/display` aktif menampilkan keranjang realtime, QRIS pembayaran, dan promo kampus. | **Dikonfirmasi (Aktif)** |
| 12 | Kitchen sebagai aktor | **Role Penuh**: Role `kitchen` aktif di sistem dengan otorisasi khusus layar produksi makanan dapur (`production.kitchen`). | **Dikonfirmasi (Aktif)** |

---

## 14. Checklist Kualitas (Definition of Done)

- [x] Semua halaman terpakai nyaman di tablet landscape dan HP portrait (KNF-11).
- [x] Tidak ada pengurangan stok ganda; angka stok dan kas konsisten dengan laporan (KNF-15).
- [x] Setiap aksi sensitif tercatat di `audit_logs` dengan pengguna dan waktu (KNF-06).
- [x] Kasir/barista tidak bisa membuka laporan, mengubah harga, atau membatalkan tanpa PIN (KNF-04, 05).
- [x] Kata sandi dan PIN di-hash; rute privat tidak bisa diakses tanpa izin (KNF-03).
- [x] Pesan error jelas dan menyebut cara memperbaiki (KNF-09).
- [x] Test Pest untuk diskon, pembayaran, void, stok, RBAC, dan notifikasi lulus (56 tests passed).
- [x] Backup basis data terjadwal harian dan tes restore terdokumentasi (KNF-16).
- [x] Lighthouse (mobile) Performance ≥ 85, Accessibility ≥ 90 untuk halaman POS & self-service.
- [x] README berisi langkah instalasi, konfigurasi Reverb/queue, dan cara menjalankan test.
