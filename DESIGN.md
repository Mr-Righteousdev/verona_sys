---
name: Verona EWS
description: Competency early warning for Verona High School Mutundwe. Calm, conventional public-service software.
colors:
  navy: "#1f4e99"
  navy-soft: "#e8eef8"
  navy-deep: "#173d7a"
  selection-blue: "#cddcf3"
  ink: "#1b2430"
  muted: "#556070"
  slate: "#475467"
  rule: "#e2e6ed"
  wall: "#f4f6f9"
  paper: "#ffffff"
  scrollbar-thumb: "#a3acba"
  success: "#11743f"
  warning: "#b25e09"
  error: "#b42318"
  info: "#1d5fb8"
  tag-high-bg: "#fde8e7"
  tag-high-text: "#9a1c13"
  tag-medium-bg: "#fdefd9"
  tag-medium-text: "#8a4a06"
  tag-low-bg: "#eef1f5"
  tag-low-text: "#3d4756"
  tag-good-bg: "#e3f3e9"
  tag-good-text: "#0d5c32"
  competency-communication: "#1a5fb4"
  competency-critical-thinking: "#7b2d8e"
  competency-creativity: "#b9470f"
  competency-citizenship: "#0f7a3d"
  competency-digital-literacy: "#0b6f71"
  competency-learning-to-learn: "#7f5a00"
  competency-self-regulation: "#a3324a"
typography:
  headline:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.75rem"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.01em"
  title-sm:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.01em"
  number:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.33
    fontFeature: "tnum"
  body:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  nav:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 700
    lineHeight: 1.5
  caption:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 400
    lineHeight: 1.33
rounded:
  field: "0.375rem"
  md: "0.375rem"
  box: "0.5rem"
  pill: "999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.25rem"
  xl: "1.75rem"
  page-x: "2rem"
  page-max: "1400px"
components:
  button-primary:
    backgroundColor: "{colors.navy}"
    textColor: "{colors.paper}"
    rounded: "{rounded.field}"
  button-outline:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.field}"
  panel:
    backgroundColor: "{colors.paper}"
    rounded: "{rounded.box}"
  panel-head:
    padding: "0.875rem 1.25rem"
  kpi:
    backgroundColor: "{colors.paper}"
    rounded: "{rounded.box}"
    padding: "0.875rem 1rem"
  tag:
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0.125rem 0.5rem"
  tag-high:
    backgroundColor: "{colors.tag-high-bg}"
    textColor: "{colors.tag-high-text}"
  tag-medium:
    backgroundColor: "{colors.tag-medium-bg}"
    textColor: "{colors.tag-medium-text}"
  tag-low:
    backgroundColor: "{colors.tag-low-bg}"
    textColor: "{colors.tag-low-text}"
  tag-good:
    backgroundColor: "{colors.tag-good-bg}"
    textColor: "{colors.tag-good-text}"
  tag-info:
    backgroundColor: "{colors.navy-soft}"
    textColor: "{colors.navy-deep}"
  nav-item-active:
    backgroundColor: "{colors.navy-soft}"
    textColor: "{colors.navy}"
    rounded: "{rounded.md}"
    padding: "0.5rem 0.75rem"
  recommendation-box:
    backgroundColor: "{colors.wall}"
    rounded: "{rounded.md}"
    padding: "0.625rem 0.75rem"
  competency-dot:
    size: "0.625rem"
    rounded: "{rounded.pill}"
---

# Design System: Verona EWS

## Overview

**Creative North Star: "The School Office Register"**

Calm, conventional public-service software: the category standard executed well, in the spirit of the GOV.UK Design System. It should read as trustworthy school administration that a head teacher, a tired subject teacher with 70 learners, and a project examiner all take seriously. White cards on a pale grey-blue ground, one navy accent, one legible sans-serif, and status told in words inside tinted pills. Nothing is decorative; every colour carries a meaning or a competency.

