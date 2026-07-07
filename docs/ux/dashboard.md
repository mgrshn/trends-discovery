# Dashboard

**URL:** `/pro/dashboard`  
**Screenshots:** `dashboard/scrolled.png` (recommended trends cards), `dashboard/api-visit.png`

## Layout

Two-column shell: fixed left nav (140 px) + full-width content area.  
Top bar: Semrush header (My apps / Store / User menu / Send feedback).

## Content Sections

### Welcome Banner (first-time / no project)
- Dark-blue panel with embedded video (autoplay thumbnail) + right-side CTA text
- Copy: "Welcome! Get started by creating your first project"
- Bullet points: "Get deep-dive data on any trend", "See products and startups before they go mainstream", "Be the first to know when a new trend is about to take off"
- Primary CTA button: **Create Project**

### Recommended Trends
- Horizontal carousel of trend cards (4 visible, right arrow to scroll)
- Top-right: **Categories** dropdown + **Browse Trends Database** link
- Each card contains:
  - Topic name (e.g. "Ai ethics", "Bow mini dress", "Non-Slip Shoes")
  - Volume (e.g. `33.1K`, `110K`) with label "Volume"
  - Growth (e.g. `+817%`, `+800%`) with label "Growth"
  - Sparkline (7-day or 5-year line chart, blue)
  - Button: **+ TRACK TOPIC**
- Right-arrow navigation button to see more cards

### Topics You're Currently Tracking (after first visit / with project)
- Section header: "Topics You're Currently Tracking"
- Top-right: **All Projects** dropdown + **View All Projects** link
- Empty state: "Add Topics To Your Project" + description + **Find Trends To Track** button

## Navigation (Left Sidebar)

| Item | Badge |
|------|-------|
| Dashboard | — |
| Trend Tracking | — |
| Trends Database | — |
| TikTok Insights | BETA |
| Trend Analysis | — |
| Trending Startups | — |
| Trending Products | — |
| Meta Trends | — |
| Reports Library | — |
| API Access | — |
| Upgrade (button) | — |

Footer: Cookie Settings · Legal Info · Contact us · `...`

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| Click **Create Project** | Opens project-creation modal |
| Click **+ TRACK TOPIC** | Adds topic to current project (or prompts to create one) |
| **Categories** dropdown | Filters recommended trends by category |
| Right-arrow on carousel | Scrolls to next 4 cards |
| Click card title | Opens Trend Analysis for that topic |

## API Calls Observed

Only telemetry observed on dashboard load (Sentry, Amplitude). Trend card data likely bundled server-side or fetched on initial load (no XHR captured during 4 s window).

Data shape from raw text:
```
topic: "Shoe washing bag"   volume: 18.1K  growth: +492%
topic: "Skin barrier cream" volume: 8.1K   growth: +500%
topic: "Magnetic power bank" volume: 22.2K growth: +500%
topic: "Floral mini dress"  volume: 33.1K  growth: +750%
topic: "Non-Slip Shoes"     volume: 110K   growth: +800%
```
