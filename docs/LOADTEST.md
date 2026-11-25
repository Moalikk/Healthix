# Load test guide – QuickCare API

For testing performance of the backend. Base URL (local): http://127.0.0.1:8000/api

## 1. Prepare the data
From the `backend` folder:

```
php artisan migrate:fresh --seed
php artisan db:seed --class=LoadTestSeeder
```

This adds 100 citizens (300 pending appointments) on top of the demo data.
It is safe to run again: old load-test accounts are replaced.

| Account | Email | Password |
|---|---|---|
| Citizens | loadtest1@quickcare.test ... loadtest100@quickcare.test | password123 |
| Staff | staff@quickcare.test | password123 |

## 2. Run the server
`php artisan serve` handles one request at a time on Windows, so results will look slow under load.
For a fairer test, start it with several workers (PowerShell):

```
$env:PHP_CLI_SERVER_WORKERS=4; php artisan serve
```

Also set `APP_DEBUG=false` and `LOG_LEVEL=warning` in `.env`.

## 3. Important: login is slow on purpose
Login uses bcrypt (about 0.4 s each). Do not log in on every request.
Log in once per virtual user, keep the token, and send it as:

```
Authorization: Bearer <token>
Accept: application/json
```

## 3b. Rate limits (important)
The API now limits login (5/min per email), register (10/min per IP) and new requests (10/min per user). A load test will hit these and get 429.
For load testing, raise them in `backend/.env`, then restart the server:

```
LOGIN_RATE_LIMIT=1000
REGISTER_RATE_LIMIT=1000
BOOKING_RATE_LIMIT=1000
```

Remove these lines (or restore the defaults 5 / 10 / 10) before the demo.
Tokens expire after 24 hours (`SANCTUM_EXPIRATION` in minutes). A user also cannot create two active requests for the same department and date (422), so vary `department_id` and `appointment_date` in POST /appointments tests.

## 4. Endpoints to test

| Scenario | Method and path | Auth |
|---|---|---|
| Health check | GET /health | none |
| Browse | GET /hospitals, /departments, /doctors?department_id=1&date=2026-11-22 | none |
| Citizen: my requests | GET /appointments | citizen |
| Citizen: new request | POST /appointments `{"department_id":1,"appointment_date":"<today or later>","complaint":"test"}` | citizen |
| Citizen: notifications | GET /notifications (the frontend calls this every 10 s) | citizen |
| Citizen: suggestion | POST /suggest-department `{"message":"kulit saya gatal"}` | citizen |
| Staff: list | GET /staff/appointments | staff |
| Staff: confirm | PATCH /staff/appointments/{id}/confirm `{"doctor_id":1}` (doctor must be in the same department and not on leave) | staff |

Expected status: 200 for reads, 201 for POST /appointments, 401 without token, 403 for a citizen on /staff/*, 422 for invalid input.

## 5. Suggested load
- Light: 20 users, 1 minute
- Normal: 50 users, 2 minutes (mix: 70% reading, 20% new requests, 10% staff actions)
- Peak: 100 users, 2 minutes

Report: average and p95 response time, error rate, and the first load level where errors appear.

## 6. Things to know
- A new request must have `appointment_date` of today or later.
- Confirming the same appointment twice returns 422 (status must be `pending`).
- To reset everything: `php artisan migrate:fresh --seed`.