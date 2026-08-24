# URL Shortener + Analytics Dashboard

Aplikasi pemendek URL dengan dashboard analytics real-time, dibangun sebagai project pembelajaran fundamental backend (bukan sekadar CRUD) — fokus ke encoding, caching, rate limiting, dan background job processing.

## Kenapa Project Ini Dibuat

Sebagian besar project portofolio biasanya berupa aplikasi CRUD dengan banyak fitur (manajemen data, dashboard admin, dsb). Project ini sengaja dibuat dengan scope kecil, tapi tiap bagiannya mendalami satu konsep fundamental yang sering muncul di technical interview maupun pekerjaan nyata:

- Bagaimana short URL (`domain.com/aB3`) dihasilkan secara efisien dan unik
- Bagaimana caching mengurangi beban database
- Bagaimana rate limiting mencegah penyalahgunaan API
- Bagaimana background job membuat response ke user tetap cepat, meski ada proses tambahan yang harus dijalankan

## Tech Stack

**Backend**
- Laravel 12 (PHP 8.3)
- MySQL — penyimpanan utama (links, link_clicks)
- Redis (via Predis) — caching, rate limiting, queue driver
- Laravel Queue + Worker — background job processing
- jenssegers/agent — parsing User-Agent (device & browser detection)

**Frontend**
- React + TypeScript (Vite)
- Recharts — visualisasi data (line chart & pie chart)
- Axios — komunikasi ke REST API
- Tailwind CSS

## Arsitektur & Alur Sistem

### 1. Membuat Short Link

```
POST /api/links
```

1. URL asli disimpan ke tabel `links`, menghasilkan auto-increment `id`
2. `id` di-encode ke Base62 (kombinasi 0-9, a-z, A-Z) menjadi `short_code`
3. `short_code` disimpan kembali ke record yang sama

Pendekatan ini dipilih dibanding "generate random string lalu cek collision di database", karena encode dari auto-increment ID menjamin keunikan tanpa perlu retry logic tambahan.

Endpoint ini dilindungi **rate limiting** (maksimal 10 request/menit per IP) menggunakan atomic counter Redis (`INCR` + `EXPIRE`), untuk mencegah spam pembuatan link.

### 2. Redirect

```
GET /{code}
```

1. `short_code` di-decode kembali menjadi `id`
2. `original_url` diambil menggunakan pola **cache-aside**: cek Redis dulu, baru query MySQL jika cache kosong (cache miss), lalu hasilnya disimpan ke Redis untuk request berikutnya
3. `click_count` di-update langsung (synchronous) di tabel `links`
4. Pencatatan detail klik (IP, referrer, device, browser) di-**dispatch sebagai background job**, bukan dijalankan langsung — supaya user tidak menunggu proses pencatatan analytics yang sebenarnya tidak relevan bagi mereka
5. User langsung di-redirect ke URL asli

### 3. Analytics Dashboard

```
GET /api/links/{id}/analytics
```

Mengembalikan data agregat menggunakan `GROUP BY`:
- Total klik
- Klik per hari
- Breakdown berdasarkan device (desktop/mobile/tablet)
- Breakdown berdasarkan browser

Data ini ditampilkan di dashboard React menggunakan line chart (tren klik harian) dan pie chart (breakdown device & browser).

## Konsep Fundamental yang Dipelajari

| Konsep | Penerapan di Project |
|---|---|
| Encoding/Decoding (Base62) | Mengubah ID numerik menjadi short-code yang reversible |
| Caching (cache-aside pattern) | Mengurangi beban query MySQL untuk data yang sering diakses tapi jarang berubah |
| Rate Limiting (fixed window) | Membatasi penyalahgunaan endpoint menggunakan Redis atomic increment |
| Queue & Background Job | Memisahkan proses non-kritis (pencatatan analytics) dari critical path request |
| Database Indexing | Composite index pada `link_clicks` untuk query filter by link & tanggal |
| Aggregate Query (GROUP BY) | Menghasilkan data ringkasan untuk kebutuhan dashboard |

## Setup Lokal

### Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Konfigurasi `.env`:
```env
DB_CONNECTION=mysql
DB_DATABASE=url_shortener
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

```bash
php artisan migrate
php artisan serve
```

Jalankan worker di terminal terpisah (wajib tetap aktif):
```bash
php artisan queue:work
```

### Frontend

```bash
cd url-shortener-frontend
npm install
npm run dev
```

## API Endpoints

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/links` | Membuat short link baru (rate limited: 10x/menit/IP) |
| GET | `/{code}` | Redirect ke URL asli + pencatatan klik |
| GET | `/api/links/{id}/analytics` | Data analytics agregat untuk sebuah link |