The system must stay conventional. This is a binding brand commitment (PRODUCT.md, user decision 10 Nov 2026): the earlier expressive "Kampala signwriting" direction was rejected as too dramatic and has been removed. Do not reintroduce expressive display type, painted panels, decorative motifs, or a second accent. New screens extend the incumbent pattern set (page header, panel, panel head, KPI card, tag, competency dot, trend chart) rather than inventing new surfaces.

Density is moderate and table-friendly: staff screens favour scannable lists and tables on laptops, and collapse to one column on low-cost Android phones. Pages are light: system-hosted font files, inline SVG charts, no imagery beyond the crest.

**Key Characteristics:**
- White panels with a hairline border and a whisper of shadow on a #f4f6f9 ground.
- A single navy accent for primary actions, links, the active nav item and focus.
- Atkinson Hyperlegible throughout, bold sentence-case headings, quiet size steps.
- Status always as a word in a tinted pill; colour never carries meaning alone.
- Competencies identified by a small colour dot plus their name.
- Inline SVG trend charts on the fixed CBC scale 0.9–3.0.

## Colors

A restrained neutral palette with one navy accent, a five-step semantic tag family, and seven data-driven competency hues kept small.

### Primary
- **Register Navy** (navy): primary buttons, text links, the active sidebar item, focus rings, caret, the KPI hover border, the crest placeholder, and the chart's intervention-start rule. It is the only accent.
- **Navy Wash** (navy-soft): active nav background, the "info" tag fill, and the highlighted row in a table (at 60% opacity).
- **Deep Navy** (navy-deep): text on the info tag.

### Neutral
- **Ink** (ink): body text, headings, the chart benchmark line and hollow below-benchmark points. Also the daisyUI neutral.
- **Muted Slate** (muted): secondary text, subtitles, table header text, captions, chart axis labels.
- **Slate** (slate): daisyUI secondary role only; not used directly in views.
- **Hairline** (rule): every border and divider: panel borders, panel-head separators, table row rules, chart grid lines.
- **Wall** (wall): the page ground, list-row hover, the recommended-action box, the empty track of competency bars.
- **Paper** (paper): panels, sidebar, mobile bar, inputs.

### Semantic
- **Success Green / Warning Amber / Error Red / Info Blue** (success, warning, error, info): daisyUI semantic roles for toasts and maryUI components.
- **Tag pairs**: High (tag-high-bg / tag-high-text), Medium (tag-medium-*), Low or neutral (tag-low-*), Good (tag-good-*), Info (navy-soft / navy-deep). Each is a pale tint with a dark text of the same hue, at legible contrast.

### Competency hues
Stored in the database (`competencies.colour`), not in CSS; the seeded values are recorded in the frontmatter so swaps stay consistent. Communication & Collaboration, Critical Thinking & Problem-Solving, Creativity & Imagination, Citizenship, Digital Literacy, Learning to Learn, Self-Regulation, in that order.

### Named Rules
**The One Accent Rule.** Navy is the only accent. Primary actions, links, active nav and focus share it; no second brand colour is introduced. The theme tokens stay swappable so official school colours can replace navy when supplied.

**The Small Hue Rule.** Competency colours appear only as small dots (10px circles), thin bars (8px tall, full-round) and chart lines/points. Never as fills for panels, buttons, text, or backgrounds.

**The Word First Rule.** Every status colour sits inside a pill that also carries a word (High, Medium, Low, Needs action, Intervention under way, Escalated, Improved). Colour reinforces; it never informs alone.

## Typography

**Body Font:** Atkinson Hyperlegible 400/700 (with ui-sans-serif, system-ui, sans-serif), self-hosted via @fontsource.

**Character:** One humanist sans designed for low-vision legibility, at two weights. Hierarchy comes from size and bold, never from a second family.

