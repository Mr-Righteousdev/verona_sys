---
name: Verona EWS
description: Competency early-warning system for Verona High School, lettered like a painted school signboard.
colors:
  signboard-blue: "#16348f"
  paint-yellow: "#f4c20d"
  paint-red: "#c41e17"
  ink: "#111111"
  board-white: "#ffffff"
  wall-grey: "#e9ecf2"
  rule-grey: "#d3d8e2"
  muted-ink: "#4a5160"
  paint-communication: "#1a5fb4"
  paint-critical-thinking: "#7b2d8e"
  paint-creativity: "#b9470f"
  paint-citizenship: "#0f7a3d"
  paint-digital-literacy: "#0b6f71"
  paint-learning-to-learn: "#7f5a00"
  paint-self-regulation: "#a3324a"
typography:
  display:
    fontFamily: "Archivo Variable, Atkinson Hyperlegible, sans-serif"
    fontSize: "3rem"
    fontWeight: 850
    lineHeight: 0.95
    letterSpacing: "0.01em"
    fontVariation: "'wdth' 68"
  headline:
    fontFamily: "Archivo Variable, Atkinson Hyperlegible, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 850
    lineHeight: 0.95
    letterSpacing: "0.01em"
  title:
    fontFamily: "Archivo Variable, Atkinson Hyperlegible, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 850
    lineHeight: 0.95
    letterSpacing: "0.01em"
  body:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  body-small:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  label:
    fontFamily: "Atkinson Hyperlegible, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 700
    lineHeight: 1.4
rounded:
  none: "0px"
spacing:
  band-y: "10px"
  board-x: "16px"
  board-gap: "20px"
  section-gap: "28px"
  page-x: "36px"
components:
  button-primary:
    backgroundColor: "{colors.paint-yellow}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    typography: "{typography.label}"
  button-outline:
    backgroundColor: "{colors.board-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
  board:
    backgroundColor: "{colors.board-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "16px"
  nav-rail:
    backgroundColor: "{colors.signboard-blue}"
    textColor: "{colors.board-white}"
    width: "256px"
  nav-item-active:
    backgroundColor: "{colors.paint-yellow}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "10px 20px"
  input:
    backgroundColor: "{colors.board-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
  count-badge:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.board-white}"
    rounded: "{rounded.none}"
---

# Design System: Verona EWS

## Overview

**Creative North Star: "The Painted Signboard"**

Every alert is a painted school signboard, after Kampala hand-painted signwriting: a learner band lettered in condensed heavy caps, a competency band in that competency's own paint, and an intervention band that says what to do. The app reads like a noticeboard wall: a signboard-blue rail down the left, a cool wall-grey ground, and white square-cornered boards framed in 2px ink with one-pixel rules between rows.

Paint is flat and carries meaning. Seven fixed competency paints identify competencies wherever they appear (bands, swatches, tally bars, trend lines). Red is reserved for high severity; yellow marks where you are and what to do next. Nothing is rounded, nothing floats on a shadow, nothing is gradient. Depth comes from frames and paint, not light.

The display voice is loud but rationed: condensed caps at a short scale for names, page titles, and board headings only. Everything read at length is Atkinson Hyperlegible, chosen for cheap screens and mixed literacy. Severity is never colour-only; it is counted.

**Key Characteristics:**
- Square corners everywhere (0 radius on every box, field, selector, and badge).
- White boards with 2px ink frames on a wall-grey ground; 1px rules inside.
- Seven fixed competency paints, all AA with white text.
- Severity counted in 1-3 edge stripes; red only for high.
- Yellow marks the active nav item and the primary action, nothing else.
- Flat: no shadows, no depth tokens, no noise.

## Colors

Flat sign paint on a cool wall: one structural blue, two signal paints, ink, and seven competency paints.

### Primary
- **Signboard Blue** (signboard-blue): the side rail, the mobile bar, the browser theme colour, the caret, by-class tally bars, the current pagination page, link text, and the default trend line. The colour of the sign itself.

### Secondary
- **Sign Yellow** (paint-yellow): the active nav item, the primary action button, text selection, the focus ring on blue surfaces, and medium-severity stripes (always with a 1px inset ink ring so it reads on white). One accent line in the login display lettering.

### Tertiary
- **Warning Red** (paint-red): high-severity stripes only. It is the daisyUI `error` colour, so form validation errors also use it; it never decorates.

