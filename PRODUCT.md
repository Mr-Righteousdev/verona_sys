# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Subject teachers** at Verona High School Mutundwe (Kampala, Uganda) teaching lower-secondary (S1–S2) classes under Uganda's Competency-Based Curriculum. Classes often exceed 70 learners; teachers are overworked and keep continuous-assessment registers by hand today. Their job: record competency scores and attendance quickly, and find out *who* needs help, *for which competency*, and *what to do* — without reading every learner's history.
- **Head teacher / administrators**: need a school-wide picture of which competencies are developing well or being neglected across classes, and manage users, classes, subjects and competency benchmarks.
- **Students**: see their own competency progress and encouraging focus areas.

## Product Purpose

An Adaptive Early Warning System that continuously analyses CBC assessment scores and attendance, detects emerging gaps in the seven NCDC competencies early, and surfaces targeted intervention recommendations to teachers before a learner falls irreversibly behind. Final-year IT research project (St. Lawrence University); success is detection accuracy vs. teacher judgement and usability (SUS) during validation at the school.

## Positioning

Built around CBC *competencies*, not grades: each alert names one learner, one competency, why it fired (below benchmark, declining trend, projected to fall below, low attendance), and a concrete classroom intervention. Contextualised to Ugandan lower-secondary realities rather than a generic LMS dashboard.

## Operating Context

- Teachers use a mix of devices: laptops/desktops in the staffroom for bulk score entry, phones (often low-to-mid Android on mobile data) for checking alerts between lessons. Connectivity is intermittent and data costs money — pages must be light.
- Assessments: assignments, tests, projects, teacher observations, activities of integration. Each assessment is tagged with the competencies it measures; each learner is scored per competency on the CBC decimal scale 0.9–3.0 (below 1.5 Basic, 1.5–2.49 Moderate, 2.5+ Outstanding).
- Interventions: when a teacher acts on an alert they record what they did; the system compares later scores with the average at that point, resolves the alert as improved when the learner is back on track, and escalates after 3 assessments without improvement.
- Daily attendance register per class.
- Gap detection runs when scores are saved and nightly.
- Terms 1–3 per year; streams such as "S1 East".

## Capabilities and Constraints

- Stack: Laravel 13, Livewire 4, Tailwind 4, maryUI (daisyUI 5). **No Flux UI.** MariaDB/MySQL in production, SQLite locally.
- Roles: admin, teacher, student (all log in). No self-registration; admin creates accounts.
- Competencies: the NCDC seven — Communication & Collaboration; Critical Thinking & Problem-Solving; Creativity & Imagination; Citizenship; Digital Literacy; Learning to Learn; Self-Regulation. Benchmark (default 1.5, admin-editable per competency) and intervention text editable per competency.
- Alert severity: low / medium / high; statuses open (needs action) / in_progress (intervention under way, may be escalated) / resolved (improved, back on track, or closed by a teacher).
- Reports: printable learner progress report (browser print to PDF), class summary, intervention effectiveness, CSV exports for Excel.
- Terminology: "learner" and "competency" (CBC usage); "alert" is teacher/admin-only vocabulary.
- Undecided: offline data entry (deferred, noted as a study limitation); notifications beyond in-app (SMS/email) not built.

## Brand Commitments

- **Conventional, serious look (user decision, 10 Nov 2026).** The user rejected the expressive "signboard" direction as too dramatic to be taken seriously by examiners and school staff. The product takes the category standard executed well: calm public-service software in the spirit of the GOV.UK Design System. Do not reintroduce expressive display type, painted panels or decorative motifs.

- Verona High School crest and school colours will be supplied by the user — leave a slot for the crest and keep colours swappable via theme tokens. Do not invent a crest or claim official school colours until provided.

## Evidence on Hand

- Research proposal: `../Jmproposal.md`, `../JMProposal.docx`.
- Seeded demo data only (fictional learners, `database/seeders/DatabaseSeeder.php`). No real learner data, testimonials, or validation results exist yet — do not fabricate them.

## Product Principles

1. **Who, which competency, what to do** — every teacher-facing view answers these three before anything else.
2. **Fast entry beats rich entry** — scoring a 70-learner class must be quick on a laptop and possible on a phone.
3. **No learner is stigmatised** — students never see "alert", "at-risk" or severity; they see progress and focus areas framed encouragingly. Alerts are visible only to authorised staff.
4. **Explain every flag** — the system shows why it flagged, in plain language, so teachers can trust or overrule it.
5. **Light and frugal** — cheap devices, metered data, intermittent connections.

## Accessibility & Inclusion

- Readable on small, low-quality screens in bright staffrooms: strong contrast, no colour-only severity cues.
- English UI; plain language for teachers with varied ICT literacy.
- Learner data is sensitive (minors): role-based access, no PII exposed beyond authorised staff.