### Hierarchy
- **Headline** (700, 1.5rem rising to 1.75rem at md, 1.25, -0.01em, balanced wrap): page titles via the page header.
- **Title** (700, 1.125rem): section headings above card grids, learner names on alert cards. The printed report title uses 1.25rem.
- **Title small** (700, 1rem): headings inside panel heads.
- **Number** (700, 1.5rem, tabular): KPI values; 1.25rem for per-competency averages.
- **Body** (400, 1rem, 1.5): running text; explanatory paragraphs capped near 65ch.
- **Body small** (400, 0.875rem): table text, list rows, card metadata. The most common size in the build.
- **Nav** (400, 0.9375rem; 700 when active): sidebar items.
- **Label** (700, 0.8125rem): tag pills and table header cells.
- **Caption** (400, 0.75rem, muted): KPI notes, dates under activity rows, chart footnotes, the demo-data notice.

### Named Rules
**The Sentence Case Rule.** Headings, buttons and labels are sentence case. The only uppercase text is the sidebar's admin group title ("School setup", 0.75rem, tracked); it is a navigation group label, not a pattern for content headings.

**The Tabular Numbers Rule.** Tables and every score, count or percentage use tabular figures.

## Layout

Sidebar app shell (maryUI `x-main` with a drawer): a white fixed sidebar with the crest and nav on large screens; below lg it becomes a drawer opened from a sticky white top bar. Content sits on the wall ground with 1rem padding on phones and 2rem x 1.75rem from md, capped at 1400px.

Every page opens with the page header: title and muted subtitle on the left, actions (selects, the navy primary button) on the right, wrapping on small screens, 1.5rem below. The dashboard summary is a KPI row (2 columns on phones, 3 at md, 6 at xl). Main content uses a two-column grid at xl (fluid main plus a 21rem aside); card grids step 1 → 2 → 3 columns.

Spacing rhythm: 0.75rem gaps inside rows and between KPI cards, 1rem between cards, 1.5–1.75rem between page sections; panel content inset 1.25rem horizontally. Rows inside panels use 0.5–0.75rem vertical padding with hairline separators.

Print: nav, sidebar and anything marked no-print disappear; panels drop their shadow and avoid page breaks. The learner progress report is a single panel laid out as a document (crest header, definition grid, competency table, signature lines).

## Elevation & Depth

Flat, with a single whisper. Depth comes from white panels on the pale wall plus a hairline border; one barely visible shadow lifts panels off the ground. daisyUI depth and noise are off.

### Shadow Vocabulary
- **Panel whisper** (`box-shadow: 0 1px 2px rgb(16 24 40 / 0.05)`): every panel and KPI card. Removed in print.

### Named Rules
**The Hairline Rule.** Separation is a 1px #e2e6ed line or a step from white to wall, never a heavier shadow, a coloured border, or a stripe.

## Shapes

Gently rounded and consistent: 6px (0.375rem) on fields, buttons, nav items and inset boxes; 8px (0.5rem) on panels and cards; full round (999px) on tag pills, competency dots and bar tracks. Borders are always 1px. The only silhouette with character is the crest shield.

## Components

### Buttons
- **Shape:** gently rounded (0.375rem), daisyUI/maryUI `btn`.
- **Primary:** navy fill, white text, optional leading 20px outline icon ("Enter scores", "Sign in", "Print or save as PDF"). One per view region.
- **Outline:** hairline-bordered secondary actions ("Learner profile", "Progress report", "Close alert", "Re-check all learners").
- **Focus:** 2px navy outline, 2px offset.

### Tags (status pills)
- **Style:** inline pill, 999px radius, 0.125rem x 0.5rem padding, 0.8125rem bold text, optional 16px icon before the word. Tinted fill with darker same-hue text.
- **Variants:** high (red), medium (amber), low (neutral grey), good (green), info (navy wash).
- **Mapping:** severity uses high/medium/low by name. Alert status: escalated → high, intervention under way → info, improved or cleared → good, other resolved and open → low. Trend label: Improving → good, Declining → medium, Steady → low. Score values in the Basic band (below the competency benchmark, default 1.5) render as medium pills; Moderate and Outstanding values render as plain text.

