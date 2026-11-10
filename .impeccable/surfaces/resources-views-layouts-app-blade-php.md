---
version: 1
slug: "resources-views-layouts-app-blade-php"
primary_target: "resources/views/layouts/app.blade.php"
related_targets: ["resources/views/pages"]
---

# Surface brief: EWS app shell (staff + student screens)

Mode: Operate. Audience: Verona teachers (laptop in staffroom for bulk entry, Android phone for checking alerts), head teacher, students. Job: see who needs help, for which competency, and what to do; record interventions and see whether they work; enter scores and attendance fast. Constraints: metered data, cheap screens, no colour-only cues, students never see alerts or severity.

## Direction contract

THESIS: Calm, conventional public-service software (the category standard executed well, GOV.UK-like): it should read as trustworthy school administration, never as a poster. Replaces the earlier "Kampala signwriting" direction, which the user rejected as too dramatic.

OWN-WORLD: White sidebar and white cards with hairline #e2e6ed borders and a faint shadow on a #f4f6f9 ground; one navy accent #1f4e99 for primary actions, links and the active nav item. Atkinson Hyperlegible throughout, sentence-case headings. Status as tinted pill badges that always carry a word (High/Medium/Low, Needs action, Intervention under way, Escalated, Improved). Competencies as small colour dots + names. 6–8px radii.

STORY: A teacher opens the dashboard, reads the key numbers, sees the learners needing action with a recommended step, records an intervention, and later sees before/after progress and whether it escalated.

FIRST VIEWPORT: White sidebar with crest and nav; "Dashboard" title with class/term line, class select and navy "Enter scores" on the right; a row of six key-number cards; "Needs action" alert cards left, escalated and by-competency panels right.

FORM: canon (the category standard, user-chosen standing exit). seed: none (unavailable)

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
