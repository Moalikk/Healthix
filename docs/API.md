# QuickCare API

Base URL (local): http://127.0.0.1:8000/api

Semua endpoint bertanda "Bearer token" memakai header `Authorization: Bearer <token>` dan `Accept: application/json`.

## Batas keamanan
- Login: maks 5 percobaan per menit per email + IP. Lebih dari itu: 429 `{ "message": "Too many login attempts. Try again in N seconds." }`
- Register: maks 10 per menit per IP.
- POST /appointments: maks 10 per menit per user.
- Token berlaku 24 jam. Setelah itu 401 dan user harus login ulang.
- Semua batas bisa diubah lewat `.env` (lihat docs/LOADTEST.md).

## GET /departments
Returns the list of departments with their hospital name.

Response 200:
```json
{
  "data": [
    {
      "id": 1,
      "name": "General Medicine",
      "description": "First check-up and common complaints. Not sure where to go? Start here.",
      "common_conditions": "fever, cough, cold, dizziness, fatigue",
      "hospital_name": "General Hospital Jakarta"
    }
  ]
}
```

## GET /hospitals
Returns active hospitals. Optional query: `city` (partial match).

Example: /hospitals?city=Jakarta

Response 200:
```json
{
  "data": [
    {
      "id": 1,
      "name": "General Hospital Jakarta",
      "type": "general_hospital",
      "address": "Jl. Contoh No. 1",
      "city": "Jakarta Pusat",
      "image_url": null
    }
  ]
}
```

## GET /doctors
Dokter aktif beserta nama departemen. Query opsional:
- `department_id`
- `date` (format `YYYY-MM-DD`): tandai dokter yang cuti pada tanggal itu. Format salah: 422.

Example: /doctors?department_id=2&date=2026-11-22

Response 200:
```json
{
  "data": [
    {
      "id": 2,
      "name": "dr. Siti Aminah",
      "specialization": "Dermatologist",
      "photo_url": null,
      "department_name": "Dermatology",
      "is_available": false,
      "leave_reason": "Annual leave"
    }
  ]
}
```
Tanpa `date`, semua dokter `is_available: true`. Jika cuti tanpa alasan, `leave_reason` bernilai "On leave".

## POST /register
Membuat akun citizen baru dan langsung mengembalikan token.

Body:
```json
{ "name": "Test Citizen", "email": "test@example.com", "password": "password123" }
```

Response 201:
```json
{
  "user": { "id": 1, "name": "Test Citizen", "email": "test@example.com", "role": "citizen" },
  "token": "1|xxxxxxxx"
}
```

## POST /login
Body: `email`, `password`. Response 200 sama seperti register.
Jika salah: 401 `{ "message": "Invalid email or password" }`

## GET /me
Butuh header `Authorization: Bearer <token>`. Mengembalikan data user yang sedang login.
Tanpa token: 401.

## POST /logout
Butuh Bearer token. Token dihapus, sehingga tidak bisa dipakai lagi.

## GET /staff/appointments
Khusus staff (Bearer token, role `staff`). Daftar semua permohonan, urgent di atas.
Query opsional: `date`, `status`, `department_id`.

## PATCH /staff/appointments/{id}/confirm
Body: `doctor_id` (wajib, harus dokter di departemen yang sama), `priority` (`routine`/`urgent`), `staff_note`.
Status `pending` menjadi `confirmed`, nomor antrean dibuat otomatis.
Aturan (422 jika dilanggar):
- status harus `pending`
- dokter harus aktif dan satu departemen dengan permohonan
- dokter tidak boleh cuti pada tanggal kunjungan (tabel `doctor_leaves`)

Jika gagal, status tetap `pending` dan citizen tidak mendapat notifikasi.
Jika berhasil, citizen mendapat notifikasi.

## PATCH /staff/appointments/{id}/decline
Body: `staff_note` (wajib, alasan penolakan). `pending` menjadi `declined`. Citizen mendapat notifikasi.

## PATCH /staff/appointments/{id}/call
`confirmed` menjadi `called`, waktu panggilan dicatat. Citizen mendapat notifikasi.

Aturan telat: pasien `called` lebih dari 10 menit otomatis kembali ke `confirmed` dengan nomor antrean terakhir dan `skip_count` +1. Setelah 2 kali skip, status `missed`. Dicek saat daftar permohonan dibuka.

## PATCH /staff/appointments/{id}/complete
`called` menjadi `completed`.

## GET /appointments
Butuh Bearer token. Daftar permohonan milik user yang login (terbaru dulu).

Response 200:
```json
{ "data": [ { "id": 1, "appointment_date": "2026-11-22", "status": "pending", "queue_number": null, "department_name": "Dermatology", "hospital_name": "Sample General Hospital", "doctor_name": null } ] }
```

## POST /appointments
Butuh Bearer token. Membuat permohonan baru dengan status `pending`.

Body:
```json
{ "department_id": 2, "appointment_date": "2026-11-22", "complaint": "kulit gatal" }
```
- `department_id`: wajib, harus ada
- `appointment_date`: wajib, hari ini atau setelahnya
- `complaint`: opsional, maks 1000 karakter

Response 201: `{ "data": { ...permohonan } }`. Validasi gagal: 422.

Permohonan ganda ditolak (422): user yang sama, departemen yang sama, tanggal yang sama, selama statusnya masih `pending`, `confirmed` atau `called`. Jika sebelumnya `declined`, `completed` atau `missed`, boleh mengajukan lagi.

## GET /notifications
Butuh Bearer token. 30 notifikasi terbaru milik user.

Response 200:
```json
{
  "data": [
    { "id": 1, "appointment_id": 1, "title": "Request confirmed", "message": "...", "read_at": null, "created_at": "2026-11-21 10:00:00" }
  ],
  "unread_count": 1
}
```
Judul yang dipakai: Request confirmed, Request declined, It's your turn, Moved to the end of the queue, Request marked as missed.

## PATCH /notifications/{id}/read
Butuh Bearer token. Menandai satu notifikasi sudah dibaca. Bukan milik user atau tidak ada: 404.

## PATCH /notifications/read-all
Butuh Bearer token. Menandai semua notifikasi user sudah dibaca.

## GET /health
Cek apakah server dan database hidup. Tanpa login.

Response 200:
```json
{ "status": "ok", "database": "ok", "time": "2026-11-19T13:20:00+07:00" }
```
Jika database mati: status 503, `"status": "degraded"`, `"database": "down"`.

## POST /suggest-department
Butuh Bearer token. Menyarankan departemen dari keluhan (bukan diagnosis).

Body: `{ "message": "kulit saya gatal" }` (3-500 karakter)

Response 200:
```json
{
  "data": {
    "emergency": false,
    "department_id": 2,
    "department_name": "Dermatology",
    "hospital_name": "Sample General Hospital",
    "matched": ["gatal", "kulit"],
    "confidence": "high",
    "reason": "Matched: gatal, kulit",
    "disclaimer": "This is a suggestion only, not a medical diagnosis."
  }
}
```
Jika terdeteksi kata darurat: `emergency: true`, `department_id: null`, dan `reason` berisi arahan ke 112 / IGD.