### Cards / Containers
- **Panel:** white, 0.5rem radius, 1px hairline border, panel whisper shadow. Internal padding 1rem–1.5rem; the printed report uses up to 2.5rem.
- **Panel head:** flex row, 0.875rem x 1.25rem padding, hairline bottom border; a small bold title on the left and a muted caption, link or count tag on the right.
- **List rows inside panels:** full-width links, 0.5–0.75rem x 1.25rem padding, hover to wall.

### KPI card
A panel at 0.875rem x 1rem: muted 0.875rem label, 1.5rem bold tabular number, optional 0.75rem muted note. When it links, its border turns navy on hover.

### Alert card
Staff-only. A panel answering who, which competency, why, and what to do: learner name (title, link) with class and admission number beneath; severity tag top-right; competency dot and name in bold small text, plus a status tag when not open; the first reason with "+n more"; a wall-coloured "Recommended action" box (0.375rem radius). The compact variant omits the box.

### Competency marker
A 10px colour dot followed by the competency name (optionally shortened before "&"). Competency bars are 8px full-round tracks in wall with a fill in the competency hue.

### Trend chart
Inline SVG, no chart library. Fixed scale 0.9–3.0; hairline grid lines at 1.0, 1.5, 2.5 and 3.0 with 10px muted axis numbers; benchmark as a dashed ink line (4 3); the score line 2px in the competency hue with 3.5px points. For staff, points below the benchmark are hollow (white fill, ink stroke), so low values read without colour. The students' soft variant fills every point. A 1.5px navy vertical rule marks where an intervention started. Each chart carries an aria-label listing scores and benchmark.

### Inputs / Fields
- **Style:** maryUI/daisyUI fields, white, 1px border, 0.375rem radius.
- **Focus:** exactly one ring: 2px navy outline, 1px offset, on the field wrapper.

### Tables
Rules not stripes: hairline row borders, 0.75rem vertical cell padding, 0.8125rem bold muted header text, tabular figures. A selected row takes the navy wash at 60%.

### Navigation
- **Sidebar:** crest and wordmark ("Verona High School" bold, "Competency early warning" caption) at top; items at 0.9375rem with 20px outline icons, 0.375rem radius, 0.5rem x 0.75rem padding; active item navy wash with bold navy text. The open-alert count sits in a low tag. User name, role and sign-out icon sit at the foot above a hairline.
- **Mobile:** sticky white bar with the brand and a hamburger that opens the same sidebar as a drawer.

### Crest
The brand slot shows `public/crest.png` when present, otherwise `public/crest-placeholder.svg` (a plain navy shield with a white V), at 40px tall. Do not invent or redraw the crest.

## Do's and Don'ts

### Do:
- **Do** keep the look conventional and serious: white panels, hairline borders, one navy accent, Atkinson Hyperlegible, sentence case.
- **Do** put every status in a tag pill with a word; use the five tag variants only.
- **Do** show competencies as a 10px dot plus the name, and keep their hues to dots, thin bars and chart lines.
- **Do** draw charts as inline SVG on the fixed 0.9–3.0 scale with the dashed ink benchmark and hollow below-benchmark points for staff.
- **Do** show Basic-band scores as medium tags and leave Moderate and Outstanding values as plain text.
- **Do** use tabular figures for every number.
- **Do** keep colours in theme tokens so official school colours and the real crest can be swapped in.

### Don't:
- **Don't** reintroduce expressive display type, painted panels, decorative motifs, or a second accent colour.
- **Don't** let colour carry meaning alone; no colour-only severity, bands, or trends.
- **Don't** show students alert, severity or at-risk language, or a "Declining" label; on student screens open alerts become "focus areas" and the soft chart variant is used.
- **Don't** use competency hues as panel, button, text or background fills.
- **Don't** add heavier shadows, coloured side borders, or zebra stripes; separation is hairline rules and the white/wall step.
- **Don't** add a charting library or raster images beyond the crest; pages must stay light on metered data.
