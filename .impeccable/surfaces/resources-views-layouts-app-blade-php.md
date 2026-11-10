---
version: 1
slug: "resources-views-layouts-app-blade-php"
primary_target: "resources/views/layouts/app.blade.php"
related_targets: ["resources/views/pages"]
---

# Surface brief: EWS app shell (staff + student screens)

Mode: Operate. Audience: Verona teachers (laptop in staffroom for bulk entry, Android phone for checking alerts), head teacher, students. Job: see who needs help, for which competency, and what to do; enter scores and attendance fast. Constraints: metered data, cheap screens, no colour-only cues, students never see alerts or severity.

## Direction contract

THESIS: Every alert is a painted school signboard — learner band, competency band in its own paint, intervention band — instead of the category's grey KPI tiles and badge tables.

OWN-WORLD: Signboard blue (#16348f) side rail, cool wall-grey ground, white square-cornered boards with 2px ink frames, one-pixel rules. Seven fixed competency paints, each AA with white text. Red only for high severity; severity also counted in 1–3 painted edge stripes. Archivo condensed heavy caps for display at a short scale; Atkinson Hyperlegible for everything read. Yellow marks the active nav item and the primary action. Resolved items drop to ink outline.

STORY: A teacher opens the app, reads one sentence ("13 learners need you this week"), sees the three most urgent boards, acts or opens the full list, and enters scores without hunting.

FIRST VIEWPORT: Blue rail with lettered school name and nav; header with the condensed-caps count sentence, class/term line, yellow "Enter scores" action right; three signboards across; right column with by-competency and by-class tallies in competency paint.

FORM: Kampala hand-painted signwriting, candidate 5 of 7 (assigned). seed:2b8023d157121f86

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
