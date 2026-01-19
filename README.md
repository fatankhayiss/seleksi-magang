# 🚀 Bulk Import Engine

### Laravel 11 • Async Queue • Streaming CSV • Real-Time Progress

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-%5E8.2-777BB3?logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/Queue-Redis%20%7C%20Database-44A833?logo=redis&logoColor=white" />
  <img src="https://img.shields.io/badge/Streaming-Low%20Memory-success" />
  <img src="https://img.shields.io/badge/License-MIT-blue" />
</p>

<p align="center">
<strong>High-performance CSV import engine</strong> untuk data skala besar  
(100.000 – 1.000.000 baris) dengan <b>streaming hemat memori</b>,  
<b>proses asynchronous</b>, dan <b>progress real-time</b>.
</p>

---

## ✨ Highlight Utama

✅ Streaming CSV (tanpa load file ke RAM)
✅ Asynchronous Processing (Laravel Queue)
✅ Real-Time Import Progress
✅ Batch Upsert (performa tinggi)
✅ Clean Architecture + Strict Typing
✅ Web UI + REST API

---

## 🧠 Gambaran Singkat

**Bulk Import Engine** dirancang untuk kebutuhan **impor data besar** tanpa membuat server overload.

Alur singkat:

1. User upload CSV
2. Request langsung return (non-blocking)
3. Job diproses di background (queue)
4. File dibaca **baris-per-baris (streaming)**
5. Progress bisa dipantau via API / Web UI

---

## 🧰 Tech Stack

| Layer         | Teknologi               |
| ------------- | ----------------------- |
| Backend       | Laravel 11              |
| Language      | PHP 8.2+                |
| Queue         | Database / Redis        |
| Storage       | Local / Public          |
| UI            | Blade + Tailwind CDN    |
| Import Method | `fopen()` + `fgetcsv()` |

---

## 📁 Format CSV

Header **WAJIB**:

```
name,email,address
```

Contoh:

```
John Doe,john@mail.com,Jakarta
Jane Doe,jane@mail.com,Bandung
```

---

## 🖥️ Web UI

* URL: `http://127.0.0.1:8000/import`
* Fitur:

  * Upload CSV
  * Progress bar
  * Status realtime (processing / done)

---

## 🔌 REST API

### Upload File

**POST** `/api/import-users`

**Request**

* `multipart/form-data`
* Field: `file` (CSV)

**Response**

```json
{
  "status": "processing",
  "import_id": 123
}
```

### Cek Progress

**GET** `/api/import-users/{id}`

```json
{
  "id": 123,
  "status": "processing",
  "processed_rows": 45000,
  "total_rows": 100000,
  "percent": 45
}
```

---

## ⚙️ Setup Cepat

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Queue (WAJIB non-sync)

```env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

---

## 🧵 Worker (File Besar)

```bash
php artisan queue:work \
  --sleep=1 \
  --tries=1 \
  --timeout=3600 \
  --memory=512
```

---

## ▶️ Cara Menggunakan (Import via Web)

1) Jalankan Queue Worker (wajib, agar proses impor berjalan di background):
```bash
php artisan queue:work --queue=default --sleep=1 --tries=3
# Untuk file sangat besar, pertimbangkan:
php -d memory_limit=1024M artisan queue:work --queue=default --sleep=1 --tries=1 --timeout=3600 --memory=512
```

2) Jalankan Web Server Laravel:
```bash
php artisan serve
```

3) Buka halaman upload:
- `http://127.0.0.1:8000/import`

4) Pilih file CSV (header: `name,email,address`) lalu upload.

5) Pantau progres di halaman (bar & angka). Saat selesai, muncul banner sukses.

Catatan:
- Pastikan `.env` memakai driver queue non-sync (mis. `QUEUE_CONNECTION=database` atau `redis`).
- Jika memakai `redis`, pastikan server Redis berjalan sebelum `queue:work`.

## 🏗️ Arsitektur

```
Controller
   ↓
Form Request (Validation)
   ↓
Service (Save file + init progress)
   ↓
Queue Job (Streaming CSV + Batch Upsert)
   ↓
Progress API
```

### File Penting

```
app/
├─ Jobs/ImportUsersJob.php
├─ Services/ImportUserService.php
├─ Http/Controllers/ImportUserController.php
├─ Http/Requests/ImportUserRequest.php
├─ Models/ImportProgress.php
└─ Models/User.php
```

---

## 🧪 Testing

```bash
php artisan test
```

---

## ⚠️ Catatan Teknis Penting

* **Tidak menggunakan `fs.readFileSync` / load full file**
* Streaming menggunakan PHP native I/O (setara Node Streams)
* Typed property **tidak dipakai di Eloquent Model** (hindari fatal error)
* Worker **tidak dijalankan via HTTP request**

---

## 🧯 Troubleshooting

* Progress tidak jalan → pastikan queue worker aktif
* Upload gagal → cek `upload_max_filesize` & `post_max_size`
* Job timeout → naikkan `--timeout` & `retry_after`

---

## 📌 Quick Links

* Upload UI: `http://127.0.0.1:8000/import`
* Upload API: `POST /api/import-users`
* Status API: `GET /api/import-users/{id}`

---

## 📜 License

MIT License

---

<p align="center">
✨ Dibuat untuk seleksi magang & studi kasus sistem backend skala besar ✨
</p>
