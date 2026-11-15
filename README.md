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
- docs/ – Documentation (API, ERD)

## Run the backend
Requirements: PHP 8.3+, Composer, MySQL (Laragon recommended)

cd backend
composer install
copy .env.example .env
php artisan key:generate
(set DB_DATABASE in .env, then create the database)
php artisan migrate
php artisan serve