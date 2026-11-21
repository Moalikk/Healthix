# Healthix – QuickCare
Government Hospital Appointment & Queue Management System (student project).

## Team
- Muhammad Malik Firdaus – Project Manager, Front-End, System Analyst
- Laraib Arshad – Front-End
- Rekyan Noviandary Widyaningsih – API Integration, Testing, Network Performance
- M. Sabili Rizky Adyallah – Back-End, Database, API

## Structure
- frontend/ – HTML, CSS, JavaScript, Bootstrap
- backend/ – Laravel (API and database)
- docs/ – Documentation ([API](docs/API.md), [ERD](docs/ERD.md))

## Run the backend
Requirements: PHP 8.3+, Composer, MySQL (Laragon recommended)

```
cd backend
composer install
copy .env.example .env
php artisan key:generate
```
Set `DB_DATABASE` in `.env` and create that database, then:
```
php artisan migrate --seed
php artisan serve
```
`php artisan migrate:fresh --seed` resets the database and re-creates the demo data.
Check it works: http://127.0.0.1:8000/api/health

## Run the frontend
Open `frontend/login.html` with VS Code Live Server (backend must be running at http://127.0.0.1:8000).
The API address is set in `frontend/js/api.js` (`API_BASE`).

## Demo accounts
Password for both: `password123`

| Role | Email |
|---|---|
| Staff | staff@quickcare.test |
| Citizen | citizen@quickcare.test |

Demo data: 4 hospitals, 5 departments, 5 doctors. Two doctors are on leave tomorrow, to demo the leave rule.

## Main rules
- A citizen sends a request (date only); staff confirm it with a doctor, which creates the queue number.
- Staff cannot confirm with a doctor who is on leave on the visit date.
- A called patient who is late by more than 10 minutes goes to the end of the queue. After 2 skips the request is marked missed.
- Citizens get notifications (bell icon) when a request is confirmed, declined, called, skipped or missed.
- Department suggestion is a keyword match, not a diagnosis. Emergency words show a 112 / ER message.