### Competency paints
Stored per competency in the database (`competencies.colour`), not in CSS; rendered inline. Each is AA with white text.
- **Communication Blue** (paint-communication): Communication & Collaboration.
- **Thinking Violet** (paint-critical-thinking): Critical Thinking & Problem-Solving.
- **Creativity Rust** (paint-creativity): Creativity & Imagination.
- **Citizenship Green** (paint-citizenship): Citizenship.
- **Digital Teal** (paint-digital-literacy): Digital Literacy.
- **Learning Ochre** (paint-learning-to-learn): Learning to Learn.
- **Regulation Rose** (paint-self-regulation): Self-Regulation.

### Neutral
- **Ink** (ink): all body text, board frames, field borders, the page-header rule, the focus ring on light surfaces, low-severity stripes, the open-alerts count badge.
- **Board White** (board-white): board and field surfaces; text on the blue rail and on competency paint.
- **Wall Grey** (wall-grey): page ground, row hover, tally-bar tracks.
- **Rule Grey** (rule-grey): one-pixel row rules inside boards, empty stripe slots, chart gridlines.
- **Muted Ink** (muted-ink): secondary lines (class and admission number, subtitles, table headers, chart axis numbers).

### Named Rules
**The Red Means High Rule.** Warning Red appears on high-severity stripes and validation errors and nowhere else. A red that decorates makes the next real high severity invisible.

**The Yellow Points Rule.** Yellow marks exactly two things: where you are (active nav item) and what to do next (the primary action). One yellow button per view region.

**The Own Paint Rule.** A competency is always shown in its database paint, never in a theme colour, and paint is always paired with the competency's name or a labelled swatch.

## Typography

**Display Font:** Archivo Variable at 68% width, weight 850, uppercase (with Atkinson Hyperlegible fallback)
**Body Font:** Atkinson Hyperlegible 400/700 (with ui-sans-serif, system-ui)

**Character:** Signwriter's condensed heavy caps against a typeface designed for low-vision legibility. The lettering shouts the name; the body explains it calmly.

### Hierarchy
- **Display** (850, 2.25rem rising to 3rem at md, line-height 0.95, uppercase): page titles in the page header; the signboard count sentence ("13 learners need you").
- **Headline** (850, 1.5rem, 0.95, uppercase): learner names on signboards; the brand lettering.
- **Title** (850, 1.25rem, 0.95, uppercase): board section headings ("Open alerts by competency"), the mobile bar wordmark.
- **Body** (400, 1rem, 1.5): running text, subtitles, recommendations; cap prose at 60ch.
- **Body small** (400, 0.875rem): board rows, reasons, tally rows, meta lines.
- **Label** (700, 0.9375rem): competency bands, nav items, buttons. Table headers are 700 at 0.8125rem in Muted Ink.

Numbers in tables and tallies use tabular figures.

### Named Rules
**The Short Scale Rule.** Display caps run from 1.25rem to 3rem. They letter names and titles; they never set a sentence of more than a line or two, and never body copy.

**The Lettering Is For Names Rule.** Condensed caps are for proper names, titles, and headings. Anything a teacher reads to decide what to do is in Atkinson Hyperlegible.

## Layout

App shell: a fixed 256px signboard-blue rail at lg and up; below lg, a sticky blue mobile bar with a menu button opens the rail as a drawer. Content sits on the wall-grey ground, padded 16px on mobile and 36px x 28px from md, capped at 1400px.

The dashboard is a main column plus a 19rem right column at xl. Signboards lay out in a grid of 1, 2 (md), and 3 (xl) columns with a 20px column gap; boards use CSS subgrid rows so learner, competency, and intervention bands align across neighbouring boards even when names wrap.

Inside boards, rows are 16px horizontal and roughly 10px vertical, separated by one-pixel Rule Grey lines. Page headers end in a 1px ink rule with 16px below the text and 24px before content.

## Elevation & Depth

Flat by construction: daisyUI depth and noise are 0 and there are no shadows anywhere. Depth is conveyed by the 2px ink frame of a board against the wall-grey ground, and by solid paint bands inside the board. The only motion is a board "lettering-in" entrance: a 6px rise with a bottom clip opening over 420ms on an expo-out curve, staggered 60ms across the first three boards, and disabled under reduced motion.

### Named Rules
**The Frame Not Shadow Rule.** Separate a surface with a 2px ink frame or a 1px rule, never a shadow.

## Shapes

