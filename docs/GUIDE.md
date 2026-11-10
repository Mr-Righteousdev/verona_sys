# Verona EWS: the complete project guide

This guide takes you through the whole project, from what problem it solves to how every part of the code works, so you can run it, change it, and defend it in your viva. Read it top to bottom once, with the app running beside you.

**Contents**

1. [The project in one page](#1-the-project-in-one-page)
2. [Getting it running](#2-getting-it-running)
3. [A guided tour as each user](#3-a-guided-tour-as-each-user)
4. [How the code is organised](#4-how-the-code-is-organised)
5. [The database](#5-the-database)
6. [The gap detection engine](#6-the-gap-detection-engine-the-heart-of-the-project)
7. [How a request travels through the app](#7-how-a-request-travels-through-the-app)
8. [Every page, file by file](#8-every-page-file-by-file)
9. [The design system](#9-the-design-system)
10. [Security and privacy](#10-security-and-privacy)
11. [Testing](#11-testing)
12. [Making common changes](#12-making-common-changes)
13. [Linking the code to your proposal](#13-linking-the-code-to-your-proposal)
14. [Running the validation study](#14-running-the-validation-study)
15. [Deploying to a server](#15-deploying-to-a-server)
16. [Limitations and future work](#16-limitations-and-future-work)
17. [Viva preparation](#17-viva-preparation-questions-you-will-likely-be-asked)
18. [Glossary](#18-glossary)

---

## 1. The project in one page

**The problem.** Uganda's lower-secondary Competency-Based Curriculum (CBC) asks teachers to track each learner's development of competencies continuously. With 70+ learners per class and paper registers, teachers only notice a learner struggling with a competency when it shows up as failure, which is too late.

**The solution.** A web app where teachers record continuous-assessment scores (per competency, on the CBC 0.9–3.0 scale) and daily attendance. Every time scores are saved, and again every evening, a **gap detection engine** checks every learner against every competency. When it spots a gap, or a gap forming, it raises an **alert** that tells the teacher three things:

1. **Who**: the learner.
2. **Which competency**: one of the seven NCDC competencies.
3. **What to do**: a concrete classroom intervention.

When the teacher acts, they record the intervention. The system then watches the next scores: it closes the alert as **improved** when the learner recovers, or **escalates** it if three assessments pass with no real improvement.

**The users.**

| Role | Who | What they do |
|---|---|---|
| `admin` | Head teacher / administrators | Everything teachers do, plus manage users, classes, subjects, competency benchmarks |
| `teacher` | Subject and class teachers | Enter scores and attendance, act on alerts and record interventions, view learner profiles and reports |
| `student` | Learners | See their own progress and "focus areas". **Never** see alerts or severity |

**The seven competencies** (NCDC lower-secondary framework): Communication & Collaboration · Critical Thinking & Problem-Solving · Creativity & Imagination · Citizenship · Digital Literacy · Learning to Learn · Self-Regulation.

> Your proposal text mostly lists *critical thinking, integrity, coordination, spatial awareness, innovation*. The system uses the official NCDC seven (as in your §2.1), because those are what Ugandan schools actually assess. Competencies are rows in a database table, so the list can be changed without touching code (see [§12](#12-making-common-changes)). Make your proposal consistent before submission.

**The stack** (the "TALL" stack):

| Layer | Technology | Why |
|---|---|---|
| Language/framework | **PHP 8.3+ / Laravel 13** | Mature, batteries-included (auth, ORM, migrations, scheduler, validation) |
| Interactivity | **Livewire 4** | Interactive pages written in PHP; no separate JavaScript front-end to build |
| UI components | **maryUI** on **daisyUI 5** | Ready-made inputs, tables, toasts; fully re-themed |
| Styling | **Tailwind CSS 4** | Utility classes, small CSS output |
| Small JS behaviours | **Alpine.js** (comes with Livewire) | Drawers, toasts |
| Database | **MariaDB** (MySQL-compatible) | What the proposal specified; SQLite works for tests |
| Tests | **Pest** | Readable PHP tests |

---

## 2. Getting it running

### First time on a new machine

```sh
git clone https://github.com/Mr-Righteousdev/verona_sys.git
cd verona_sys
composer install              # PHP dependencies → vendor/
npm install                   # JS/CSS build tools + fonts → node_modules/
cp .env.example .env          # your local settings (never committed)
php artisan key:generate      # encryption key for sessions
```

Create the database (once):

```sh
mariadb -u your_db_user -p -e "CREATE DATABASE verona_ews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then set these lines in `.env`:

```
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=verona_ews
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### Every time

```sh
php artisan migrate:fresh --seed   # rebuild tables + demo school (WIPES the database)
npm run build                      # compile CSS/JS into public/build
php artisan serve                  # http://127.0.0.1:8000
```

While editing styles or templates, run `npm run dev` in a second terminal instead of `npm run build`. The page then reloads as you save.

### Demo accounts (password: `password`)

| Email | Role | Notes |
|---|---|---|
| `admin@verona.test` | admin | Head Teacher, sees the whole school |
| `teacher@verona.test` | teacher | Mr. Kasozi Peter, class teacher of S1 East |
| `teacher2@verona.test` … `teacher4@verona.test` | teacher | Class teachers of the other streams |
| `student@verona.test` | student | Brian Okello, S1 East |
| `student2@verona.test` … `student72@verona.test` | student | The other learners |

The demo data is **synthetic**: fictional names and generated scores (see `database/seeders/DatabaseSeeder.php`). Never present it as real Verona data.

In local development only, `http://127.0.0.1:8000/dev/login/{userId}?to=/dashboard` signs you in as any user without a password. It is registered only when `APP_ENV=local` (`routes/web.php`), so it doesn't exist in production.

---

## 3. A guided tour as each user

Do this with the app open. It's the fastest way to understand the product.

### As the class teacher (`teacher@verona.test`)

1. **Login** (`/login`). A plain sign-in card.
2. **Dashboard** (`/dashboard`). It opens on *your* class (S1 East).
   - **Key numbers:** learners, alerts that *need action*, *interventions under way*, *escalated*, *improved*, and attendance. Each card links to the matching list.
   - **Needs action:** cards for the three most urgent learners nobody has acted on yet. Each shows the learner, the competency, the reason, a severity badge and the **recommended action**.
   - **Average score by class and competency:** every class against every competency. Basic-band averages (below 1.5) are highlighted.
   - **Recent improvements:** learners whose intervention worked, with the average before and since ("1.13 → 2.80 (+1.67)").
   - On the right: **Escalated** interventions (no improvement after 3 assessments), **open alerts by competency**, and **recent activity**.
3. **Alerts** (`/alerts`). Every alert, most urgent first. Filter by class, competency, severity and status (*Needs action / Intervention under way / Resolved*), or tick *Escalated only*.
4. **An alert** (`/alerts/{id}`). This is where you act:
   - **Why this was flagged:** every reason, plus the recommended action.
   - **Record an intervention:** write what you did and when. The alert becomes *Intervention under way*.
   - **Is the intervention working?** Before, since and change, and how many scores have come in since.
   - If it's **escalated**, **Try a different approach** records a new intervention and restarts the follow-up.
   - **Close without follow-up** is only for alerts that don't need action, for example scores entered wrongly.
   - The chart shows the whole term: dashed line = benchmark, hollow points = below it, navy vertical line = when the intervention started.
5. **Learners** (`/learners`) and **a learner** (`/learners/{id}`). The profile shows key numbers, open alerts, all seven competencies with charts and an *Improving / Steady / Declining* label, and the alerts resolved this term. **Progress report** opens the printable report.
6. **Assessments & scores** (`/assessments`). Create a task: tick the competencies it assesses and press **Create and enter scores**.
7. **Score sheet** (`/assessments/{id}/scores`).
   - Type a score from **0.9 to 3.0** for each learner per competency. The band (*Basic, Moderate, Outstanding*) appears as you type, and anything out of range is flagged.
   - **Enter** jumps to the next learner. **Fill blanks** fills empty cells with the value you set.
   - **Save scores** stores them *and re-runs gap detection for the class*. The toast tells you how many alerts were raised or resolved.
8. **Attendance** (`/attendance`). Everyone starts present; tap the absentees and save.
9. **Reports** (`/reports`).
   - Every learner's average per competency for a class.
   - **Do interventions work?**: per competency, how many interventions there were, how many improved, are under way or were escalated, and the success rate.
   - **Scores (Excel)** and **Alerts (Excel)** download CSV files that open in Excel.

### As the head teacher (`admin@verona.test`)

Everything above, for the whole school, plus **School setup**:

- **Users** (`/admin/users`): create teachers, admins and students. Choosing *Student* reveals class, gender and admission-number fields, and creates the learner record too.
- **Classes & subjects** (`/admin/classes`): add streams, assign class teachers, add subjects.
- **Competencies** (`/admin/competencies`): edit each competency's **benchmark** (learners averaging below it are flagged; default 1.5, the start of Moderate), its description and its recommended intervention. **Re-check all learners** re-runs detection right away.

### As a learner (`student@verona.test`)

- **My progress** (`/me`): "Your progress, Brian", with counts of *Outstanding* and *Improving* competencies and *focus areas*.
- Any competency with an open alert appears as a **focus area**, written encouragingly ("Everyone has competencies they are still building…"). If they're already improving there, it says so.
- All seven competencies show their score, band and a soft chart with no "below benchmark" marks. The *Improving* and *Steady* labels are shown, but *Declining* is not.
- The words *alert*, *severity* and *at risk* never appear. This implements the ethics commitment in your proposal (§1.6): no learner is stigmatised by system-generated flags.

---

## 4. How the code is organised

Only the folders you'll touch are listed:

```
ews/
├── app/
│   ├── Http/Middleware/EnsureRole.php   ← blocks users without the right role (403)
│   ├── Models/                          ← one class per database table (Eloquent ORM)
│   ├── Services/GapDetector.php         ← THE DETECTION ENGINE
│   └── View/Components/AppBrand.php     ← school name + crest in the sidebar
├── bootstrap/app.php                    ← registers the `role` middleware, login redirects
├── database/
│   ├── migrations/                      ← table definitions (the schema)
│   └── seeders/DatabaseSeeder.php       ← synthetic demo school
├── resources/
│   ├── css/app.css                      ← theme: colours, font, panels and badges
│   └── views/
│       ├── layouts/app.blade.php        ← the shell: blue sidebar + content area
│       ├── layouts/guest.blade.php      ← shell for the login page
│       ├── components/                  ← reusable pieces (alert card, badges, trend chart…)
│       └── pages/                       ← one file per screen (Livewire components)
├── routes/
│   ├── web.php                          ← URL → page, with who may see it
│   └── console.php                      ← `ews:detect` command + nightly schedule
├── tests/Feature/                       ← automated tests
├── public/crest-placeholder.svg         ← replace with public/crest.png
├── PRODUCT.md                           ← product brief (users, principles)
├── DESIGN.md                            ← design system (colours, type, rules)
└── .env                                 ← local secrets (NOT in git)
```

### The three ideas that make Laravel + Livewire click

1. **Models are tables.** `App\Models\Student` is the `students` table. `$student->alerts` follows the relationship to that learner's alerts. You rarely write SQL.
2. **A page is one file.** Each file in `resources/views/pages/` holds a PHP class at the top and the HTML (Blade) at the bottom. Public properties like `$classroomId` are the page's state. Methods like `save()` run on the server when a button with `wire:click="save"` is pressed. Livewire sends the click to the server, runs the method, re-renders, and patches the browser. That's why there's almost no JavaScript in this project.
3. **Routes connect URLs to pages.** In `routes/web.php`, `Route::livewire('/alerts', 'pages::alerts.index')` means `/alerts` shows `resources/views/pages/alerts/index.blade.php`.

---

## 5. The database

Defined in `database/migrations/2026_11_10_000100_create_ews_tables.php`, plus `users` in `0001_01_01_000000_create_users_table.php`.

```
users ─────────────┐ (role: admin | teacher | student)
  │ 1               │
  │ 0..1            │ teacher_id
students ──┐        │
  │ n       │       assessments ───── n:m ───── competencies
  │         │         │  (classroom, subject,       (name, colour, benchmark,
  │ 1       │         │   title, type, term, date)   description, intervention)
classrooms  │         │ 1
  (S1 East) │         │ n
            │       scores (assessment, student, competency, level 0.9–3.0)
            │
            ├── attendances (student, date, present)
            └── alerts (student, competency, severity, reasons, recent_average,
                  │      projected_level, recommendation, status, outcome,
                  │      escalated_at, resolved_by, resolution_note)
                  │ 1
                  │ n
                interventions (alert, teacher, action, started_on, baseline_average)
```

| Table | One row is… | Key columns |
|---|---|---|
| `users` | an account | `role` decides what they can see |
| `classrooms` | a stream, e.g. S1 East | `class_teacher_id` → users |
| `subjects` | English, Mathematics… | |
| `students` | a learner | `user_id` (their login), `classroom_id`, `admission_no` |
| `competencies` | one NCDC competency | `benchmark` (default 1.5), `colour`, `intervention` |
| `assessments` | a task: test, project, assignment, observation | `term`, `assessed_on` |
| `assessment_competency` | "this task assesses this competency" | links the two |
| `scores` | one learner's score on one competency in one task | `level` is a decimal from **0.9 to 3.0**; unique per (assessment, student, competency) |
| `attendances` | one learner on one day | `present` true/false; unique per (student, date) |
| `alerts` | one detected gap for one learner in one competency | `status`: `open` (needs action), `in_progress` (intervention under way), `resolved`; `outcome` when resolved: `improved`, `cleared`, `closed`; `escalated_at` set when an intervention isn't working |
| `interventions` | one thing a teacher did about an alert | `action` (what they did), `started_on`, `baseline_average` (the learner's average at that moment, used for before/after) |

**The CBC score bands** (`Score::band()` in `app/Models/Score.php`):

| Score | Band |
|---|---|
| 0.9 – 1.49 | Basic |
| 1.5 – 2.49 | Moderate |
| 2.5 – 3.0 | Outstanding |

Check these ranges against Verona's actual continuous-assessment registers during your document review. If they differ, change `band()` and the benchmark default, and nothing else needs to change.

**The key design decision** is that a score is per competency, not per task. One project can measure several competencies, and the learner gets a score on each. That's what makes competency-level detection possible: a learner can be strong in Citizenship and weak in Digital Literacy *on the same task*.

Your proposal's ERD (Appendix V) only shows Students–Assessments. Replace it with the diagram above.

---

## 6. The gap detection engine (the heart of the project)

File: `app/Services/GapDetector.php`. This is objective (iii) of your proposal. Read the file alongside this section.

### The settings (top of the class)

| Constant | Value | Meaning |
|---|---|---|
| `WINDOW` | 5 | Look at the learner's **5 most recent** scores for a competency |
| `MIN_SCORES` | 2 | Need at least 2 scores before judging at all |
| `MIN_TREND_SCORES` | 4 | Need at least 4 before judging a *decline*. With fewer, one bad day looks like a collapse |
| `DECLINE_SLOPE` | −0.25 | Losing 0.25 or more per assessment counts as "declining" (an alert reason) |
| `TREND_LABEL` | 0.1 | A slope above +0.1 is labelled **Improving**, below −0.1 **Declining**, otherwise **Steady** |
| `LOW_ATTENDANCE` | 0.75 | Below 75% attendance over 30 days adds weight |
| `ESCALATE_AFTER` | 3 | Assessments after an intervention before escalating if nothing has improved |
| `IMPROVEMENT` | 0.2 | Rise over the baseline that counts as real progress |

### The three rules (`analyse()`)

For a learner's recent scores (oldest first) and the competency's benchmark (default **1.5**, the start of the Moderate band, editable by the admin per competency):

1. **Below benchmark.** The average of the recent scores is less than the benchmark.
2. **Declining.** With at least 4 scores, the *least-squares slope* is −0.25 or steeper. This is the slope of the best-fit straight line through the points (x = 0, 1, 2…, y = score):

   ```
   slope = Σ (x − x̄)(y − ȳ) / Σ (x − x̄)²
   ```

3. **Projected below benchmark (the early warning).** If the trend is declining and the learner is *still at or above* the benchmark on average, extend the line one assessment ahead:

   ```
   projected = ȳ + slope × (n − x̄)        (kept between 0.9 and 3.0)
   ```

   If the projection falls below the benchmark, the alert fires *before* the learner has actually fallen behind. This is the "early" in early warning, and the system's predictive component.

### Worked example

Scores **2.6, 2.2, 1.8, 1.4**, benchmark **1.5**:

- Average ȳ = 8.0 / 4 = **2.0**, which is above the benchmark, so rule 1 doesn't fire.
- x = 0, 1, 2, 3, so x̄ = 1.5. The deviations are x: −1.5, −0.5, 0.5, 1.5 and y: 0.6, 0.2, −0.2, −0.6.
- Numerator = (−0.9) + (−0.1) + (−0.1) + (−0.9) = −2.0. Denominator = 2.25 + 0.25 + 0.25 + 2.25 = 5.0.
- **slope = −0.4**, which is ≤ −0.25, so rule 2 fires: "Scores are declining by 0.40 per assessment." The trend label is **Declining**.
- **projected** = 2.0 + (−0.4 × (4 − 1.5)) = 2.0 − 1.0 = **1.0** < 1.5, so rule 3 fires: "Trend projects the next score at 1.00, below the benchmark."
- Two reasons → severity **medium**.

This exact case is a test: `tests/Feature/GapDetectorTest.php`, "warns early when a decline projects below benchmark".

### Attendance

If at least one rule fired *and* attendance over the last 30 days is below 75%, a further reason is added: "Attendance is 62% over the last 30 days." Attendance never raises an alert on its own, because absence isn't a competency gap, but it explains one and raises its severity.

### Severity (`severity()`)

| Condition | Severity |
|---|---|
| 3 or more reasons, **or** average more than 0.3 below the benchmark | **high** |
| exactly 2 reasons, **or** average below the benchmark | **medium** |
| an early warning from the trend alone | **low** |

### The life of an alert

```
            gap detected                 teacher records             later scores back
 (none) ───────────────▶  Needs action ──── an intervention ───▶ Intervention ───── on track ─────▶ Resolved: Improved
                              │                                  under way
                              │ scores recover                      │ 3 assessments, no
                              ▼ on their own                        ▼ real improvement
                       Resolved: Back on track                   Escalated ── teacher tries a
                                                                    │         different approach
                              teacher closes with a reason          └──────▶ (back to under way)
                       ───────────────────────────▶ Resolved: Closed
```

In code (`evaluate()`):

- **Reasons found, no unresolved alert yet** → a new alert is created, status `open`, with the competency's intervention text as the recommendation.
- **Reasons found, an unresolved alert already exists** → it's *updated*, never duplicated.
- **A teacher records an intervention** (alert page) → an `interventions` row is saved with the learner's current average as the **baseline**, and the alert becomes `in_progress`.
- **Follow-up.** On every later run, the engine looks at the scores since the intervention started (`Intervention::progress()`). After `ESCALATE_AFTER` (3) of them, if the average hasn't risen by at least `IMPROVEMENT` (0.2) over the baseline, the alert is **escalated**: severity becomes high, a reason is added ("No real improvement after 3 assessments since the intervention on 7 Oct (1.45 → 1.45)"), and the head teacher sees it on the dashboard. Recording a different approach resets the follow-up.
- **No reasons any more** → the alert is resolved automatically. If an intervention was under way, the outcome is **improved** ("Improved after intervention: back on track"); otherwise it's **cleared** ("Back on track without intervention").
- A teacher can also **close** an alert with a reason, for example if scores were entered wrongly. If later scores show the same gap, a *new* alert is raised.

### How we know a learner is improving

1. **Trend labels.** Every competency on the learner profile and the progress report shows *Improving*, *Steady* or *Declining*, from the slope of the recent scores.
2. **Before and after.** Each intervention compares the average when it began (the baseline) with the average of scores since, shown as "Before 1.05 · Since 1.10 · Change +0.05" on the alert page.
3. **Resolved as improved.** When the learner gets back on track, the alert closes with the outcome *Improved*. These appear under **Recent improvements** on the dashboard.
4. **Across the school.** **Reports → Do interventions work?** shows, per competency, how many interventions led to improvement: your success rate.

### When it runs

1. **When scores are saved.** The score sheet's `save()` calls `$detector->run()` for every learner in that class.
2. **Every evening at 18:00.** `routes/console.php` schedules `php artisan ews:detect`, which runs `runAll()` for every learner. On a server, cron must call `php artisan schedule:run` every minute (see [§15](#15-deploying-to-a-server)).
3. **On demand.** The admin's **Re-check all learners** button, or `php artisan ews:detect` in a terminal.

### Why rules and not machine learning?

Be ready for this one. The honest answer:

- **There's no labelled historical data.** ML models need many past learners whose outcomes are known. Verona has none in digital form yet.
- **Teachers must trust and overrule it.** Every alert says in plain language *why* it fired (principle 4 in `PRODUCT.md`). A neural network can't explain itself that way.
- **The proposal itself specifies this.** §3.6.1: "threshold-based rules derived from CBC competency benchmarks, supplemented by trend analysis algorithms".
- **The prediction is real but simple.** It's a linear trend projection, a standard statistical forecast. Once a few terms of data exist, the same `analyse()` interface could be backed by a trained model; that's future work.

---

## 7. How a request travels through the app

Example: a teacher opens `/alerts?class=1`.

1. **Route** (`routes/web.php`): `/alerts` sits inside `middleware('auth')` (must be logged in, else redirect to `/login`) and `middleware('role:admin,teacher')`.
2. **Middleware** (`app/Http/Middleware/EnsureRole.php`) checks `$user->role` is in the allowed list, otherwise **403 Forbidden**. That's why a student who types `/alerts` is refused.
3. **Livewire page** `pages::alerts.index` is created. `#[Url(as: 'class')]` fills `$classroomId = 1` from the query string.
4. **`with()`** runs the query: `Alert::query()->with(...)->inClass(1)->...->urgentFirst()->paginate(25)`. `inClass` and `urgentFirst` are *scopes*, reusable query pieces defined in `app/Models/Alert.php`.
5. **Blade** renders the HTML, wrapped in `layouts/app.blade.php` (the sidebar).
6. **Interaction.** When the teacher changes a filter (`wire:model.live="severity"`), Livewire sends only that change to the server, re-runs `with()`, and patches the table. No full page reload.

Saving scores works the same way: `wire:click="save"` → `save()` validates scores are 0.9–3.0 → writes `scores` in a database transaction → runs the detector → shows a toast.

---

## 8. Every page, file by file

All under `resources/views/pages/`.

| File | URL | Who | What to look at in the code |
|---|---|---|---|
| `auth/login.blade.php` | `/login` | guests | `login()`: validation, **rate limiting** (5 failed tries locks for a while), `Auth::attempt`, session regeneration |
| `dashboard.blade.php` | `/dashboard` | admin, teacher | `mount()` defaults a teacher to their own class; key numbers, class × competency averages (one grouped query), recent improvements and activity |
| `alerts/index.blade.php` | `/alerts` | admin, teacher | Filters bound to the URL (`#[Url]`), so filtered views can be bookmarked or shared |
| `alerts/show.blade.php` | `/alerts/{alert}` | admin, teacher | `recordIntervention()` saves the baseline and sets *in progress*; `Intervention::progress()` gives before/after; `close()`, `reopen()` |
| `learners/index.blade.php` | `/learners` | admin, teacher | `withCount` computes open alerts and attendance in one query |
| `learners/show.blade.php` | `/learners/{student}` | admin, teacher | `Student::competencySummary()` gives scores, average and trend label per competency |
| `assessments/index.blade.php` | `/assessments` | admin, teacher | `create()` validates and attaches competencies, then goes to the score sheet |
| `assessments/scores.blade.php` | `/assessments/{id}/scores` | admin, teacher | Decimal entry validated to 0.9–3.0; live band label in Alpine; `fillBlanks()`; `save()` → detector |
| `attendance.blade.php` | `/attendance` | admin, teacher | Unrecorded days start all-present; `updateOrCreate` makes saving twice safe |
| `me.blade.php` | `/me` | student | Turns open alerts into "focus areas"; uses `<x-trend soft>`; never shows *Declining* |
| `reports/index.blade.php` | `/reports` | admin, teacher | Learner × competency averages for a class; intervention success rate per competency |
| `reports/learner.blade.php` | `/reports/learners/{student}` | admin, teacher | Printable progress report; `@media print` in `app.css` hides the sidebar |
| `app/Http/Controllers/ExportController.php` | `/reports/export/scores`, `/reports/export/alerts` | admin, teacher | CSV for Excel (UTF-8 BOM; text starting with `=` is escaped against formula injection) |
| `admin/users.blade.php` | `/admin/users` | admin | One form for create/edit; creating a *student* also creates the `students` row |
| `admin/classes.blade.php` | `/admin/classes` | admin | Change a class teacher straight from the dropdown |
| `admin/competencies.blade.php` | `/admin/competencies` | admin | Edit benchmark/intervention; `recheck()` runs the detector |

**Reusable components** (`resources/views/components/`):

| Component | Used as | What it draws |
|---|---|---|
| `alert-card` | `<x-alert-card :alert="$alert" />` | Learner, competency, reason, severity badge and recommended action |
| `severity` | `<x-severity level="high" />` | High / Medium / Low badge |
| `alert-status` | `<x-alert-status :alert="$alert" />` | Needs action / Intervention under way / Escalated / Improved… badge |
| `trend-label` | `<x-trend-label trend="improving" />` | Improving / Steady / Declining badge |
| `kpi` | `<x-kpi label="..." :value="..." note="..." />` | One key-number card |
| `competency` | `<x-competency :competency="$c" />` | Colour dot + name |
| `trend` | `<x-trend :levels="[2.4, 2.1, 1.8]" :benchmark="1.5" :colour="..." />` | Inline SVG chart; `soft` hides below-benchmark marks for students; `marker` draws the intervention start |
| `page-header` | `<x-page-header title="..." subtitle="...">` | Page title, subtitle, action buttons |

The chart is hand-drawn SVG, not a chart library. That keeps pages small for teachers on mobile data.

---

## 9. The design system

The full rules are in `DESIGN.md`. The summary:

- **The idea: calm, conventional public-service software**, in the spirit of the UK government's GOV.UK Design System. You chose this over an earlier expressive "signboard" look because a school tool should look serious and trustworthy. It's recorded in `PRODUCT.md` so future work keeps it that way.
- **Surfaces.** White sidebar and white cards (`.panel`) with a hairline border on a light grey ground.
- **Colour.** One navy accent `#1f4e99` for primary buttons, links and the active menu item.
- **Status is always a word in a tinted pill** (`.tag`): *High / Medium / Low* severity, and *Needs action / Intervention under way / Escalated / Improved / Back on track / Closed*. Colour only reinforces the word, so colour-blind users and bad screens still read it.
- **Competencies** appear as a small colour dot plus the name. Each competency has one fixed colour, stored in `competencies.colour`.
- **Font.** *Atkinson Hyperlegible*, self-hosted so there are no Google requests on mobile data. It was designed for low-vision readers, which suits cheap, bright-lit screens. Its zero is slashed on purpose so it can't be confused with the letter O.
- **Charts** are hand-drawn SVG (`components/trend.blade.php`). Grid lines mark the band edges at 1.5 and 2.5, the dashed line is the benchmark, hollow points are below it, and a navy vertical line marks when an intervention started.

**Placeholder crest.** Put the real crest at `public/crest.png` and it replaces `public/crest-placeholder.svg` automatically (`app/View/Components/AppBrand.php`). School colours, once you have them, go in the `verona` theme block in `resources/css/app.css`.

---

## 10. Security and privacy

| Concern | How it's handled | Where |
|---|---|---|
| Only logged-in users | `auth` middleware on every page except login | `routes/web.php` |
| Role separation | `role:` middleware returns 403 | `app/Http/Middleware/EnsureRole.php` |
| Passwords | Hashed with bcrypt (`'password' => 'hashed'` cast), never stored in plain text | `app/Models/User.php` |
| Guessing passwords | 5 failed attempts per email+IP, then locked out for a while | `pages/auth/login.blade.php` |
| Session theft after login | Session ID regenerated on login, invalidated on logout | login page, `routes/web.php` |
| CSRF (forged form posts) | Laravel CSRF tokens on every form and Livewire request | automatic |
| SQL injection | Eloquent / query builder bind every value | automatic |
| Bad input | Server-side validation on every save (scores 0.9–3.0, dates not in the future, unique emails…) | each page's `validate()` |
| Learner dignity | Students never see alerts or severity | `pages/me.blade.php`, tested in `PagesTest.php` |
| Secrets | `.env` is git-ignored | `.gitignore` |
| Dev back door | `/dev/login` exists only when `APP_ENV=local` | `routes/web.php` |

**In production you must:** set `APP_ENV=production`, set `APP_DEBUG=false`, use HTTPS, change every demo password, and use real accounts rather than the demo seed.

---

## 11. Testing

```sh
php artisan test
```

Tests use an in-memory SQLite database (`phpunit.xml`), so they never touch your MariaDB data.

| File | What it proves |
|---|---|
| `tests/Feature/GapDetectorTest.php` (9 tests) | Score bands; on-track learners aren't flagged and get a trend label; below-benchmark works; declines need 4 scores; the 2.6, 2.2, 1.8, 1.4 early warning; alerts are created, updated (not duplicated) and auto-cleared; **resolved as improved after an intervention**; **escalated when an intervention shows no improvement**; attendance only adds to an existing flag |
| `tests/Feature/PagesTest.php` (5 tests) | Guests are redirected; every page loads for the head teacher (including reports); teachers can't open admin pages; students can't open staff pages; a flagged learner's page contains no alert language; CSV exports download |
| `tests/Feature/EntryTest.php` (4 tests) | Saving a score sheet stores decimal scores and raises an alert after repeated low scores; a score of 3.4 is rejected; the attendance register saves absentees; recording an intervention saves the baseline and moves the alert to *in progress* |

These map directly to your proposal's §3.6.2: **unit testing** of the detection logic, **integration testing** of the pipeline from data input to alert generation, and verification "that the system correctly detects known competency gap scenarios introduced via controlled test data".

---

## 12. Making common changes

| You want to… | Do this |
|---|---|
| Change a benchmark or intervention | Admin → Competencies (no code), then **Re-check all learners** |
| Change the competency list | Edit `COMPETENCIES` in `database/seeders/DatabaseSeeder.php`, then `php artisan migrate:fresh --seed`. On a live system, add or rename via tinker or a small admin form instead, so data is kept |
| Make detection more or less sensitive | Change the constants at the top of `app/Services/GapDetector.php`, then run `php artisan test` |
| Change the nightly time | `routes/console.php`: `->dailyAt('18:00')` |
| Add the real crest | Save it as `public/crest.png` |
| Change the school colours | The `@plugin "daisyui/theme"` block in `resources/css/app.css`, then `npm run build` |
| Add a page | Create `resources/views/pages/whatever.blade.php` (copy a similar page), add a `Route::livewire(...)` line in `routes/web.php` inside the right `role:` group, and add a `<x-menu-item>` in `layouts/app.blade.php` |
| Add a database column | `php artisan make:migration add_x_to_y_table`, write it, `php artisan migrate`, add the column to the model's `#[Fillable([...])]` |
| Try code interactively | `php artisan tinker`, e.g. `App\Models\Alert::open()->count()` |
| See errors | The browser shows them while `APP_DEBUG=true`; also `storage/logs/laravel.log` |

**The git routine:**

```sh
git status                    # what changed
git add -A
git commit -m "Describe the change"
git push
```

---

## 13. Linking the code to your proposal

| Proposal item | Where it lives |
|---|---|
| Objective (i): analyse current monitoring practices | Your interviews, questionnaires and document review (Appendices I–II). Their findings shaped `PRODUCT.md` (large classes, fast entry, phones + laptops) |
| Objective (ii): architecture to ingest data and map it to CBC competencies | Schema in [§5](#5-the-database): per-competency `scores` via `assessment_competency`; layered architecture in [§4](#4-how-the-code-is-organised) and [§7](#7-how-a-request-travels-through-the-app) |
| Objective (iii): predictive gap detection engine | `app/Services/GapDetector.php`, [§6](#6-the-gap-detection-engine-the-heart-of-the-project) |
| §3.4.1 user requirements: input scores, analyse, dashboards, recommendations | Score sheet, detector, dashboard + learner profile, "Recommended action" on every alert, intervention tracking |
| §3.4.2 functional: threshold + trend detection, alerts, role-based dashboards, historical trends | Detector rules, alerts pages, `role:` middleware, `<x-trend>` charts |
| §3.4.3 non-functional: browser-based, responsive, role security, maintainable | Web app, mobile layouts (attendance and dashboard tested at phone width), [§10](#10-security-and-privacy), plain Laravel conventions |
| §3.4.3 "operate under intermittent connectivity" | **Partly.** Pages are light (self-hosted fonts, no chart library, about 36 KB of gzipped CSS) but there's no offline mode. State it as a limitation |
| §3.6.1 Laravel, MySQL, Livewire, Tailwind | Laravel 13, MariaDB, Livewire 4, Tailwind 4 |
| §3.6.2 unit, integration, controlled-test-data testing | [§11](#11-testing) |
| §3.6.3 validation: SUS + comparison with teacher judgement | [§14](#14-running-the-validation-study) |
| §1.6 ethics: no stigmatisation, authorised access only | Students never see alerts (tested); role middleware |

**Where the build differs from your architecture diagram (Appendix VII), and why:**

- **Laravel 13** instead of 11: it's the current version.
- **No Laravel Horizon or Redis.** Detection runs instantly on save and nightly via the scheduler; a school this size doesn't need a separate queue server. Fewer moving parts makes it cheaper to host and easier for non-specialist IT staff to maintain (§3.4.3).
- **No Spatie permissions package.** Three fixed roles are handled by one `role` column and a 10-line middleware.
- **Reports** are a printable page (save as PDF from the browser) and CSV files for Excel, instead of server-side PDF/Excel libraries: smaller and nothing extra to install.

Update the diagram and say this in Chapter 4: each simplification serves the maintainability requirement.

---

## 14. Running the validation study

Your §3.6.3 has two parts.

**1. Detection accuracy vs teacher judgement.**

1. With permission, enter a term of real (anonymised) scores for one class, or have teachers enter them.
2. Before showing the alerts, ask the class teacher to list, per learner, which competencies they believe are weak.
3. Export the system's open alerts:

   ```sh
   php artisan tinker
   >>> App\Models\Alert::open()->with('student','competency')->get()->map(fn($a) => [$a->student->admission_no, $a->competency->name, $a->severity])->toArray();
   ```

4. Build a confusion table. A system alert that matches the teacher is a true positive. An alert the teacher didn't name is a false positive. A teacher concern with no alert is a false negative. Report **precision** = TP / (TP + FP) and **recall** = TP / (TP + FN).
5. If precision is low, raise the benchmark sensitivity or `DECLINE_SLOPE`. If recall is low, loosen them. Rerun the tests after any change.

**2. Usability (SUS).** After teachers have used the app for a week, give them the standard 10-item System Usability Scale. Score: for odd items, score − 1; for even items, 5 − score; sum and multiply by 2.5. That gives 0–100, and about 68 is average.

---

## 15. Deploying to a server

For a cheap Ubuntu VPS with Nginx, PHP 8.3+ and MariaDB:

```sh
git clone https://github.com/Mr-Righteousdev/verona_sys.git /var/www/verona && cd /var/www/verona
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate
# edit .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://..., DB_* credentials
php artisan migrate --force          # NOT migrate:fresh: that wipes data
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Then:

- Point Nginx's root at `/var/www/verona/public`.
- Give the web server write access to `storage/` and `bootstrap/cache/`.
- Add HTTPS (Let's Encrypt).
- Add the scheduler to cron with `crontab -e`:

  ```
  * * * * * cd /var/www/verona && php artisan schedule:run >> /dev/null 2>&1
  ```

- Create the real head-teacher account with `php artisan tinker`:

  ```php
  App\Models\User::create(['name'=>'...','email'=>'...','password'=>'...','role'=>'admin']);
  ```

  The head teacher then creates everyone else from **Users**.

---

## 16. Limitations and future work

Be upfront about these. Examiners respect a candidate who knows the edges of their work.

1. **Single school, synthetic data so far.** Results need validating on real Verona data (§14).
2. **No offline mode.** Pages are light, but entry needs a connection. A future version could use a Progressive Web App with local storage and sync.
3. **Teachers can see all classes.** Simpler for a small school; a future version could limit teachers to the classes they teach.
4. **In-app alerts only.** No SMS or email notifications yet (Africa's Talking SMS would suit Uganda).
5. **Reports are print-to-PDF and CSV.** No server-generated PDF or native .xlsx files; the browser's *Save as PDF* and Excel's CSV import cover the need without extra libraries.
6. **Rule-based prediction.** A linear projection on up to 5 points; ML could be tried once several terms of data exist.
7. **Data quality.** Alerts are only as good as the scores teachers enter (as your §1.7 says).
8. **Changing a user's role from student to staff** leaves the old learner record in place. That's an edge case to tidy.

---

## 17. Viva preparation: questions you will likely be asked

- **"Walk me through what happens when a teacher saves scores."** → [§7](#7-how-a-request-travels-through-the-app), last paragraph, then [§6](#6-the-gap-detection-engine-the-heart-of-the-project) "What happens to the alert".
- **"How exactly does it predict?"** → The least-squares slope and projection, with the 3,3,2,2 example worked on paper ([§6](#6-the-gap-detection-engine-the-heart-of-the-project)).
- **"Why not machine learning?"** → [§6](#6-the-gap-detection-engine-the-heart-of-the-project), "Why rules and not machine learning?"
- **"Why four scores before a decline counts?"** → With three points, a single bad day (2.4, 1.8, 1.7) already looks like a steep fall. In early development this produced a flood of false alerts (135 for 72 learners), so the minimum was raised. Projections also only count when the trend is genuinely declining, so a flat-but-noisy learner isn't flagged.
- **"What happens after the teacher acts?"** → [§6](#6-the-gap-detection-engine-the-heart-of-the-project), "The life of an alert": the intervention is recorded with a baseline, later scores are compared, and the alert resolves as improved or is escalated.
- **"How do you know interventions work?"** → Reports → *Do interventions work?*: the success rate per competency, from real before/after data.
- **"Why decimals?"** → The CBC assessment scale is 0.9–3.0 with bands; whole numbers 1–3 threw away information and made trends jumpy.
- **"How do you stop students being stigmatised?"** → Separate softened student view, no alert language (and an automated test proving it), role middleware.
- **"How is it secure?"** → [§10](#10-security-and-privacy) table.
- **"How did you test it?"** → [§11](#11-testing): 18 automated tests, mapped to unit, integration and controlled-data scenarios.
- **"Why these competencies?"** → The NCDC lower-secondary framework, stored as editable data.
- **"Why did you choose this stack?"** → One language (PHP) for everything, built-in auth, validation, scheduler and ORM, cheap hosting, and maintainable by Ugandan developers and school IT staff.
- **"What would you do next?"** → [§16](#16-limitations-and-future-work).

---

## 18. Glossary

| Term | Meaning |
|---|---|
| **CBC** | Competency-Based Curriculum, Uganda's lower-secondary curriculum since 2020 |
| **NCDC** | National Curriculum Development Centre, which defines the competencies |
| **Score** | A decimal from 0.9 to 3.0 on one competency in one task |
| **Band** | Basic (below 1.5), Moderate (1.5–2.49), Outstanding (2.5 and above) |
| **Benchmark** | The average below which a learner is flagged (default 1.5, per competency) |
| **Intervention** | What a teacher did about an alert, with the learner's average at that time (the baseline) |
| **Escalated** | An intervention with no real improvement after 3 assessments; needs a different approach |
| **Trend label** | Improving / Steady / Declining, from the slope of recent scores |
| **Alert** | A detected gap for one learner in one competency, with reasons and a recommendation |
| **Severity** | low (trend warning only), medium (below benchmark or two reasons), high (well below, three reasons, or escalated) |
| **Focus area** | What a student sees instead of an alert |
| **Least-squares slope** | The slope of the straight line that best fits the points; negative means declining |
| **Eloquent** | Laravel's ORM: database tables as PHP classes |
| **Migration** | A PHP file that creates or changes database tables |
| **Seeder** | Code that fills the database with (demo) data |
| **Livewire component** | A page whose PHP methods run on the server when the user clicks or types |
| **Blade** | Laravel's HTML template language (`{{ }}`, `@if`, `@foreach`) |
| **Middleware** | Code that runs before a page, e.g. checking login or role |
| **Scope** | A reusable query piece on a model, e.g. `Alert::open()` |
| **Scheduler** | Laravel's cron replacement; runs `ews:detect` nightly |
