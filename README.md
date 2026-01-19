## Bulk Import Engine (Laravel 11, Async + Streaming)

### Dokumentasi (Sesuai Syarat)

#### Requirement Server
- PHP >= 8.2
- Composer 2.x
- Ekstensi PHP: `pdo_mysql` atau `pdo_sqlite`, `mbstring`, `openssl`, `json`, `tokenizer`, `ctype`
- Database: MySQL/MariaDB atau PostgreSQL (SQLite untuk lokal dev juga bisa)
- Queue driver: `database` (mudah) atau `redis` (disarankan produksi)

#### Cara Setup Project
```bash
# Install dependencies
composer install

# Salin file environment
# Windows (PowerShell)
Copy-Item .env.example .env
# macOS/Linux
cp .env.example .env

# Generate app key
php artisan key:generate

# Konfigurasi DB di .env, lalu migrasi
php artisan migrate
```

#### Cara Menjalankan Background Worker (Queue)
```bash
# Jalankan worker (umum)
php artisan queue:work --queue=default --sleep=1 --tries=3

# Opsi untuk file sangat besar (meningkatkan batas memori/timeout)
php -d memory_limit=1024M artisan queue:work --queue=default --sleep=1 --tries=1 --timeout=3600 --memory=512
```

> Pastikan `.env` memakai driver non-`sync`, misal `QUEUE_CONNECTION=database` atau `QUEUE_CONNECTION=redis`.

Mesin impor CSV skala besar (100.000–1.000.000 baris) untuk data user (name, email, address) dengan pemrosesan streaming (hemat memori), berjalan asynchronous via Queue, dan endpoint progress real‑time. Termasuk halaman web sederhana untuk upload dan memantau progres.

### Fitur Utama
- Streaming CSV: baca baris‑per‑baris via `fopen()`/`fgetcsv()` (tanpa load seluruh file ke RAM).
- Asynchronous Queue: request upload non‑blocking; job berjalan di background (`ShouldQueue`).
- Progress Tracking: simpan `total_rows`, `processed_rows`, dan hitung `percent`.
- Batch Upsert: performa tinggi dengan `User::upsert()` per batch.
- Web UI: halaman `/import` untuk unggah CSV dan polling progress.
- Clean Architecture + Strict Types: controller tipis, Form Request untuk validasi, service + job untuk logika.

### Persyaratan
- PHP 8.2+
- Composer 2.x
- Database: MySQL/MariaDB atau PostgreSQL
- Queue Driver: `database` (mudah) atau `redis` (disarankan produksi)
- Ekstensi umum Laravel: `mbstring`, `openssl`, `pdo` (lainnya sesuai environment)

### Setup Cepat
```
composer install
cp .env.example .env
php artisan key:generate

# Konfigurasikan koneksi database di .env
php artisan migrate

# Pilih driver queue (WAJIB non-sync)
# Opsi A: Database queue (paling mudah)
php artisan queue:table
php artisan migrate

# Set di .env
# QUEUE_CONNECTION=database

# Opsi B: Redis queue (disarankan produksi)
# Install Redis dan set di .env
# QUEUE_CONNECTION=redis

# Jalankan worker (terminal terpisah)
php artisan queue:work --queue=default --sleep=1 --tries=3

# Jalankan server aplikasi
php artisan serve
```

### Web UI
- Buka `http://127.0.0.1:8000/import`
- Pilih file CSV (header: `name,email,address`), upload.
- Progres akan tampil (bar, processed/total, percent) dan selesai saat status `done`.

### API
- POST `/api/import-users`
	- Form: `multipart/form-data`
	- Field: `file` (mimes: csv)
	- Response:
		```json
		{ "status": "processing", "import_id": 123 }
		```

- GET `/api/import-users/{id}`
	- Response:
		```json
		{
			"id": 123,
			"status": "processing|done",
			"processed_rows": 45000,
			"total_rows": 100000,
			"percent": 45.0
		}
		```

Contoh cURL:
```
curl -F "file=@users.csv" http://127.0.0.1:8000/api/import-users
curl http://127.0.0.1:8000/api/import-users/123
```

### Format CSV
Baris pertama wajib header: `name,email,address`
Contoh: lihat `users.csv` atau `big_users.csv` di root repo.

### Arsitektur & Alur
- Controller: `ImportUserController` hanya menerima request dan mengembalikan JSON.
- Validasi: `ImportUserRequest` memastikan file CSV valid (mimes: csv).
- Service: `ImportUserService` menyimpan file ke storage dan membuat record progress.
- Job: `ImportUsersJob` (queue) membaca CSV secara streaming, batch upsert ke DB, dan update progress.
- Model: `ImportProgress` menyimpan status/angka progres.

File terkait:
- Service: `app/Services/ImportUserService.php`
- Job: `app/Jobs/ImportUsersJob.php`
- Controller: `app/Http/Controllers/ImportUserController.php`
- Request: `app/Http/Requests/ImportUserRequest.php`
- Model: `app/Models/ImportProgress.php`, `app/Models/User.php`
- Routes API: `routes/api.php` (import + status)
- Routes Web: `routes/web.php` (halaman `/import`)
- UI: `resources/views/import.blade.php`

### Kesesuaian Dengan Challenge
- Wajib Laravel 10/11: Menggunakan Laravel 11 (lihat `composer.json`).
- Strict Typing: `declare(strict_types=1);` diterapkan pada file inti aplikasi.
- Type Hinting: method dan properti diberi hints seperlunya, model mengikuti aturan Eloquent (tanpa typed property yang bentrok dengan base `Model`).
- Clean Code:
	- Controller ramping; logika bisnis di Service dan Job.
	- Validasi via Form Request (bukan manual di controller).
