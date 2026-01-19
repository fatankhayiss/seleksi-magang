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

* ✅ Streaming CSV (tanpa load file ke RAM)
* ✅ Asynchronous Processing (Laravel Queue)
* ✅ Real-Time Import Progress
* ✅ Batch Upsert (performa tinggi)
* ✅ Clean Architecture + Strict Typing
* ✅ Web UI + REST API

---

## 🧠 Gambaran Singkat

**Bulk Import Engine** dirancang untuk kebutuhan **impor data besar** tanpa membuat server overload.

**Alur singkat:**

1. User upload CSV
2. Request langsung return (non-blocking)
3. Job diproses di background (queue)
4. File dibaca baris-per-baris (streaming)
5. Progress dipantau via API / Web UI

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

```csv
name,email,address
```

Contoh:

```csv
John Doe,john@mail.com,Jakarta
Jane Doe,jane@mail.com,Bandung
```

---

## 📦 Sample Data (1 Juta Baris)

Karena keterbatasan GitHub (maksimal ±25MB per file), dataset CSV berukuran besar **tidak disertakan langsung di repository**.

### 🔹 Opsi 1 — Download Dataset Siap Pakai

Dataset contoh berisi **±1.000.000 baris data user**:

👉 **Download CSV (1M rows)**
[https://drive.google.com/file/d/1tWZZtV4t2OL8m6jXRCZTFpgWrrHgAQ0F/view?usp=sharing](https://drive.google.com/file/d/1tWZZtV4t2OL8m6jXRCZTFpgWrrHgAQ0F/view?usp=sharing)

**Langkah:**

1. Download file CSV
2. Buka `http://127.0.0.1:8000/import`
3. Upload file melalui Web UI

---

### 🔹 Opsi 2 — Generate Data Sendiri

Jika tidak ingin mengunduh file besar, Anda dapat meng-generate CSV sendiri.

Contoh struktur:

```csv
name,email,address
User 1,user1@mail.com,Jakarta
User 2,user2@mail.com,Bandung
```

Jumlah baris bebas (100K – 1M) sesuai kebutuhan testing.

> 💡 Pendekatan ini umum di industri untuk menjaga repository tetap ringan.

**Catatan:**

* Header CSV **WAJIB**: `name,email,address`
* File CSV besar diabaikan oleh Git (`.gitignore`)
* Fokus repo: **streaming, queue, dan stabilitas sistem**

---

## 🖥️ Web UI

* URL: `http://127.0.0.1:8000/import`
* Fitur:

  * Upload CSV
  * Progress bar
  * Status realtime

---

## 🔌 REST API

### Upload File

**POST** `/api/import-users`

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

## ▶️ Cara Menggunakan

```bash
php artisan serve
php artisan queue:work
```

Buka:
`http://127.0.0.1:8000/import`

---

## 🏗️ Arsitektur

```
Controller
  ↓
Form Request
  ↓
Service
  ↓
Queue Job (Streaming + Batch Upsert)
  ↓
Progress API
```

---

## 🧪 Testing

```bash
php artisan test
```

---

## ⚠️ Catatan Teknis

* Tidak load full file ke RAM
* Streaming PHP native (setara Node Streams)
* Worker tidak dijalankan via HTTP request

---

## 📜 License

MIT License

<p align="center">
✨ Dibuat untuk seleksi magang & studi kasus backend skala besar ✨
</p>