Square corners everywhere: radius 0 on boxes, fields, selectors, nav items, and badges. Borders come in two weights: 2px ink for boards, the brand plate, and buttons; 1px for row rules (Rule Grey) and section rules (ink). Severity stripes are short painted bars (5px wide horizontally, 12px wide vertically along a board's right edge, 3px gaps). Chart points are squares: filled paint squares on or above benchmark, hollow ink squares below it.

## Components

### Buttons
Painted, framed, and square.
- **Shape:** square (0) with a 2px ink border.
- **Primary:** Sign Yellow fill, ink text, bold label, usually a leading outline icon. Used for the one forward action: Enter scores, Save scores, Save register, Resolve alert, Sign in, Create.
- **Outline:** white with a 2px ink border for secondary, non-destructive actions (Re-check all learners).
- **Ghost:** borderless, for Cancel beside a primary.
- **Focus:** 3px ink outline offset 2px; on blue surfaces the outline turns yellow.

### Signboard (signature)
The alert as a painted sign, in three bands inside a `board`:
1. **Learner band:** learner name in headline caps, class and admission number in Muted Ink beneath; the whole band links to the alert, hovering to Wall Grey.
2. **Competency band:** the competency name in bold white on the competency's paint.
3. **Reason and intervention:** the first reason (with "+N more"), a 1px rule, then the recommendation under a short bold "Do this" band label. The compact variant drops the intervention band.

Severity stripes run down the board's right edge, overlapping the frame. Resolved boards drop to 80% opacity and the competency band becomes an ink outline (1px top and bottom) on white.

### Severity stripes
Three slots, filled by count: low is one ink stripe, medium two yellow stripes with a 1px inset ink ring, high three red stripes; unfilled slots are Rule Grey. Always labelled for assistive tech ("High severity"). Horizontal inline form for tables; vertical edge form for boards.

### Trend chart
Competency levels on the 1-3 scale as an SVG line in the competency's paint (2.5px), with Rule Grey gridlines, axis numbers in Muted Ink, and the benchmark as a 1px dashed ink line. Points are 8px paint squares; points below benchmark are 7px hollow white squares with a 2px ink stroke. The **soft** variant (student view) paints every point as a filled square so nothing is flagged.

### Competency swatch
A 12px square of paint followed by the competency name (or its short form before the ampersand). Used in tallies, filters, and tables so paint is never the only cue.

### Cards / Containers
- **Corner Style:** square (0).
- **Background:** Board White on Wall Grey.
- **Shadow Strategy:** none; see Elevation & Depth.
- **Border:** 2px ink; inner rows ruled 1px Rule Grey; board headings ruled 1px ink.
- **Internal Padding:** 16px rows; 24-32px for standalone boards (login, empty states).

### Inputs / Fields
- **Style:** white, square, 1px ink border (the same ink as the frames).
- **Focus:** a single 3px Signboard Blue outline offset 1px; the default daisyUI ring is suppressed so there is one ring, not two.
- **Error:** Warning Red message beneath the field.

### Navigation
Signboard-blue rail, white text at 0.9375rem, full-bleed square items with 20px x 10px padding and outline icons. The active item is Sign Yellow with ink bold text. The Alerts item carries an open-count badge: square ink chip, white bold number. Admin groups sit under a small muted group heading. The brand plate is the school name lettered in two lines of headline caps inside a 2px frame in the current colour, with a one-line descriptor; a school crest at `public/crest.png` appears beside the lettering when supplied. The user block sits at the rail foot above a 1px white/25% rule.

### Tally rows
Board rows of label, a 10px-tall bar on a Wall Grey track, and a bold tabular count. Competency tallies fill in competency paint; class tallies fill in Signboard Blue. Each row is a link or filter.

## Do's and Don'ts

### Do:
- **Do** keep every corner square (0 radius), including badges, nav items, and pagination.
- **Do** frame boards in 2px ink on Wall Grey (#e9ecf2) and rule rows in 1px Rule Grey (#d3d8e2).
- **Do** count severity in 1-3 edge stripes: low ink, medium yellow with an ink ring, high red.
- **Do** show below-benchmark chart points as hollow ink squares, and use the soft trend variant on every student-facing chart.
- **Do** pair every competency paint with its name or a labelled swatch.
- **Do** use Sign Yellow (#f4c20d) only for the active nav item, the primary action, selection, and medium severity.
- **Do** set names and titles in Archivo condensed caps at 1.25-3rem, and everything read in Atkinson Hyperlegible.

### Don't:
- **Don't** use Warning Red (#c41e17) for anything but high-severity stripes and validation errors.
- **Don't** show alert, severity, or stripe language to students; their views show levels and progress only.
- **Don't** add shadows, gradients, or rounded corners; separation is frame and rule.
- **Don't** use colour as the only signal for severity or competency.
- **Don't** introduce a competency colour in CSS; competency paint comes from the database.
