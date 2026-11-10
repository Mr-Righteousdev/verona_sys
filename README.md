# Verona EWS: Adaptive Early Warning System for CBC competency gaps

Final-year project (St. Lawrence University, BAIT). Case study: Verona High School Mutundwe.

**New here? Read [docs/GUIDE.md](docs/GUIDE.md): the full walkthrough of the project, the code and the detection engine.**

Laravel 13 · Livewire 4 · Tailwind 4 · maryUI/daisyUI 5 · MariaDB/MySQL (SQLite locally).

## Run it

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate   # first time only
php artisan migrate:fresh --seed                   # demo school: 72 learners, 4 streams, one term
npm run build                                      # or `npm run dev` while editing
php artisan serve
```

Demo logins (password `password`): `admin@verona.test` (head teacher), `teacher@verona.test` (class teacher, S1 East), `student@verona.test`.

For MariaDB, create a database and user, then set `DB_CONNECTION=mariadb`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

Gap detection runs when scores are saved and nightly at 18:00 (`php artisan ews:detect`). In production, add the scheduler cron entry: `* * * * * php /path/to/artisan schedule:run`.

## How detection works

`app/Services/GapDetector.php`. For each learner and competency, it looks at the last 5 scores on the CBC scale 0.9–3.0 (below 1.5 Basic, 1.5–2.49 Moderate, 2.5+ Outstanding) and raises an alert when any of these is true:

- the recent average is below the competency benchmark (default 1.5, editable by the admin),
- the least-squares slope is at or below −0.25 per assessment (needs at least 4 scores),
- the trend is declining and projects the next score below the benchmark while the average is still above it (the early warning).

Attendance below 75% over 30 days adds weight to an existing alert but never raises one by itself.

When a teacher records an intervention, the system follows it up: the alert closes as **improved** when the learner is back on track, or is **escalated** after 3 assessments without real improvement. Reports show the intervention success rate per competency, and export scores and alerts to Excel (CSV).

Students never see alerts or severity. Their page shows progress and "focus areas".

## Tests

```sh
php artisan test
```

## Design

The design brief is in `PRODUCT.md`. The look is calm, conventional public-service software (see `DESIGN.md`); design decisions are recorded in `.impeccable/`. The school crest is a placeholder shield (`public/crest-placeholder.svg`); add the real one at `public/crest.png` and it replaces the placeholder automatically. The school colours are theme tokens in `resources/css/app.css`.
