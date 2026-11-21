# ERD – QuickCare

Diagram basis data (MySQL). Gambar: [`ERD.png`](ERD.png).

```mermaid
erDiagram
    HOSPITALS ||--o{ DEPARTMENTS : has
    DEPARTMENTS ||--o{ DOCTORS : has
    DOCTORS ||--o{ DOCTOR_LEAVES : "takes leave"
    USERS ||--o{ APPOINTMENTS : requests
    DEPARTMENTS ||--o{ APPOINTMENTS : receives
    DOCTORS |o--o{ APPOINTMENTS : "assigned to"
    USERS ||--o{ USER_NOTIFICATIONS : receives
    APPOINTMENTS |o--o{ USER_NOTIFICATIONS : about

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role "citizen | staff"
    }
    HOSPITALS {
        bigint id PK
        string name
        string type
        string address
        string city
        string image_url
        bool is_active
    }
    DEPARTMENTS {
        bigint id PK
        bigint hospital_id FK
        string name
        text description
        text common_conditions
        text keywords
    }
    DOCTORS {
        bigint id PK
        bigint department_id FK
        string name
        string specialization
        string photo_url
        bool is_active
    }
    DOCTOR_LEAVES {
        bigint id PK
        bigint doctor_id FK
        date leave_date "unique with doctor_id"
        string reason
    }
    APPOINTMENTS {
        bigint id PK
        bigint user_id FK
        bigint department_id FK
        bigint doctor_id FK "null until confirmed"
        date appointment_date
        text complaint
        string priority "normal | urgent"
        string status "pending confirmed called completed declined missed"
        text staff_note
        int queue_number
        datetime called_at
        int skip_count
    }
    USER_NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        bigint appointment_id FK
        string title
        text message
        datetime read_at
    }
```

## Catatan

- `users.role` bernilai `citizen` atau `staff`.
- `appointments.doctor_id` kosong sampai staf mengonfirmasi permintaan.
- `appointments.status`: pending → confirmed → called → completed. Cabang lain: declined (ditolak staf) dan missed (telat/skip 2 kali).
- Telat 10 menit setelah dipanggil: status kembali `confirmed`, nomor antrian jadi terakhir, `skip_count` bertambah.
- `doctor_leaves`: satu dokter hanya boleh punya satu baris cuti per tanggal (unique `doctor_id` + `leave_date`). Staf tidak bisa mengonfirmasi dengan dokter yang cuti di tanggal kunjungan.
- Tabel bawaan Laravel (`personal_access_tokens`, `sessions`, `cache`, `jobs`, `migrations`) tidak digambar.
