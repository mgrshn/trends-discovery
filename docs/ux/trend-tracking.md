# Trend Tracking

**URL:** `/pro/tracking`  
**Screenshots:** `trend-tracking/empty-state.png` (text empty state), `trend-tracking/loading-state.png` (blurred card grid)

## Layout

Full-width content area. Header row: page title **Trend Tracking** + right-aligned action buttons.

## Top Action Bar

| Button | Action |
|--------|--------|
| **Create New Project** | Opens project-creation modal |
| **Export Projects To CSV** | Downloads tracked topics as CSV |

## Empty State (no topics tracked)

- Project name shown: "Initial Project"
- **Add Topics** button inline
- Large center text: "No topics tracked"
- Footer: "Made with ❤️ by data nerds"

## Populated State (blurred — not fully loaded in session)

Visible from loading screenshot:
- Row of **project summary cards** across the top (4 columns wide)
- Each card: project name, topic count, status badges (blurred)
- Below: grid of **topic cards** (4 per row), each card shows:
  - Trend sparkline
  - Topic name
  - Volume / Growth metrics (blurred in screenshot)

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| **Create New Project** | Modal: project name input + Create button |
| **Add Topics** | Opens topic-search modal (searches Trends Database) |
| **Export Projects To CSV** | Streams CSV file download |
| Click project card | Expands / navigates into that project |

## Data Model Inferred

- Projects are containers; each project has N tracked topics
- Per-topic tracking stores: volume, growth %, sparkline history
- Polling cadence unknown (likely daily refresh)