- No UI libraries besar: hanya Blade + Tailwind CDN sederhana.
- Response API konsisten JSON.
- Streaming (hemat memori): `fopen/fgetcsv` baris‑per‑baris + batch upsert.
- Async & Non‑blocking: job `ShouldQueue` + worker queue.
- Stability: endpoint status progres dan pemrosesan batch mencegah OOM.

Catatan Node.js Streams:
- Instruksi menekankan streaming (hemat memori). Karena proyek wajib Laravel, kami memakai streaming I/O native PHP yang setara karakteristiknya dengan Node Streams (incremental, low memory). Jika diwajibkan literal Node, dapat ditambah microservice Node terpisah yang mengirim batch ke Laravel (opsional).

### Deviasi/Justifikasi dari Syarat Seleksi
- Node.js Streams (di syarat) vs Laravel (wajib framework):
	- Syarat menyebut “Gunakan Node.js Streams”, namun juga mewajibkan Laravel (PHP). Kami mengimplementasikan streaming dengan I/O PHP (`fopen`/`fgetcsv`) yang memiliki sifat teknis setara (membaca chunk/baris, tidak load full file, low memory). Jika literal Node wajib, solusi alternatif adalah microservice Node kecil untuk parsing streaming yang mengirim batch ke Laravel via Redis/HTTP. Pendekatan saat ini menjaga konsistensi dengan syarat “Wajib Laravel” sambil memenuhi tujuan performa.
- Strict typing di setiap file PHP:
	- Diterapkan di file inti aplikasi (controllers, requests, services, jobs, models, routes). Tidak diterapkan pada folder `vendor/` (bawaan dependency) dan beberapa file scaffold/bootstrapping Laravel (mis. sebagian `config/`, `bootstrap/`, `public/index.php`) untuk menjaga kompatibilitas dan menghindari modifikasi pada file kerangka kerja.
	- Pada model Eloquent, typed property seperti `$table`, `$fillable`, `$hidden`, `$casts` sengaja TIDAK digunakan karena akan bertentangan dengan definisi `Illuminate\Database\Eloquent\Model` (menyebabkan Fatal Error). Sebagai gantinya dipakai PHPDoc tipe agar tetap jelas tanpa melanggar kontrak inheritance.
- BullMQ/Worker Threads (di syarat contoh Node) vs Laravel Queue:
	- Asynchronous processing diwujudkan lewat Laravel Queue (driver `database`/`redis`) yang fungsinya ekuivalen dengan BullMQ: mendorong job ke antrian, diproses worker terpisah, non‑blocking untuk request upload.
- Menyalakan worker dari web request:
	- Tidak dilakukan demi keamanan dan stabilitas (daemon harus dijalankan dan diawasi oleh sistem/layanan, bukan melalui HTTP request yang short‑lived). Disediakan panduan menjalankan worker melalui terminal, Windows Service (NSSM), atau Task Scheduler.

### Menjalankan Worker Queue (Windows)
Disarankan worker berjalan sebagai proses terawasi (bukan dari web request):

1) Dev / Demo (terminal):
```
php artisan queue:work --queue=default --sleep=1 --tries=3 --timeout=120 --memory=256
```

2) Windows Service via NSSM (stabil):
```
nssm install laravel-queue "C:\Path\to\php.exe" "artisan queue:work --queue=default --sleep=1 --tries=3 --timeout=120 --memory=256"
nssm set laravel-queue AppDirectory "C:\project magang\seleksi teknis\bulk-import-engine"
nssm set laravel-queue Start SERVICE_AUTO_START
nssm start laravel-queue
```

3) Task Scheduler (tanpa tool tambahan):
- Action: Program `C:\Path\to\php.exe`
- Arguments: `artisan queue:work --queue=default --sleep=1 --tries=3 --timeout=120 --memory=256`
- Start in: `C:\project magang\seleksi teknis\bulk-import-engine`

> Pastikan `.env` tidak menggunakan `QUEUE_CONNECTION=sync`.

### Tips Performa & Stabilitas
- Gunakan Redis untuk beban besar (`QUEUE_CONNECTION=redis`).
- Sesuaikan `batchSize` di job (default 500) dengan resource DB.
- Perbesar `--memory` dan `--timeout` worker bila file 500MB+.
- Pastikan index unik `users.email` untuk mendukung `upsert` efektif.
 - Untuk impor 1 juta baris, set pengaturan timeout agar job tidak diputus:
	 - Jalankan worker dengan opsi lebih besar:
		 ```
		 php artisan queue:work --queue=default --sleep=1 --tries=1 --timeout=3600 --memory=512
		 ```
	 - Tambahkan di `.env` agar `retry_after` cukup besar (database/redis):
		 ```
		 DB_QUEUE_RETRY_AFTER=3600
		 REDIS_QUEUE_RETRY_AFTER=3600
		 ```
	 - Job sudah disetel `public $timeout = 3600;` dan `public $tries = 1;` untuk mencegah duplikasi.

### Troubleshooting
- Progres tidak jalan? Cek worker aktif dan `.env` driver queue.
- Error typed property Eloquent: jangan ketik properti yang didefinisikan base `Model` (gunakan PHPDoc, sudah diterapkan di repo ini).
- 500 saat upload: pastikan `post_max_size` dan `upload_max_filesize` di `php.ini` cukup besar.

### Pengujian
```
php artisan test
```

### Lisensi
MIT (mengikuti lisensi Laravel dan dependency terkait).
#   s e l e k s i - m a g a n g 

 
 
